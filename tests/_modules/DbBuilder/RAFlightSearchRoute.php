<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;

class RAFlightSearchRoute extends AbstractDbEntity
{
    private ?RAFlightSearchQuery $raFlightSearchQuery;

    /**
     * @var RAFlightSearchRouteSegment[]
     */
    private array $segments;

    public function __construct(
        string $depCode,
        string $arrCode,
        ?RAFlightSearchQuery $raFlightSearchQuery = null,
        array $segments = [],
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'TotalDistance' => 2002,
            'Parser' => 'test',
            'ApiRequestID' => StringHandler::getRandomCode(10),
        ], $fields, [
            'DepCode' => $depCode,
            'ArrCode' => $arrCode,
        ]));

        $this->raFlightSearchQuery = $raFlightSearchQuery;
        $this->segments = $segments;
    }

    public function getRAFlightSearchQuery(): ?RAFlightSearchQuery
    {
        return $this->raFlightSearchQuery;
    }

    public function setRAFlightSearchQuery(?RAFlightSearchQuery $raFlightSearchQuery): self
    {
        $this->raFlightSearchQuery = $raFlightSearchQuery;

        return $this;
    }

    /**
     * @return RAFlightSearchRouteSegment[]
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @param RAFlightSearchRouteSegment[] $segments
     */
    public function setSegments(array $segments): self
    {
        $this->segments = $segments;

        return $this;
    }
}
