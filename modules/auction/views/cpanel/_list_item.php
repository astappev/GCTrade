<?php
/**
 * Шаблон одного пользователя на странице всех записей.
 * @var yii\base\View $this
 * @var app\modules\auction\models\Lot $model
 */

use yii\helpers\Html;
?>
<div class="well clearfix">
    <div class="info">
        <h3><a href="<?= Yii::$app->urlManager->createUrl(['auction/default/view', 'id' => $model->id]) ?>"><?= Html::encode($model->name) ?></a></h3>

        <?php if($model->time_elapsed < time()): ?>
            <a href="#" class="btn btn-success disabled" role="button">Аукцион закрыт</a>
            <a href="#" class="btn btn-primary disabled" role="button"><?= ($model->bid) ? \Yii::$app->formatter->asInteger($model->bid->cost).' зелени' : 'нет ставок' ?></a>
        <?php elseif($bid = $model->bid): ?>
            <a href="#" class="btn btn-success disabled countdown" role="button" data-time="<?= $model->time_elapsed ?>">00:00:00</a>
            <a href="#" class="btn btn-primary disabled" role="button"><?= \Yii::$app->formatter->asInteger($bid->cost) ?> зелени</a>
        <?php else: ?>
            <a href="#" class="btn btn-success disabled countdown" role="button" data-time="<?= $model->time_elapsed ?>">00:00:00</a>
            <a href="#" class="btn btn-primary disabled" role="button">нет ставок</a>
        <?php endif; ?>

        <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => $model->bid ? 'btn btn-danger disabled' : 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы действительно хотите удалить магазин?',
                'method' => 'post',
            ],
        ]) ?>
    </div>
</div>