<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Shop;
use app\models\Price;
use app\models\Item;
use yii\web\HttpException; // throw new HttpException(404);
use vova07\fileapi\actions\UploadAction as FileAPIUpload;
use vova07\imperavi\actions\UploadAction as ImperaviUpload;

class ShopController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'complaint'],
                        'roles' => ['?']
                    ],
                    [
                        'allow' => false,
                        'roles' => ['?']
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ]
            ],
        ];
    }

    public function actions()
    {
        return [
            'logo-upload' => [
                'class' => FileAPIUpload::className(),
                'path' => 'images/shop/tmp/'
            ],
            'image-upload' => [
                'class' => ImperaviUpload::className(),
                'url' => 'http://gctrade.ru/images/shop/description/',
                'path' => 'images/shop/description/',
                'validatorOptions' => [
                    'maxWidth' => 1600,
                    'maxHeight' => 2000
                ]
            ]
        ];
    }

	public function actionIndex()
	{
		return $this->render('index');
	}

    public function actionView($alias)
    {
        $shop = Shop::find()->where(['alias' => $alias])->one();
        if ($shop === null) {
            throw new HttpException(404, 'Магазин не найден.');
        }
        return $this->render('view', ['shop' => $shop]);
    }

    public function actionUpdate($alias)
    {
        $model = Shop::findByAlias($alias);
        if ($model != null) {
            if($model->owner != Yii::$app->user->id)
                throw new HttpException(403, 'Вы не являетесь владельцем данного магазина.');

            $model->scenario = 'update';
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Магазин '.$model->name.', успешно обновлен.');
                } else {
                    Yii::$app->session->setFlash('error', 'Возникла ошибка при сохранении.');
                }
                return $this->refresh();
            } else {
                return $this->render('update', ['model' => $model]);
            }
        }

        throw new HttpException(403, 'Магазин не найден.');
    }

    function actionDeleteLogo($id)
    {
        $model = Shop::findOne($id);
        if ($model->owner == \Yii::$app->user->id) {
            $model->setScenario('delete-logo');
            $model->save(false);
        } else {
            throw new HttpException(403, 'Вы не являетесь владельцем данного магазина.');
        }
    }

    public function actionEdit()
    {
        return $this->render('edit');
    }

    public function actionItem($alias)
    {
        return $this->render('item', ['url' => $alias]);
    }

    public function actionEdititem($id_item, $id_shop, $price_sell = null, $price_buy = null, $stuck)
    {
        $shop = Shop::findOne($id_shop);
        if($shop->owner != Yii::$app->user->id)
            return '<span class="glyphicon glyphicon-ban-circle red" data-toggle="tooltip" title="У вас нет прав"></span>';
        if(($price_sell && $price_sell < 0) || ($price_buy && $price_buy < 0) || !$stuck || $stuck <= 0)
            return '<span class="glyphicon glyphicon-ban-circle red" data-toggle="tooltip" title="Некоректные значения"></span>';

        $item = Item::findByAlias($id_item);
        if(!$item)
            return '<span class="glyphicon glyphicon-ban-circle red" data-toggle="tooltip" title="Данный товар не существует"></span>';

        $price = Price::find()->where(['id_item' => $item->id, 'id_shop' => $id_shop])->one();
        if($price)
        {
            $price->price_sell = $price_sell;
            $price->price_buy = $price_buy;
            $price->stuck = $stuck;
            if($price->save())
                return '<span class="glyphicon glyphicon-refresh blue" data-toggle="tooltip" title="Обновлено"></span>';
        } else {
            $price = new Price();
            $price->id_item = $item->id;
            $price->id_shop = $id_shop;
            $price->price_sell = $price_sell;
            $price->price_buy = $price_buy;
            $price->stuck = $stuck;
            if($price->save())
                return '<span class="glyphicon glyphicon-ok-circle twosize green" data-toggle="tooltip" title="Добавлено"></span>';
        }
    }

    public function actionRemoveitem($id_shop, $id_item)
    {
        $item = Item::findByAlias($id_item);
        $shop = Shop::findOne($id_shop);
        if($shop->owner != Yii::$app->user->id) return false;
        $price = Price::find()->where(['id_item' => $item->id, 'id_shop' => $id_shop])->one();
        if($price->delete()) return true;
    }

    public function actionClearitem($id_shop)
    {
        if (Yii::$app->request->get())
        {
            $shop = Shop::findOne($id_shop);
            if($shop->owner != Yii::$app->user->id) return true;

            $prices = Price::find()->where(['id_shop' => $id_shop])->all();
            foreach($prices as $price)
            {
                $price->delete();
            }
            return Yii::$app->getResponse()->redirect(['shop/item/'.$shop->alias]);
        }
    }

    public function actionExport($id)
    {
        $shop = Shop::findOne($id);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$shop->alias.'_'.date('Ymd').'.csv"');

        if($shop->owner == Yii::$app->user->id)
        {
            $content = '';
            foreach($shop->prices as $price)
            {
                $price_sell = ($price->price_sell)?$price->price_sell:'null';
                $price_buy = ($price->price_buy)?$price->price_buy:'null';
                $content .= $price->item->alias.'; '.$price_sell.'; '.$price_buy.'; '.$price->stuck.PHP_EOL;
            }
            return $content;
        } else {
            Yii::$app->session->setFlash('error', 'Вы не являетесь владельцем данного магазина.');
            $this->goBack();
        }
    }

    public function actionDelete($alias)
    {
        $shop = Shop::findByAlias($alias);
        if($shop->owner == Yii::$app->user->id)
        {
            Yii::$app->session->setFlash('success', 'Магазин '.$shop->name.', успешно удален.');
            $shop->delete();
            return $this->redirect(['shop/edit']);
        } else {
            Yii::$app->session->setFlash('error', 'Вы не являетесь владельцем данного магазина.');
            $this->goBack();
        }
    }

    public function actionCreate()
    {
        $model = new Shop();
        $model->scenario = 'create';
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Магазин '.$model->name.', успешно создан.');
            } else {
                Yii::$app->session->setFlash('error', 'Возникла ошибка при сохранении магазина.');
            }
            return $this->redirect('/shop/edit');
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    public function actionComplaint($id_item = null, $id_shop, $type = null)
    {
        if (Yii::$app->user->isGuest) {
            return 'guest';
        }

        $shop = Shop::findOne($id_shop);
        if(isset($id_item) && isset($id_shop) && isset($type))
        {
            $item = Item::findOne($id_item);

            $isOwner = false;
            if($shop->owner === Yii::$app->user->id) $isOwner = true;
            $price = Price::find()->where(['id_item' => $item->id, 'id_shop' => $id_shop])->one();

            if($type == 'buy')
                $price->complaint_buy = 1;
            else if($type == 'sell')
                $price->complaint_sell = 1;
            else
                return false;

            if($price->save())
                return ($isOwner)?'del':'add';
            else
                return false;
        } else {
            if($shop->owner !== Yii::$app->user->id)
                throw new HttpException(403, 'У вас нет дсотупа.');

            foreach($shop->prices as $price)
            {
                $price->save();
            }
            return $this->redirect(['shop/item', 'alias' => $shop->alias]);
        }
    }
}