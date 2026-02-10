<?php

namespace AwardWallet\MainBundle\Timeline\Item;

interface CoupleInterface
{
    public function setConnection(?ItineraryInterface $connection);

    public function getConnection(): ?ItineraryInterface;
}
