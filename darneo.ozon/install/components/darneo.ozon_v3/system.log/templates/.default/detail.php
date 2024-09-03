<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Bitrix vars
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @var $component
 */

$elementId = $arResult['VARIABLES']['ELEMENT_ID'];
$folder = $arResult['SEF_FOLDER'];
?>

<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:system.log.detail',
    '',
    [
        'SEF_FOLDER' => $folder,
        'ELEMENT_ID' => $elementId,
    ],
    false
);
?>
