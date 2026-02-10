<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class RAFlightRouteSearchVolume extends AbstractDbEntity
{
    private ?Provider $provider = null;

    public function __construct(
        string $depCode,
        string $arrCode,
        int $saved = 0,
        int $excluded = 0,
        array $fields = []
    ) {
        parent::__construct(array_merge(
            [
                'ClassOfService' => 'Economy',
                'TimesSearched' => $saved + $excluded + random_int(0, 10),
                'LastSearch' => date('Y-m-d H:i:s'),
            ],
            $fields,
            [
                'DepartureAirport' => $depCode,
                'ArrivalAirport' => $arrCode,
                'Saved' => $saved,
                'Excluded' => $excluded,
            ]
        ));
    }

    public function setProvider(Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public static function create(
        string $depCode,
        string $arrCode,
        int $saved = 0,
        int $excluded = 0,
        array $fields = []
    ): self {
        return new self($depCode, $arrCode, $saved, $excluded, $fields);
    }
}
