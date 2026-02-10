<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class MileValue extends AbstractDbEntity
{
    private ?Provider $provider;

    private ?Trip $trip;

    public function __construct(
        ?Provider $provider = null,
        ?Trip $trip = null,
        array $fields = []
    ) {
        parent::__construct(array_merge([
            'CreateDate' => date('Y-m-d H:i:s'),
            'UpdateDate' => date('Y-m-d H:i:s'),
            'Hash' => 'hash',
        ], $fields));

        $this->provider = $provider;
        $this->trip = $trip;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(?Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): self
    {
        $this->trip = $trip;

        return $this;
    }
}
