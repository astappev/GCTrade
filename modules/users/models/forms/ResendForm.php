<?php

namespace app\modules\users\models\forms;

use Yii;
use yii\base\Model;

/**
 * Forgot password form
 */
class ResendForm extends Model {

    /**
     * @var string Username and/or email
     */
    public $email;

    /**
     * @var \app\modules\users\models\User
     */
    protected $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules() {
        return [
            ["email", "required"],
            ["email", "email"],
            ["email", "validateEmailInactive"],
            ["email", "filter", "filter" => "trim"],
        ];
    }

    /**
     * Validate email exists and set user property
     */
    public function validateEmailInactive() {

        // check for valid user
        $user = $this->getUser();
        if (!$user) {
            $this->addError("email", "Email not found");
        }
        elseif ($user->status == $user::STATUS_ACTIVE) {
            $this->addError("email", "Email is already active");
        }
        else {
            $this->_user = $user;
        }
    }

    /**
     * Get user based on email
     *
     * @return \app\modules\users\models\User|null
     */
    public function getUser() {
        if ($this->_user === false) {
            $user = Yii::$app->getModule("user")->model("User");

            // check email first, then new_email (former is indexed, latter is not)
            $this->_user = $user::findOne(["email" => $this->email]);
            if (!$this->_user) {
                $this->_user = $user::findOne(["new_email" => $this->email]);
            }
        }
        return $this->_user;
    }

    /**
     * Send forgot email
     *
     * @return bool
     */
    public function sendEmail() {

        // validate
        if ($this->validate()) {

            // get user
            /** @var \app\modules\users\models\Userkey $userkey */
            $user = $this->getUser();

            $userkey = Yii::$app->getModule("user")->model("Userkey");

            // calculate type
            if ($user->status == $user::STATUS_INACTIVE) {
                $type = $userkey::TYPE_EMAIL_ACTIVATE;
            }
            //elseif ($user->status == $user::STATUS_UNCONFIRMED_EMAIL) {
            else {
                $type = $userkey::TYPE_EMAIL_CHANGE;
            }

            // generate userkey
            $userkey = $userkey::generate($user->id, $type);

            // send email confirmation
            return $user->sendEmailConfirmation($userkey);
        }

        return false;
    }
}