<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Timeline\Formatter\FormatHandlerGeneric;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\PlanItem;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\SegmentItem;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class FormatHandler extends FormatHandlerGeneric
{
    public const ALTERNATIVE_FLIGTHS = 1;
    public const FLIGHT_STATUS = 1 << 1;
    public const ACCOUNT_NUMBERS = 1 << 2;
    public const PARKINGS_ADS = 1 << 3;
    public const FILTER_PLANS_ITEMS = 1 << 4;
    public const GEO_NOTIFICATIONS = 1 << 5;
    public const OFFER = 1 << 6;
    public const OUT_URL = 1 << 7;
    public const FLIGHT_PROGRESS = 1 << 8;
    public const DETAILS_BLOCKS_V2 = 1 << 9;
    public const REGIONAL_SETTINGS = 1 << 10;
    public const CONFIRM_CHANGES = 1 << 11;
    public const TAXI_RIDE = 1 << 12;
    public const DESANITIZED_STRINGS = 1 << 13;
    public const NO_SHOW_MORE = 1 << 14;
    public const DETAILED_ITINERARIES_V2_INFO = 1 << 15;
    public const PARKINGS_ICON = 1 << 16;
    public const SAVINGS = 1 << 17;
    public const SOURCES = 1 << 18;
    public const CRUISE_LIST_ITEMS = 1 << 19;
    public const LOUNGES = 1 << 20;
    public const NOT_FEES_CARDS = 1 << 21;
    public const TRAVEL_PLAN_DURATION = 1 << 22;
    public const ITINERARY_FILES = 1 << 23;
    public const AI_WARNING = 1 << 24;
    public const ITINERARY_NOTE_AND_FILES = 1 << 25;
    public const SOURCES_TRIPIT = 1 << 26;
    public const TRIP_TITLE_POINT = 1 << 27;
    public const MOVE_TRAVEL_SEGMENTS = 1 << 28;
    public const LOUNGES_OFFLINE = 1 << 29;
    public const READABLE_TRIP_AND_RESTAURANT_DATES = 1 << 30;

    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var GeofencesHelper
     */
    private $geofencesHelper;
    /**
     * @var AlternativeFlightsUtils
     */
    private $alternativeFlightsHelper;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        ApiVersioningService $apiVersioning,
        GeofencesHelper $geofencesHelper,
        AlternativeFlightsUtils $alternativeFlightsHelper,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->apiVersioning = $apiVersioning;
        $this->geofencesHelper = $geofencesHelper;
        $this->alternativeFlightsHelper = $alternativeFlightsHelper;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Item\ItemInterface[] $items
     * @return mixed
     */
    public function handle(array $items, QueryOptions $options): array
    {
        $formatOptions = $options->getFormatOptions();

        if ($formatOptions->supports(self::ALTERNATIVE_FLIGTHS)) {
            $this->alternativeFlightsHelper->schedule(
                array_filter(
                    $items,
                    fn (Item\ItemInterface $item) =>
                        $item instanceof Item\AirTrip
                        && !empty($item->getSource()->getDepcode())
                        && !empty($item->getSource()->getArrcode())
                ),
                $formatOptions
            );
        }

        if ($formatOptions->supports(self::FILTER_PLANS_ITEMS)) {
            $items = array_values(array_filter($items, function (Item\ItemInterface $item) {
                return !(
                    $item instanceof Item\PlanStart
                    || $item instanceof Item\PlanEnd
                );
            }));
        }

        /** @var Formatted\SegmentItem[] $formattedSegments */
        $formattedSegments = parent::handle($items, $options);

        if ($formatOptions->supports(self::DETAILS_BLOCKS_V2)) {
            // Add endDate for meta-segments(date, layover, plans)
            // use endDate of next non-meta segment
            $formattedSegmentsMaxIndex = count($formattedSegments) - 1;

            for ($i = $formattedSegmentsMaxIndex; $i >= 0; $i--) {
                if (!in_array($formattedSegments[$i]->type, ['date', 'layover', 'planStart', 'planEnd'], true)) {
                    continue;
                }

                if (
                    ($i === $formattedSegmentsMaxIndex)
                    && !isset($formattedSegments[$i]->endDate)
                ) {
                    $formattedSegments[$i]->endDate = clone $formattedSegments[$i]->startDate;
                    $formattedSegments[$i]->endDate->old = null;

                    continue;
                }

                for ($j = $i + 1; $j <= $formattedSegmentsMaxIndex; $j++) {
                    if (
                        isset($formattedSegments[$j]->endDate)
                        && !isset($formattedSegments[$i]->endDate)
                    ) {
                        $formattedSegments[$i]->endDate = clone $formattedSegments[$j]->endDate;
                        $formattedSegments[$i]->endDate->old = null;

                        break;
                    }
                }
            }
        }

        $this->fillCreatePlanFlag($formattedSegments);

        return $formattedSegments;
    }

    /**
     * @param SegmentItem[]|PlanItem[] $items
     */
    private function fillCreatePlanFlag(array $items): void
    {
        $createPlan = false;

        foreach ($items as $item) {
            if ($item->type === 'date') {
                $createPlan = $item->createPlan;
            } elseif (!($item instanceof PlanItem)) {
                $item->createPlan = $createPlan;
            }
        }
    }
}
