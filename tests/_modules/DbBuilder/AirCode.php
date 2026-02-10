<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class AirCode extends AbstractDbEntity
{
    /**
     * @param string $airCode код аэропорта (IATA)
     * @param string $cityCode код города, к которому относится аэропорт
     * @param string $cityName название города
     * @param float $lat географическая широта
     * @param float $lng географическая долгота
     * @param array $fields массив дополнительных полей
     */
    public function __construct(
        string $airCode,
        string $cityCode,
        string $cityName,
        float $lat,
        float $lng,
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'AirCode' => $airCode,
            'CityCode' => $cityCode,
            'CityName' => $cityName,
            'Lat' => $lat,
            'Lng' => $lng,
        ], $fields));
    }
}
