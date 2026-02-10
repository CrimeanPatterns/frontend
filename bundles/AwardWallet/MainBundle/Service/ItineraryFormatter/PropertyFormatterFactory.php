<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\LayerLocator;
use AwardWallet\MainBundle\Timeline\Diff\Changes;

class PropertyFormatterFactory
{
    private LayerLocator $layerLocator;

    public function __construct(
        LayerLocator $layerLocator
    ) {
        $this->layerLocator = $layerLocator;
    }

    public function createFromTripSegment(Tripsegment $tripsegment, Changes $changes, ?\DateTime $minDateTime = null, ?string $locale = null, ?string $lang = null): PropertyFormatter
    {
        return $this->createFormatter(
            new ItineraryWrapper(
                $tripsegment,
                $tripsegment->getTripid(),
                $changes,
                $minDateTime
            ),
            $locale,
            $lang
        );
    }

    public function createFromItinerary(Itinerary $itinerary, Changes $changes, ?\DateTime $minDateTime = null, ?string $locale = null, ?string $lang = null): PropertyFormatter
    {
        return $this->createFormatter(
            new ItineraryWrapper(
                $itinerary,
                $itinerary,
                $changes,
                $minDateTime
            ),
            $locale,
            $lang
        );
    }

    protected function createFormatter(ItineraryWrapper $input, ?string $locale = null, ?string $lang = null): PropertyFormatter
    {
        $encoderContext = new EncoderContext();
        $encoderContext->lang = $lang;
        $encoderContext->locale = $locale;

        return new PropertyFormatter(
            $input,
            $encoderContext,
            $this->layerLocator
        );
    }
}
