<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class FlightStats extends AbstractDbEntity
{
    public function getPrimaryKey(): array
    {
        return [
            'DepCode',
            'DepDate',
            'ArrCode',
            'ArrDate',
            'FlightNumber',
        ];
    }
}
