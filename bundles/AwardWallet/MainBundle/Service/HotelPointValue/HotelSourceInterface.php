<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

interface HotelSourceInterface
{
    /**
     * @return HotelFinderResult[]
     */
    public function searchByLatLng(float $lat, float $lng): array;
}
