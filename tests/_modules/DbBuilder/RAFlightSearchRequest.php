<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

use AwardWallet\MainBundle\Globals\StringHandler;

class RAFlightSearchRequest extends AbstractDbEntity
{
    private ?RAFlightSearchQuery $raFlightSearchQuery;

    public function __construct(
        \DateTime $requestDate,
        ?\DateTime $responseDate = null,
        ?string $requestId = null,
        ?RAFlightSearchQuery $raFlightSearchQuery = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'RAFlightSearchRequestID' => is_null($requestId) ? StringHandler::getRandomCode(10) : $requestId,
            'RequestDate' => $requestDate->format('Y-m-d H:i:s'),
            'ResponseDate' => is_null($responseDate) ? null : $responseDate->format('Y-m-d H:i:s'),
        ]));

        $this->raFlightSearchQuery = $raFlightSearchQuery;
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
}
