<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('�������');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:export.stock',
    '',
    [
        'COMPONENT_TEMPLATE' => '',
        'SEF_URL_TEMPLATES' => [
            'list' => '',
            'detail' => 'detail/#ELEMENT_ID#/',
            'export' => 'export/#ELEMENT_ID#/',
            'cron' => 'cron/#ELEMENT_ID#/',
        ],
        'SEF_FOLDER' => '#SITE_DIR#export/stock/',
        'SETTING_CRON_FOLDER' => '#SITE_DIR#settings/cron/',
        'SYSTEM_LOG_FOLDER' => '#SITE_DIR#system/log/',
        'SEF_MODE' => 'Y'
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>