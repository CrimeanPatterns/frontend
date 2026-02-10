<?php

namespace AwardWallet\MainBundle\Entity\Geo\Adapters;

use AwardWallet\MainBundle\Entity\Geo\OptionalGeoDataAwareInterface;
use AwardWallet\MainBundle\Entity\UserIP;

class UserIPAdapter implements OptionalGeoDataAwareInterface
{
    private UserIP $userIp;

    public function __construct(UserIP $userIp)
    {
        $this->userIp = $userIp;
    }

    public function getLat(): ?float
    {
        return $this->userIp->getLat();
    }

    /**
     * @param float|string|null $lat
     */
    public function setLat($lat): self
    {
        $this->userIp->setLat($lat);

        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getLng()
    {
        return $this->userIp->getLng();
    }

    /**
     * @param float|string|null $lng
     */
    public function setLng($lng): self
    {
        $this->userIp->setLng($lng);

        return $this;
    }

    public function isPointSet(): bool
    {
        return $this->userIp->isPointSet();
    }

    public function setIsPointSet(bool $isPointSet): self
    {
        $this->userIp->setIsPointSet($isPointSet);

        return $this;
    }
}
