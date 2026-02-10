<?php

namespace AwardWallet\MainBundle\Service\RA\Hotel\DTO;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

class Hotel implements \JsonSerializable
{
    use FilterNull;

    public string $key;

    public string $name;

    public string $checkInDate;

    public string $checkOutDate;

    public ?string $description;

    public int $numberOfNights;

    public int $pointsPerNight;

    public string $pointsPerNightFormatted;

    public ?float $cashPerNight;

    public ?string $cashPerNightFormatted;

    public string $address;

    public string $thumb;

    public ?float $rating;

    public ?string $ratingFormatted;

    public ?float $distance;

    public ?string $distanceFormatted;

    public string $providercode;
}
