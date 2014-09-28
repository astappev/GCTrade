<?php
use yii\helpers\Html;

$this->title = 'Политика конфиденциальности';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="body-content">
    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-danger">Для поддержки функционала, GCTrade использует некоторое количество ваших персональных данных!</p>
    <p>Персональные данные собираются на протяжение всего использования сервиса и хранятся на сервере БД,
        а так же логах создаваемых сервером. Эти данные не доступны за пределами GCTrade, кроме того данная информация никогда не будет передана третьим лицам.</p>
    <p>Часть персональных данных, таких как: логин и дата последнего посещения общедоступны.</p>
    <p>Ваши персональные, а так же конфиденциальные данные используются только для поддержания
        работоспособности сервиса и не могут быть использованы в каких-либо других целях. Во время взаимодействия с
        браузером используются локальные хранилища браузера, такие как cookies, кэш и LocalStorage.
    </p>
    <p>Информация собираемая сервером используется для оптимизации вашего соединения и включает в себя: ваш IP адрес, дату и время, тип операционной системы и браузер используемый для доступа к GCTrade. Защищена политикой безопасности <?= Html::a('хостинг-провайдера' ,'https://www.ukraine.com.ua/legal/privacypolicy/', ['target' => '_blank'])?>.</p>

    <p><span class="label label-default">Правила имеют силу с 11.09.2014</span></p>
</div>