<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\shop\Modules as Module;

/* @var $this yii\web\View */
/* @var $model app\modules\shop\models\search\Shop */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="test-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'name') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app/shop', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app/shop', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>