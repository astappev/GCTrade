<?php

namespace app\modules\users\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Security;

/**
 * Userkey model
 *
 * @property string $id
 * @property string $user_id
 * @property int $type
 * @property string $key
 * @property string $created_at
 * @property string $consume_time
 * @property string $expire_time
 *
 * @property User $user
 */
class Userkey extends ActiveRecord {

    /**
     * @var int Key for email activations (=registering)
     */
    const TYPE_EMAIL_ACTIVATE = 1;

    /**
     * @var int Key for email changes (=updating account page)
     */
    const TYPE_EMAIL_CHANGE = 2;

    /**
     * @var int Key for password resets
     */
    const TYPE_PASSWORD_RESET = 3;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return static::getDb()->tablePrefix . "userkey";
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
//            [['user_id', 'type', 'key'], 'required'],
//            [['user_id', 'type'], 'integer'],
//            [['created_at', 'consume_time', 'expire_time'], 'safe'],
//            [['key'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'key' => 'Key',
            'created_at' => 'Create Time',
            'update_time' => 'Update Time',
            'expire_time' => 'Expire Time',
        ];
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getUser() {
        $user = Yii::$app->getModule("user")->model("User");
        return $this->hasOne($user::className(), ['id' => 'user_id']);
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    // set only created_at because there is no update_time
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => function() { return date("Y-m-d H:i:s"); },
            ],
        ];
    }

    /**
     * Generate and return a new userkey
     *
     * @param int $userId
     * @param int $type
     * @param string $expireTime
     * @return static
     */
    public static function generate($userId, $type, $expireTime = null) {

        // attempt to find existing record
        // otherwise create new record
        $model = static::findActiveByUser($userId, $type);
        if (!$model) {
            $model = new static();
        }

        // set/update data
        $model->user_id = $userId;
        $model->type = $type;
        $model->created_at = date("Y-m-d H:i:s");
        $model->expire_time = $expireTime;
        $model->key = Security::generateRandomKey();
        $model->save(false);

        return $model;
    }

    /**
     * Find an active userkey
     *
     * @param int $userId
     * @param int $type
     * @return static
     */
    public static function findActiveByUser($userId, $type) {

        $now = date("Y-m-d H:i:s");
        return static::find()
            ->where([
                "user_id" => $userId,
                "type" => $type,
                "consume_time" => null,
            ])
            ->andWhere("([[expire_time]] >= '$now' or [[expire_time]] is NULL)")
            ->one();
    }

    /**
     * Find a userkey object for confirming
     *
     * @param string $key
     * @param array|string $type
     * @return static
     */
    public static function findActiveByKey($key, $type) {

        $now = date("Y-m-d H:i:s");
        return static::find()
            ->where([
                "key" => $key,
                "type" => $type,
                "consume_time" => null,
            ])
            ->andWhere("([[expire_time]] >= '$now' or [[expire_time]] is NULL)")
            ->one();
    }

    /**
     * Consume userkey record
     *
     * @return static
     */
    public function consume() {
        $this->consume_time = date("Y-m-d H:i:s");
        $this->save(false);
        return $this;
    }

    /**
     * Expire userkey record
     *
     * @return static
     */
    public function expire() {
        $this->expire_time = date("Y-m-d H:i:s");
        $this->save(false);
        return $this;
    }
}
