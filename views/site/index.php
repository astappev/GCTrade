<?php
\app\assets\IntroAsset::register($this);

$this->title = 'Приветствуем!';
?>
    <div class="site-index">
        <div class="jumbotron">
            <h1>Приветствую</h1>
            <p class="lead">Здесь на GCTrade, вся экономика <strong><span class="green">Green</span>Cubes</strong> в одном месте!</p>
            <p><button class="btn btn-lg btn-success intro-finish">Ознакомиться с GCTrade</button></p>
        </div>

        <div class="body-content">
            <div class="row">
                <div class="col-lg-4 intro-col1">
                    <h2>Для покупателей</h2>
                    <div style="margin-bottom: 15px;">
                        <img src="/images/homepage/01_b.png" align="left" style="margin: 10px;" />
                        <h4>Подбор магазина</h4>
                        <p>Вы можете найти свой любимый магазин: по расположению, по ценам, по удобству.</p>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <img src="/images/homepage/03_b.png" align="left" style="margin: 10px;" />
                        <h4>Поиск товара</h4>
                        <p>Вы выбираете товар и смотрите в каких магазинах он в наличии.</p>
                    </div>
                </div>
                <div class="col-lg-4 intro-col2">
                    <h2>Для продавцов</h2>
                    <div style="margin-bottom: 15px;">
                        <img src="/images/homepage/02_b.png" align="left" style="margin: 10px;" />
                        <h4>Доступная информация</h4>
                        <p>Держите в курсе вашего покупателя об актуальных ценах. Поддерживайте конкурентоспособность.</p>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <img src="/images/homepage/04_b.png" align="left" style="margin: 10px;" />
                        <h4>Простое управление</h4>
                        <p>Вашим прайсом очень легко управлять. Только попробуйте!</p>
                    </div>
                </div>
                <div class="col-lg-4 intro-col3">
                    <h2>Общая информация</h2>
                    <div style="margin-bottom: 15px;">
                        <img src="/images/homepage/05_b.png" align="left" style="margin: 10px;" />
                        <h4>Сравнение цен</h4>
                        <p>Хотели узнать где дешевле всего купить кровокамень? Мы укажем вам, где дешевле!</p>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <img src="/images/homepage/06_b.png" align="left" style="margin: 10px;" />
                        <h4>Информация об экономике</h4>
                        <p>Вы всегда можете узнать актуальную цену продажи или покупки.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $this->registerJS("var introguide = introJs();

    introguide.setOptions({
        steps: [
            {
                element: '.lead',
                intro: 'Пользуясь данным сервисом вы откроете все удобства торговли. И сможете быть конкурентоспособным на рынке GreenCubes.',
                position: 'bottom'
            },
            {
                element: '.intro-shop',
                intro: 'Магазин — основной способ продажи материалов и вещей. Здесь вы можете продавать или покупать. После авторизации сможете добавить свой магазин.',
                position: 'bottom'
            },
            {
                element: '.intro-item',
                intro: 'Товары — поиск, сравнение цен, актуальность информации. В этом смысл данного сервиса.',
                position: 'bottom'
            },
            {
                element: '.intro-economy',
                intro: 'Краткие сводки и данные о экономике GreenCubes.',
                position: 'bottom'
            },
            {
                element: '.intro-maps',
                intro: 'Карта магазинов, а так же после авторизации доступны другие полезные штучки связанные с картой.',
                position: 'bottom'
            },
            {
                element: '.intro-other',
                intro: 'Прочие небольшие безделушки, которые могут быть полезны для вас.'
            },
            {
                element: '.body-content',
                intro: 'Условно, сервис разделен на 2 части.',
                position: 'top'
            },
            {
                element: '.intro-col1',
                intro: 'Первая часть: для простого пользователя — вы можете зайти, посмотреть цены, посмотреть список товаров в магазине и решить куда вам ехать за покупками.',
                position: 'top'
            },
            {
                element: '.intro-col2',
                intro: 'Вторая часть: для владельцев магазинов и частных предпринимателей — вы можете создавать магазины, размещать товар оставлять объявления и быть в курсе экономики.',
                position: 'top'
            },
            {
                element: '.intro-col3',
                intro: 'Тот кто владеет информацией — владеет миром. Здесь не та информация которая позволит владеть миром, но она явно сможет помочь сэкономить зелень.',
                position: 'top'
            },
            {
                element: '.intro-finish',
                intro: 'Благодарю за ознакомление. Приятного использования данного сервиса.',
                position: 'bottom'
            }
        ],
        'skipLabel': 'Выход',
        'nextLabel': 'Дальше →',
        'prevLabel': '← Назад',
        'doneLabel': 'Завершить',
        'showStepNumbers': false
    });

    $('button.btn').click(function() {
        introguide.start();
    });");