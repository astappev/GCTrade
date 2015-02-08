<table class="table table-hover item-list sort">
    <thead>
    <tr>
        <th width="5%"></th>
        <th width="5%"><?= Yii::t('shop', 'ID') ?></th>
        <th class="name"><?= Yii::t('shop', 'NAME') ?></th>
        <th width="30%"><?= Yii::t('shop', 'AUTHOR') ?></th>
        <th width="15%"><?= Yii::t('shop', 'SELLING_PRICE') ?></th>
    </tr>
    </thead>
    <tbody>

    <?php foreach($model->products as $price): ?>

        <tr>
            <td><img src="/images/items/<?= $price->item->getAlias() ?>.png" alt="<?= $price->item->name; ?>" class="small-icon"></td>
            <td><?= $price->id ?></td>
            <td class="name"><?= $price->name; ?></td>
            <td><?= $price->author ?></td>
            <td><?= $price->price_sell ? $price->price_sell : '—' ?></td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>