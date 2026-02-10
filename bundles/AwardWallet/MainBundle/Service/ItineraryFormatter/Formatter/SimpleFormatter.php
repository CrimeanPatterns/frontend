<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertyFormatter;

/**
 * @NoDI()
 */
class SimpleFormatter implements SimpleFormatterInterface
{
    private string $propertyLayer;

    private string $changesLayer;

    private PropertyFormatter $propertyFormatter;

    /**
     * @var callable
     */
    private $translator;

    public function __construct(
        string $propertyLayer,
        string $changesLayer,
        PropertyFormatter $propertyFormatter,
        callable $translator
    ) {
        $this->propertyLayer = $propertyLayer;
        $this->changesLayer = $changesLayer;
        $this->propertyFormatter = $propertyFormatter;
        $this->translator = $translator;
    }

    public function getValue(string $code)
    {
        return $this->propertyFormatter->getProperty($code, $this->propertyLayer);
    }

    public function getValues(array $codes): array
    {
        return $this->propertyFormatter->getProperties($codes, $this->propertyLayer);
    }

    public function getExistingValues(array $codes): array
    {
        return $this->propertyFormatter->getExistingProperties($codes, $this->propertyLayer);
    }

    public function getPreviousValue(string $code)
    {
        return $this->propertyFormatter->getProperty($code, $this->changesLayer);
    }

    public function getPreviousValues(array $codes): array
    {
        return $this->propertyFormatter->getProperties($codes, $this->changesLayer);
    }

    public function getExistingPreviousValues(array $codes): array
    {
        return $this->propertyFormatter->getProperties($codes, $this->changesLayer);
    }

    public function translatePropertyName(string $code): string
    {
        return ($this->translator)($code);
    }
}
