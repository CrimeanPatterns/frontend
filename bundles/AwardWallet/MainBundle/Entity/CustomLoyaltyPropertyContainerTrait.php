<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\PersistentCollection;

/**
 * @property CustomLoyaltyProperty[]|PersistentCollection $customLoyaltyProperties
 */
trait CustomLoyaltyPropertyContainerTrait
{
    /**
     * @param CustomLoyaltyProperty[]|PersistentCollection $customLoyaltyProperties
     * @return $this
     */
    public function setCustomLoyaltyProperties($customLoyaltyProperties)
    {
        $this->customLoyaltyProperties = $customLoyaltyProperties;

        return $this;
    }

    /**
     * @return CustomLoyaltyProperty[]|PersistentCollection
     */
    public function &getCustomLoyaltyProperties()
    {
        return $this->customLoyaltyProperties;
    }

    /**
     * @return $this
     */
    public function addCustomLoyaltyProperty(CustomLoyaltyProperty $customLoyaltyProperty)
    {
        $this->customLoyaltyProperties[$customLoyaltyProperty->getName()] = $customLoyaltyProperty;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeCustomLoyaltyProperty(CustomLoyaltyProperty $customLoyaltyProperty)
    {
        if (is_array($this->customLoyaltyProperties)) {
            foreach ($this->customLoyaltyProperties as $key => $iterCustomLoyaltyProperty) {
                if ($iterCustomLoyaltyProperty === $customLoyaltyProperty) {
                    unset($this->customLoyaltyProperties[$key]);

                    break;
                }
            }
        } elseif (is_object($this->customLoyaltyProperties)) {
            $this->customLoyaltyProperties->removeElement($customLoyaltyProperty);
        } else {
            throw new \RuntimeException('CustomLoyaltyProperties are uninitialized');
        }

        return $this;
    }

    public function removeCustomLoyaltyPropertyByName($propertyName)
    {
        unset($this->customLoyaltyProperties[$propertyName]);

        return $this;
    }
}
