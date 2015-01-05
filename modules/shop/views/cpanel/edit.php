<?php

use yii\helpers\Html;
use yii\bootstrap\Modal;
use app\modules\shop\models\Good;

/**
 * @var yii\web\View $this
 * @var app\modules\shop\models\Shop $model
 */

$this->registerJsFile(YII_ENV_PROD ? '@web/js/jquery/jquery.spin.min.js' : '@web/js/jquery/jquery.spin.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
$this->registerJsFile(YII_ENV_PROD ? '@web/js/item.gctrade.min.js' : '@web/js/item.gctrade.js', ['depends' => [\yii\web\JqueryAsset::className()]]);

$this->title = 'Обновление товара';
$this->params['breadcrumbs'][] = ['label' => 'Панель управления', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Магазины', 'url' => ['cpanel/index']];
$this->params['breadcrumbs'][] = $this->title . ' - ' . $model->name;
?>
<div class="body-content edit-shop" id="<?= $model->id ?>">
	<h1><?= Html::encode($this->title) ?></h1>

    <div class="panel panel-default">
        <div class="panel-heading">
            <button class="btn btn-warning" data-toggle="modal" data-target="#ImportFileModal" role="button"><span class="glyphicon glyphicon-import"></span> Импорт</button>
            <a class="btn btn-info" href="<?= Yii::$app->urlManager->createUrl(['shop/cpanel/export', 'alias' => $model->alias]) ?>" role="button"><span class="glyphicon glyphicon-export"></span> Экспорт</a>
            <button class="btn btn-primary" data-toggle="modal" data-target="#AddModal" role="button"><span class="glyphicon glyphicon-plus"></span> Добавить товар</button>
            <a class="btn btn-danger" href="<?= Yii::$app->urlManager->createUrl(['shop/cpanel/item-clear', 'shop_id' => $model->id]) ?>" role="button"><span class="glyphicon glyphicon-exclamation-sign"></span> Удалить все</a>
            <?php if($model->status == 8) echo '<a class="btn btn-success" href="'.Yii::$app->urlManager->createUrl(['shop/parser/'.$model->alias]).'" role="button"><span class="glyphicon glyphicon-download"></span> Синхронизировать</a>'; ?>
        </div>
        <?php if(empty($model->products)): ?>
            <div class="panel-body text-center">У данного магазина, нет товара.</div>
        <?php else: ?>
            <table class="table table-hover item-list sort not-cursor">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="5%">ID</th>
                        <th class="name">Название</th>
                        <th width="15%">Цена продажи</th>
                        <th width="15%">Цена покупки</th>
                        <th width="15%">Кол-во за сделку</th>
                        <th width="10%"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach(Good::find()->where(['shop_id' => $model->id])->orderBy(['item_id' => SORT_ASC])->all() as $price): ?>
                    <tr>
                        <td><img src="/images/items/<?= $price->item->alias; ?>.png" alt="<?= $price->item->name; ?>" align="left" class="small-icon" /></td>
                        <td><?= $price->item->alias; ?></td>
                        <td class="name"><a href="<?= Yii::$app->urlManager->createUrl(['shop/item/view', 'alias' => $price->item->alias]) ?>"><?= $price->item->name; ?></a></td>
                        <td><?= ($price->price_sell)?$price->price_sell:'—' ?></td>
                        <td><?= ($price->price_buy)?$price->price_buy:'—' ?></td>
                        <td><?= $price->stuck; ?></td>
                        <td class="control">
                            <div class="btn-group btn-group-sm">
                                <button id="editButtons" class="btn btn-primary" title="Редактировать"><span class="glyphicon glyphicon-pencil"></span></button>
                                <button id="removeButtons" class="btn btn-danger" title="Удалить"><span class="glyphicon glyphicon-remove"></span></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php Modal::begin([
    'id' => 'ImportFileModal',
    'toggleButton' => false,
    'header' => '<h3 class="modal-title">Импорт товаров с файла</h3>',
    'footer' => '<button type="button" id="sync" class="btn btn-warning"><span class="glyphicon glyphicon-import"></span>Импорт</button>',
]); ?>
    <div class="form-group">
        <label for="InputFile">Выбирите CSV файл прайса</label>
        <input type="file" id="InputFile" accept=".csv, text/plain">
        <p class="help-block">Файл должен содержать в себе информацию по такому шаблону: (количество строк не ограничено)</p>
        <pre>id, цена продажи, цена покупки, количество за одну операцию;</pre>
        <p class="help-block">В случае если товар есть в базе, он бдует обновлен, если его нет - добавлен.</p>
    </div>
    <table class="table AddItem" id="ImportItemFile" style="display: none;">
        <thead>
            <tr>
                <th>Товар</th>
                <th>Цена продажи</th>
                <th>Цена покупки</th>
                <th>Кол-во</th>
                <th width="40px"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
<?php Modal::end(); ?>

<?php Modal::begin([
    'id' => 'AddModal',
    'toggleButton' => false,
    'header' => '<h3 class="modal-title">Добавление товаров</h3>',
    'footer' => '<button type="button" id="sync" class="btn btn-warning"><span class="glyphicon glyphicon-import"></span> Импорт</button>',
]); ?>
    <form class="form-inline" role="form" id="AddItemForm">
        <div class="form-group">
            <label class="sr-only" for="InputID">ID товара</label>
            <input type="text" name="ID" class="form-control" id="InputID" placeholder="ID товара">
        </div>
        <div class="form-group">
            <label class="sr-only" for="InputBuy">Цена продажи</label>
            <input type="text" name="Buy" class="form-control" id="InputBuy" placeholder="Цена продажи">
        </div>
        <div class="form-group">
            <label class="sr-only" for="InputSell">Цена покупки</label>
            <input type="text" name="Sell" class="form-control" id="InputSell" placeholder="Цена покупки">
        </div>
        <div class="form-group">
            <label class="sr-only" for="InputStuck">Кол-во</label>
            <input type="text" name="Stuck" class="form-control" id="InputStuck" placeholder="Кол-во">
        </div>
        <button type="submit" class="btn btn-primary">Добавить</button>
    </form>
    <table class="table AddItem" id="AddItemTable" style="display: none;">
        <thead><tr><th>Товар</th><th>Цена продажи</th><th>Цена покупки</th><th>Кол-во</th><th width="40px"></th></tr></thead>
        <tbody></tbody>
    </table>
<?php Modal::end(); ?>

<?php Modal::begin([
    'id' => 'EditModal',
    'toggleButton' => false,
    'header' => '<h3 class="modal-title">Редактировать товар</h3>',
    'footer' => '<button type="button" id="editButtonModal" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Изменить</button>',
]); ?>
    <form class="form-horizontal" role="form" id="EditItemForm">
        <input type="hidden" class="hide" id="IdHide">
        <div class="form-group">
            <label class="col-sm-4 control-label" id="name" data-id=""></label>
            <div class="col-sm-6">
                <p class="form-control-static" id="name"></p>
            </div>
        </div>
        <div class="form-group">
            <label for="Sell" class="col-sm-4 control-label">Цена продажи</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="Sell">
            </div>
        </div>
        <div class="form-group">
            <label for="Buy" class="col-sm-4 control-label">Цена покупки</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="Buy">
            </div>
        </div>
        <div class="form-group">
            <label for="Stuck" class="col-sm-4 control-label">Кол-во за сделку</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="Stuck">
            </div>
        </div>
    </form>
<?php Modal::end(); ?>