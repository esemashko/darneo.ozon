<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Darneo\Ozon\Export\Stock\Manager as ExportStockManager;
use Darneo\Ozon\Export\Table\StockListTable;
use Darneo\Ozon\Export\Table\StockLogTable;

class OzonExportStockExchangeComponent extends CBitrixComponent
{
    private const TRIGGER_TMP = 'TMP';
    private const TRIGGER_MAIN = 'MAIN';
    private static array $moduleNames = ['darneo.ozon'];
    protected int $limit = 100;
    protected int $page = 0;
    protected int $totalCount = 0;
    private ExportStockManager $manager;
    private int $elementId;
    private array $settings;
    private bool $isStart = false;
    private string $trigger = '';

    public function executeComponent(): array
    {
        $result = [];
        try {
            $this->loadModules();
            $this->dataManager();
            switch ($this->arParams['ACTION']) {
                case 'start':
                    $this->isStart = true;
                    $this->page = $this->request['page'] ?: $this->page;
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
        $this->trigger = $this->request['trigger'] ?: self::TRIGGER_TMP;
        $this->manager = new ExportStockManager($this->elementId);
        switch ($this->trigger) {
            case self::TRIGGER_MAIN:
                $this->totalCount = $this->manager->getDataTmpCount();
                break;
            default:
                $this->totalCount = $this->manager->getDataIblockCount();
                break;
        }
        $this->settings = StockListTable::getById($this->elementId)->fetch();
    }

    private function setTemplateData(): void
    {
        $countAll = number_format($this->totalCount, 0, '.', ' ');
        $countCurrent = number_format($this->page * $this->limit, 0, '.', ' ');

        if ($this->isStart) {
            $this->initSend();
        }

        $isFinish = $this->isFinish();
        if ($isFinish) {
            $this->page = 0;
        }

        $this->arResult['DATA_VUE'] = [
            'COUNT_HELPER' => Loc::getMessage('DARNEO_OZON_MODULE_STOCK_EXCHANGE_COUNT_HELPER_' . $this->trigger),
            'STATUS_HELPER' => Loc::getMessage('DARNEO_OZON_MODULE_STOCK_EXCHANGE_STATUS_HELPER_' . $this->trigger),
            'PAGE' => $this->page,
            'COUNT_ALL' => $this->totalCount,
            'COUNT_ALL_FORMATED' => $countAll,
            'COUNT_CURRENT' => $this->page * $this->limit,
            'COUNT_CURRENT_FORMATED' => $countCurrent,
            'FINISHED' => $isFinish,
            'TRIGGER' => $this->trigger,
            'DISABLE_OPTIMISATION' => (bool)$this->settings['DISABLE_OPTIMISATION']
        ];

        $this->arResult['PATH_TO_AJAX'] = $this->getPath() . '/ajax.php';
        $this->arResult['SIGNED_PARAMS'] = (new Signer())->sign(
            base64_encode(serialize($this->arParams)),
            'darneo.ozon.export.stock.exchange'
        );
    }

    private function initSend(): void
    {
        switch ($this->trigger) {
            case self::TRIGGER_TMP:
                $this->manager->initDataTmp($this->page, $this->limit);
                break;
            case self::TRIGGER_MAIN:
                $data = $this->manager->getDataOzon($this->page, $this->limit);
                $rowLog = [];
                foreach ($data as $elementId => $item) {
                    $result = StockLogTable::add(
                        [
                            'STOCK_ID' => $this->elementId,
                            'ELEMENT_ID' => $elementId,
                            'OFFER_ID' => $item['offer_id'],
                            'SEND_JSON' => $item,
                            'ANSWER' => [],
                        ]
                    );
                    $rowLog[$item['offer_id']] = $result->getId();
                }
                $data = array_values($data);
                $answer = (new \Darneo\Ozon\Api\v2\Product())->stocks($data);
                if ($answer['result']) {
                    foreach ($answer['result'] as $datum) {
                        $rowLogId = $rowLog[$datum['offer_id']];
                        StockLogTable::update(
                            $rowLogId,
                            [
                                'ANSWER' => $datum,
                                'IS_ERROR' => count($datum['errors']),
                                'SYSTEM_LOG_ID' => $answer['__system_log_id'] ?: 0
                            ]
                        );
                    }
                } else {
                    foreach ($rowLog as $rowLogId) {
                        StockLogTable::update(
                            $rowLogId,
                            [
                                'ANSWER' => $answer,
                                'IS_ERROR' => true,
                                'SYSTEM_LOG_ID' => $answer['__system_log_id'] ?: 0
                            ]
                        );
                    }
                }
                sleep(1);
                break;
        }
    }

    private function isFinish(): bool
    {
        if ($this->isStart) {
            return $this->page * $this->limit >= $this->totalCount;
        }
        return false;
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
