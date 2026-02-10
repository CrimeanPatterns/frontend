<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\MileValue;
use AwardWallet\MainBundle\Entity\Trip as TripEntity;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\LazyVal;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter\BlockFactory;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertiesDB;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertyInfo;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\Tags;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use AwardWallet\MainBundle\Service\Lounge\Finder;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\ViewInflater;
use AwardWallet\MainBundle\Service\MileValue\MileValueAlternativeFlights;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;
use AwardWallet\MainBundle\Timeline;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Icon;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\ListView;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Value\Time;
use AwardWallet\MainBundle\Timeline\Formatter\Utils\TripHeaderResolver;
use AwardWallet\MainBundle\Timeline\Util\ItineraryUtil;
use Clock\ClockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;
use function Duration\days;

class Trip implements ItemFormatterInterface
{
    private Desanitizer $desanitizer;
    private TranslatorInterface $translator;
    private BlockHelper $blockHelper;
    private Timeline\Util\TripHelper $tripHelper;
    private PropertiesDB $propertiesDB;
    /**
     * @var LazyVal<PropertyInfo>
     */
    private LazyVal $excludedProperties;
    /**
     * @var LazyVal<PropertyInfo>
     */
    private LazyVal $privateProperties;
    private MileValueAlternativeFlights $mileValueAlternativeFlights;
    private UrlGeneratorInterface $urlGenerator;
    private LegacyUrlGenerator $legacyUrlGenerator;
    private DateTimeIntervalFormatter $dateTimeIntervalFormatter;
    private LocalizeService $localizer;
    private Finder $loungeFinder;
    private CreditCardRepository $cardRepository;
    private Timeline\NoForeignFeesCardsQuery $noForeignFeesCardsQuery;
    private ClockInterface $clock;
    private $noForeignFeesCards;
    private ViewInflater $loungeViewInflater;

    public function __construct(
        TranslatorInterface $translator,
        BlockHelper $blockHelper,
        Timeline\Util\TripHelper $tripHelper,
        Desanitizer $desanitizer,
        PropertiesDB $propertiesDB,
        MileValueAlternativeFlights $mileValueAlternativeFlights,
        UrlGeneratorInterface $urlGenerator,
        LegacyUrlGenerator $legacyUrlGenerator,
        DateTimeIntervalFormatter $dateTimeIntervalFormatter,
        LocalizeService $localizer,
        Finder $loungeFinder,
        Timeline\NoForeignFeesCardsQuery $noForeignFeesCardsQuery,
        ClockInterface $clock,
        ViewInflater $loungeViewInflater
    ) {
        $this->translator = $translator;
        $this->blockHelper = $blockHelper;
        $this->tripHelper = $tripHelper;
        $this->desanitizer = $desanitizer;
        $this->propertiesDB = $propertiesDB;
        $this->excludedProperties = lazy(fn () =>
            it($this->propertiesDB->getProperties())
            ->filterNot(fn (PropertyInfo $propertyInfo) =>
                $propertyInfo->hasTag(Tags::COMMON)
                || $propertyInfo->hasTag(Tags::TRIP)
            )
            ->keys()
            ->toArray()
        );
        $this->privateProperties = lazy(fn () =>
            it($this->propertiesDB->getProperties())
            ->filter(fn (PropertyInfo $property) => $property->isPrivate())
            ->keys()
            ->toArray()
        );
        $this->mileValueAlternativeFlights = $mileValueAlternativeFlights;
        $this->urlGenerator = $urlGenerator;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->dateTimeIntervalFormatter = $dateTimeIntervalFormatter;
        $this->localizer = $localizer;
        $this->loungeFinder = $loungeFinder;
        $this->noForeignFeesCardsQuery = $noForeignFeesCardsQuery;
        $this->clock = $clock;
        $this->loungeViewInflater = $loungeViewInflater;
    }

