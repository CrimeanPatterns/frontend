<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class RAFlightSearchRouteSegment extends AbstractDbEntity
{
    private ?RAFlightSearchRoute $raFlightSearchRoute;

    public function __construct(
        \DateTime $depDate,
        string $depCode,
        \DateTime $arrDate,
        string $arrCode,
        ?RAFlightSearchRoute $raFlightSearchRoute = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'DepDate' => $depDate->format('Y-m-d H:i:s'),
            'DepCode' => $depCode,
            'ArrDate' => $arrDate->format('Y-m-d H:i:s'),
            'ArrCode' => $arrCode,
        ]));

        $this->raFlightSearchRoute = $raFlightSearchRoute;
    }

    public function getRAFlightSearchRoute(): ?RAFlightSearchRoute
    {
        return $this->raFlightSearchRoute;
    }

    public function setRAFlightSearchRoute(?RAFlightSearchRoute $raFlightSearchRoute): self
    {
        $this->raFlightSearchRoute = $raFlightSearchRoute;

        return $this;
    }
}
