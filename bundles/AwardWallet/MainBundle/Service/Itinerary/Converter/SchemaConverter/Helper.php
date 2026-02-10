<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\Common\Entity\Geotag as EntityGeotag;
use AwardWallet\Common\Geo\GeoCodingFailedException;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;
use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\Schema\Itineraries\ConfNo as SchemaConfNo;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Person as SchemaPerson;
use Psr\Log\LoggerInterface;

class Helper
{
    protected LoggerInterface $logger;

    private GoogleGeo $geoCoder;

    public function __construct(GoogleGeo $geoCoder, LoggerFactory $loggerFactory)
    {
        $this->geoCoder = $geoCoder;
        $this->logger = $loggerFactory->createLogger($loggerFactory->createProcessor());
    }

    public function validateObject(object $object, string $expectedClassName)
    {
        if (!$object instanceof $expectedClassName) {
            throw new \InvalidArgumentException(sprintf('Expected "%s", got "%s"', $expectedClassName, get_class($object)));
        }
    }

    public function convertAddress2GeoTag(string $address): ?EntityGeotag
    {
        try {
            return $this->geoCoder->findGeoTagEntity($address);
        } catch (GeoCodingFailedException $e) {
            // log it and do nothing
            $this->logger->critical($e->getMessage(), ['address' => $address]);
        }

        return null;
    }

    /**
     * @param SchemaConfNo[] $confirmationNumbers
     */
    public function extractPrimaryConfirmationNumber(array $confirmationNumbers): ?string
    {
        $fallbackNumber = null;

        foreach ($confirmationNumbers as $confirmationNumber) {
            if (is_null($fallbackNumber)) {
                $fallbackNumber = $confirmationNumber->number;
            }

            if ($confirmationNumber->isPrimary) {
                return $confirmationNumber->number;
            }
        }

        return $fallbackNumber;
    }

    /**
     * @param SchemaPerson[] $schemaTravelers
     */
    public function updateTravelerNames(
        array $schemaTravelers,
        EntityItinerary $entityItinerary,
        SchemaItinerary $schemaItinerary,
        bool $partialUpdate
    ): void {
        // Overwrite traveler names only if new names are full
        if (
            (
                empty($entityItinerary->getTravelerNames())
                || $this->haveFullNames($schemaTravelers)
            ) && (
                !$partialUpdate
                && (
                    (
                        $this->emptyPricingInfo($entityItinerary)
                        && !isset($schemaItinerary->pricingInfo)
                    )
                    || (
                        !is_null($schemaItinerary->pricingInfo->total ?? null)
                        && (float) $entityItinerary->getPricingInfo()->getTotal() !== $schemaItinerary->pricingInfo->total
                    )
                )
                || count($entityItinerary->getTravelerNames()) <= count($schemaTravelers)
            )
        ) {
            $entityItinerary->setTravelerNames(array_map(fn (SchemaPerson $schemaPerson) => $schemaPerson->name, $schemaTravelers));
        }
    }

    public function setSeats(
        array $seats,
        EntityTrip $entityTrip,
        EntitySegment $segment,
        SchemaItinerary $schemaItinerary
    ): void {
        if (
            (
                $this->emptyPricingInfo($entityTrip)
                && !isset($schemaItinerary->pricingInfo)
            )
            || (
                !is_null($schemaItinerary->pricingInfo->total ?? null)
                && (float) $entityTrip->getPricingInfo()->getTotal() != $schemaItinerary->pricingInfo->total
            )
            || count($segment->getSeats()) <= count($seats)
        ) {
            $segment->setSeats($seats);
        }
    }

    /**
     * @param SchemaPerson[] $schemaTravelers
     */
    private function haveFullNames(array $schemaTravelers): bool
    {
        foreach ($schemaTravelers as $schemaPerson) {
            if (false === $schemaPerson->full) {
                return false;
            }
        }

        return true;
    }

    private function emptyPricingInfo(EntityItinerary $itinerary): bool
    {
        $pricingInfo = $itinerary->getPricingInfo();

        return is_null($pricingInfo->getTotal())
            && is_null($pricingInfo->getCost())
            && is_null($pricingInfo->getFees())
            && is_null($pricingInfo->getTax())
            && is_null($pricingInfo->getDiscount())
            && is_null($pricingInfo->getEarnedAwards())
            && is_null($pricingInfo->getSpentAwards())
            && is_null($pricingInfo->getTravelAgencyEarnedAwards());
    }
}