    /**
     * @param Timeline\Item\AbstractTrip $item
     * @return Formatted\SegmentItem
     */
    public function format($item, Timeline\QueryOptions $queryOptions)
    {
        $formatted = new Formatted\SegmentItem();
        $formatOptions = $queryOptions->getFormatOptions();
        $this->blockHelper->formatCommonSegmentProperties($item, $formatted, $formatOptions);
        $propertyFormatter = $item->getContext()->getPropFormatter();

        if ($item instanceof Timeline\Item\AirTrip) {
            $geofences = $item->getGeofences();

            if (count($geofences) > 0) {
                $formatted->geofences = $geofences;
            }
        }

        /** @var Tripsegment $source */
        $source = $item->getSource();
        /** @var TripEntity $itinerary */
        $itinerary = $item->getItinerary();
        $changes = $item->getChanges();

        $resolvedFlightInfo = $this->tripHelper->resolveFlightName($item);
        $persistedFlightInfo = $item->getTripInfo();

        $depCode = $source->getDepcode();
        $arrCode = $source->getArrcode();

        if (
            $formatOptions->supports(FormatHandler::CRUISE_LIST_ITEMS)
            && ($itinerary->getCategory() === TRIP_CATEGORY_CRUISE)
        ) {
            if ($item->getCruiseName() !== null) {
                $hint = $this->translator->trans('cruise.duration', [
                    '%duration%' => $this->dateTimeIntervalFormatter->formatDuration($item->getStartDate(), $item->getEndDate(), false, true),
                    '%cruiseName%' => $item->getCruiseName(),
                ], 'trips');
            } else {
                $hint = $this->translator->trans('cruise.duration.without-name', [
                    '%duration%' => $this->dateTimeIntervalFormatter->formatDuration($item->getStartDate(), $item->getEndDate(), false, true),
                ], 'trips');
            }

            $formatted->listView = new ListView\TripPointView(
                $item->getDeparture(),
                $item->getArrival(),
                $hint
            );
        } else {
            if (null !== $depCode && '' !== $depCode && null !== $arrCode && '' !== $arrCode) {
                $formatted->listView = new ListView\TripChainView(
                    $depCode,
                    $arrCode,
                    $formatted->endDate->fmt,
                    $propertyFormatter->getValue(PropertiesList::DURATION)
                );
            } elseif (
                ($stationNames = TripHeaderResolver::getStationNames($item))
                && in_array($itinerary->getCategory(), [TRIP_CATEGORY_BUS, TRIP_CATEGORY_TRAIN, TRIP_CATEGORY_FERRY, TRIP_CATEGORY_TRANSFER])
            ) {
                if ($formatOptions->supports(FormatHandler::TRIP_TITLE_POINT)) {
                    $formatted->listView = new ListView\TripPointView(
                        $stationNames['departure'],
                        $stationNames['arrival'],
                        ''
                    );
                } else {
                    $formatted->listView = new ListView\SimpleView(null, $stationNames['departure'] . ' → ' . $stationNames['arrival']);
                }
            } else {
                $listTitle =
                    ($formatOptions->supports(FormatHandler::DETAILED_ITINERARIES_V2_INFO)) ?
                        ($persistedFlightInfo->primaryTripNumberInfo->companyInfo->companyName ?? '') :
                        $resolvedFlightInfo->airlineName;

                if ($formatOptions->supports(FormatHandler::DESANITIZED_STRINGS)) {
                    $listTitle = $this->desanitizer->fullDesanitize($listTitle);
                }

                $flightNumber =
                    ($formatOptions->supports(FormatHandler::DETAILED_ITINERARIES_V2_INFO)) ?
                        ($persistedFlightInfo->primaryTripNumberInfo->tripNumber ?? '') :
                        $resolvedFlightInfo->flightNumber;

                if (!StringUtils::isEmpty($flightNumber)) {
                    $listTitle .= " {$flightNumber}";
                }

                $formatted->listView = new ListView\SimpleView(null, $listTitle);
            }
        }

        $isBlocksV2Enabled = $formatOptions->supports(FormatHandler::DETAILS_BLOCKS_V2);

        if (
            ($item->getEndDate()->getTimestamp() > $this->clock->current()->sub(days(1))->getAsSecondsInt())
            && !StringUtils::isEmpty($arrCode)
            && !StringUtils::isEmpty($depCode)
            && $formatOptions->supports(FormatHandler::FLIGHT_PROGRESS)
            && ($itinerary->getCategory() === TRIP_CATEGORY_AIR)
        ) {
            $block = Block::fromKindValue(Block::KIND_FLIGHT_PROGRESS, null);
            $block->arrival = $arrCode;
            $block->depart = $depCode;
            $block->startDate = $item->getStartDate()->getTimestamp();
            $block->endDate = $item->getEndDate()->getTimestamp();

            $formatted->blocks[] = $block;
        }

        if ($formatOptions->supports(FormatHandler::DETAILED_ITINERARIES_V2_INFO)) {
            // Confirmation # (British Airways)   GH45JK
            if (
                !$queryOptions->noPersonalData()
                && StringUtils::isNotEmpty($persistedFlightInfo->primaryConfirmationNumberInfo->confirmationNumber ?? null)
            ) {
                $confNumberBlock = Block::fromKindNameValue(
                    Block::KIND_CONFNO,
                    $this->translator->trans(/** @Desc("Confirmation #") */ 'timeline.section.conf.long'),
                    $persistedFlightInfo->primaryConfirmationNumberInfo->confirmationNumber
                );

                if (StringUtils::isNotEmpty($persistedFlightInfo->primaryConfirmationNumberInfo->airlineInfo->companyName ?? null)) {
                    $confNumberBlock->hint = '(' . $persistedFlightInfo->primaryConfirmationNumberInfo->airlineInfo->companyName . ')';
                }

                $formatted->blocks[] = $confNumberBlock;
            }

            // ✈ British Airways     5939
            if (StringUtils::isNotEmpty($persistedFlightInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)) {
                $formatted->blocks[] = new Block(
                    Block::KIND_TITLE,
                    $item->getIcon(),

                    $persistedFlightInfo->primaryTripNumberInfo->companyInfo->companyName .
                    (
                        StringUtils::isNotEmpty($persistedFlightInfo->primaryTripNumberInfo->companyInfo->companyCode) ?
                            " ({$persistedFlightInfo->primaryTripNumberInfo->companyInfo->companyCode})" :
                            ''
                    ),
                    $persistedFlightInfo->primaryTripNumberInfo->tripNumber
                );
            }

            // Ticketed As: American Airlines
            if (
                $persistedFlightInfo->secondaryTripNumberInfo
                && StringUtils::isNotEmpty($persistedFlightInfo->secondaryTripNumberInfo->companyInfo->companyName ?? null)
            ) {
                $formatted->blocks[] = Block::fromKindNameValue(
                    Block::KIND_STRING,
                    $this->translator->trans(/** @Desc("Ticketed As") */ 'timeline.section.ticketed.as'),

                    $persistedFlightInfo->secondaryTripNumberInfo->companyInfo->companyName .
                    (
                        StringUtils::isNotEmpty($persistedFlightInfo->secondaryTripNumberInfo->companyInfo->companyCode) ?
                            " ({$persistedFlightInfo->secondaryTripNumberInfo->companyInfo->companyCode})" :
                            ''
                    ) .
                    (
                        StringUtils::isNotEmpty($persistedFlightInfo->secondaryTripNumberInfo->tripNumber) ?
                            " {$persistedFlightInfo->secondaryTripNumberInfo->tripNumber}" :
                            ''
                    )
                );

                // AA Confirmation #;     [AH49KJ]
                if (
                    !$queryOptions->noPersonalData()
                    && StringUtils::isNotEmpty($persistedFlightInfo->secondaryConfirmationNumber)
                ) {
                    $formatted->blocks[] = Block::fromKindNameValue(
                        Block::KIND_BOXED,

                        (
                            $persistedFlightInfo->secondaryTripNumberInfo->companyInfo->companyCode ??
                            $persistedFlightInfo->secondaryTripNumberInfo->companyInfo->companyName
                        ) .
                        ' ' .
                        $this->translator->trans(/** @Desc("Confirmation #") */ 'timeline.section.conf.long'),
                        $persistedFlightInfo->secondaryConfirmationNumber
                    );
                }
            }
        } else {
            // Confirmation #   100500
            if (
                (null !== $item->getConfNo())
                && !$queryOptions->noPersonalData()
            ) {
                $formatted->blocks[] = new Block(Block::KIND_CONFNO, null, $this->translator->trans(/** @Desc("Confirmation #") */ 'timeline.section.conf.long'), $item->getConfNo());
            }

            // ✈ American Airlines 1170
            if (!(
                StringUtils::isEmpty($resolvedFlightInfo->airlineName)
                && StringUtils::isEmpty($resolvedFlightInfo->flightNumber)
            )) {
                $formatted->blocks[] = new Block(Block::KIND_TITLE, $item->getIcon(), $resolvedFlightInfo->airlineName, $resolvedFlightInfo->flightNumber);
            }
        }

        // ♽ AI Warning
        $this->blockHelper->formatAIWarning($item, $formatted, $formatOptions);
        // ↡ Origin
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Origin") */ 'itineraries.trip.air.origin', [], 'trips'));

        // ✞ [FLL]   Holywood, FL
        if (StringUtils::isNotEmpty($depName = $source->getDepAirportName(false))) {
            $code = $source->getDepcode();

            if ($queryOptions->getFormatOptions()->supports(FormatHandler::LOUNGES)) {
                $location = new Value\AirportLocation($depName, $code);

                if (!empty($code)) {
                    $lounges = $this->loungeFinder->getNumberAirportLounges($code);

                    if ($lounges > 0) {
                        $location->lounges = $lounges;
                        $location->stage = ViewInflater::STAGE_DEP;
                        $location->segmentId = $item->getId();

                        $startDate = $this->clock->current()->getAsDateTime();
                        $endDate = clone $startDate;
                        $endDate->modify('+10 days');

                        if (
                            $formatOptions->supports(FormatHandler::LOUNGES_OFFLINE)
                            && (
                                ($item->getStartDate() >= $startDate && $item->getStartDate() <= $endDate)
                                || ($item->getEndDate() >= $startDate && $item->getEndDate() <= $endDate)
                            )
                        ) {
                            $location->listOfLounges = $this->loungeViewInflater->listLounges(
                                $queryOptions->getUser(),
                                $source,
                                ViewInflater::STAGE_DEP,
                                null,
                                null,
                                null,
                                true
                            );
                        }
                    }
                }

                $formatted->blocks[] = new Block('location', null, null, $location);
            } else {
                $formatted->blocks[] = Block::fromValue(new Value\Location($depName, $code));
            }
        }

        if ($isBlocksV2Enabled) {
            // ✈ Terminal 1 Gate 10B
            $this->addTerminalAndGateBlock(array_merge([PropertiesList::DEPARTURE_TERMINAL], $queryOptions->noPersonalData() ? [] : [PropertiesList::DEPARTURE_GATE]), 'dep', $formatted, $item);
        } else {
            // ✈ Gate 10
            if ($property = $this->blockHelper->translateSegmentProperty($item, PropertiesList::DEPARTURE_GATE)) {
                [$name, $value] = $property;
                $formatted->blocks[] = new Block(
                    Block::KIND_IMPORTANT,
                    Icon::GATE,
                    $name,
                    $value,
                    $changes ? $changes->getPreviousValue(PropertiesList::DEPARTURE_GATE) : null
                );
            }
        }

        $isReadableTripDatesEnabled = $formatOptions->supports(FormatHandler::READABLE_TRIP_AND_RESTAURANT_DATES);

        // ▤ in 8 days on Friday, April 2, 2028 12:22
        if ($isBlocksV2Enabled) {
            $oldDateFormatted = null;
            $dateBlock = Block::fromValue(
                new Time($startDateFormatted = $this->blockHelper->createLocalizedDate(
                    DateTimeExtended::create($item->getStartDate(), $item->getTimezoneAbbr()),
                    null,
                    $isReadableTripDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                )),
                ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, 'DepDate'))) ?
                    new Time($oldDateFormatted = $this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create($oldDateTime, $item->getTimezoneAbbr()),
                        null,
                        $isReadableTripDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                    )) :
                    null
            );

            $formatted->startDate = clone $startDateFormatted;
            $formatted->startDate->old = $oldDateFormatted;
        } else {
            $oldStartDateFormatted = $changes ? Utils::getChangedDate($changes, 'DepDate') : null;
            $dateBlock = Block::fromValue(
                new Time($startDateFormatted = new Components\Date($item->getStartDate())),
                $oldStartDateFormatted
            );
            $formatted->startDate = $startDateFormatted;
            $formatted->startDate->old = $oldStartDateFormatted;
        }

        $formatted->blocks[] = $dateBlock;

        // Seats     [12B, 12A]
        if (
            !$queryOptions->noPersonalData()
            && ($property = $this->blockHelper->translateSegmentProperty($item, PropertiesList::SEATS))
        ) {
            [$name, $value] = $property;

            if ($isBlocksV2Enabled) {
                $formatted->blocks[] = new Block(
                    Block::KIND_IMPORTANT,
                    Icon::SEATS,
                    $name . ':',
                    implode(', ', $value),
                    $changes ? $changes->getPreviousValue(PropertiesList::SEATS) : null
                );
            } else {
                $formatted->blocks[] = Block::fromKindNameValue(
                    Block::KIND_BOXED,
                    $name . ':',
                    implode(', ', $value),
                    $changes ? $changes->getPreviousValue(PropertiesList::SEATS) : null
                );
            }
        }

        // ↡ Destination
        $formatted->blocks[] = Block::fromKindName(Block::KIND_GROUP, $this->translator->trans(/** @Desc("Destination") */ 'itineraries.trip.air.destination', [], 'trips'));

        // ✞ [JFK]  New York, NY
        if (StringUtils::isNotEmpty($arrName = $source->getArrAirportName(false))) {
            $code = $source->getArrcode();

            if ($queryOptions->getFormatOptions()->supports(FormatHandler::LOUNGES)) {
                $location = new Value\AirportLocation($arrName, $code);

                if (!empty($code)) {
                    $lounges = $this->loungeFinder->getNumberAirportLounges($code);

                    if ($lounges > 0) {
                        $location->lounges = $lounges;
                        $location->stage = ViewInflater::STAGE_ARR;
                        $location->segmentId = $item->getId();

                        $startDate = $this->clock->current()->getAsDateTime();
                        $endDate = clone $startDate;
                        $endDate->modify('+10 days');

                        if (
                            $formatOptions->supports(FormatHandler::LOUNGES_OFFLINE)
                            && (
                                ($item->getStartDate() >= $startDate && $item->getStartDate() <= $endDate)
                                || ($item->getEndDate() >= $startDate && $item->getEndDate() <= $endDate)
                            )
                        ) {
                            $location->listOfLounges = $this->loungeViewInflater->listLounges(
                                $queryOptions->getUser(),
                                $source,
                                ViewInflater::STAGE_ARR,
                                null,
                                null,
                                null,
                                true
                            );
                        }
                    }
                }

                $formatted->blocks[] = new Block('location', null, null, $location);
            } else {
                $formatted->blocks[] = Block::fromValue(new Value\Location($arrName, $code));
            }
        }

        if ($isBlocksV2Enabled) {
            // ✈ Terminal 2 Gate 20B
            $this->addTerminalAndGateBlock(array_merge([PropertiesList::ARRIVAL_TERMINAL], $queryOptions->noPersonalData() ? [] : [PropertiesList::ARRIVAL_GATE]), 'arr', $formatted, $item);
        }

        // ⚾ Baggage claim 27
        if ($property = $this->blockHelper->translateSegmentProperty($item, PropertiesList::BAGGAGE_CLAIM)) {
            [$name, $value] = $property;
            $formatted->blocks[] = new Block(
                Block::KIND_IMPORTANT,
                Icon::BAGGAGE, $name,
                $value,
                $changes ? $changes->getPreviousValue(PropertiesList::BAGGAGE_CLAIM) : null
            );
        }

        // ▤ in 8 days on Friday, April 2, 2028 13:39
        if ($isBlocksV2Enabled) {
            $formatted->blocks[] = Block::fromValue(
                new Time($formatted->endDate = $this->blockHelper->createLocalizedDate(
                    DateTimeExtended::create($item->getEndDate()),
                    null,
                    $isReadableTripDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                )),
                ($changes && ($oldDateTime = Utils::getChangedDateTime($changes, 'ArrDate'))) ?
                    new Time($this->blockHelper->createLocalizedDate(
                        DateTimeExtended::create($oldDateTime),
                        null,
                        $isReadableTripDatesEnabled ? LocalizeService::FORMAT_MEDIUM : LocalizeService::FORMAT_SHORT,
                    )) :
                    null
            );
        } else {
            $formatted->blocks[] = Block::fromValue(
                new Time(new Components\Date($item->getEndDate())),
                $changes ?
                    Utils::getChangedDate($changes, 'ArrDate') :
                    null
            );
        }

        // Duration [2 hrs 55mins]
        $formatted->blocks[] = Block::fromKindNameValue(
            Block::KIND_BOXED,
            $propertyFormatter->translatePropertyName(PropertiesList::DURATION),
            $propertyFormatter->getValue(PropertiesList::DURATION)
        );
        $diffTrackedProperties = [];

        $formatted->changed =
            $item->isChanged()
            && $changes
            && array_intersect(
                $changes->getChangedProperties(),
                $diffTrackedProperties = $isBlocksV2Enabled ?
                    [
                        PropertiesList::AIRCRAFT,
                        PropertiesList::ARRIVAL_AIRPORT_CODE,
                        PropertiesList::ARRIVAL_DATE,
                        PropertiesList::ARRIVAL_TERMINAL,
                        PropertiesList::ARRIVAL_GATE,
                        PropertiesList::COST,
                        PropertiesList::BOOKING_CLASS,
                        PropertiesList::FLIGHT_CABIN_CLASS,
                        PropertiesList::DEPARTURE_TERMINAL,
                        PropertiesList::DEPARTURE_AIRPORT_CODE,
                        PropertiesList::DEPARTURE_DATE,
                        PropertiesList::FLIGHT_NUMBER,
                        PropertiesList::DEPARTURE_GATE,
                        PropertiesList::TRAVELER_NAMES,
                        PropertiesList::SEATS,
                        PropertiesList::SPENT_AWARDS,
                        PropertiesList::TICKET_NUMBERS,
                        PropertiesList::TOTAL_CHARGE,
                    ] :
                    [
                        PropertiesList::DEPARTURE_DATE,
                        PropertiesList::ARRIVAL_DATE,
                        PropertiesList::DEPARTURE_GATE,
                        PropertiesList::SEATS,
                        PropertiesList::BAGGAGE_CLAIM,
                    ]
            );

        $excludedProperties = \array_merge(
            $isBlocksV2Enabled ?
                [
                    PropertiesList::ARRIVAL_GATE,
                    PropertiesList::ARRIVAL_TERMINAL,
                    PropertiesList::DEPARTURE_TERMINAL,
                ] : [],
            [
                PropertiesList::ARRIVAL_DATE,
                PropertiesList::ARRIVAL_ADDRESS,
                PropertiesList::ARRIVAL_NAME,
                PropertiesList::ARRIVAL_AIRPORT_CODE,

                PropertiesList::DEPARTURE_DATE,
                PropertiesList::DEPARTURE_ADDRESS,
                PropertiesList::DEPARTURE_NAME,
                PropertiesList::DEPARTURE_AIRPORT_CODE,
                PropertiesList::DEPARTURE_GATE,

                PropertiesList::FLIGHT_NUMBER,
                PropertiesList::BAGGAGE_CLAIM,
                PropertiesList::SEATS,
                PropertiesList::NOTES,
                PropertiesList::DURATION,
                PropertiesList::CONFIRMATION_NUMBER,
                PropertiesList::AIRLINE_NAME,
                PropertiesList::OPERATING_AIRLINE_NAME,
                PropertiesList::RETRIEVE_FROM,
                PropertiesList::CANCELLATION_POLICY,
                PropertiesList::CONFIRMATION_NUMBERS,
                PropertiesList::COMMENT,
            ],
            $this->excludedProperties->getValue()
        );

        if ($queryOptions->noPersonalData()) {
            $excludedProperties = array_merge($excludedProperties, $this->privateProperties->getValue());
        }

        $propertiesOrder = [
            PropertiesList::CONFIRMATION_NUMBERS,
            PropertiesList::CONFIRMATION_NUMBER,
            PropertiesList::AIRLINE_NAME,
            PropertiesList::OPERATING_AIRLINE_NAME,
            PropertiesList::FLIGHT_NUMBER,
            PropertiesList::DEPARTURE_TERMINAL,
            PropertiesList::DEPARTURE_AIRPORT_CODE,
            PropertiesList::DEPARTURE_DATE,
            PropertiesList::DEPARTURE_NAME,
            PropertiesList::DEPARTURE_GATE,
            PropertiesList::ARRIVAL_AIRPORT_CODE,
            PropertiesList::ARRIVAL_DATE,
            PropertiesList::ARRIVAL_GATE,
            PropertiesList::ARRIVAL_TERMINAL,
            PropertiesList::ARRIVAL_NAME,
            PropertiesList::BAGGAGE_CLAIM,
            PropertiesList::SEATS,
            PropertiesList::ACCOUNT_NUMBERS,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::FLIGHT_CABIN_CLASS,
            PropertiesList::BOOKING_CLASS,
            PropertiesList::DURATION,
            PropertiesList::EARNED_AWARDS,
            PropertiesList::TRAVELED_MILES,
            PropertiesList::AIRCRAFT,
            PropertiesList::MEAL,
            PropertiesList::RESERVATION_DATE,
            PropertiesList::SPENT_AWARDS,
            PropertiesList::COST,
            PropertiesList::FEES,
            PropertiesList::DISCOUNT,
            PropertiesList::TOTAL_CHARGE,
        ];

        // Show more >>
        $extPropBlocks = $this->blockHelper->getExtPropertiesBlocks(
            $item,
            $formatOptions,
            $excludedProperties,
            $diffTrackedProperties,
            $propertiesOrder,
            !$queryOptions->noPersonalData()
        );

        // Notes
        $this->blockHelper->formatNotes($item, $formatted, $queryOptions, $extPropBlocks);

        if ($extPropBlocks) {
            $formatted->addBlocksOrFold($extPropBlocks, ($formatOptions->supports(FormatHandler::NO_SHOW_MORE)) ? 100 : 7);
        }

        if (
            !$queryOptions->noPersonalData()
            && $formatOptions->supports(FormatHandler::SAVINGS)
            && ($mileValue = $item->getMileValue())
        ) {
            $savingsPrices = MileValue::CUSTOM_PICK_CHEAPEST == $mileValue->CustomPick ?
                [
                    'cost' => $mileValue->AlternativeCost,
                    'mileValue' => $mileValue->MileValue,
                ] :
                [
                    'cost' => $mileValue->CustomAlternativeCost,
                    'mileValue' => $mileValue->CustomMileValue,
                ];
            $savingsPrices['cost'] = $this->localizer->formatCurrency(
                $savingsPrices['cost'],
                BlockFactory::DEFAULT_CURRENCY,
                false
            );
            $savingsPrices['mileValue'] .= ProviderMileValueItem::CURRENCY_CENT;
            $savingsPrices['tripSegmentId'] = $source->getId();
            $formatted->blocks[] = Block::fromKindNameValue(
                Block::KIND_SAVINGS,
                $this->translator->trans('savings'),
                $savingsPrices
            );
        }

        // For your overseas trip...
        // [Personal Cards] ... [Business Cards] ...
        if ($formatOptions->supports(FormatHandler::NOT_FEES_CARDS)
            && ItineraryUtil::isOverseasTravel($source->getGeoTags(), $item->isOverseasTrip())
        ) {
            if (!empty($this->noForeignFeesCards)) {
                $formatted->blocks[] = new Block(Block::KIND_NO_FOREIGN_FEES_CARDS, '', '', $this->noForeignFeesCards);
            } elseif (null === $this->noForeignFeesCards) {
                $notFeesData = $this->noForeignFeesCardsQuery->getCards($item->getItinerary()->getUser()->getId(),
                    false);

                if (null === $notFeesData) {
                    $this->noForeignFeesCards = false;
                } else {
                    foreach ($notFeesData as $key => $cards) {
                        if (!in_array($key, ['business', 'personal', 'list'])) {
                            continue;
                        }

                        foreach ($cards as $index => $card) {
                            $notFeesData[$key][$index]['image'] = $this->legacyUrlGenerator->generateAbsoluteUrl($notFeesData[$key][$index]['image']);
                        }
                    }

                    $formatted->blocks[] = new Block(
                        Block::KIND_NO_FOREIGN_FEES_CARDS,
                        '',
                        '',
                        $this->noForeignFeesCards = $notFeesData
                    );
                }
            }
        }

        $formatted->menu = $this->formatAirMenuProperties(new Components\Menu\AirMenu(), $item, $formatOptions, $queryOptions->noPersonalData());
        $this->blockHelper->formatConfirmChanges($item, $formatted, $formatOptions);

        if (
            $formatOptions->supports(FormatHandler::PARKINGS_ADS)
            && (null !== $spotHeroUrl = Utils::parkingUrl($source->getArrgeotagid(), $source->getArrivalDate(), '+1 hour', '+5 hours', $arrCode))
        ) {
            $formatted->menu->parkingUrl = $spotHeroUrl;
        }

        if (
            !$queryOptions->noPersonalData()
            && $formatOptions->supports(FormatHandler::SOURCES)
        ) {
            $this->blockHelper->formatSegmentSources($item, $formatted, $formatOptions);
        }

        $this->blockHelper->formatNoteFiles($item, $formatted, $formatOptions);

        /** @var Timeline\Item\AirTrip $item */
        if (
            $formatOptions->supports(FormatHandler::OFFER)
            && ($itinerary->getCategory() === TRIP_CATEGORY_AIR)
            && !empty($bookUrl = $item->getBookingUrl())
            && !empty($bookInfo = $item->getBookingInfo())
        ) {
            $block = Block::fromKindValue(Block::KIND_OFFER, $this->translator->trans('airbnb.ad.1'));
            $block->link = new Components\Link(
                $this->blockHelper->generateSafeUrl($bookUrl),
                $bookInfo
            );
            $formatted->blocks[] = $block;
        }

        return $formatted;
    }

    protected function formatAirMenuProperties(Components\Menu\AirMenu $airMenu, Timeline\Item\AbstractTrip $item, FeaturesBitSet $formatOptions, $clearPersonalData = false)
    {
        $this->blockHelper->formatBaseMenuProperties($airMenu, $item, $formatOptions, $clearPersonalData);

        if ($item instanceof Timeline\Item\AirTrip) {
            $altFlights = $item->getTripAlternatives();

            if (isset($altFlights)) {
                $airMenu->alternativeFlights = $altFlights;
            }
        }

        if (!StringUtils::isEmpty($boardingpassurl = $item->getSource()->getBoardingpassurl())) {
            $airMenu->boardingPassUrl = $boardingpassurl;
        }

        return $airMenu;
    }

    protected function addTerminalAndGateBlock(array $propertyNames, $direction, Formatted\SegmentItem $formatted, Timeline\Item\AbstractTrip $item)
    {
        $terminalPropertyName = $propertyNames[0];
        $gatePropertyName = $propertyNames[1] ?? null;
        $changes = $item->getChanges();

        $propertyGate = isset($gatePropertyName) ?
            $this->blockHelper->translateSegmentProperty($item, $gatePropertyName) :
            null;
        $propertyTerminal = $this->blockHelper->translateSegmentProperty($item, $terminalPropertyName);

        // ✈ Terminal 1 Gate 10B
        if ($propertyTerminal || $propertyGate) {
            $name = new Value\TerminalAndGate(
                $this->translator->trans(/** @Desc("Terminal") */ 'itineraries.trip.air.terminal', [], 'trips'),
                $this->translator->trans('itineraries.trip.air.gate', [], 'trips')
            );

            $value = new Value\TerminalAndGate(
                !empty($propertyTerminal) ? $propertyTerminal[1] : null,
                !empty($propertyGate) ? $propertyGate[1] : null
            );

            $oldValue = $changes ?
                new Value\TerminalAndGate(
                    !StringUtils::isEmpty($prevValue = $changes->getPreviousValue($terminalPropertyName)) ? $prevValue : null,
                    !StringUtils::isEmpty($prevValue = $changes->getPreviousValue($gatePropertyName)) ? $prevValue : null
                ) : null;

            $formatted->blocks[] = Block::fromNameIconValue(
                $name,
                ($propertyGate && $propertyTerminal) ?
                    Icon::TERMINAL_AND_GATE :
                    (
                        'arr' === $direction ?
                            Icon::ARRIVAL_TERMINAL :
                            Icon::DEPARTURE_TERMINAL
                    ),
                $value,
                ($oldValue && !BlockHelper::isEmpty($oldValue)) ? $oldValue : null
            );
        }
    }
}
