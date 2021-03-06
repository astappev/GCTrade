<?php
namespace app\components\helpers;

use yii\authclient\OAuth2;

class GreenCubesOAuth extends OAuth2
{
    public $authUrl = 'https://api.greencubes.org/oauth/authorize';
    public $tokenUrl = 'https://api.greencubes.org/oauth/access_token';
    public $apiBaseUrl = 'https://api.greencubes.org';

    public $scope = 'profile,email,regions';

    protected function initUserAttributes()
    {
        return $this->api('user', 'GET');
    }

    protected function apiInternal($accessToken, $url, $method, array $params, array $headers = null)
    {
        $params['access_token'] = $accessToken->getToken();
        $params['access_token'] = $params['access_token']['token'];

        return $this->sendRequest($method, $url, $params);
    }

    protected function defaultName()
    {
        return 'greencubes';
    }

    protected function defaultTitle()
    {
        return 'GreenCubes';
    }

    protected function defaultViewOptions()
    {
        return [
            'popupWidth' => 450,
            'popupHeight' => 500,
        ];
    }
}
