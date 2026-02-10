<?php

namespace AwardWallet\MainBundle\Timeline\Item;

trait CoupleTrait
{
    protected ?ItineraryInterface $connection = null;

    public function getConnection(): ?ItineraryInterface
    {
        return $this->connection;
    }

    public function setConnection(?ItineraryInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }
}
