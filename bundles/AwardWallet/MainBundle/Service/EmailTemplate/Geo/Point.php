<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\Geo;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Point
{
    private string $longitude;
    private string $latitude;

    public function __construct(string $latitude, string $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public static function fromAirCode(Aircode $aircode): self
    {
        return new self(
            (string) $aircode->getLat(),
            (string) $aircode->getLng()
        );
    }
}
