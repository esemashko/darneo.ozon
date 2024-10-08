<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Table\PriceListTable;
use Darneo\Ozon\Fields\Config\Price as ConfigPrice;
use Darneo\Ozon\Fields\Field;
use Darneo\Ozon\Fields\Value\Type\Date;
use Darneo\Ozon\Main\Helper\Cron;
use Darneo\Ozon\Main\Table\SettingsCronTable;

class OzonExportPriceCronComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    private static array $fieldsParams = [
        'SHOW' => [
            'IBLOCK',
            'IS_CRON',
        ]
    ];
    private array $fields;
    private array $fieldsSelect;
    private array $fieldsShow;
    private int $elementId;
    private int $iblockId = 0;
    private PriceListTable $manager;

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'list':
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'update':
                    $status = $this->update();
                    $this->setTemplateData();
                    $result = $this->getActionResult($status);
                    break;
                default:
                    $this->setTemplateData();
                    $this->includeComponentTemplate();
            }
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }

        return $result;
    }

    private function loadModules(): void
    {
        foreach (self::$moduleNames as $moduleName) {
            $moduleLoaded = Loader::includeModule($moduleName);
            if (!$moduleLoaded) {
                throw new LoaderException(
                    Loc::getMessage('DARNEO_OZON_MODULE_LOAD_ERROR', ['#MODULE_NAME#' => $moduleName])
                );
            }
        }
        if (!\Darneo\Ozon\Main\Helper\Access::isPermission()) {
            throw new LoaderException('Access is not allowed');
        }
    }

    private function dataManager(): void
    {
        $this->manager = new PriceListTable();
        $this->fields = (new ConfigPrice())->getFields();
        foreach (self::$fieldsParams['SHOW'] as $fieldName) {
            /** @var Field $field */
            $field = $this->fields[$fieldName];
            if ($field) {
                $this->fieldsShow[] = $fieldName;
            }
        }

        $this->fieldsSelect = $this->getFieldsSelect();
    }

    private function getFields($row): array
    {
        $result = [];

        foreach ($this->fieldsShow as $fieldName) {
            $field = $this->fields[$fieldName];
            /** @var Field $field */
            if ($field) {
                $data = [];
                $field->setValueFromDb($row);

                $data['CODE'] = $field->getInfo()->getName();
                $data['NAME'] = $field->getInfo()->getTitle();
                $data['SHOW'] = $this->getFieldShowHtml($field);

                if ($field->isEdit()) {
                    $data['EDIT']['VALUE'] = $this->getFieldEditHtml($field);
                    $data['EDIT']['HIDDEN'] = $this->getFieldHiddenValue($fieldName);
                }

                $result[$fieldName] = $data;
            }
        }

        return $result;
    }

    private function getFieldShowHtml($field): string
    {
        $value = $field->getValue()->get();

        return $value ? $field->getShowView()->getHtml() : '[---]';
    }

    private function getFieldEditHtml($field)
    {
        switch ($field->getInfo()->getName()) {
            default:
                if ($field->getValue() instanceof Date) {
                    $field->getValue()->setDateType('full');
                }
                $value = $field->getValue()->getRaw();
                break;
        }

        return $value;
    }

    private function getFieldHiddenValue($fieldName): string
    {
        $signer = new Signer();
        $params = array_merge($this->arParams, ['FIELD_NAME' => $fieldName]);
        $params = base64_encode(serialize($params));

        return $signer->sign($params, 'darneo.ozon.export.price.cron');
    }

    private function getFieldsSelect(): array
    {
        $select = [];
        /** @var Field $field */
        foreach ($this->fields as $fieldName => $field) {
            if (in_array($fieldName, $this->fieldsShow, true)) {
                $select[] = $field->getSelect();
            }
        }
        $select = array_merge(...$select);

        return array_unique($select);
    }

    private function setTemplateData(): void
    {
        $row = $this->getList();
        $lang = Cron::getLang('EXPORT_PRICE');

        $this->arResult['DATA_VUE'] = [
            'FIELDS' => $row,
            'ELEMENT_ID' => $this->elementId,
            'GENERAL_ACTIVE' => $this->getGeneralActive(),
            'SETTING_CRON_FOLDER' => $this->arParams['SETTING_CRON_FOLDER'],
            'SETTING_CRON_HELPER' => $lang ? $lang['DESCRIPTION'] : '',
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.price.cron'
        );
    }

    private function getGeneralActive(): bool
    {
        $settings = SettingsCronTable::getById('EXPORT_PRICE')->fetch();

        return $settings['VALUE'];
    }

    private function getList(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'ID' => $this->elementId
            ],
            'select' => $this->fieldsSelect,
            'order' => ['ID' => 'DESC']
        ];
        $result = PriceListTable::getList($parameters);
        if ($row = $result->fetch()) {
            $this->iblockId = $row['IBLOCK_ID'];
            $rows = $this->getFields($row);
        }

        return $rows;
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function update(): array
    {
        $status = [];
        $params = [];
        $result = null;

        /** @var Field $field */
        foreach ($this->fields as $field) {
            $fieldNameTable = $field->getInfo()->getName();
            $value = $this->request[$fieldNameTable];
            if ($value !== null) {
                $params[$fieldNameTable] = $value ?: '';
            }
        }

        if (!empty($params)) {
            $result = $this->manager::update($this->elementId, $params);
        }

        if ($result !== null) {
            if ($result->isSuccess()) {
                $status['STATUS'] = 'SUCCESS';
                $status['MESSAGE'] = Loc::getMessage('DARNEO_OZON_MODULE_PRICE_CRON_UPDATE_SUCCESS');
            } else {
                $status['STATUS'] = 'ERROR';
                $status['ERROR_LIST'] = $result->getErrorMessages();
            }
        } else {
            $status['STATUS'] = 'ERROR';
            $status['ERROR_LIST'] = [Loc::getMessage('DARNEO_OZON_MODULE_PRICE_CRON_UPDATE_ERROR')];
        }

        return $status;
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->elementId = $arParams['ELEMENT_ID'];
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
