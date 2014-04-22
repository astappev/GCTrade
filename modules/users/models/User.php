<?php

namespace app\modules\users\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\swiftmailer\Mailer;
use yii\helpers\Inflector;
use yii\helpers\Security;
use ReflectionClass;
/**
 * User model
 *
 * @property int $id
 * @property int $role_id
 * @property string $email
 * @property string $new_email
 * @property string $username
 * @property string $password
 * @property int $status
 * @property string $auth_key
 * @property string $api_key
 * @property string $created_at
 * @property string $update_at
 * @property string $ban_time
 * @property string $ban_reason
 * @property string $registration_ip
 * @property string $login_ip
 * @property string $login_time
 *
 * @property Profile $profile
 * @property Role $role
 * @property Userkey[] $userkeys
 */
class User extends ActiveRecord implements IdentityInterface {

    /**
     * @var int Inactive status
     */
    const STATUS_INACTIVE = 0;

    /**
     * @var int Active status
     */
    const STATUS_ACTIVE = 1;

    /**
     * @var int Unconfirmed email status
     */
    const STATUS_UNCONFIRMED_EMAIL = 2;

    /**
     * @var string New password - for registration and changing password
     */
    public $newPassword;

    /**
     * @var string Current password - for account page updates
     */
    public $currentPassword;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return static::getDb()->tablePrefix . "user";
    }

    /**
     * @inheritdoc
     */
    public function rules() {

        // set initial rules
        $rules = [
            // general email and username rules
            [['email', 'username'], 'string', 'max' => 255],
            [['email', 'username'], 'unique'],
            [['email', 'username'], 'filter', 'filter' => 'trim'],
            [['email'], 'email'],
            [['username'], 'match', 'pattern' => '/^[A-Za-z0-9_]+$/u', 'message' => "{attribute} can contain only letters, numbers, and '_'."],

            // password rules
            [['newPassword'], 'string', 'min' => 3],
            [['newPassword'], 'filter', 'filter' => 'trim'],
            [['newPassword'], 'required', 'on' => ['signup']],

            // account page
            [['currentPassword'], 'required', 'on' => ['account']],
            [['currentPassword'], 'validateCurrentPassword', 'on' => ['account']],

            // admin crud rules
			[['role_id', 'status'], 'required', 'on' => ['admin']],
			[['role_id', 'status'], 'integer', 'on' => ['admin']],
			[['ban_time'], 'integer', 'on' => ['admin']],
			[['ban_reason'], 'string', 'max' => 255, 'on' => 'admin'],
        ];

        // add required rules for email/username depending on module properties
        $requireFields = ["requireEmail", "requireUsername"];
        foreach ($requireFields as $requireField) {
            if (Yii::$app->getModule("user")->$requireField) {
                $attribute = strtolower(substr($requireField, 7)); // "email" or "username"
                $rules[] = [$attribute, "required"];
            }
        }

        return $rules;
    }

    /**
     * Validate password
     */
    public function validateCurrentPassword() {

        // check password
        if (!$this->verifyPassword($this->currentPassword)) {
            $this->addError("currentPassword", "Current password incorrect");
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'role_id' => 'Role ID',
            'email' => 'Email',
            'new_email' => 'New Email',
            'username' => 'Username',
            'password' => 'Password',
            'status' => 'Status',
            'auth_key' => 'Auth Key',
            'ban_time' => 'Ban Time',
            'ban_reason' => 'Ban Reason',
            'created_at' => 'Create Time',
            'updated_at' => 'Update Time',

            // attributes in model
            'newPassword' => ($this->isNewRecord) ? 'Password' : 'New Password',
        ];
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getUserkeys() {
        $userkey = Yii::$app->getModule("user")->model("Userkey");
        return $this->hasMany($userkey::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    /*
    public function getProfiles() {
        $profile = Yii::$app->getModule("user")->model("Profile");
        return $this->hasMany($profile::className(), ['user_id' => 'id']);
    }
    */

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getProfile() {
        $profile = Yii::$app->getModule("user")->model("Profile");
        return $this->hasOne($profile::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getRole() {
        $role = Yii::$app->getModule("user")->model("Role");
        return $this->hasOne($role::className(), ['id' => 'role_id']);
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function() { return date("Y-m-d H:i:s"); },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token) {
        return static::findOne(["api_key" => $token]);
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->auth_key === $authKey;
    }

    /**
     * Get a clean display name for the user
     *
     * @var string $default
     * @return string|int
     */
    public function getDisplayName($default = "") {

        // define possible names
        $possibleNames = [
            "username",
            "email",
        ];

        // go through each and return if valid
        foreach ($possibleNames as $possibleName) {
            if (!empty($this->$possibleName)) {
                return $this->$possibleName;
            }
        }

        return $default;
    }

    /**
     * Send email confirmation to user
     *
     * @param Userkey $userkey
     * @return int
     */
    public function sendEmailConfirmation($userkey) {

        // modify view path to module views
        /** @var Mailer $mailer */
        $mailer = Yii::$app->mail;
        $mailer->viewPath = Yii::$app->getModule("user")->emailViewPath;

        // send email
        $user = $this;
        $profile = $user->profile;
        $email = ($user->new_email !== null && $user->new_email !== '') ? $user->new_email : $user->email;
        $subject = Yii::$app->id . " - Email confirmation";
        return $mailer->compose('confirmEmail', compact("subject", "user", "profile", "userkey"))
            ->setTo($email)
            ->setSubject($subject)
            ->send();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert) {

        // hash new password if set
        if ($this->newPassword) {
            $this->encryptNewPassword();
        }

        // generate auth_key and api_key if needed
        if (!$this->auth_key) {
            $this->auth_key = Security::generateRandomKey();
        }
        if (!$this->api_key) {
            $this->api_key = Security::generateRandomKey();
        }


        // convert ban_time checkbox to date
        if ($this->ban_time) {
            $this->ban_time = date("Y-m-d H:i:s");
        }

        // ensure fields are null so they won't get set as empty string
        $nullAttributes = ["email", "username", "ban_time", "ban_reason"];
        foreach ($nullAttributes as $nullAttribute) {
            $this->$nullAttribute = $this->$nullAttribute ? $this->$nullAttribute : null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * Encrypt newPassword into password
     *
     * @return static
     */
    public function encryptNewPassword() {
        $this->password_hash = Security::generatePasswordHash($this->newPassword);
        return $this;
    }

    /**
     * Validate password
     *
     * @param string $password
     * @return bool
     */
    public function verifyPassword($password) {
        return Security::validatePassword($password, $this->password_hash);
    }

    /**
     * Register a new user
     *
     * @param int $roleId
     * @param string $userIp
     * @return static
     */
    public function signup($roleId, $userIp) {

        // set default attributes for registration
        $attributes = [
            "role_id" => $roleId,
            "registration_ip" => $userIp,
        ];

        // determine if we need to change status based on module properties
        $emailConfirmation = Yii::$app->getModule("user")->emailConfirmation;

        // set status inactive if email is required
        if ($emailConfirmation and Yii::$app->getModule("user")->requireEmail) {
            $attributes["status"] = static::STATUS_INACTIVE;
        }
        // set unconfirmed if email is set but NOT required
        elseif ($emailConfirmation and Yii::$app->getModule("user")->useEmail and $this->email) {
            $attributes["status"] = static::STATUS_UNCONFIRMED_EMAIL;
        }
        // set active otherwise
        else {
            $attributes["status"] = static::STATUS_ACTIVE;
        }

        // set attributes
        $this->setAttributes($attributes, false);

        // save and return
        // note: we assume that we have already validated (both $user and $profile)
        $this->save(false);
        return $this;
    }

    /**
     * Set login ip and time
     *
     * @param bool $save Save record
     * @return static
     */
    public function setLoginIpAndTime($save = true) {

        // set data
        $this->login_ip = Yii::$app->getRequest()->getUserIP();
        $this->login_time = date("Y-m-d H:i:s");

        // save and return
        // auth key is added here in case user doesn't have one set from registration
        // it will be calculated in [[before_save]]
        if ($save) {
            $this->save(false, ["login_ip", "login_time", "auth_key"]);
        }
        return $this;
    }

    /**
     * Check and prepare for email change
     *
     * @return bool
     */
    public function checkAndPrepareEmailChange() {

        // check if user is removing email address
        // this only happens if $requireEmail = false
        if (trim($this->email) === "") {
            return false;
        }

        // check for change in email
        if ($this->email != $this->getOldAttribute("email")) {

            // change status
            $this->status = static::STATUS_UNCONFIRMED_EMAIL;

            // set new_email attribute and restore old one
            $this->new_email = $this->email;
            $this->email = $this->getOldAttribute("email");

            return true;
        }

        return false;
    }

    /**
     * Confirm user email
     *
     * @return static
     */
    public function confirm() {

        // update status
        $this->status = static::STATUS_ACTIVE;

        // update new_email if set
        if ($this->new_email) {
            $this->email = $this->new_email;
            $this->new_email = null;
        }

        // save and return
        $this->save(true, ["email", "new_email", "status"]);
        return $this;
    }

    /**
     * Check if user can do specified $permission
     *
     * @param string $permission
     * @return bool
     */
    public function can($permission) {
        return $this->role->checkPermission($permission);
    }

    /**
     * Get list of statuses for creating dropdowns
     *
     * @return array
     */
    public static function statusDropdown() {

        // get data if needed
        static $dropdown;
        if ($dropdown === null) {

            // create a reflection class to get constants
            $refl = new ReflectionClass(get_called_class());
            $constants = $refl->getConstants();

            // check for status constants (e.g., STATUS_ACTIVE)
            foreach ($constants as $constantName => $constantValue) {

                // add prettified name to dropdown
                if (strpos($constantName, "STATUS_") === 0) {
                    $prettyName = str_replace("STATUS_", "", $constantName);
                    $prettyName = Inflector::humanize(strtolower($prettyName));
                    $dropdown[$constantValue] = $prettyName;
                }
            }
        }

        return $dropdown;
    }

}
