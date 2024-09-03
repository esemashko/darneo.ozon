<?php

namespace Darneo\Ozon\Export\Table;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\Type;
use Darneo\Ozon\EventHandlers;

class StockCronTable extends Data\DataManager
{
    public static string $tablePrefix = '';

    public static function getTableName(): string
    {
        if (self::$tablePrefix) {
            return 'darneo_ozon_export_stock_cron_' . self::$tablePrefix;
        }
        return 'darneo_ozon_export_stock_cron';
    }

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
        if ($tablePrefix) {
            $connection = Application::getConnection();
            $tableName = 'darneo_ozon_export_stock_cron_' . self::$tablePrefix;
            if (!$connection->isTableExists($tableName)) {
                self::getEntity()->createDbTable();
            }
        }
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_CRON_ID')
            ]),

            new Fields\DatetimeField(
                'DATE_CREATED',
                [
                    'default_value' => new Type\DateTime(),
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_CRON_DATE_CREATED')
                ]
            ),
            new Fields\DatetimeField(
                'DATE_FINISHED',
                [
                    'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_CRON_DATE_FINISHED')
                ]
            ),
            new Fields\IntegerField('STOCK_ID', [
                'validation' => static function () {
                    return [
                        new Validators\ForeignValidator(StockListTable::getEntity()->getField('ID'))
                    ];
                },
                'required' => true,
                'title' => Loc::getMessage('DARNEO_OZON_EXPORT_TABLE_STOCK_CRON_STOCK_ID')
            ]),
        ];
    }
}
