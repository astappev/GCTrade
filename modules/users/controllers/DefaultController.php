<?php

namespace app\modules\users\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\widgets\ActiveForm;

/**
 * Default controller for User module
 */
class DefaultController extends Controller {

    public function actions()
    {
        return [
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'successCallback'],
            ],
        ];
    }

    public function successCallback($client)
    {
        $attributes = $client->getUserAttributes();
        $user = Yii::$app->getModule("user")->model("User");
        $user = $user::findOne(["email" => $attributes["email"]]);

        if($user) {
            $user_login = Yii::$app->getModule("user")->model("LoginForm");
            $_POST["LoginForm"]["username"] = $user->username;
            $_POST["LoginForm"]["rememberMe"] = 1;
            $user_login->load($_POST);
            if(Yii::$app->user->login($user_login->getUser(), Yii::$app->getModule("user")->loginDuration)) {
                Yii::$app->session->setFlash('success', 'Вы успешно авторизовались, '.$user->username);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка авторизации.');
            }
        } else {
            $user = Yii::$app->getModule("user")->model("User");
            $user->email = $attributes["email"];
            $user->username = current(explode('@', $attributes["email"]));
            $user->generateAuthKey();
            if ($user->save()) {
                if (Yii::$app->getUser()->login($user)) {
                    Yii::$app->session->setFlash('success', 'Вы успешно зарегистрировали, '.$user->username);
                }
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка регистрации.');
            }
        }
    }

    /**
     * Get view path based on module property
     *
     * @return string
     */
    public function getViewPath() {
        return Yii::$app->getModule("user")->viewPath
            ? rtrim(Yii::$app->getModule("user")->viewPath, "/\\") . DIRECTORY_SEPARATOR . $this->id
            : parent::getViewPath();
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'confirm', 'resend'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['account', 'profile', 'resend-change', 'cancel', 'logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['login', 'signup', 'forgot', 'reset'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Display index
     */
    public function actionIndex() {

        // display debug page if YII_DEBUG is set
        if (defined('YII_DEBUG') and YII_DEBUG) {
            $actions = Yii::$app->getModule("user")->getActions();
            return $this->render('index', ["actions" => $actions]);
        }
        // redirect to login page if user is guest
        elseif (Yii::$app->user->isGuest) {
            return $this->redirect(["/user/login"]);
        }
        // redirect to account page if user is logged in
        else {
            return $this->redirect(["/user/account"]);
        }
    }

    /**
     * Display login page and log user in
     */
    public function actionLogin() {

        // load data from $_POST and attempt login
        /** @var \app\modules\users\models\forms\LoginForm $model */
        $model = Yii::$app->getModule("user")->model("LoginForm");
        if ($model->load($_POST) && $model->login(Yii::$app->getModule("user")->loginDuration)) {
            return $this->goBack(Yii::$app->getModule("user")->loginRedirect);
        }

        // render view
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Log user out and redirect home
     */
    public function actionLogout() {
        Yii::$app->user->logout();
        return $this->redirect(Yii::$app->getModule("user")->logoutRedirect);
    }

    /**
     * Display signup page
     */
    public function actionSignup() {

        // set up user/profile and attempt to load data from $_POST
        /** @var \app\modules\users\models\User $user */
        /** @var \app\modules\users\models\Profile $profile */
        $user = Yii::$app->getModule("user")->model("User", ["scenario" => "signup"]);
        $profile = Yii::$app->getModule("user")->model("Profile");
        if ($user->load($_POST)) {

            // validate for ajax request
            $profile->load($_POST);
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user, $profile);
            }

            // validate for normal request
            if ($user->validate() and $profile->validate()) {

                // perform registration
                /** @var \app\modules\users\models\Role $role */
                $role = Yii::$app->getModule("user")->model("Role");
                $user->signup($role::ROLE_USER, Yii::$app->request->userIP);
                $profile->signup($user->id);
                $this->_calcEmailOrLogin($user);

                // set flash
                // dont use $this->refresh() because user may automatically be logged in and get 403 forbidden
                $userDisplayName = $user->getDisplayName();
                $guestText = Yii::$app->user->isGuest ? " - Please check your email to confirm your account" : "";
                Yii::$app->session->setFlash("Register-success", "Successfully registered [ $userDisplayName ]" . $guestText);
            }
        }

        // render view
        return $this->render("signup", [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Calculate whether we need to send confirmation email or log user in based on user's status
     *
     * @param \app\modules\users\models\User $user
     */
    protected function _calcEmailOrLogin($user) {

        // determine userkey type to see if we need to send email
        /** @var \app\modules\users\models\User $user */
        /** @var \app\modules\users\models\Userkey $userkey */
        $userkeyType = null;
        $userkey = Yii::$app->getModule("user")->model("Userkey");
        if ($user->status == $user::STATUS_INACTIVE) {
            $userkeyType = $userkey::TYPE_EMAIL_ACTIVATE;
        }
        elseif ($user->status == $user::STATUS_UNCONFIRMED_EMAIL) {
            $userkeyType = $userkey::TYPE_EMAIL_CHANGE;
        }

        // check if we have a userkey type to process
        if ($userkeyType !== null) {

            // generate userkey and send email
            $userkey = $userkey::generate($user->id, $userkeyType);
            if (!$numSent = $user->sendEmailConfirmation($userkey)) {

                // handle email error
                //Yii::$app->session->setFlash("Email-error", "Failed to send email");
            }
        }
        // log user in automatically
        else {
            Yii::$app->user->login($user, Yii::$app->getModule("user")->loginDuration);
        }
    }

    /**
     * Confirm email
     */
    public function actionConfirm($key) {

        // search for userkey
        /** @var \app\modules\users\models\Userkey $userkey */
        $success = false;
        $userkey = Yii::$app->getModule("user")->model("Userkey");
        $userkey = $userkey::findActiveByKey($key, [$userkey::TYPE_EMAIL_ACTIVATE, $userkey::TYPE_EMAIL_CHANGE]);
        if ($userkey) {

            // confirm user
            /** @var \app\modules\users\models\User $user */
            $user = Yii::$app->getModule("user")->model("User");
            $user = $user::findOne($userkey->user_id);
            $user->confirm();

            // consume userkey and set success
            $userkey->consume();
            $success = $user->email;
        }

        // render view
        return $this->render("confirm", [
            "userkey" => $userkey,
            "success" => $success
        ]);
    }

    /**
     * Account
     */
    public function actionAccount() {

        // set up user/profile and attempt to load data from $_POST
        /** @var \app\modules\users\models\User $user */
        $user = Yii::$app->user->identity;
        $user->setScenario("account");
        if ($user->load($_POST)) {

            // validate for ajax request
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($user);
            }

            // validate for normal request
            if ($user->validate()) {

                // generate userkey and send email if user changed his email
                if (Yii::$app->getModule("user")->emailChangeConfirmation and $user->checkAndPrepareEmailChange()) {

                    /** @var \app\modules\users\models\Userkey $userkey */
                    $userkey = Yii::$app->getModule("user")->model("Userkey");
                    $userkey = $userkey::generate($user->id, $userkey::TYPE_EMAIL_CHANGE);
                    if (!$numSent = $user->sendEmailConfirmation($userkey)) {

                        // handle email error
                        //Yii::$app->session->setFlash("Email-error", "Failed to send email");
                    }
                }

                // save, set flash, and refresh page
                $user->save(false);
                Yii::$app->session->setFlash("Account-success", "Account updated");
                return $this->refresh();
            }
        }

        // render view
        return $this->render("account", [
            'user' => $user,
        ]);
    }

    /**
     * Profile
     */
    public function actionProfile() {

        // set up profile and attempt to load data from $_POST
        /** @var \app\modules\users\models\Profile $profile */
        $profile = Yii::$app->user->identity->profile;
        if ($profile->load($_POST)) {

            // validate for ajax request
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($profile);
            }

            // save
            if ($profile->save()) {
                Yii::$app->session->setFlash("Profile-success", "Profile updated");
                return $this->refresh();
            }
        }

        // render view
        return $this->render("profile", [
            'profile' => $profile,
        ]);
    }

    /**
     * Resend email confirmation
     */
    public function actionResend() {

        // attempt to load $_POST data, validate, and send email
        /** @var \app\modules\users\models\forms\ResendForm $model */
        $model = Yii::$app->getModule("user")->model("ResendForm");
        if ($model->load($_POST) && $model->sendEmail()) {

            // set flash and refresh page
            Yii::$app->session->setFlash("Resend-success", "Confirmation email resent");
            return $this->refresh();
        }

        // render view
        return $this->render("resend", [
            "model" => $model,
        ]);
    }

    /**
     * Resend email change confirmation
     */
    public function actionResendChange() {

        // attempt to find userkey and get user/profile to send confirmation email
        /** @var \app\modules\users\models\Userkey $userkey */
        $userkey = Yii::$app->getModule("user")->model("Userkey");
        $userkey = $userkey::findActiveByUser(Yii::$app->user->id, $userkey::TYPE_EMAIL_CHANGE);
        if ($userkey) {
            /** @var \app\modules\users\models\User $user */
            $user = Yii::$app->user->identity;
            $user->sendEmailConfirmation($userkey);

            // set flash message
            Yii::$app->session->setFlash("Resend-success", "Confirmation email resent");
        }

        // go to account page
        return $this->redirect(["/user/account"]);
    }

    /**
     * Cancel email change
     */
    public function actionCancel() {

        // attempt to find userkey
        /** @var \app\modules\users\models\Userkey $userkey */
        $userkey = Yii::$app->getModule("user")->model("Userkey");
        $userkey = $userkey::findActiveByUser(Yii::$app->user->id, $userkey::TYPE_EMAIL_CHANGE);
        if ($userkey) {

            // remove user.new_email
            /** @var \app\modules\users\models\User $user */
            $user = Yii::$app->user->identity;
            $user->new_email = null;
            $user->save(false);

            // delete userkey and set flash message
            $userkey->expire();
            Yii::$app->session->setFlash("Cancel-success", "Email change cancelled");
        }

        // go to account page
        return $this->redirect(["/user/account"]);
    }

    /**
     * Forgot password
     */
    public function actionForgot() {

        // attempt to load $_POST data, validate, and send email
        /** @var \app\modules\users\models\forms\ForgotForm $model */
        $model = Yii::$app->getModule("user")->model("ForgotForm");
        if ($model->load($_POST) && $model->sendForgotEmail()) {

            // set flash and refresh page
            Yii::$app->session->setFlash("Forgot-success", "Instructions to reset your password have been sent");
            //return $this->refresh();
        }

        // render view
        return $this->render("forgot", [
            "model" => $model,
        ]);
    }

    /**
     * Reset password
     */
    public function actionReset($key) {

        // check for invalid userkey
        /** @var \app\modules\users\models\Userkey $userkey */
        $userkey = Yii::$app->getModule("user")->model("Userkey");
        $userkey = $userkey::findActiveByKey($key, $userkey::TYPE_PASSWORD_RESET);
        if (!$userkey) {
            return $this->render('reset', ["invalidKey" => true]);
        }

        // attempt to load $_POST data, validate, and reset user password
        /** @var \app\modules\users\models\forms\ResetForm $model */
        $success = false;
        $model = Yii::$app->getModule("user")->model("ResetForm", ["userkey" => $userkey]);
        if ($model->load($_POST) && $model->resetPassword()) {
            $success = true;
        }

        // render view
        return $this->render('reset', compact("model", "success"));
    }
}