<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;

class RentalMatcher extends AbstractItineraryMatcher
{
    /**
     * @param EntityItinerary|EntityRental $entityRental
     * @param SchemaItinerary|SchemaRental $schemaRental
     */
    public function match(EntityItinerary $entityRental, SchemaItinerary $schemaRental): float
    {
        $confidence = parent::match($entityRental, $schemaRental);
        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaRental->confirmationNumbers ?? [],
                $schemaRental->travelAgency->confirmationNumbers ?? [],
            )
        );

        if (!is_null($mainConfirmationNumber)) {
            $mainConfirmationNumber = AbstractItineraryMatcher::filterConfirmationNumber($mainConfirmationNumber);

            if (strcasecmp(AbstractItineraryMatcher::filterConfirmationNumber((string) $entityRental->getConfirmationNumber()), $mainConfirmationNumber) === 0) {
                $confidence = max($confidence, 0.99);
            }

            if (in_array($mainConfirmationNumber, array_map([AbstractItineraryMatcher::class, "filterConfirmationNumber"], $entityRental->getTravelAgencyConfirmationNumbers()))) {
                $confidence = max($confidence, 0.99);
            }
        }

        $notEqualsByProviderAndConfNo = $this->notEqualsByProviderAndConfNo($entityRental, $schemaRental);
        $sameTotal = !is_null($entityRental->getPricingInfo()->getTotal())
            && !is_null($schemaRental->pricingInfo->total ?? null)
            && abs($entityRental->getPricingInfo()->getTotal() - $schemaRental->pricingInfo->total) <= 0.01;
        $matchDates = !is_null($schemaRental->pickup->localDateTime ?? null)
            && !is_null($schemaRental->dropoff->localDateTime ?? null)
            && $entityRental->getPickupdatetime() == new \DateTime($schemaRental->pickup->localDateTime)
            && $entityRental->getDropoffdatetime() == new \DateTime($schemaRental->dropoff->localDateTime);

        if (
            !$notEqualsByProviderAndConfNo
            && (
                $sameTotal || (
                    is_null($entityRental->getPricingInfo()->getTotal())
                    || is_null($schemaRental->pricingInfo->total ?? null)
                )
            )
            && $matchDates
            && $this->locationMatcher->match(
                $schemaRental->pickup->address->text ?? null,
                $entityRental->getPickupgeotagid() ?? $entityRental->getPickuplocation(),
                0.5
            )
            && $this->locationMatcher->match(
                $schemaRental->dropoff->address->text ?? null,
                $entityRental->getDropoffgeotagid() ?? $entityRental->getDropofflocation(),
                0.5
            )
        ) {
            $confidence = max($confidence, 0.97);
        }

        if (
            !$notEqualsByProviderAndConfNo
            && (
                $sameTotal || (
                    is_null($entityRental->getPricingInfo()->getTotal())
                    || is_null($schemaRental->pricingInfo->total ?? null)
                )
            )
            && $matchDates
            && (
                $schemaRental->driver === null
                || count($entityRental->getTravelerNames()) === 0
                || TravelerNamesMatcher::same([$schemaRental->driver], $entityRental->getTravelerNames())
            )
        ) {
            $confidence = max($confidence, 0.96);
        }

        return $confidence;
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityRental::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaRental::class;
    }

    private function notEqualsByProviderAndConfNo(EntityRental $entityRental, SchemaRental $schemaRental): bool
    {
        // compare provider confirmation numbers
        if (
            !empty($schemaProvider = $schemaRental->providerInfo->code ?? null)
            && is_array($schemaRental->confirmationNumbers) && \count($schemaRental->confirmationNumbers) > 0
            && !is_null($entityProvider = $entityRental->getProvider())
            && !empty($entityRental->getConfirmationNumber())
            && $entityProvider->getCode() === $schemaProvider
            && !$this->isEqualsConfNo([$entityRental->getConfirmationNumber()], $schemaRental->confirmationNumbers)
        ) {
            return true;
        }

        // compare travel agency confirmation numbers
        if (
            !empty($schemaProvider = $schemaRental->travelAgency->providerInfo->code ?? null)
            && is_array($schemaRental->travelAgency->confirmationNumbers) && \count($schemaRental->travelAgency->confirmationNumbers) > 0
            && !is_null($entityProvider = $entityRental->getTravelAgency())
            && !empty($entityRental->getTravelAgencyConfirmationNumbers())
            && $entityProvider->getCode() === $schemaProvider
            && !$this->isEqualsConfNo($entityRental->getTravelAgencyConfirmationNumbers(), $schemaRental->travelAgency->confirmationNumbers)
        ) {
            return true;
        }

        return false;
    }

    private function isEqualsConfNo(array $entityConfNo, array $schemaConfNo): bool
    {
        foreach ($entityConfNo as $entityConfNoItem) {
            $preparedEntityConfNo = AbstractItineraryMatcher::filterConfirmationNumber($entityConfNoItem);

            foreach ($schemaConfNo as $schemaConfNoItem) {
                $preparedSchemaConfNo = AbstractItineraryMatcher::filterConfirmationNumber($schemaConfNoItem->number);

                if (
                    $preparedEntityConfNo === $preparedSchemaConfNo
                    || (
                        \strlen($preparedSchemaConfNo) >= 10
                        && strpos($preparedEntityConfNo, $preparedSchemaConfNo) === 0
                    )
                    || (
                        \strlen($preparedEntityConfNo) >= 10
                        && strpos($preparedSchemaConfNo, $preparedEntityConfNo) === 0
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
