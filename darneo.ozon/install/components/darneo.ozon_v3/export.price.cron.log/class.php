<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\TYPE\DateTime;
use Darneo\Ozon\Export\Table\PriceCronTable;

class OzonExportPriceCronLogComponent extends CBitrixComponent
{
    private static array $moduleNames = ['darneo.ozon'];
    protected int $limit = 50;
    protected int $page = 1;
    protected int $totalCount = 0;
    protected string $filterSearch;
    private int $elementId;

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
    }

    private function setTemplateData(): void
    {
        $elements = $this->getLog();
        $isPageStop = $this->page * $this->limit >= $this->totalCount;

        $this->arResult['DATA_VUE'] = [
            'LIST' => $elements,
            'PAGE' => $this->page,
            'FINAL_PAGE' => $isPageStop,
            'FILTER' => [
                'SEARCH' => $this->filterSearch
            ]
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.price.cron.log'
        );
    }

    private function getLog(): array
    {
        $rows = [];
        $parameters = [
            'filter' => [
                'PRICE_ID' => $this->elementId
            ],
            'select' => [
                'ID',
                'DATE_CREATED',
                'DATE_FINISHED',
            ],
            'order' => ['ID' => 'DESC'],
            'limit' => $this->page * $this->limit
        ];
        if ($this->filterSearch) {
            $parameters['filter']['ID'] = $this->filterSearch;
        }
        $result = PriceCronTable::getList($parameters);
        while ($row = $result->fetch()) {
            if ($row['DATE_CREATED'] instanceof DateTime) {
                $row['DATE_CREATED'] = $row['DATE_CREATED']->toString();
            }
            if ($row['DATE_FINISHED'] instanceof DateTime) {
                $row['DATE_FINISHED'] = $row['DATE_FINISHED']->toString();
            }
            $rows[] = $row;
        }

        $this->initElementCountAll($parameters);

        return $rows;
    }

    private function initElementCountAll(array $parameters): void
    {
        unset($parameters['limit']);
        $result = PriceCronTable::getList($parameters);
        $this->totalCount = $result->getSelectedRowsCount();
    }

    private function getActionResult(array $status): array
    {
        $result = [
            'DATA_VUE' => $this->arResult['DATA_VUE']
        ];

        return array_merge($status, $result);
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->elementId = $arParams['ELEMENT_ID'];
        $this->arParams = $arParams;

        return $this->arParams;
    }
}
