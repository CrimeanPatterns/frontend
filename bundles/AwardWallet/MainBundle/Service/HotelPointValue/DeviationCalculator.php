<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use Doctrine\DBAL\Connection;

class DeviationCalculator
{
    private Connection $connection;
    private \Memcached $memcached;

    public function __construct(Connection $connection, \Memcached $memcached)
    {
        $this->connection = $connection;
        $this->memcached = $memcached;
    }

    public function calcDeviationParams(int $hotelBrandId): DeviationCalculatorResult
    {
        $cacheKey = "hpv_dvtn_v1_" . $hotelBrandId;

        $result = $this->memcached->get($cacheKey);

        if ($result !== false) {
            return $result;
        }

        $row = $this->connection->executeQuery("select 
            ROUND(STD(PointValue), 6) as Deviation,
            ROUND(AVG(PointValue), 6) as Average,
            COUNT(*) as BasedOnRecords
        from
            HotelPointValue
        where 
            BrandID = ?
            and CreateDate >= adddate(now(), interval -18 month)
            and Status not in('" . implode("', '", CalcMileValueCommand::EXCLUDED_STATUSES) . "')", [$hotelBrandId])->fetchAssociative();

        $result = new DeviationCalculatorResult(...array_values($row));

        $this->memcached->set($cacheKey, $result, 300);

        return $result;
    }
}
