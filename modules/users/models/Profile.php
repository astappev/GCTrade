<?php

namespace app\modules\users\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Profile model
 *
 * @property int $id
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $full_name
 *
 * @property User $user
 */
class Profile extends ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return static::getDb()->tablePrefix . "profile";
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
//            [['user_id'], 'required'],
//            [['user_id'], 'integer'],
//            [['created_at', 'updated_at'], 'safe'],
            [['full_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'created_at' => 'Create Time',
            'updated_at' => 'Update Time',
            'full_name' => 'Full Name',
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
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function() { return date("Y-m-d H:i:s"); },
            ],
        ];
    }

    /**
     * Register a new profile for user
     *
     * @param int $userId
     * @return static
     */
    public function signup($userId) {

        $this->user_id = $userId;
        $this->save();
        return $this;
    }

    /**
     * Set user id for profile
     *
     * @param int $userId
     * @return static
     */
    public function setUser($userId) {

        $this->user_id = $userId;
        return $this;
    }
}
