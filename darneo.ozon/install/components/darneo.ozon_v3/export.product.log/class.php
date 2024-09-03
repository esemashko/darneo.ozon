<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\TYPE\DateTime;
use Darneo\Ozon\Export\Product\Cron\Manager;
use Darneo\Ozon\Export\Table\ProductListTable;
use Darneo\Ozon\Export\Table\ProductLogTable;
use Darneo\Ozon\Import\Table\TreeTypeTable;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Darneo\Ozon\Main\Table\ApiLogTable;
use Darneo\Ozon\Main\Table\SettingsTable;

class OzonExportProductLogComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    protected int $limit = 50;
    protected int $page = 1;
    protected int $totalCount = 0;
    protected string $filterSearch;
    private int $elementId;
    private array $settings;

    private array $systemLogIds = [];

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
                case 'import':
                    (new Manager())->importStatus();
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'clear':
                    $this->deleteData();
                    $this->setTemplateData();
                    $result = $this->getActionResult(['STATUS' => 'SUCCESS']);
                    break;
                case 'setSettingLog':
                    $count = $this->request['count'] ?: 1;
                    $status = $this->setSettingLog($count);
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
        $this->filterSearch = $this->request['filterSearch'] ?: '';
        $this->page = (int)$this->request['page'] > 0 ? (int)$this->request['page'] : 1;
        $this->settings = ProductListTable::getById($this->elementId)->fetch();
    }

    private function setTemplateData(): void
    {
        $elements = $this->getLog();

        $existLodIds = $this->getExistLog();
        foreach ($elements as $key => $row) {
            if (in_array($row['SYSTEM_LOG_ID'], $existLodIds, true)) {
                $elements[$key]['SYSTEM_LOG_LINK'] = $this->generateDetailUrl($row['SYSTEM_LOG_ID']);
            } else {
                $elements[$key]['SYSTEM_LOG_LINK'] = '';
            }
        }

        $isPageStop = $this->page * $this->limit >= $this->totalCount;

        $this->arResult['DATA_VUE'] = [
            'LIST' => $elements,
            'PAGE' => $this->page,
            'COUNT_ALL' => number_format($this->totalCount, 0, '.', ' '),
            'LOG_SAVE' => HelperSettings::getLogRetentionDays(),
            'FINAL_PAGE' => $isPageStop,
            'FILTER' => [
                'SEARCH' => $this->filterSearch
            ]
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.product.log'
        );
    }

    private function getLog(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'PRODUCT_ID' => $this->elementId
            ],
            'select' => [
                'ID',
                'DATE_CREATED',
                'PRODUCT_ID',
                'ELEMENT_ID',
                'OFFER_ID',
                'SEND_JSON',
                'ANSWER',
                'ANSWER_JSON',
                'IS_ERROR',
                'IS_FINISH',
                'SYSTEM_LOG_ID',
                'ELEMENT_NAME' => 'ELEMENT.NAME',
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => $this->page * $this->limit
        ];
        if ($this->filterSearch) {
            $parameters['filter']['ID'] = $this->filterSearch;
        }
        $result = ProductLogTable::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['IS_FINISH']) {
                $row['STATUS'] = $row['IS_ERROR'] ? 'Error' : 'OK';
            } else {
                $row['STATUS'] = '';
            }
            if ($row['DATE_CREATED'] instanceof DateTime) {
                $row['DATE_CREATED'] = $row['DATE_CREATED']->toString();
            }

            $sectionOzonId = $row['SEND_JSON']['description_category_id'];
            $row['ELEMENT_SECTION'] = $sectionOzonId > 0 ? $this->getSectionOzon($sectionOzonId) : '';

            $element = ElementTable::getList(
                [
                    'filter' => [
                        'ID' => $row['ELEMENT_ID']
                    ],
                    'select' => [
                        'IBLOCK_ID',
                        'IBLOCK_TYPE_ID' => 'IBLOCK.IBLOCK_TYPE_ID'
                    ],
                    'cache' => ['ttl' => 86400, 'cache_joins' => true]
                ]
            )->fetch();
            $row['ELEMENT_LINK'] = Loc::getMessage('DARNEO_OZON_PRODUCT_LOG_ELEMENT_LINK', [
                '#IBLOCK_ID#' => $element['IBLOCK_ID'],
                '#IBLOCK_TYPE_ID#' => $element['IBLOCK_TYPE_ID'],
                '#ID#' => $row['ELEMENT_ID']
            ]);

            $this->systemLogIds[] = $row['SYSTEM_LOG_ID'];

            $rows[] = $row;
        }

        $this->initElementCountAll($parameters);

        return $rows;
    }

    private function getSectionOzon(int $sectionOzonId): string
    {
        $parameters = [
            'filter' => [
                'TYPE_ID' => $sectionOzonId
            ],
            'select' => ['TYPE_NAME'],
            'cache' => ['ttl' => 86400]
        ];
        $result = TreeTypeTable::getList($parameters);

        if ($row = $result->fetch()) {
            return $row['TYPE_NAME'] ?: '';
        }

        return '';
    }

    private function initElementCountAll(array $parameters): void
    {
        unset($parameters['limit']);
        $result = ProductLogTable::getList($parameters);
        $this->totalCount = $result->getSelectedRowsCount();
    }

    private function getExistLog(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'ID' => $this->systemLogIds
            ],
            'select' => [
                'ID'
            ],
        ];

        $result = ApiLogTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['ID'];
        }

        return $rows;
    }

    private function generateDetailUrl(int $elementId): string
    {
        return $this->arParams['SYSTEM_LOG_FOLDER'] . $elementId . '/';
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    private function deleteData(): void
    {
        $result = ProductLogTable::getList(['filter' => ['PRODUCT_ID' => $this->elementId], 'select' => ['ID']]);
        while ($row = $result->fetch()) {
            ProductLogTable::delete($row['ID']);
        }
    }

    private function setSettingLog(int $count): array
    {
        if ($count > 0 && $count <= 365) {
            $result = SettingsTable::update('CRON', ['VALUE' => $count]);
            if ($result->isSuccess()) {
                return ['STATUS' => 'SUCCESS'];
            }
            return ['STATUS' => 'ERROR', 'ERROR_LIST' => $result->getErrorMessages()];
        }

        return ['STATUS' => 'ERROR', 'ERROR_LIST' => ['Error count']];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->elementId = $arParams['ELEMENT_ID'];
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
