<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

class HotelNameFilter
{
    public function filter(string $name): string
    {
        // AC Hotel by Marriott Marseille Prado Velodrome -> AC Hotel Marseille Prado Velodrome
        return str_ireplace(" by marriott", "", $name);
    }
}
