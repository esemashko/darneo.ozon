# 🚀 Darneo.Ozon

**🛒 OZON интеграция: товары, цены, остатки**  
Модуль для интернет-магазинов на 1C-Битрикс.

## 📄 Описание

Модуль Darneo.Ozon предназначен для интеграции интернет-магазинов на Битрикс с маркетплейсом OZON. Решение поддерживает работу с несколькими личными кабинетами на OZON, а также позволяет настраивать выгрузку данных из любых инфоблоков торгового каталога, включая поддержку торговых предложений.

## ⚙️ Возможности

### 📦 Выгрузка товаров
- Автоматическая и ручная выгрузка товаров из вашего интернет-магазина на OZON.
- Поддержка торговых предложений.
- Сопоставление характеристик и категорий товаров.

![Ссылка на изображение настроек](https://github.com/esemashko/darneo.ozon/blob/main/images/preview-1.png)

### 💰 Выгрузка цен
- Выгрузка цен с учетом скидок.
- Возможность автоматической или ручной синхронизации цен.

### 🏷️ Выгрузка остатков
- Выбор склада для выгрузки остатков.
- Поддержка ручной и автоматической выгрузки остатков товаров.

![Ссылка на изображение настроек](https://github.com/esemashko/darneo.ozon/blob/main/images/preview-2.png)

## 🛠️ Установка модуля

1. Скачайте и установите модуль `darneo.ozon` в папку `/bitrix/modules/`.
2. Запустите мастер установки публичной части решения и выберите отдельный сайт для установки (например, `/ozon/`).
3. Получите API ключ интеграции и укажите его в настройках `/ozon/settings/key/` (установите ключу настройку "Базовый").
4. Обновите список ваших складов в разделе `/ozon/settings/stock/`.
5. Настройте выгрузку товаров: `/ozon/export/product/`.
6. Настройте выгрузку цен: `/ozon/export/price/`.
7. Настройте выгрузку остатков: `/ozon/export/stock/`.
8. Настройте выполнение скриптов на cron для автоматизации процессов: `/ozon/settings/cron/`.

## 👨‍💻 Инструкция для разработчиков

Модуль предоставляет возможность модификации данных перед отправкой на OZON с помощью событий. Для этого нужно добавить обработчики событий в файле `/local/php_interface/init.php`:

```php
AddEventHandler('darneo.ozon', 'onExportProduct', ['MyClass', 'onExportProduct']);
AddEventHandler('darneo.ozon', 'onExportPrice', ['MyClass', 'onExportPrice']);
AddEventHandler('darneo.ozon', 'onExportStock', ['MyClass', 'onExportStock']);

class MyClass
{
    // экспорт товара на OZON
    public static function onExportProduct(int $elementId, array $dataItem): array
    {
        // your code
        return $dataItem;
    }

    // экспорт цены на OZON
    public static function onExportPrice(int $elementId, array $dataItem): array
    {
        // your code
        return $dataItem;
    }

    // экспорт остатка на OZON
    public static function onExportStock(int $elementId, array $dataItem): array
    {
        // your code
        return $dataItem;
    }
}
```

## 📋 Технические требования

- PHP 8.1

## 📝 Лицензия

Этот проект лицензирован на условиях [MIT License](LICENSE).

## 📧 Обратная связь

Если у вас возникли вопросы, предложения или вы нашли баги, пожалуйста, создайте новый [Issue](../../issues) в этом репозитории. Мы ценим вашу обратную связь и постараемся ответить как можно скорее.
