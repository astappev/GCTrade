<?php

namespace app\modules\shop\controllers;

use Yii;
use app\modules\shop\models\Shop;
use app\modules\shop\models\Good;
use app\modules\shop\models\Item;
use yii\helpers\Json;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use vova07\fileapi\actions\UploadAction as FileAPIUpload;
use vova07\imperavi\actions\UploadAction as ImperaviUpload;

/**
 * CpanelController includes manage your Shop actions
 */
class CpanelController extends DefaultController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'item-edit' => ['get'],
                    'item-remove' => ['get'],
                ],
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

    /**
     * Lists all your Shop models.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Creates a new Shop model.
     * If creation is successful, the browser will be redirected to the 'edit' page.
     * @return mixed
     */
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
            return $this->redirect(['cpanel/index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Shop model.
     * If update is successful, the browser will be refresh this page.
     * @param string $alias
     * @return mixed
     * @throws ForbiddenHttpException if you do not have permission to update this model
     */
    public function actionUpdate($alias)
    {
        $model = $this->findModel($alias, true);
        $model->scenario = 'update';

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Магазин '.$model->name.', успешно обновлен.');
            } else {
                Yii::$app->session->setFlash('error', 'Возникла ошибка при сохранении.');
            }
            return $this->refresh();
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Shop model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $alias
     * @return mixed
     * @throws ForbiddenHttpException if you do not have permission to delete this model
     */
    public function actionDelete($alias)
    {
        $model = $this->findModel($alias, true);
        $model->delete();

        Yii::$app->session->setFlash('success', 'Магазин '.$model->name.', успешно удален.');
        return $this->redirect(['cpanel/index']);
    }

    /**
     * Edit item from Shop model.
     * @param string $alias
     * @return mixed
     */
    public function actionEdit($alias)
    {
        return $this->render('edit', [
            'model' => $this->findModel($alias, true),
        ]);
    }

    public function actionItemEdit($shop_id, $item_id, $price_sell, $price_buy, $stuck)
    {
        if($price_sell < 0 || $price_buy < 0 || $stuck < 1)
            return Json::encode(['status' => 0, 'message' => Yii::t('app/shop', 'ERROR_TRANSMITTED_DATA')]);

        if(($shop = Shop::findOne($shop_id)) === null)
            return Json::encode(['status' => 0, 'message' => Yii::t('app/shop', 'SHOP_NOT_FOUND')]);

        if($shop->user_id !== Yii::$app->user->id)
            throw new ForbiddenHttpException(Yii::t('app/shop', 'SHOP_NO_PERMISSION'));

        if(($item = Item::findByAlias($item_id)) === null)
            return Json::encode(['status' => 0, 'message' => Yii::t('app/shop', 'ITEM_NOT_FOUND')]);

        if(($price = Good::find()->where(['shop_id' => $shop_id, 'item_id' => $item->id])->one()) !== null)
        {
            $price->price_sell = ($price_sell == 0) ? null : $price_sell;
            $price->price_buy = ($price_buy == 0) ? null : $price_buy;
            $price->stuck = $stuck;
            if($price->save()) return Json::encode(['status' => 1, 'message' => Yii::t('app/shop', 'ITEM_UPDATED')]);
        } else {
            $price = new Good();
            $price->item_id = $item->id;
            $price->shop_id = $shop_id;
            $price->price_sell = ($price_sell == 0) ? null : $price_sell;
            $price->price_buy = ($price_buy == 0) ? null : $price_buy;
            $price->stuck = $stuck;
            if($price->save()) return Json::encode(['status' => 2, 'message' => Yii::t('app/shop', 'ITEM_CREATED')]);
        }
        return false;
    }

    public function actionItemRemove($shop_id, $item_id)
    {
        if(($item = Item::findByAlias($item_id)) !== null && ($shop = Shop::findOne($shop_id)) !== null)
        {
            if($shop->user_id != Yii::$app->user->id)
                throw new ForbiddenHttpException(Yii::t('app/shop', 'SHOP_NO_PERMISSION'));

            if(($price = Good::find()->where(['item_id' => $item->id, 'shop_id' => $shop_id])->one()) !== null && $price->delete())
                return Json::encode(['status' => 1, 'message' => Yii::t('app/shop', 'ITEM_REMOVED')]);
        }

        return Json::encode(['status' => 0, 'message' => Yii::t('app/shop', 'ERROR_TRANSMITTED_DATA')]);
    }

    public function actionItemClear($shop_id)
    {
        if(($shop = Shop::findOne($shop_id)) === null)
            throw new NotFoundHttpException(Yii::t('app/shop', 'SHOP_NOT_FOUND'));

        if($shop->user_id !== Yii::$app->user->id)
            throw new ForbiddenHttpException(Yii::t('app/shop', 'SHOP_NO_PERMISSION'));

        Good::deleteAll(['shop_id' => $shop_id]);
        return $this->redirect(['cpanel/edit', 'alias' => $shop->alias]);
    }

    public function actionExport($alias)
    {
        $model = $this->findModel($alias, true);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$model->alias.'_'.date('Ymd').'.csv"');

        $content = '';
        foreach($model->prices as $price)
        {
            $price_sell = ($price->price_sell) ? $price->price_sell : 'null';
            $price_buy = ($price->price_buy) ? $price->price_buy : 'null';
            $content .= $price->item->alias . '; ' . $price_sell . '; ' . $price_buy . '; ' . $price->stuck . PHP_EOL;
        }
        return $content;
    }
}