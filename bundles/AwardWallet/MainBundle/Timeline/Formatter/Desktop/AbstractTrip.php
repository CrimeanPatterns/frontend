<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\DesktopFormatterFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\MileValue\MileValueAlternativeFlights;
use AwardWallet\MainBundle\Timeline\Formatter\Origin;
use AwardWallet\MainBundle\Timeline\Formatter\Utils\ParkingHeaderResolver;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\AbstractTrip as AbstractTripItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\Util\TripHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTrip extends AbstractItinerary
{
    protected TripHelper $tripHelper;

    protected MileValueAlternativeFlights $mileValueAlternativeFlights;

    public function __construct(
        TripHelper $tripHelper,
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        DateTimeIntervalFormatter $intervalFormatter,
        DesktopFormatterFactory $desktopFormatterFactory,
        Origin $originFormatter,
        ParkingHeaderResolver $parkingHeaderResolver,
        MileValueAlternativeFlights $mileValueAlternativeFlights
    ) {
        parent::__construct(
            $localizeService,
            $translator,
            $urlGenerator,
            $authorizationChecker,
            $tokenStorage,
            $intervalFormatter,
            $desktopFormatterFactory,
            $originFormatter,
            $parkingHeaderResolver
        );

        $this->tripHelper = $tripHelper;
        $this->mileValueAlternativeFlights = $mileValueAlternativeFlights;
    }

    /**
     * @param AbstractTripItem $item
     */
    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        $result = parent::format($item, $queryOptions);

        $persistedTripInfo = $item->getTripInfo();
        $formatter = $item->getContext()->getPropFormatter();

        $result['endTimezone'] = $item->getEndDate()->format("T");

        // [1] тут будет ничего или ticketing airline
        if ($persistedTripInfo->secondaryTripNumberInfo && StringUtils::isNotEmpty($persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyName ?? null)) {
            $result['ticketed'] = [
                'airline' => [
                    'name' => $persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyName,
                    'code' => $persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyCode ?? '',
                ],
                'flightNumber' => $persistedTripInfo->secondaryTripNumberInfo->tripNumber ?? '',
            ];

            // [2] if (operationAirline != ticketingAirline && operationAirline.confirmationNumber != null
            //     then we put ticketingAirline.confirmationNumber here
            if (
                $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyCode !== $persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyCode
                && null === $persistedTripInfo->primaryTripNumberInfo->tripNumber
            ) {
                $result['ticketed']['confirmationNumber'] = $persistedTripInfo->secondaryConfirmationNumber ?? '';
            }
        }

        // [3] if (operatingAirline != ticketingAirline) && operationAirline.confirmationNumber == null
        //     the we put ticketingAirline name and ticketingAirline.confirmationNumber here
        if (
            StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            && StringUtils::isNotEmpty($persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyName ?? null)
            && $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName !== $persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyName
            && StringUtils::isEmpty($persistedTripInfo->primaryConfirmationNumberInfo->confirmationNumber ?? null)
        ) {
            $result['confirmationNumberAirline'] = $persistedTripInfo->secondaryTripNumberInfo->companyInfo->companyName;
            // [4]
            $item->setConfNo($persistedTripInfo->secondaryConfirmationNumber);
        }

        /** @var Tripsegment $segment */
        $source = $item->getSource();
        $result['deleted'] = $result['deleted'] || $item->getSource()->getHidden();

        foreach ($result['details']['columns'] as $k => $column) {
            if (!in_array($k, [0, 2])) {
                continue;
            }

            foreach ($column['rows'] as $row) {
                if (isset($row['prevTime']) || isset($row['prevDate']) || isset($row['prevValue'])) {
                    $result['changed'] = true;

                    break 2;
                }
            }
        }

        if (!empty($result['details']['columns'][0]['rows'][1]['prevTime'])) {
            $result['prevTime'] = $result['details']['columns'][0]['rows'][1]['prevTime'];
        }

        $segments = $source->getTripid()->getSegments()->count();

        if ($segments > 1) {
            $result['segments'] = $segments;
        }

        $alternativeFlights = $this->mileValueAlternativeFlights->getTimelineFields($item->getMileValue());

        if (!empty($alternativeFlights)) {
            $result = array_merge($result, $alternativeFlights);
        }

        return $result;
    }

    /**
     * @param AbstractTripItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        $formatter = $item->getContext()->getPropFormatter();
        $source = $item->getSource();
        $props = [
            PropertiesList::CRUISE_NAME,
            PropertiesList::DECK,
            PropertiesList::SHIP_CABIN_NUMBER,
            PropertiesList::SHIP_CODE,
            PropertiesList::SHIP_NAME,
            PropertiesList::SHIP_CABIN_CLASS,
            PropertiesList::IS_SMOKING,
            PropertiesList::STOPS_COUNT,
            PropertiesList::FARE_BASIS,
            PropertiesList::TRAIN_SERVICE_NAME,
            PropertiesList::TRAIN_CAR_NUMBER,
            PropertiesList::ADULTS_COUNT,
            PropertiesList::KIDS_COUNT,
            PropertiesList::TRAVELER_NAMES,
            PropertiesList::AIRCRAFT,
            PropertiesList::ACCOMMODATIONS,
            PropertiesList::VESSEL,
            PropertiesList::PETS,
            PropertiesList::VEHICLES,
            PropertiesList::COMMENT,
        ];
        $result = \array_merge(
            $result,
            $formatter->getExistingValues($props)
        );

        $column1 = [
            [
                'type' => 'airport',
                'text' => ['place' => $source->getDepAirportName(false), 'code' => $source->getDepcode()],
            ],
            [
                'type' => 'datetime',
                'time' => $this->localizeService->formatDateTime($item->getLocalDate(), null),
                'date' => $this->localizeService->formatDateTime($item->getLocalDate(), 'medium', null),
                'timestamp' => date_timestamp_get($item->getStartDate()),
                'timezone' => $item->getStartDate()->format("T"),
                'formattedDate' => $item->getLocalDate()->format("Y-m-d"),
            ],
        ];
        $column2 = [
            [
                'type' => 'airport',
                'text' => ['place' => $source->getArrAirportName(false), 'code' => $source->getArrcode()],
            ],
            [
                'type' => 'datetime',
                'time' => $this->localizeService->formatDateTime($source->getArrivalDate(), null),
                'date' => $this->localizeService->formatDateTime($source->getArrivalDate(), 'medium', null),
                'timestamp' => date_timestamp_get($item->getEndDate()),
                'timezone' => $item->getEndDate()->format("T"),
                'arrivalDay' => $this->localizeService->formatDateTime($this->getArrivalDay($item), 'medium', null),
                'formattedDate' => $this->getArrivalDay($item)->format("Y-m-d"),
            ],
        ];

        if ($item->isChanged()) {
            foreach ([PropertiesList::DEPARTURE_DATE => &$column1, PropertiesList::ARRIVAL_DATE => &$column2] as $field => &$column) {
                $prev = $item->getChanges()->getPreviousValue($field);

                if (!empty($prev)) {
                    $prevTimeValue = $this->localizeService->formatDateTime($prev, null);

                    if ($prevTimeValue != $column[1]['time']) {
                        $column[1]['prevTime'] = $prevTimeValue;
                    }

                    $date = $this->localizeService->formatDateTime($prev, 'medium', null);

                    if ($date != $column[1]['date']) {
                        $column[1]['prevDate'] = $date;
                    }
                }
            }
        }

        if ($this->authorizationChecker->isGranted('EDIT', $item->getItinerary())) {
            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::DEPARTURE_TERMINAL,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::DEPARTURE_TERMINAL, $source->getType()),
                            [],
                            'trips'
                        ),
                        'departure-terminal',
                        $item
                    )
                )
            ) {
                $column1[] = $field;
            }

            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::ARRIVAL_TERMINAL,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::ARRIVAL_TERMINAL, $source->getType()),
                            [],
                            'trips'
                        ),
                        'arrival-terminal',
                        $item
                    )
                )
            ) {
                $column2[] = $field;
            }

            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::DEPARTURE_GATE,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::DEPARTURE_GATE, $source->getType()),
                            [],
                            'trips'
                        ),
                        'gate',
                        $item
                    )
                )
            ) {
                $column1[] = $field;
            }

            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::ARRIVAL_GATE,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::ARRIVAL_GATE, $source->getType()),
                            [],
                            'trips'
                        ),
                        'gate',
                        $item
                    )
                )
            ) {
                $column2[] = $field;
            }

            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::BAGGAGE_CLAIM,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::BAGGAGE_CLAIM, $source->getType()),
                            [],
                            'trips'
                        ),
                        'baggage',
                        $item
                    )
                )
            ) {
                $column2[] = $field;
            }

            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::SEATS,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::SEATS, $source->getType()),
                            [],
                            'trips'
                        ),
                        'seats',
                        $item
                    )
                )
            ) {
                $column1[] = $field;
            }

            if (
                !is_null(
                    $field = $this->formatForColumn(
                        PropertiesList::DURATION,
                        $this->translator->trans(/** @Ignore */
                            PropertiesList::getTranslationKeyForProperty(PropertiesList::DURATION, $source->getType()),
                            [],
                            'trips'
                        ),
                        null,
                        $item
                    )
                )
            ) {
                $column2[] = $field;
            }
        }

        $result = array_merge($result, [
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => $column1,
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => $column2,
                ],
            ],
        ]);

        if ($this->authorizationChecker->isGranted('EDIT', $item->getItinerary())) {
            $privateProps = [
                PropertiesList::TICKET_NUMBERS,
                PropertiesList::TRAVELED_MILES,
                PropertiesList::MEAL,
                PropertiesList::BOOKING_CLASS,
                PropertiesList::FLIGHT_CABIN_CLASS,
            ];
            $result = \array_merge(
                $result,
                $formatter->getExistingValues($privateProps)
            );
        }

        return $result;
    }

    /**
     * @param AbstractTripItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        $resolvedTripInfo = $this->tripHelper->resolveFlightName($item);
        $persistedTripInfo = $item->getTripInfo();

        $title = [];
        $title['companyName'] = StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            ? $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName
            : '';
        $title['companyCode'] = StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyCode ?? null)
            ? "({$persistedTripInfo->primaryTripNumberInfo->companyInfo->companyCode})"
            : '';

        if (
            empty($title['companyCode'])
            && StringUtils::isNotEmpty($resolvedTripInfo->getIataCode() ?? null)
            && StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            && $resolvedTripInfo->getAirlineName() === $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName
        ) {
            $title['companyCode'] = "({$resolvedTripInfo->getIataCode()})";
        }

        if (StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->tripNumber ?? null)) {
            $title['tripNumber'] = $persistedTripInfo->primaryTripNumberInfo->tripNumber;
        } elseif (
            StringUtils::isNotEmpty($resolvedTripInfo->getFlightNumber() ?? null)
            && StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            && $resolvedTripInfo->getAirlineName() === $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName
        ) {
            $title['tripNumber'] = $resolvedTripInfo->getFlightNumber();
        }

        return trim(implode(' ', $title));
    }

    protected function getArrivalDay(AbstractTripItem $item)
    {
        $arrivalHour = $item->getEndDate()->format('H');
        $arrivalDate = clone $item->getEndDate();

        if ($arrivalHour >= 0 && $arrivalHour <= 4) {
            $arrivalDate->modify('-1 day');
        }

        return $arrivalDate;
    }

    protected function formatForColumn(
        string $code,
        string $label,
        ?string $icon,
        AbstractTripItem $item
    ): ?array {
        $formatter = $item->getContext()->getPropFormatter();
        $value = $formatter->getValue($code);

        if (StringHandler::isEmpty($value)) {
            return null;
        }

        $row = [
            'type' => 'pair',
            'name' => $label,
            'value' => $value,
        ];

        if (!empty($icon)) {
            $row['icon'] = $icon;
        }

        if ($item->isChanged()) {
            $prevValue = $formatter->getPreviousValue($code);

            if (!StringHandler::isEmpty($prevValue) && $prevValue != $value) {
                $row['prevValue'] = $prevValue;
            }
        }

        return $row;
    }

    protected function getDetailsOrder(): array
    {
        return PropertiesList::$tripPropertiesOrder;
    }

    protected function showAIWarning(): bool
    {
        return true;
    }
}
