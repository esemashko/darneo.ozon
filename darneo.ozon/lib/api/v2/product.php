<?php

namespace Darneo\Ozon\Api\v2;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;
use Darneo\Ozon\Main\Helper\MethodTracker;

class Product extends Base
{
    public function info(int $productId): array
    {
        $uri = new Uri(Config::HOST . '/v2/product/info');
        $dataPost = [
            'product_id' => $productId
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }

    public function list(string $lastId = ''): array
    {
        $uri = new Uri(Config::HOST . '/v2/product/list');
        $dataPost = [
            'limit' => 1000,
            'last_id' => $lastId,
            'filter' => [
                'visibility' => 'ALL'
            ]
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        $method = MethodTracker::internalMethod();
        $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
        $result = array_merge($result, ['__system_log_id' => $logId]);

        return $result;
    }

    public function stocks(array $data): array
    {
        if ($this->isTest()) {
            $result = [Loc::getMessage('DARNEO_OZON_API_IS_TEST_MESSAGE')];
        } else {
            $uri = new Uri(Config::HOST . '/v2/products/stocks');
            $dataPost = [
                'stocks' => $data
            ];
            $encode = Json::encode($dataPost);
            $result = $this->httpClient->post($uri->getUri(), $encode);
            $result = Json::decode($result);

            $method = MethodTracker::internalMethod();
            $logId = $this->writeToLog($uri->getUri(), $dataPost, $result, $method);
            $result = array_merge($result, ['__system_log_id' => $logId]);
        }

        return $result;
    }
}
