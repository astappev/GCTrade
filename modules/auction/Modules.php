<?php

namespace app\modules\auction;

use Yii;

class Modules extends \yii\base\Module implements \yii\base\BootstrapInterface
{
    public $controllerNamespace = 'app\modules\auction\controllers';

    public $auctionUrl = 'auction';

    public function bootstrap($app)
    {
        $app->getUrlManager()->addRules([
            /* Auction rules */
            $this->auctionUrl . '/<id:\d+>' => $this->id . '/default/view',
            $this->auctionUrl => $this->id . '/default/view',
            $this->auctionUrl . 's/<page:[0-9]{1,3}+>' => $this->id . '/default/index',
            $this->auctionUrl . 's' => $this->id . '/default/index',
            $this->auctionUrl . 's/<_action:\w+>' => $this->id . '/default/<_action>',

            'cpanel/' . $this->auctionUrl . '' => $this->id . '/cpanel/index',
            'cpanel/' . $this->auctionUrl . '/<_action:\w+>/<id:\d+>' => $this->id . '/cpanel/<_action>',
            'cpanel/' . $this->auctionUrl . '/<_action:\w+>' => $this->id . '/cpanel/<_action>',

            //$this->auctionUrl . '/<_action:\w+>' => $this->id . '/default/<_action>',
            //$this->auctionUrl . '/<_action:\w+>/<alias:\w+>' => $this->id . '/default/<_action>',

            $this->auctionUrl . '/<_controller:\w+>/<_action:\w+>' => $this->id . '/<_controller>/<_action>',
        ], false);

        if (!isset($app->i18n->translations['auction']) && !isset($app->i18n->translations['auction*'])) {
            $app->i18n->translations['auction'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'system',
                'basePath' => '@app/modules/auction/messages',
                'forceTranslation' => true
            ];
        }
    }
}
