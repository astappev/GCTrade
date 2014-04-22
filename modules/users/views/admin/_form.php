<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
$role = \Yii::$app->getModule("user")->model("Role");

/**
 * @var yii\web\View $this
 * @var app\modules\users\models\User $user
 * @var app\modules\users\models\Profile $profile
 * @var app\modules\users\models\Role $role
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="user-form">

	<?php $form = ActiveForm::begin(); ?>

		<?= $form->field($user, 'email')->textInput(['maxlength' => 255]) ?>

		<?= $form->field($user, 'username')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($profile, 'full_name'); ?>

        <?= $form->field($user, 'newPassword'); ?>

		<?= $form->field($user, 'role_id')->dropDownList($role::dropdown()); ?>

		<?= $form->field($user, 'status')->dropDownList($user::statusDropdown()); ?>

        <?php // use checkbox for ban_time ?>
        <?= Html::activeLabel($user, 'ban_time', ['label' => 'Banned']); ?>
        <?= Html::activeCheckbox($user, 'ban_time'); ?>
        <?= Html::error($user, 'ban_time'); ?>

		<?= $form->field($user, 'ban_reason'); ?>

		<div class="form-group">
			<?= Html::submitButton($user->isNewRecord ? 'Create' : 'Update', ['class' => $user->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
		</div>

	<?php ActiveForm::end(); ?>

</div>
