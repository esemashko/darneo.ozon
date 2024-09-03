<?php

namespace Darneo\Ozon\Order\Fbs\Cron;

use Bitrix\Main\Type;
use Darneo\Ozon\Order\Fbs\Import;
use Darneo\Ozon\Order\Table\FbsListTable;

class Manager
{
    public function start(): void
    {
        if ($this->isEmptyTable()) {
            $since = (new Type\DateTime())->add('-11 months')->format('Y-m-d\TH:i:s\Z');
        } else {
            $since = (new Type\DateTime())->add('-3 months')->format('Y-m-d\TH:i:s\Z');
        }

        $filter = [
            'since' => $since,
            'to' => (new Type\DateTime())->format('Y-m-d\TH:i:s.u\Z'),
        ];

        $page = 0;
        $limit = 1000;

        $manager = new Import();

        nextPost:
        $isFinish = !$manager->initData($page, $limit, $filter);

        if (!$isFinish) {
            $page++;
            goto nextPost;
        }
    }

    private function isEmptyTable(): bool
    {
        return FbsListTable::getCount() === 0;
    }
}
