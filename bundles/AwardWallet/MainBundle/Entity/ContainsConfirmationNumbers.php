<?php

namespace AwardWallet\MainBundle\Entity;

interface ContainsConfirmationNumbers
{
    /**
     * If Itinerary has segments, then individual confirmation numbers of those segments would not be included.
     */
    public function getAllConfirmationNumbers(): array;

    public function getConfirmationNumber(): ?string;
}
