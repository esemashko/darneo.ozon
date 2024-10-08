<?php
/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var SaleOrderAjax $component
 * @var string $templateFolder
 */

const STOP_STATISTICS = true;
const NO_KEEP_STATISTIC = 'Y';
const NO_AGENT_STATISTIC = 'Y';
const DisableEventsCheck = true;
const BX_SECURITY_SHOW_MESSAGE = true;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\PostDecodeFilter;

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new PostDecodeFilter());

$signer = new Signer();
try {
    $params = $signer->unsign($request->get('signedParamsString'), 'darneo.ozon.export.price.log');
    $serialize = base64_decode($params);
    if (CheckSerializedData($serialize)) {
        $params = unserialize($serialize, ['allowed_classes' => false]);
    } else {
        throw new SystemException('Error Serialized');
    }
} catch (BadSignatureException $e) {
    die();
} catch (ArgumentTypeException $e) {
    die();
} catch (SystemException $e) {
    die();
}

switch ($request->get('action')) {
    case 'list':
        $params['ACTION'] = 'list';
        break;
    case 'clear':
        $params['ACTION'] = 'clear';
        break;
    case 'setSettingLog':
        $params['ACTION'] = 'setSettingLog';
        break;
    default:
        exit();
}

$result = $APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.price.log',
    '',
    $params
);

$APPLICATION->RestartBuffer();

echo Json::encode($result);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
