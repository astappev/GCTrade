<?php

namespace app\modules\auction\models;

use app\modules\users\models\User;
use vova07\fileapi\behaviors\UploadBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class Lot
 * @package app\modules\auction\models
 * Model Lot.
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $type_id
 * @property string $name
 * @property string $metadata
 * @property string $description
 * @property string $picture_url
 * @property integer $price_min
 * @property integer $price_step
 * @property integer $price_blitz
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property Bid $bids
 * @property Bid $bid
 * @property \app\modules\users\models\User $user
 */
class Lot extends ActiveRecord
{
    const TYPE_ITEM = 1;
    const TYPE_LAND = 2;
    const TYPE_PROJECT = 3;
    const TYPE_OTHER = 9;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auction_lot}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => TimestampBehavior::className(),
            'uploadBehavior' => [
                'class' => UploadBehavior::className(),
                'attributes' => [
                    'picture_url' => [
                        'path' => 'images/auction/',
                        'tempPath' => 'images/auction/tmp/',
                        'url' => '/images/auction'
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            'create' => ['user_id', 'type_id', 'name', 'metadata', 'description', 'picture_url', 'price_min', 'price_step', 'price_blitz', 'created_at', 'updated_at'],
            'update' => ['name', 'metadata', 'description', 'picture_url', 'price_min', 'price_step', 'price_blitz', 'updated_at'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'price_min', 'metadata'], 'required'],

            ['user_id', 'default', 'value' => \Yii::$app->user->id],

            [['name', 'metadata', 'description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'picture_url'], 'string', 'max' => 255],

            [['price_min', 'price_step', 'price_blitz'], 'integer', 'max' => 100000000000],

            ['type_id', 'in', 'range' => array_keys(self::getTypeArray())]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'name' => 'Название лота',
            'metadata' => 'Данные о лоте',
            'type_id' => 'Тип лота',
            'description' => 'Описание',
            'picture_url' => 'Изображение',
            'price_min' => 'Начальная цена',
            'price_step' => 'Шаг аукциона',
            'price_blitz' => 'Блиц цена',
            'created_at' => 'Создан',
            'updated_at' => 'Последнее обновление',
        ];
    }

    /**
     * @return array
     */
    public static function getTypeArray()
    {
        return [
            self::TYPE_ITEM => 'Предмет',
            self::TYPE_LAND => 'Территория',
            //self::TYPE_PROJECT => 'Проект',
            //self::TYPE_OTHER => 'Прочее',
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        $type = self::getTypeArray();
        return $type[$this->type_id];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBid()
    {
        return $this->hasOne(Bid::className(), ['lot_id' => 'id'])->orderBy(['cost' => SORT_DESC])->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBids()
    {
        return $this->hasMany(Bid::className(), ['lot_id' => 'id'])->orderBy(['cost' => SORT_DESC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
