<?php

namespace AwardWallet\MainBundle\Entity\Geo;

interface OptionalGeoDataAwareInterface
{
    /**
     * @return float|string|null
     */
    public function getLat();

    /**
     * @param float|string|null $lat
     */
    public function setLat($lat): self;

    /**
     * @return float|string|null
     */
    public function getLng();

    /**
     * @param float|string|null $lng
     */
    public function setLng($lng): self;

    public function isPointSet(): bool;

    public function setIsPointSet(bool $isPointSet): self;
}
