<?php

const NEED_AUTH = true;
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('������� ����');
?>
<?php
$APPLICATION->IncludeComponent(
    'darneo.ozon_v3:dashboard.sale',
    '',
    [
        'SETTING_CRON_FOLDER' => '#SITE_DIR#settings/cron/',
    ]
);
?>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>