<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\PersistentCollection;

interface CustomLoyaltyPropertyContainerInterface
{
    /**
     * @return CustomLoyaltyProperty[]|PersistentCollection
     */
    public function setCustomLoyaltyProperties($customLoyaltyProperties);

    /**
     * @return CustomLoyaltyProperty[]|PersistentCollection
     */
    public function &getCustomLoyaltyProperties();

    /**
     * @return $this
     */
    public function addCustomLoyaltyProperty(CustomLoyaltyProperty $customLoyaltyProperty);

    /**
     * @return $this
     */
    public function removeCustomLoyaltyProperty(CustomLoyaltyProperty $customLoyaltyProperty);

    /**
     * @param string $name
     * @return $this
     */
    public function removeCustomLoyaltyPropertyByName($name);
}
