<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Exception\EncoderException;

/**
 * @NoDI()
 */
class EncoderContext
{
    public ?string $locale = null;

    public ?string $lang = null;

    public ?PropertyFormatter $propertyFormatter = null;

    private array $propertiesInProcessMap = [];

    private array $propertyValuesMap = [];

    public function getProperty(string $property, string $layer)
    {
        if (
            \array_key_exists($property, $this->propertyValuesMap)
            && \array_key_exists($layer, $this->propertiesInProcessMap[$property])
        ) {
            return $this->propertyValuesMap[$property][$layer];
        }

        return $this->propertyValuesMap[$property][$layer] = $this->loadProperty($property, $layer);
    }

    private function setPropertyWorkInProgress(string $property, string $layer): void
    {
        if (isset($this->propertiesInProcessMap[$property][$layer])) {
            throw new EncoderException('Cycle in property depenencies!');
        } else {
            $this->propertiesInProcessMap[$property][$layer] = true;
        }
    }

    private function removePropertyWorkInProgress(string $property, string $layer): void
    {
        unset($this->propertiesInProcessMap[$property][$layer]);
    }

    private function loadProperty(string $property, ?string $layer)
    {
        $this->setPropertyWorkInProgress($property, $layer);
        $value = $this->doLoadProperty($property, $layer);
        $this->removePropertyWorkInProgress($property, $layer);

        return $value;
    }

    private function doLoadProperty(string $property, string $layer)
    {
        return $this->propertyFormatter->getProperty($property, $layer);
    }
}
