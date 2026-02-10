<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\ItineraryMatcherInterface;

abstract class AbstractItineraryWithoutSegmentsTest extends AbstractItineraryMatcherTest
{
    abstract protected function getMatcher(GeoLocationMatcher $locationMatcher): ItineraryMatcherInterface;
}
