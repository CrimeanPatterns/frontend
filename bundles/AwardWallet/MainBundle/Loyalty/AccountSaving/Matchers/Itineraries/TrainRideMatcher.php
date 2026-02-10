<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Trip as EntityTrainRide;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments\TrainRideSegmentMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Schema\Itineraries\Train as SchemaTrainRide;
use Psr\Log\LoggerInterface;

class TrainRideMatcher extends AbstractItineraryMatcher
{
    use MatchesBySegments;

    /**
     * TrainRideMatcher constructor.
     */
    public function __construct(
        Helper $helper,
        TrainRideSegmentMatcher $segmentsMatcher,
        GeoLocationMatcher $locationMatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($helper, $locationMatcher, $logger);
        $this->segmentMatcher = $segmentsMatcher;
    }

    /**
     * @param EntityItinerary|EntityTrainRide $entityTrainRide
     * @param SchemaItinerary|SchemaTrainRide $schemaTrainRide
     */
    public function match(EntityItinerary $entityTrainRide, SchemaItinerary $schemaTrainRide): float
    {
        $confidence = parent::match($entityTrainRide, $schemaTrainRide);
        $schemaTravelAgencyNumbers = [];

        if (!empty($schemaTrainRide->travelAgency->confirmationNumbers)) {
            $schemaTravelAgencyNumbers = array_map(function (ConfNo $number) {
                return strtolower($number->number);
            }, $schemaTrainRide->travelAgency->confirmationNumbers);
        }

        $entityTravelAgencyNumbers = [];

        if (!empty($entityTrainRide->getTravelAgencyConfirmationNumbers())) {
            $entityTravelAgencyNumbers = array_map('strtolower', $entityTrainRide->getTravelAgencyConfirmationNumbers());
        }

        if (
            !empty($entityTravelAgencyNumbers)
            && !empty($schemaTravelAgencyNumbers)
        ) {
            $sameTravelAgencyConfirmationNumbers = !empty(
                array_intersect($entityTravelAgencyNumbers, $schemaTravelAgencyNumbers)
            );
            $confidence = max($confidence, 0.99 * $sameTravelAgencyConfirmationNumbers);
        }

        $mainConfirmationNumber = $this->helper->extractPrimaryConfirmationNumber(
            array_merge(
                $schemaTrainRide->confirmationNumbers ?? [],
                $schemaTrainRide->travelAgency->confirmationNumbers ?? [],
            )
        );

        if (strcasecmp((string) $entityTrainRide->getConfirmationNumber(), (string) $mainConfirmationNumber) === 0) {
            $confidence = max($confidence, 0.99);
        }

        // match by any number, if there are only one number
        $schemaNumbers = [];

        if (!empty($schemaTrainRide->confirmationNumbers)) {
            foreach ($schemaTrainRide->confirmationNumbers as $confNo) {
                $schemaNumbers[] = $confNo->number;
            }
        }
        $schemaNumbers = array_merge($schemaNumbers, $schemaTravelAgencyNumbers);
        $schemaNumbers = array_unique(array_map("strtolower", $schemaNumbers));
        sort($schemaNumbers);

        $entityNumbers = [];
        $number = $entityTrainRide->getConfirmationNumber();

        if ($number !== null) {
            $entityNumbers[] = $number;
        }
        $entityNumbers = array_merge($entityNumbers, $entityTravelAgencyNumbers);
        $entityNumbers = array_unique(array_map("strtolower", $entityNumbers));
        sort($entityNumbers);

        if ($entityNumbers === $schemaNumbers) {
            $confidence = max($confidence, 0.99);
        }

        return max($confidence, $this->getMeanConfidence($entityTrainRide->getSegments()->toArray(), $schemaTrainRide->segments));
    }

    protected function getSupportedEntityClass(): string
    {
        return EntityTrainRide::class;
    }

    protected function getSupportedSchemaClass(): string
    {
        return SchemaTrainRide::class;
    }
}
