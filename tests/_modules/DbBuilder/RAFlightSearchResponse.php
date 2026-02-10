<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class RAFlightSearchResponse extends AbstractDbEntity
{
    private ?RAFlightSearchRequest $raFlightSearchRequest;

    private ?RAFlightSearchRoute $raFlightSearchRoute;

    public function __construct(
        ?RAFlightSearchRequest $raFlightSearchRequest = null,
        ?RAFlightSearchRoute $raFlightSearchRoute = null,
        array $fields = []
    ) {
        parent::__construct($fields);

        $this->raFlightSearchRequest = $raFlightSearchRequest;
        $this->raFlightSearchRoute = $raFlightSearchRoute;
    }

    public function getRAFlightSearchRequest(): ?RAFlightSearchRequest
    {
        return $this->raFlightSearchRequest;
    }

    public function setRAFlightSearchRequest(?RAFlightSearchRequest $raFlightSearchRequest): self
    {
        $this->raFlightSearchRequest = $raFlightSearchRequest;

        return $this;
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

    public function getPrimaryKey(): array
    {
        return ['RAFlightSearchRequestID', 'RAFlightSearchRouteID'];
    }
}
