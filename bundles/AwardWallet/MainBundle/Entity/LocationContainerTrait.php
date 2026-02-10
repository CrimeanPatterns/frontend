<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @property Location[]|Collection $locations
 */
trait LocationContainerTrait
{
    /**
     * @var ?\DateTime
     * @ORM\Column(name="LastStoreLocationUpdateDate", type="datetime", nullable=true)
     */
    protected $lastStoreLocationUpdateDate;

    /**
     * @param Location[]|Collection $locations
     * @return $this
     */
    public function setLocations($locations)
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * @return Location[]|Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @return $this
     */
    public function addLocation(Location $location)
    {
        $this->locations[] = $location;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeLocation(Location $location)
    {
        $this->locations->removeElement($location);

        return $this;
    }

    public function getLastStoreLocationUpdateDate(): ?\DateTime
    {
        return $this->lastStoreLocationUpdateDate;
    }

    /**
     * @return $this
     */
    public function setLastStoreLocationUpdateDate(?\DateTime $lastStoreLocationUpdateDate)
    {
        $this->lastStoreLocationUpdateDate = $lastStoreLocationUpdateDate;

        return $this;
    }
}
