<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertyFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertyFormatterFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Util;
use AwardWallet\MainBundle\Timeline\Diff\Changes;

abstract class AbstractSimpleFormatterFactory
{
    protected PropertyFormatterFactory $propertyFormatterFactory;

    protected Util $propertyTranslator;

    public function __construct(PropertyFormatterFactory $propertyFormatterFactory, Util $propertyTranslator)
    {
        $this->propertyFormatterFactory = $propertyFormatterFactory;
        $this->propertyTranslator = $propertyTranslator;
    }

    public function createFromTripSegment(Tripsegment $tripsegment, Changes $changes, ?\DateTime $minChangeDate = null, ?string $locale = null, ?string $lang = null): SimpleFormatterInterface
    {
        return $this->doCreateFormatter(
            $this->propertyFormatterFactory->createFromTripSegment(
                $tripsegment,
                $changes,
                $minChangeDate,
                $locale,
                $lang
            ),
            $this->createTranslator($tripsegment->getType())
        );
    }

    public function createFromItinerary(Itinerary $itinerary, Changes $changes, ?\DateTime $minChangeDate = null, ?string $locale = null, ?string $lang = null): SimpleFormatterInterface
    {
        return $this->doCreateFormatter(
            $this->propertyFormatterFactory->createFromItinerary(
                $itinerary,
                $changes,
                $minChangeDate,
                $locale,
                $lang
            ),
            $this->createTranslator($itinerary->getType(), $lang)
        );
    }

    protected function doCreateFormatter(PropertyFormatter $currentValuesFormatter, callable $translator): SimpleFormatterInterface
    {
        [$currentLayer, $previousLayer] = $this->getCurrentPreviousLayersPair();

        return new SimpleFormatter(
            $currentLayer,
            $previousLayer,
            $currentValuesFormatter,
            $translator
        );
    }

    /**
     * @return string[]
     */
    abstract protected function getCurrentPreviousLayersPair(): array;

    protected function createTranslator(string $itineraryType, ?string $providedLang = null): callable
    {
        return function (string $code, ?string $lang = null) use ($itineraryType, $providedLang) {
            $info = $this->propertyTranslator->translatePropertyName(
                $code,
                $itineraryType,
                $lang ?? $providedLang
            );

            return $info['translation'];
        };
    }
}
