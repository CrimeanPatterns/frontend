<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\LayerLocator;

/**
 * @NoDI()
 */
class PropertyFormatter
{
    private $input;

    private EncoderContext $encoderContext;

    private LayerLocator $layerLocator;

    public function __construct($input, EncoderContext $encoderContext, LayerLocator $layerLocator)
    {
        $this->input = $input;
        $this->encoderContext = $encoderContext;
        $this->layerLocator = $layerLocator;
        $encoderContext->propertyFormatter = $this;
    }

    public function getProperty(string $property, string $layer)
    {
        return $this->layerLocator->getLayer($layer)->getEncodersMap()[$property]->encode($this->input, $this->encoderContext);
    }

    /**
     * @param string[] $properties
     * @return string[]
     */
    public function getProperties(array $properties, string $layer): array
    {
        $result = [];

        foreach ($properties as $property) {
            $result[$property] = $this->layerLocator->getLayer($layer)->getEncodersMap()[$property]->encode($this->input, $this->encoderContext);
        }

        return $result;
    }

    /**
     * @param string[] $properties
     * @return string[]
     */
    public function getExistingProperties(array $properties, string $layer): array
    {
        $result = [];

        foreach ($properties as $property) {
            $value = $this->layerLocator->getLayer($layer)->getEncodersMap()[$property]->encode($this->input, $this->encoderContext);

            if (!\is_null($value)) {
                $result[$property] = $value;
            }
        }

        return $result;
    }
}
