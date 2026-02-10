<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Restaurant as EntityEvent;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\ConfirmationNumberHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\CurrencyHelper;
use AwardWallet\MainBundle\Service\Itinerary\Matcher\Helper\DateHelper;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class EventMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityEvent $entityItinerary
     * @param SchemaItinerary|SchemaEvent $schemaItinerary
     */
    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$entityItinerary instanceof EntityEvent) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', EntityEvent::class, get_class($entityItinerary)));
        }

        if (!$schemaItinerary instanceof SchemaEvent) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', SchemaEvent::class, get_class($schemaItinerary)));
        }

        $sameProviderButDifferentConfirmationNumber =
            ConfirmationNumberHelper::isSameProviderButDifferentConfirmationNumber($entityItinerary, $schemaItinerary);
        $sameStartDate = DateHelper::isSameEntityDateWithSchemaDate($entityItinerary->getStartdate(), $schemaItinerary->startDateTime);
        $sameEventName = $this->isSameEventName($entityItinerary, $schemaItinerary);
        $sameEventType = $this->isSameEventType($entityItinerary, $schemaItinerary);
        $sameOrEmptyTotal = CurrencyHelper::isSameOrEmptyTotal($entityItinerary, $schemaItinerary);

        $result = MatchResult::create()
            ->merge($this->baseMatch($entityItinerary, $schemaItinerary))
            ->addResult(
                'event.sameStartDate+sameEventName+sameEventType+sameOrEmptyTotal',
                $sameStartDate && $sameEventName && $sameEventType && !$sameProviderButDifferentConfirmationNumber && $sameOrEmptyTotal,
                0.97
            );

        $result->writeLogs($this->logger, $entityItinerary, $schemaItinerary);

        return $result->maxConfidence();
    }

    private function isSameEventName(EntityEvent $entityEvent, SchemaEvent $schemaEvent): bool
    {
        return !empty($entityEvent->getName())
            && !empty($schemaEvent->eventName ?? null)
            && strcasecmp($entityEvent->getName(), $schemaEvent->eventName) === 0;
    }

    private function isSameEventType(EntityEvent $entityEvent, SchemaEvent $schemaEvent): bool
    {
        return !empty($entityEvent->getEventtype())
            && !empty($schemaEvent->eventType ?? null)
            && $entityEvent->getEventtype() == $schemaEvent->eventType;
    }
}
