<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\PersistentCollection;

interface LocationContainerInterface
{
    /**
     * @return PersistentCollection|Location[]
     */
    public function getLocations();

    /**
     * @param PersistentCollection|Location[] $locations
     * @return $this
     */
    public function setLocations($locations);

    /**
     * @return $this
     */
    public function addLocation(Location $location);

    /**
     * @return $this
     */
    public function removeLocation(Location $location);

    public function getLastStoreLocationUpdateDate(): ?\DateTime;

    /**
     * @return $this
     */
    public function setLastStoreLocationUpdateDate(?\DateTime $lastStoreLocationUpdateDate);
}
