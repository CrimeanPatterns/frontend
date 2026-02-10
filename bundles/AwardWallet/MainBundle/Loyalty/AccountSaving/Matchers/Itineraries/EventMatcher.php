<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Restaurant as EntityEvent;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class EventMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityEvent $entityEvent
     * @param SchemaItinerary|SchemaEvent $schemaEvent
     */
    public function match(EntityItinerary $entityEvent, SchemaItinerary $schemaEvent): float
    {
        $confidence = parent::match($entityEvent, $schemaEvent);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaEvent->confirmationNumbers ?? [],
                $schemaEvent->travelAgency->confirmationNumbers ?? [],
            )
        );

        if ($mainConfirmationNumber !== null) {
            $mainConfirmationNumber = AbstractItineraryMatcher::filterConfirmationNumber($mainConfirmationNumber);

            if ($entityEvent->getConfirmationNumber() !== null && AbstractItineraryMatcher::filterConfirmationNumber($entityEvent->getConfirmationNumber()) == $mainConfirmationNumber) {
                $confidence = max($confidence, 0.99);
            }

            if (in_array($mainConfirmationNumber,
                array_map([AbstractItineraryMatcher::class, "filterConfirmationNumber"],
                    $entityEvent->getTravelAgencyConfirmationNumbers()))) {
                $confidence = max($confidence, 0.99);
            }
        } else {
            if ($entityEvent->getStartDate() != new \DateTime($schemaEvent->startDateTime)) {
                $this->logger->info(sprintf('Event start date does not match. Entity: "%s", schema: "%s"',
                    $entityEvent->getStartDate()->format('Y-m-d H:i:s'),
                    $schemaEvent->startDateTime,
                ));

                return 0;
            }

            if (strcasecmp($entityEvent->getName(), $schemaEvent->eventName) !== 0) {
                $this->logger->info(sprintf('Event name does not match. Entity: "%s", schema: "%s"',
                    $entityEvent->getName(),
                    $schemaEvent->eventName,
                ));

                return 0;
            }

            if ($entityEvent->getEventtype() !== $schemaEvent->eventType) {
                $this->logger->info(sprintf('Event type does not match. Entity: "%s", schema: "%s"',
                    $entityEvent->getEventtype(),
                    $schemaEvent->eventType,
                ));

                return 0;
            }

            //            if (is_null($schemaAddress = $schemaEvent->address->text ?? null)) {
            //                $this->logger->info('Event address is null');
            //
            //                return 0;
            //            }
            //
            //            if (!$this->locationMatcher->match($schemaAddress, $entityEvent->getGeotagid() ?? $entityEvent->getAddress())) {
            //                $this->logger->info(sprintf('Event address does not match. Schema: "%s"', $schemaAddress));
            //
            //                return 0;
            //            }

            return 0.97;
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityEvent::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaEvent::class;
    }
}
