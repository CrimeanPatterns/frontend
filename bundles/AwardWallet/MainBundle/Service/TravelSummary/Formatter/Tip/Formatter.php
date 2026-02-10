<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Formatter\Tip;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\TravelSummary\Data\PeriodDatesResult;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Formatter implements TranslationContainerInterface
{
    private const TRANSLATION_DOMAIN = 'trips';

    private const CATEGORY_YEAR_TO_DATE = 1;
    private const CATEGORY_LAST_YEAR = 2;
    private const CATEGORY_PREVIOUS_YEAR = 3;
    private const CATEGORY_ALL_TIME = 4;
    private const CATEGORY_SELECTED_YEAR = 5;

    private LocalizeService $localizer;
    private TranslatorInterface $translator;

    private array $tips = [
        'travel-summary.tip.plural.years' => "%years% year",
        'travel-summary.tip.plural.countries' => ["1 country", "%countries% countries"],
        'travel-summary.tip.plural.cities' => ["1 city", "%cities% cities"],
        'travel-summary.tip.plural.continents' => ["1 continent", "%continents% continents"],
        'travel-summary.tip.plural.miles' => "%miles% mile",
        'travel-summary.tip.plural.times' => "%times% time",
        'travel-summary.tip.visits.this-year.inc.you' => "This year so far, you visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% more than the previous year.",
        'travel-summary.tip.visits.this-year.inc.user' => "This year so far, %user% visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% more than the previous year.",
        'travel-summary.tip.visits.this-year.dec.you' => "This year so far, you visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% less than the previous year.",
        'travel-summary.tip.visits.this-year.dec.user' => "This year so far, %user% visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% less than the previous year.",
        'travel-summary.tip.visits.this-year.same.you' => "This year so far, you visited %numberVisitedPlural%, the same as the previous year.",
        'travel-summary.tip.visits.this-year.same.user' => "This year so far, %user% visited %numberVisitedPlural%, the same as the previous year.",
        'travel-summary.tip.visits.last-year.inc.you' => "Last year, you visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% more than the previous year.",
        'travel-summary.tip.visits.last-year.inc.user' => "Last year, %user% visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% more than the previous year.",
        'travel-summary.tip.visits.last-year.dec.you' => "Last year, you visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% less than the previous year.",
        'travel-summary.tip.visits.last-year.dec.user' => "Last year, %user% visited %numberVisitedPlural%, which is %numberVisitedPrevPlural% less than the previous year.",
        'travel-summary.tip.visits.last-year.same.you' => "Last year, you visited %numberVisitedPlural%, the same as the previous year.",
        'travel-summary.tip.visits.last-year.same.user' => "Last year, %user% visited %numberVisitedPlural%, the same as the previous year.",
        'travel-summary.tip.visits.prev-year.you' => "In the last %numberYearsPlural%, you visited %numberVisitedPlural%.",
        'travel-summary.tip.visits.prev-year.user' => "In the last %numberYearsPlural%, %user% visited %numberVisitedPlural%.",
        'travel-summary.tip.visits.all-time.you' => "Since %year%, you visited %numberVisitedPlural%.",
        'travel-summary.tip.visits.all-time.user' => "Since %year%, %user% visited %numberVisitedPlural%.",
        'travel-summary.tip.visits.selected-year.you' => "In %year%, you visited %numberVisitedPlural%.",
        'travel-summary.tip.visits.selected-year.user' => "In %year%, %user% visited %numberVisitedPlural%.",
        'travel-summary.tip.distance.this-year.inc.you' => "In total, you've traveled %numberMilesPlural% this year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% more compared to the previous year.",
        'travel-summary.tip.distance.this-year.inc.user' => "In total, %user% has traveled %numberMilesPlural% this year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% more compared to the previous year.",
        'travel-summary.tip.distance.this-year.dec.you' => "In total, you've traveled %numberMilesPlural% this year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% less compared to the previous year.",
        'travel-summary.tip.distance.this-year.dec.user' => "In total, %user% has traveled %numberMilesPlural% this year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% less compared to the previous year.",
        'travel-summary.tip.distance.this-year.same.you' => "In total, you've traveled %numberMilesPlural% this year (the equivalent of %numberTimesPlural% around the world), the same as the previous year.",
        'travel-summary.tip.distance.this-year.same.user' => "In total, %user% has traveled %numberMilesPlural% this year (the equivalent of %numberTimesPlural% around the world), the same as the previous year.",
        'travel-summary.tip.distance.last-year.inc.you' => "In total, you've traveled %numberMilesPlural% last year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% more compared to the previous year.",
        'travel-summary.tip.distance.last-year.inc.user' => "In total, %user% has traveled %numberMilesPlural% last year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% more compared to the previous year.",
        'travel-summary.tip.distance.last-year.dec.you' => "In total, you've traveled %numberMilesPlural% last year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% less compared to the previous year.",
        'travel-summary.tip.distance.last-year.dec.user' => "In total, %user% has traveled %numberMilesPlural% last year (the equivalent of %numberTimesPlural% around the world), which is %numberMilesPrevPlural% less compared to the previous year.",
        'travel-summary.tip.distance.last-year.same.you' => "In total, you've traveled %numberMilesPlural% last year (the equivalent of %numberTimesPlural% around the world), the same as the previous year.",
        'travel-summary.tip.distance.last-year.same.user' => "In total, %user% has traveled %numberMilesPlural% last year (the equivalent of %numberTimesPlural% around the world), the same as the previous year.",
        'travel-summary.tip.distance.prev-year.you' => "In total, you've traveled %numberMilesPlural% in %numberYearsPlural% (the equivalent of %numberTimesPlural% around the world).",
        'travel-summary.tip.distance.prev-year.user' => "In total, %user% has traveled %numberMilesPlural% in %numberYearsPlural% (the equivalent of %numberTimesPlural% around the world).",
        'travel-summary.tip.distance.all-time.you' => "In total, you've traveled %numberMilesPlural% since %year% (the equivalent of %numberTimesPlural% around the world).",
        'travel-summary.tip.distance.all-time.user' => "In total, %user% has traveled %numberMilesPlural% since %year% (the equivalent of %numberTimesPlural% around the world).",
        'travel-summary.tip.distance.selected-year.you' => "In total, you've traveled %numberMilesPlural% in %year% (the equivalent of %numberTimesPlural% around the world).",
        'travel-summary.tip.distance.selected-year.user' => "In total, %user% has traveled %numberMilesPlural% in %year% (the equivalent of %numberTimesPlural% around the world).",

        // Airports
        'travel-summary.tip.airports.this-year.you' => "You've been to %airportCode% airport %numberTimesPlural% this year.",
        'travel-summary.tip.airports.this-year.user' => "%user% has been to %airportCode% airport %numberTimesPlural% this year.",
        'travel-summary.tip.airports.last-year.you' => "You've been to %airportCode% airport %numberTimesPlural% last year.",
        'travel-summary.tip.airports.last-year.user' => "%user% has been to %airportCode% airport %numberTimesPlural% last year.",
        'travel-summary.tip.airports.prev-year.you' => "You've been to %airportCode% airport %numberTimesPlural% in the last %numberYearsPlural%.",
        'travel-summary.tip.airports.prev-year.user' => "%user% has been to %airportCode% airport %numberTimesPlural% in the last %numberYearsPlural%.",
        'travel-summary.tip.airports.all-time.you' => "You've been to %airportCode% airport %numberTimesPlural% since %year%.",
        'travel-summary.tip.airports.all-time.user' => "%user% has been to %airportCode% airport %numberTimesPlural% since %year%.",
        'travel-summary.tip.airports.selected-year.you' => "You've been to %airportCode% airport %numberTimesPlural% in %year%.",
        'travel-summary.tip.airports.selected-year.user' => "%user% has been to %airportCode% airport %numberTimesPlural% in %year%.",

        // Countries
        'travel-summary.tip.country.this-year.you' => "You've been to %country% %numberTimesPlural% this year.",
        'travel-summary.tip.country.this-year.user' => "%user% has been to %country% %numberTimesPlural% this year.",
        'travel-summary.tip.country.last-year.you' => "You've been to %country% %numberTimesPlural% last year.",
        'travel-summary.tip.country.last-year.user' => "%user% has been to %country% %numberTimesPlural% last year.",
        'travel-summary.tip.country.prev-year.you' => "You've been to %country% %numberTimesPlural% in the last %numberYearsPlural%.",
        'travel-summary.tip.country.prev-year.user' => "%user% has been to %country% %numberTimesPlural% in the last %numberYearsPlural%.",
        'travel-summary.tip.country.all-time.you' => "You've been to %country% %numberTimesPlural% since %year%.",
        'travel-summary.tip.country.all-time.user' => "%user% has been to %country% %numberTimesPlural% since %year%.",
        'travel-summary.tip.country.selected-year.you' => "You've been to %country% %numberTimesPlural% in %year%.",
        'travel-summary.tip.country.selected-year.user' => "%user% has been to %country% %numberTimesPlural% in %year%.",

        // Airlines
        'travel-summary.tip.airline.this-year.you' => "You've flown %airline% %numberTimesPlural% this year.",
        'travel-summary.tip.airline.this-year.user' => "%user% has flown %airline% %numberTimesPlural% this year.",
        'travel-summary.tip.airline.last-year.you' => "You've flown %airline% %numberTimesPlural% last year.",
        'travel-summary.tip.airline.last-year.user' => "%user% has flown %airline% %numberTimesPlural% last year.",
        'travel-summary.tip.airline.prev-year.you' => "You've flown %airline% %numberTimesPlural% in the last %numberYearsPlural%.",
        'travel-summary.tip.airline.prev-year.user' => "%user% has flown %airline% %numberTimesPlural% in the last %numberYearsPlural%.",
        'travel-summary.tip.airline.all-time.you' => "You've flown %airline% %numberTimesPlural% since %year%.",
        'travel-summary.tip.airline.all-time.user' => "%user% has flown %airline% %numberTimesPlural% since %year%.",
        'travel-summary.tip.airline.selected-year.you' => "You've flown %airline% %numberTimesPlural% in %year%.",
        'travel-summary.tip.airline.selected-year.user' => "%user% has flown %airline% %numberTimesPlural% in %year%.",
    ];

    public function __construct(LocalizeService $localizer, TranslatorInterface $translator)
    {
        $this->localizer = $localizer;
        $this->translator = $translator;
    }

    public function formatFlightsTakenTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.flights.current-year.same', $params),
                    new Translation('trips.tip.flights.current-year.same.user', $params),
                    new Translation('trips.tip.flights.current-year.more', $params),
                    new Translation('trips.tip.flights.current-year.more.user', $params),
                    new Translation('trips.tip.flights.current-year.less', $params),
                    new Translation('trips.tip.flights.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.flights.last-year.same', $params),
                    new Translation('trips.tip.flights.last-year.same.user', $params),
                    new Translation('trips.tip.flights.last-year.more', $params),
                    new Translation('trips.tip.flights.last-year.more.user', $params),
                    new Translation('trips.tip.flights.last-year.less', $params),
                    new Translation('trips.tip.flights.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.flights.previous-year', $params),
                    new Translation('trips.tip.flights.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.flights.all-time', $params),
                    new Translation('trips.tip.flights.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.flights.selected-year', $params),
                    new Translation('trips.tip.flights.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.flights.previous-year.additional', [
                'default' => true,
                'isVisible' => ($value > 10),
            ])
        );
    }

    public function formatHotelsTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];
        $months = floor($value / 30);

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.hotel-nights.current-year.same', $params),
                    new Translation('trips.tip.hotel-nights.current-year.same.user', $params),
                    new Translation('trips.tip.hotel-nights.current-year.more', $params),
                    new Translation('trips.tip.hotel-nights.current-year.more.user', $params),
                    new Translation('trips.tip.hotel-nights.current-year.less', $params),
                    new Translation('trips.tip.hotel-nights.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.hotel-nights.last-year.same', $params),
                    new Translation('trips.tip.hotel-nights.last-year.same.user', $params),
                    new Translation('trips.tip.hotel-nights.last-year.more', $params),
                    new Translation('trips.tip.hotel-nights.last-year.more.user', $params),
                    new Translation('trips.tip.hotel-nights.last-year.less', $params),
                    new Translation('trips.tip.hotel-nights.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.hotel-nights.previous-year', $params),
                    new Translation('trips.tip.hotel-nights.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.hotel-nights.all-time', $params),
                    new Translation('trips.tip.hotel-nights.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.hotel-nights.selected-year', $params),
                    new Translation('trips.tip.hotel-nights.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.hotel-nights.previous-year.additional', [
                'default' => true,
                'isVisible' => ($months > 0),
                '%count%' => $months,
                '%months%' => $months,
            ])
        );
    }

    public function formatRentalCarsTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];
        $months = floor($value / 30);

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.rental-car-days.current-year.same', $params),
                    new Translation('trips.tip.rental-car-days.current-year.same.user', $params),
                    new Translation('trips.tip.rental-car-days.current-year.more', $params),
                    new Translation('trips.tip.rental-car-days.current-year.more.user', $params),
                    new Translation('trips.tip.rental-car-days.current-year.less', $params),
                    new Translation('trips.tip.rental-car-days.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.rental-car-days.last-year.same', $params),
                    new Translation('trips.tip.rental-car-days.last-year.same.user', $params),
                    new Translation('trips.tip.rental-car-days.last-year.more', $params),
                    new Translation('trips.tip.rental-car-days.last-year.more.user', $params),
                    new Translation('trips.tip.rental-car-days.last-year.less', $params),
                    new Translation('trips.tip.rental-car-days.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.rental-car-days.previous-year', $params),
                    new Translation('trips.tip.rental-car-days.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.rental-car-days.all-time', $params),
                    new Translation('trips.tip.rental-car-days.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.rental-car-days.selected-year', $params),
                    new Translation('trips.tip.rental-car-days.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.rental-car-days.previous-year.additional', [
                'default' => true,
                'isVisible' => ($months > 0),
                '%count%' => $months,
                '%months%' => $months,
            ])
        );
    }

    public function formatParkingDaysTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];
        $months = floor($value / 30);

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.parking-days.current-year.same', $params),
                    new Translation('trips.tip.parking-days.current-year.same.user', $params),
                    new Translation('trips.tip.parking-days.current-year.more', $params),
                    new Translation('trips.tip.parking-days.current-year.more.user', $params),
                    new Translation('trips.tip.parking-days.current-year.less', $params),
                    new Translation('trips.tip.parking-days.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.parking-days.last-year.same', $params),
                    new Translation('trips.tip.parking-days.last-year.same.user', $params),
                    new Translation('trips.tip.parking-days.last-year.more', $params),
                    new Translation('trips.tip.parking-days.last-year.more.user', $params),
                    new Translation('trips.tip.parking-days.last-year.less', $params),
                    new Translation('trips.tip.parking-days.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.parking-days.previous-year', $params),
                    new Translation('trips.tip.parking-days.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.parking-days.all-time', $params),
                    new Translation('trips.tip.parking-days.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.parking-days.selected-year', $params),
                    new Translation('trips.tip.parking-days.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.parking-days.previous-year.additional', [
                'default' => true,
                'isVisible' => ($months > 0),
                '%count%' => $months,
                '%months%' => $months,
            ])
        );
    }

    public function formatCruisesTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];
        $weeks = floor($value / 7);

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.cruises.current-year.same', $params),
                    new Translation('trips.tip.cruises.current-year.same.user', $params),
                    new Translation('trips.tip.cruises.current-year.more', $params),
                    new Translation('trips.tip.cruises.current-year.more.user', $params),
                    new Translation('trips.tip.cruises.current-year.less', $params),
                    new Translation('trips.tip.cruises.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.cruises.last-year.same', $params),
                    new Translation('trips.tip.cruises.last-year.same.user', $params),
                    new Translation('trips.tip.cruises.last-year.more', $params),
                    new Translation('trips.tip.cruises.last-year.more.user', $params),
                    new Translation('trips.tip.cruises.last-year.less', $params),
                    new Translation('trips.tip.cruises.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.cruises.previous-year', $params),
                    new Translation('trips.tip.cruises.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.cruises.all-time', $params),
                    new Translation('trips.tip.cruises.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.cruises.selected-year', $params),
                    new Translation('trips.tip.cruises.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.cruises.previous-year.additional', [
                'default' => true,
                'isVisible' => ($weeks > 0),
                '%count%' => $weeks,
                '%weeks%' => $weeks,
            ])
        );
    }

    public function formatFerriesTakenTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.ferries-taken.current-year.same', $params),
                    new Translation('trips.tip.ferries-taken.current-year.same.user', $params),
                    new Translation('trips.tip.ferries-taken.current-year.more', $params),
                    new Translation('trips.tip.ferries-taken.current-year.more.user', $params),
                    new Translation('trips.tip.ferries-taken.current-year.less', $params),
                    new Translation('trips.tip.ferries-taken.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.ferries-taken.last-year.same', $params),
                    new Translation('trips.tip.ferries-taken.last-year.same.user', $params),
                    new Translation('trips.tip.ferries-taken.last-year.more', $params),
                    new Translation('trips.tip.ferries-taken.last-year.more.user', $params),
                    new Translation('trips.tip.ferries-taken.last-year.less', $params),
                    new Translation('trips.tip.ferries-taken.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.ferries-taken.previous-year', $params),
                    new Translation('trips.tip.ferries-taken.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.ferries-taken.all-time', $params),
                    new Translation('trips.tip.ferries-taken.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.ferries-taken.selected-year', $params),
                    new Translation('trips.tip.ferries-taken.selected-year.user', $params),
                ],
            ]
        );
    }

    public function formatBusRidesTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.bus-rides.current-year.same', $params),
                    new Translation('trips.tip.bus-rides.current-year.same.user', $params),
                    new Translation('trips.tip.bus-rides.current-year.more', $params),
                    new Translation('trips.tip.bus-rides.current-year.more.user', $params),
                    new Translation('trips.tip.bus-rides.current-year.less', $params),
                    new Translation('trips.tip.bus-rides.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.bus-rides.last-year.same', $params),
                    new Translation('trips.tip.bus-rides.last-year.same.user', $params),
                    new Translation('trips.tip.bus-rides.last-year.more', $params),
                    new Translation('trips.tip.bus-rides.last-year.more.user', $params),
                    new Translation('trips.tip.bus-rides.last-year.less', $params),
                    new Translation('trips.tip.bus-rides.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.bus-rides.previous-year', $params),
                    new Translation('trips.tip.bus-rides.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.bus-rides.all-time', $params),
                    new Translation('trips.tip.bus-rides.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.bus-rides.selected-year', $params),
                    new Translation('trips.tip.bus-rides.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.bus-rides.previous-year.additional', [
                'default' => true,
                'isVisible' => ($value > 10),
            ])
        );
    }

    public function formatRestaurantReservationsTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.restaurant-reservations.current-year.same', $params),
                    new Translation('trips.tip.restaurant-reservations.current-year.same.user', $params),
                    new Translation('trips.tip.restaurant-reservations.current-year.more', $params),
                    new Translation('trips.tip.restaurant-reservations.current-year.more.user', $params),
                    new Translation('trips.tip.restaurant-reservations.current-year.less', $params),
                    new Translation('trips.tip.restaurant-reservations.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.restaurant-reservations.last-year.same', $params),
                    new Translation('trips.tip.restaurant-reservations.last-year.same.user', $params),
                    new Translation('trips.tip.restaurant-reservations.last-year.more', $params),
                    new Translation('trips.tip.restaurant-reservations.last-year.more.user', $params),
                    new Translation('trips.tip.restaurant-reservations.last-year.less', $params),
                    new Translation('trips.tip.restaurant-reservations.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.restaurant-reservations.previous-year', $params),
                    new Translation('trips.tip.restaurant-reservations.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.restaurant-reservations.all-time', $params),
                    new Translation('trips.tip.restaurant-reservations.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.restaurant-reservations.selected-year', $params),
                    new Translation('trips.tip.restaurant-reservations.selected-year.user', $params),
                ],
            ]
        );
    }

    public function formatTrainRidesTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.train-rides.current-year.same', $params),
                    new Translation('trips.tip.train-rides.current-year.same.user', $params),
                    new Translation('trips.tip.train-rides.current-year.more', $params),
                    new Translation('trips.tip.train-rides.current-year.more.user', $params),
                    new Translation('trips.tip.train-rides.current-year.less', $params),
                    new Translation('trips.tip.train-rides.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.train-rides.last-year.same', $params),
                    new Translation('trips.tip.train-rides.last-year.same.user', $params),
                    new Translation('trips.tip.train-rides.last-year.more', $params),
                    new Translation('trips.tip.train-rides.last-year.more.user', $params),
                    new Translation('trips.tip.train-rides.last-year.less', $params),
                    new Translation('trips.tip.train-rides.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.train-rides.previous-year', $params),
                    new Translation('trips.tip.train-rides.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.train-rides.all-time', $params),
                    new Translation('trips.tip.train-rides.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.train-rides.selected-year', $params),
                    new Translation('trips.tip.train-rides.selected-year.user', $params),
                ],
            ],
            new Translation('trips.tip.train-rides.previous-year.additional', [
                'default' => true,
                'isVisible' => ($value > 10),
            ])
        );
    }

    public function formatEventsTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        $params = [
            'default' => true,
            '%user%' => $user->getUserName(),
            '%count%' => $value,
            '%count_difference%' => $diff,
            '%years%' => $this->getNumberYears($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation('trips.tip.events.current-year.same', $params),
                    new Translation('trips.tip.events.current-year.same.user', $params),
                    new Translation('trips.tip.events.current-year.more', $params),
                    new Translation('trips.tip.events.current-year.more.user', $params),
                    new Translation('trips.tip.events.current-year.less', $params),
                    new Translation('trips.tip.events.current-year.less.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation('trips.tip.events.last-year.same', $params),
                    new Translation('trips.tip.events.last-year.same.user', $params),
                    new Translation('trips.tip.events.last-year.more', $params),
                    new Translation('trips.tip.events.last-year.more.user', $params),
                    new Translation('trips.tip.events.last-year.less', $params),
                    new Translation('trips.tip.events.last-year.less.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation('trips.tip.events.previous-year', $params),
                    new Translation('trips.tip.events.previous-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation('trips.tip.events.all-time', $params),
                    new Translation('trips.tip.events.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation('trips.tip.events.selected-year', $params),
                    new Translation('trips.tip.events.selected-year.user', $params),
                ],
            ]
        );
    }

    public function formatCountriesTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        return $this->translateLocationBlock(
            $user,
            $datesResult,
            $diff,
            new Plural(/** @Ignore */ 'travel-summary.tip.plural.countries', $value, ['%countries%' => $value]),
            $diff ? new Plural(/** @Ignore */ 'travel-summary.tip.plural.countries', $diff, ['%countries%' => $diff]) : null
        );
    }

    public function formatCitiesTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        return $this->translateLocationBlock(
            $user,
            $datesResult,
            $diff,
            new Plural(/** @Ignore */ 'travel-summary.tip.plural.cities', $value, ['%cities%' => $value]),
            $diff ? new Plural(/** @Ignore */ 'travel-summary.tip.plural.cities', $diff, ['%cities%' => $diff]) : null
        );
    }

    public function formatContinentsTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff): string
    {
        return $this->translateLocationBlock(
            $user,
            $datesResult,
            $diff,
            new Plural(/** @Ignore */ 'travel-summary.tip.plural.continents', $value, ['%continents%' => $value]),
            $diff ? new Plural(/** @Ignore */ 'travel-summary.tip.plural.continents', $diff, ['%continents%' => $diff]) : null
        );
    }

    public function formatDistanceTip(User $user, PeriodDatesResult $datesResult, int $value, ?int $diff, float $numberTimes): string
    {
        $params = [
            '%user%' => $user->getUserName(),
            '%numberMilesPlural%' => new Plural(/** @Ignore */ 'travel-summary.tip.plural.miles', $value, ['%miles%' => $value]),
            '%numberMilesPrevPlural%' => $diff ? new Plural(/** @Ignore */ 'travel-summary.tip.plural.miles', $diff, ['%miles%' => $diff]) : null,
            '%numberYearsPlural%' => $this->getNumberYearsPlural($datesResult->getCurrentPeriod()),
            '%numberTimesPlural%' => new Plural(/** @Ignore */ 'travel-summary.tip.plural.times', $numberTimes, ['%times%' => $numberTimes]),
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.this-year.same.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.this-year.same.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.this-year.inc.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.this-year.inc.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.this-year.dec.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.this-year.dec.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.last-year.same.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.last-year.same.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.last-year.inc.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.last-year.inc.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.last-year.dec.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.last-year.dec.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.prev-year.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.prev-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.all-time.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.selected-year.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.distance.selected-year.user', $params),
                ],
            ]
        );
    }

    public function formatAirportTip(User $user, PeriodDatesResult $datesResult, string $airportCode, int $times): ?string
    {
        $yearsPlural = $this->getNumberYearsPlural($datesResult->getCurrentPeriod());
        $params = [
            '%user%' => $user->getUserName(),
            '%airportCode%' => $airportCode,
            '%numberTimesPlural%' => $this->t(new Plural(/** @Ignore */ 'travel-summary.tip.plural.times', $times, ['%times%' => $times])),
            '%numberYearsPlural%' => $yearsPlural ? $this->t($yearsPlural) : null,
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateStatisticsTab($user, $datesResult->getCurrentPeriod(), [
            self::CATEGORY_YEAR_TO_DATE => [
                new Translation('travel-summary.tip.airports.this-year.you', $params),
                new Translation('travel-summary.tip.airports.this-year.user', $params),
            ],
            self::CATEGORY_LAST_YEAR => [
                new Translation('travel-summary.tip.airports.last-year.you', $params),
                new Translation('travel-summary.tip.airports.last-year.user', $params),
            ],
            self::CATEGORY_PREVIOUS_YEAR => [
                new Translation('travel-summary.tip.airports.prev-year.you', $params),
                new Translation('travel-summary.tip.airports.prev-year.user', $params),
            ],
            self::CATEGORY_ALL_TIME => [
                new Translation('travel-summary.tip.airports.all-time.you', $params),
                new Translation('travel-summary.tip.airports.all-time.user', $params),
            ],
            self::CATEGORY_SELECTED_YEAR => [
                new Translation('travel-summary.tip.airports.selected-year.you', $params),
                new Translation('travel-summary.tip.airports.selected-year.user', $params),
            ],
        ]);
    }

    public function formatCountryTip(User $user, PeriodDatesResult $datesResult, string $country, int $times): ?string
    {
        $yearsPlural = $this->getNumberYearsPlural($datesResult->getCurrentPeriod());
        $params = [
            '%user%' => $user->getUserName(),
            '%country%' => $country,
            '%numberTimesPlural%' => $this->t(new Plural(/** @Ignore */ 'travel-summary.tip.plural.times', $times, ['%times%' => $times])),
            '%numberYearsPlural%' => $yearsPlural ? $this->t($yearsPlural) : null,
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateStatisticsTab($user, $datesResult->getCurrentPeriod(), [
            self::CATEGORY_YEAR_TO_DATE => [
                new Translation('travel-summary.tip.country.this-year.you', $params),
                new Translation('travel-summary.tip.country.this-year.user', $params),
            ],
            self::CATEGORY_LAST_YEAR => [
                new Translation('travel-summary.tip.country.last-year.you', $params),
                new Translation('travel-summary.tip.country.last-year.user', $params),
            ],
            self::CATEGORY_PREVIOUS_YEAR => [
                new Translation('travel-summary.tip.country.prev-year.you', $params),
                new Translation('travel-summary.tip.country.prev-year.user', $params),
            ],
            self::CATEGORY_ALL_TIME => [
                new Translation('travel-summary.tip.country.all-time.you', $params),
                new Translation('travel-summary.tip.country.all-time.user', $params),
            ],
            self::CATEGORY_SELECTED_YEAR => [
                new Translation('travel-summary.tip.country.selected-year.you', $params),
                new Translation('travel-summary.tip.country.selected-year.user', $params),
            ],
        ]);
    }

    public function formatAirlineTip(User $user, PeriodDatesResult $datesResult, string $airline, int $times): ?string
    {
        $yearsPlural = $this->getNumberYearsPlural($datesResult->getCurrentPeriod());
        $params = [
            '%user%' => $user->getUserName(),
            '%airline%' => $airline,
            '%numberTimesPlural%' => $this->t(new Plural(/** @Ignore */ 'travel-summary.tip.plural.times', $times, ['%times%' => $times])),
            '%numberYearsPlural%' => $yearsPlural ? $this->t($yearsPlural) : null,
            '%year%' => $datesResult->getYear(),
        ];

        return $this->translateStatisticsTab($user, $datesResult->getCurrentPeriod(), [
            self::CATEGORY_YEAR_TO_DATE => [
                new Translation('travel-summary.tip.airline.this-year.you', $params),
                new Translation('travel-summary.tip.airline.this-year.user', $params),
            ],
            self::CATEGORY_LAST_YEAR => [
                new Translation('travel-summary.tip.airline.last-year.you', $params),
                new Translation('travel-summary.tip.airline.last-year.user', $params),
            ],
            self::CATEGORY_PREVIOUS_YEAR => [
                new Translation('travel-summary.tip.airline.prev-year.you', $params),
                new Translation('travel-summary.tip.airline.prev-year.user', $params),
            ],
            self::CATEGORY_ALL_TIME => [
                new Translation('travel-summary.tip.airline.all-time.you', $params),
                new Translation('travel-summary.tip.airline.all-time.user', $params),
            ],
            self::CATEGORY_SELECTED_YEAR => [
                new Translation('travel-summary.tip.airline.selected-year.you', $params),
                new Translation('travel-summary.tip.airline.selected-year.user', $params),
            ],
        ]);
    }

    /**
     * @return Message[]
     */
    public static function getTranslationMessages()
    {
        $domain = self::TRANSLATION_DOMAIN;

        return [
            // Flights
            (new Message('trips.tip.flights.current-year.same', $domain))->setDesc('Sticking to your usual travel pace — %count% flight, just like last year.|Sticking to your usual travel pace — %count% flights, just like last year.'),
            (new Message('trips.tip.flights.current-year.same.user', $domain))->setDesc('Sticking to the usual travel pace — %count% flight, just like last year.|Sticking to the usual travel pace — %count% flights, just like last year.'),
            (new Message('trips.tip.flights.current-year.more', $domain))->setDesc('Jet-setting more than ever! You\'ve taken %count% flight so far — %count_difference% more than last year. 🌍🛫|Jet-setting more than ever! You\'ve taken %count% flights so far — %count_difference% more than last year. 🌍🛫'),
            (new Message('trips.tip.flights.current-year.more.user', $domain))->setDesc('Jet-setting more than ever! %user% has taken %count% flight so far — %count_difference% more than last year. 🌍🛫|Jet-setting more than ever! %user% has taken %count% flights so far — %count_difference% more than last year. 🌍🛫'),
            (new Message('trips.tip.flights.current-year.less', $domain))->setDesc('Traveling a bit less? %count% flight taken — %count_difference% fewer than last year. 🛄|Traveling a bit less? %count% flights taken — %count_difference% fewer than last year. 🛄'),
            (new Message('trips.tip.flights.current-year.less.user', $domain))->setDesc('Traveling a bit less? %count% flight taken — %count_difference% fewer than last year. 🛄|Traveling a bit less? %count% flights taken — %count_difference% fewer than last year. 🛄'),
            (new Message('trips.tip.flights.last-year.same', $domain))->setDesc('No surprises — %count% flight, the same as the year before.|No surprises — %count% flights, the same as the year before.'),
            (new Message('trips.tip.flights.last-year.same.user', $domain))->setDesc('No surprises — %count% flight, the same as the year before.|No surprises — %count% flights, the same as the year before.'),
            (new Message('trips.tip.flights.last-year.more', $domain))->setDesc('Last year, you took %count% flight — %count_difference% more than the year before! ☁️🛩️✨|Last year, you took %count% flights — %count_difference% more than the year before! ☁️🛩️✨'),
            (new Message('trips.tip.flights.last-year.more.user', $domain))->setDesc('Last year, %user% took %count% flight — %count_difference% more than the year before! ☁️🛩️✨|Last year, %user% took %count% flights — %count_difference% more than the year before! ☁️🛩️✨'),
            (new Message('trips.tip.flights.last-year.less', $domain))->setDesc('Last year, you flew %count% time — %count_difference% fewer than the previous year.|Last year, you flew %count% times — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.flights.last-year.less.user', $domain))->setDesc('Last year, %user% flew %count% time — %count_difference% fewer than the previous year.|Last year, %user% flew %count% times — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.flights.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve taken %count% flight|Over the past %years% years, you\'ve taken %count% flights'),
            (new Message('trips.tip.flights.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has taken %count% flight|Over the past %years% years, %user% has taken %count% flights'),
            (new Message('trips.tip.flights.all-time', $domain))->setDesc('Since %year%, you\'ve taken %count% flight|Since %year%, you\'ve taken %count% flights'),
            (new Message('trips.tip.flights.all-time.user', $domain))->setDesc('Since %year%, %user% has taken %count% flight|Since %year%, %user% has taken %count% flights'),
            (new Message('trips.tip.flights.selected-year', $domain))->setDesc('In %year%, you\'ve taken %count% flight|In %year%, you\'ve taken %count% flights'),
            (new Message('trips.tip.flights.selected-year.user', $domain))->setDesc('In %year%, %user% has taken %count% flight|In %year%, %user% has taken %count% flights'),
            (new Message('trips.tip.flights.previous-year.additional', $domain))->setDesc('that\'s a lot of miles and adventures in the sky! ☁️🛫🌍'),

            // Hotels
            (new Message('trips.tip.hotel-nights.current-year.same', $domain))->setDesc('Consistency is key! You\'ve spent %count% night in a hotel, just like last year.|Consistency is key! You\'ve spent %count% nights in hotels, just like last year.'),
            (new Message('trips.tip.hotel-nights.current-year.same.user', $domain))->setDesc('Consistency is key! %user% has spent %count% night in a hotel, just like last year.|Consistency is key! %user% has spent %count% nights in hotels, just like last year.'),
            (new Message('trips.tip.hotel-nights.current-year.more', $domain))->setDesc('You\'ve been on the move! %count% night in a hotel already — %count_difference% more than last year. 🏨|You\'ve been on the move! %count% nights in hotels already — %count_difference% more than last year. 🏨'),
            (new Message('trips.tip.hotel-nights.current-year.more.user', $domain))->setDesc('%user% has been on the move! %count% night in a hotel already — %count_difference% more than last year. 🏨|%user% has been on the move! %count% nights in hotels already — %count_difference% more than last year. 🏨'),
            (new Message('trips.tip.hotel-nights.current-year.less', $domain))->setDesc('Slowing down a bit? You\'ve spent %count% night in a hotel — %count_difference% fewer than last year. 🛌|Slowing down a bit? You\'ve spent %count% nights in hotels — %count_difference% fewer than last year. 🛌'),
            (new Message('trips.tip.hotel-nights.current-year.less.user', $domain))->setDesc('Slowing down a bit? %user% has spent %count% night in a hotel — %count_difference% fewer than last year. 🛌|Slowing down a bit? %user% has spent %count% nights in hotels — %count_difference% fewer than last year. 🛌'),
            (new Message('trips.tip.hotel-nights.last-year.same', $domain))->setDesc('No change here! %count% night in a hotel, same as the year before.|No change here! %count% nights in hotels, same as the year before.'),
            (new Message('trips.tip.hotel-nights.last-year.same.user', $domain))->setDesc('No change here! %count% night in a hotel, same as the year before.|No change here! %count% nights in hotels, same as the year before.'),
            (new Message('trips.tip.hotel-nights.last-year.more', $domain))->setDesc('Last year, you racked up %count% hotel night — %count_difference% more than the year before! 🏡|Last year, you racked up %count% hotel nights — %count_difference% more than the year before! 🏡'),
            (new Message('trips.tip.hotel-nights.last-year.more.user', $domain))->setDesc('Last year, %user% racked up %count% hotel night — %count_difference% more than the year before! 🏡|Last year, %user% racked up %count% hotel nights — %count_difference% more than the year before! 🏡'),
            (new Message('trips.tip.hotel-nights.last-year.less', $domain))->setDesc('You spent %count% night in a hotel last year — %count_difference% fewer than the previous year. 🛌|You spent %count% nights in hotels last year — %count_difference% fewer than the previous year. 🛌'),
            (new Message('trips.tip.hotel-nights.last-year.less.user', $domain))->setDesc('%user% spent %count% night in a hotel last year — %count_difference% fewer than the previous year. 🛌|%user% spent %count% nights in hotels last year — %count_difference% fewer than the previous year. 🛌'),
            (new Message('trips.tip.hotel-nights.previous-year', $domain))->setDesc('In the past %years% years, you\'ve spent a total of %count% night in a hotel|In the past %years% years, you\'ve spent a total of %count% nights in hotels'),
            (new Message('trips.tip.hotel-nights.previous-year.user', $domain))->setDesc('In the past %years% years, %user% has spent a total of %count% night in a hotel|In the past %years% years, %user% has spent a total of %count% nights in hotels'),
            (new Message('trips.tip.hotel-nights.all-time', $domain))->setDesc('Since %year%, you\'ve spent a total of %count% night in a hotel|Since %year%, you\'ve spent a total of %count% nights in hotels'),
            (new Message('trips.tip.hotel-nights.all-time.user', $domain))->setDesc('Since %year%, %user% has spent a total of %count% night in a hotel|Since %year%, %user% has spent a total of %count% nights in hotels'),
            (new Message('trips.tip.hotel-nights.selected-year', $domain))->setDesc('In %year%, you\'ve spent a total of %count% night in a hotel|In %year%, you\'ve spent a total of %count% nights in hotels'),
            (new Message('trips.tip.hotel-nights.selected-year.user', $domain))->setDesc('In %year%, %user% has spent a total of %count% night in a hotel|In %year%, %user% has spent a total of %count% nights in hotels'),
            (new Message('trips.tip.hotel-nights.previous-year.additional', $domain))->setDesc('almost %months% full month of travel! 🛎️🌍|almost %months% full months of travel! 🛎️🌍'),

            // Rental Cars
            (new Message('trips.tip.rental-car-days.current-year.same', $domain))->setDesc('Sticking to your usual routine! %count% rental day, same as last year.|Sticking to your usual routine! %count% rental days, same as last year.'),
            (new Message('trips.tip.rental-car-days.current-year.same.user', $domain))->setDesc('Sticking to your usual routine! %count% rental day, same as last year.|Sticking to your usual routine! %count% rental days, same as last year.'),
            (new Message('trips.tip.rental-car-days.current-year.more', $domain))->setDesc('You\'ve been hitting the road! %count% day in a rental car — %count_difference% more than last year. 🛣️|You\'ve been hitting the road! %count% days in a rental car — %count_difference% more than last year. 🛣️'),
            (new Message('trips.tip.rental-car-days.current-year.more.user', $domain))->setDesc('%user% has been hitting the road! %count% day in a rental car — %count_difference% more than last year. 🛣️|%user% has been hitting the road! %count% days in a rental car — %count_difference% more than last year. 🛣️'),
            (new Message('trips.tip.rental-car-days.current-year.less', $domain))->setDesc('Taking it easy? This year, you\'ve rented a car for %count% day — %count_difference% fewer than last year. 🚘|Taking it easy? This year, you\'ve rented a car for %count% days — %count_difference% fewer than last year. 🚘'),
            (new Message('trips.tip.rental-car-days.current-year.less.user', $domain))->setDesc('Taking it easy? This year, %user% has rented a car for %count% day — %count_difference% fewer than last year. 🚘|Taking it easy? This year, %user% has rented a car for %count% days — %count_difference% fewer than last year. 🚘'),
            (new Message('trips.tip.rental-car-days.last-year.same', $domain))->setDesc('No surprises here — %count% day of rentals, just like the year before.|No surprises here — %count% days of rentals, just like the year before.'),
            (new Message('trips.tip.rental-car-days.last-year.same.user', $domain))->setDesc('No surprises here — %count% day of rentals, just like the year before.|No surprises here — %count% days of rentals, just like the year before.'),
            (new Message('trips.tip.rental-car-days.last-year.more', $domain))->setDesc('Last year, you rented a car for %count% day — %count_difference% more than the year before! 🚦|Last year, you rented a car for %count% days — %count_difference% more than the year before! 🚦'),
            (new Message('trips.tip.rental-car-days.last-year.more.user', $domain))->setDesc('Last year, %user% rented a car for %count% day — %count_difference% more than the year before! 🚦|Last year, %user% rented a car for %count% days — %count_difference% more than the year before! 🚦'),
            (new Message('trips.tip.rental-car-days.last-year.less', $domain))->setDesc('You were behind the wheel for %count% rental day — %count_difference% fewer than the previous year. 🏁|You were behind the wheel for %count% rental days — %count_difference% fewer than the previous year. 🏁'),
            (new Message('trips.tip.rental-car-days.last-year.less.user', $domain))->setDesc('%user% has been behind the wheel for %count% rental day — %count_difference% fewer than the previous year. 🏁|%user% has been behind the wheel for %count% rental days — %count_difference% fewer than the previous year. 🏁'),
            (new Message('trips.tip.rental-car-days.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve spent a total of %count% day driving rental cars|Over the past %years% years, you\'ve spent a total of %count% days driving rental cars'),
            (new Message('trips.tip.rental-car-days.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has spent a total of %count% day driving rental cars|Over the past %years% years, %user% has spent a total of %count% days driving rental cars'),
            (new Message('trips.tip.rental-car-days.all-time', $domain))->setDesc('Since %year%, you\'ve spent a total of %count% day driving rental cars|Since %year%, you\'ve spent a total of %count% days driving rental cars'),
            (new Message('trips.tip.rental-car-days.all-time.user', $domain))->setDesc('Since %year%, %user% has spent a total of %count% day driving rental cars|Since %year%, %user% has spent a total of %count% days driving rental cars'),
            (new Message('trips.tip.rental-car-days.selected-year', $domain))->setDesc('In %year%, you\'ve spent a total of %count% day driving rental cars|In %year%, you\'ve spent a total of %count% days driving rental cars'),
            (new Message('trips.tip.rental-car-days.selected-year.user', $domain))->setDesc('In %year%, %user% has spent a total of %count% day driving rental cars|In %year%, %user% has spent a total of %count% days driving rental cars'),
            (new Message('trips.tip.rental-car-days.previous-year.additional', $domain))->setDesc('that\'s almost %months% month of adventures on the road! 🛻🌍|that\'s almost %months% months of adventures on the road! 🛻🌍'),

            // Parking days
            (new Message('trips.tip.parking-days.current-year.same', $domain))->setDesc('Keeping steady! %count% day of parking, same as last year.|Keeping steady! %count% days of parking, same as last year.'),
            (new Message('trips.tip.parking-days.current-year.same.user', $domain))->setDesc('Keeping steady! %count% day of parking, same as last year.|Keeping steady! %count% days of parking, same as last year.'),
            (new Message('trips.tip.parking-days.current-year.more', $domain))->setDesc('Your car\'s been parked %count% day — %count_difference% more than last year! 🚗|Your car\'s been parked %count% days — %count_difference% more than last year! 🚗'),
            (new Message('trips.tip.parking-days.current-year.more.user', $domain))->setDesc('%user%\'s car\'s been parked %count% day — %count_difference% more than last year! 🚗|%user%\'s car\'s been parked %count% days — %count_difference% more than last year! 🚗'),
            (new Message('trips.tip.parking-days.current-year.less', $domain))->setDesc('Parking less? You\'ve logged %count% day — %count_difference% fewer than last year.|Parking less? You\'ve logged %count% days — %count_difference% fewer than last year.'),
            (new Message('trips.tip.parking-days.current-year.less.user', $domain))->setDesc('Parking less? %user% has logged %count% day — %count_difference% fewer than last year.|Parking less? %user% has logged %count% days — %count_difference% fewer than last year.'),
            (new Message('trips.tip.parking-days.last-year.same', $domain))->setDesc('No changes here — %count% day of parking, just like the year before.|No changes here — %count% days of parking, just like the year before.'),
            (new Message('trips.tip.parking-days.last-year.same.user', $domain))->setDesc('No changes here — %count% day of parking, just like the year before.|No changes here — %count% days of parking, just like the year before.'),
            (new Message('trips.tip.parking-days.last-year.more', $domain))->setDesc('Last year, your car was parked for %count% day — %count_difference% more than the year before! 🅿️|Last year, your car was parked for %count% days — %count_difference% more than the year before! 🅿️'),
            (new Message('trips.tip.parking-days.last-year.more.user', $domain))->setDesc('Last year, %user%\'s car was parked for %count% day — %count_difference% more than the year before! 🅿️|Last year, %user%\'s car was parked for %count% days — %count_difference% more than the year before! 🅿️'),
            (new Message('trips.tip.parking-days.last-year.less', $domain))->setDesc('You used parking for %count% day — %count_difference% fewer than the previous year.|You used parking for %count% days — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.parking-days.last-year.less.user', $domain))->setDesc('%user% used parking for %count% day — %count_difference% fewer than the previous year.|%user% used parking for %count% days — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.parking-days.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve racked up %count% day of parking|Over the past %years% years, you\'ve racked up %count% days of parking'),
            (new Message('trips.tip.parking-days.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has racked up %count% day of parking|Over the past %years% years, %user% has racked up %count% days of parking'),
            (new Message('trips.tip.parking-days.all-time', $domain))->setDesc('Since %year%, you\'ve racked up %count% day of parking|Since %year%, you\'ve racked up %count% days of parking'),
            (new Message('trips.tip.parking-days.all-time.user', $domain))->setDesc('Since %year%, %user% has racked up %count% day of parking|Since %year%, %user% has racked up %count% days of parking'),
            (new Message('trips.tip.parking-days.selected-year', $domain))->setDesc('In %year%, you\'ve racked up %count% day of parking|In %year%, you\'ve racked up %count% days of parking'),
            (new Message('trips.tip.parking-days.selected-year.user', $domain))->setDesc('In %year%, %user% has racked up %count% day of parking|In %year%, %user% has racked up %count% days of parking'),
            (new Message('trips.tip.parking-days.previous-year.additional', $domain))->setDesc('that\'s %months% month of having a reserved spot! 🅿️🚙|that\'s %months% months of having a reserved spot! 🅿️🚙'),

            // Cruises
            (new Message('trips.tip.cruises.current-year.same', $domain))->setDesc('Anchored at the same pace — %count% cruise day, just like last year. 🌴|Anchored at the same pace — %count% cruise days, just like last year. 🌴'),
            (new Message('trips.tip.cruises.current-year.same.user', $domain))->setDesc('Anchored at the same pace — %count% cruise day, just like last year. 🌴|Anchored at the same pace — %count% cruise days, just like last year. 🌴'),
            (new Message('trips.tip.cruises.current-year.more', $domain))->setDesc('Smooth sailing! You\'ve spent %count% day on a cruise — %count_difference% more than last year. 🌊🌴|Smooth sailing! You\'ve spent %count% days on a cruise — %count_difference% more than last year. 🌊🌴'),
            (new Message('trips.tip.cruises.current-year.more.user', $domain))->setDesc('Smooth sailing! %user% has spent %count% day on a cruise — %count_difference% more than last year. 🌊🌴|Smooth sailing! %user% has spent %count% days on a cruise — %count_difference% more than last year. 🌊🌴'),
            (new Message('trips.tip.cruises.current-year.less', $domain))->setDesc('Taking a shorter voyage? %count% day at sea — %count_difference% fewer than last year. 🛳️|Taking a shorter voyage? %count% days at sea — %count_difference% fewer than last year. 🛳️'),
            (new Message('trips.tip.cruises.current-year.less.user', $domain))->setDesc('Taking a shorter voyage? %count% day at sea — %count_difference% fewer than last year. 🛳️|Taking a shorter voyage? %count% days at sea — %count_difference% fewer than last year. 🛳️'),
            (new Message('trips.tip.cruises.last-year.same', $domain))->setDesc('No changes — %count% day on a cruise, just like the year before. ⛴️✨|No changes — %count% days on a cruise, just like the year before. ⛴️✨'),
            (new Message('trips.tip.cruises.last-year.same.user', $domain))->setDesc('No changes — %count% day on a cruise, just like the year before. ⛴️✨|No changes — %count% days on a cruise, just like the year before. ⛴️✨'),
            (new Message('trips.tip.cruises.last-year.more', $domain))->setDesc('Last year, you cruised for %count% day — %count_difference% more than the year before! ⛴️|Last year, you cruised for %count% days — %count_difference% more than the year before! ⛴️'),
            (new Message('trips.tip.cruises.last-year.more.user', $domain))->setDesc('Last year, %user% has cruised for %count% day — %count_difference% more than the year before! ⛴️|Last year, %user% has cruised for %count% days — %count_difference% more than the year before! ⛴️'),
            (new Message('trips.tip.cruises.last-year.less', $domain))->setDesc('You spent %count% day at sea — %count_difference% fewer than the previous year. 🌊|You spent %count% days at sea — %count_difference% fewer than the previous year. 🌊'),
            (new Message('trips.tip.cruises.last-year.less.user', $domain))->setDesc('%user% has spent %count% day at sea — %count_difference% fewer than the previous year. 🌊|%user% has spent %count% days at sea — %count_difference% fewer than the previous year. 🌊'),
            (new Message('trips.tip.cruises.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve spent %count% day cruising|Over the past %years% years, you\'ve spent %count% days cruising'),
            (new Message('trips.tip.cruises.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% spent %count% day cruising|Over the past %years% years, %user% spent %count% days cruising'),
            (new Message('trips.tip.cruises.all-time', $domain))->setDesc('Since %year%, you\'ve spent %count% day cruising|Since %year%, you\'ve spent %count% days cruising'),
            (new Message('trips.tip.cruises.all-time.user', $domain))->setDesc('Since %year%, %user% spent %count% day cruising|Since %year%, %user% spent %count% days cruising'),
            (new Message('trips.tip.cruises.selected-year', $domain))->setDesc('In %year%, you\'ve spent %count% day cruising|In %year%, you\'ve spent %count% days cruising'),
            (new Message('trips.tip.cruises.selected-year.user', $domain))->setDesc('In %year%, %user% spent %count% day cruising|In %year%, %user% spent %count% days cruising'),
            (new Message('trips.tip.cruises.previous-year.additional', $domain))->setDesc('that\'s %weeks% week of ocean breezes and sunsets at sea! 🌅🚢🌴|that\'s %weeks% weeks of ocean breezes and sunsets at sea! 🌅🚢🌴'),

            // Ferries taken
            (new Message('trips.tip.ferries-taken.current-year.same', $domain))->setDesc('Keeping steady! %count% ferry ride, just like last year.|Keeping steady! %count% ferry rides, just like last year.'),
            (new Message('trips.tip.ferries-taken.current-year.same.user', $domain))->setDesc('Keeping steady! %count% ferry ride, just like last year.|Keeping steady! %count% ferry rides, just like last year.'),
            (new Message('trips.tip.ferries-taken.current-year.more', $domain))->setDesc('All aboard! You\'ve taken %count% ferry so far — %count_difference% more than last year. 🌊🚢|All aboard! You\'ve taken %count% ferries so far — %count_difference% more than last year. 🌊🚢'),
            (new Message('trips.tip.ferries-taken.current-year.more.user', $domain))->setDesc('All aboard! %user% has taken %count% ferry so far — %count_difference% more than last year. 🌊🚢|All aboard! %user% has taken %count% ferries so far — %count_difference% more than last year. 🌊🚢'),
            (new Message('trips.tip.ferries-taken.current-year.less', $domain))->setDesc('Fewer crossings this year? %count% ferry taken — %count_difference% fewer than last year.|Fewer crossings this year? %count% ferries taken — %count_difference% fewer than last year.'),
            (new Message('trips.tip.ferries-taken.current-year.less.user', $domain))->setDesc('Fewer crossings this year? %count% ferry taken — %count_difference% fewer than last year.|Fewer crossings this year? %count% ferries taken — %count_difference% fewer than last year.'),
            (new Message('trips.tip.ferries-taken.last-year.same', $domain))->setDesc('No changes — %count% ferry taken, same as the year before.|No changes — %count% ferries taken, same as the year before.'),
            (new Message('trips.tip.ferries-taken.last-year.same.user', $domain))->setDesc('No changes — %count% ferry taken, same as the year before.|No changes — %count% ferries taken, same as the year before.'),
            (new Message('trips.tip.ferries-taken.last-year.more', $domain))->setDesc('Last year, you hopped on %count% ferry — %count_difference% more than the year before! 🌊🚢|Last year, you hopped on %count% ferries — %count_difference% more than the year before! 🌊🚢'),
            (new Message('trips.tip.ferries-taken.last-year.more.user', $domain))->setDesc('Last year, %user% has hopped on %count% ferry — %count_difference% more than the year before! 🌊🚢|Last year, %user% has hopped on %count% ferries — %count_difference% more than the year before! 🌊🚢'),
            (new Message('trips.tip.ferries-taken.last-year.less', $domain))->setDesc('You took %count% ferry trip — %count_difference% fewer than the previous year.|You took %count% ferry trips — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.ferries-taken.last-year.less.user', $domain))->setDesc('%user% took %count% ferry trip — %count_difference% fewer than the previous year.|%user% took %count% ferry trips — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.ferries-taken.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve taken %count% ferry trip — that\'s a lot of scenic water crossings! 🚢🏞️|Over the past %years% years, you\'ve taken %count% ferry trips — that\'s a lot of scenic water crossings! 🚢🏞️'),
            (new Message('trips.tip.ferries-taken.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has taken %count% ferry trip — that\'s a lot of scenic water crossings! 🚢🏞️|Over the past %years% years, %user% has taken %count% ferry trips — that\'s a lot of scenic water crossings! 🚢🏞️'),
            (new Message('trips.tip.ferries-taken.all-time', $domain))->setDesc('Since %year%, you\'ve taken %count% ferry trip — that\'s a lot of scenic water crossings! 🚢🏞️|Since %year%, you\'ve taken %count% ferry trips — that\'s a lot of scenic water crossings! 🚢🏞️'),
            (new Message('trips.tip.ferries-taken.all-time.user', $domain))->setDesc('Since %year%, %user% has taken %count% ferry trip — that\'s a lot of scenic water crossings! 🚢🏞|Since %year%, %user% has taken %count% ferry trips — that\'s a lot of scenic water crossings! 🚢🏞️'),
            (new Message('trips.tip.ferries-taken.selected-year', $domain))->setDesc('In %year%, you\'ve taken %count% ferry trip — that\'s a lot of scenic water crossings! 🚢🏞️|In %year%, you\'ve taken %count% ferry trips — that\'s a lot of scenic water crossings! 🚢🏞️'),
            (new Message('trips.tip.ferries-taken.selected-year.user', $domain))->setDesc('In %year%, %user% has taken %count% ferry trip — that\'s a lot of scenic water crossings! 🚢🏞|In %year%, %user% has taken %count% ferry trips — that\'s a lot of scenic water crossings! 🚢🏞️'),

            // Bus Rides
            (new Message('trips.tip.bus-rides.current-year.same', $domain))->setDesc('Keeping steady! %count% bus ride, just like last year.|Keeping steady! %count% bus rides, just like last year.'),
            (new Message('trips.tip.bus-rides.current-year.same.user', $domain))->setDesc('Keeping steady! %count% bus ride, just like last year.|Keeping steady! %count% bus rides, just like last year.'),
            (new Message('trips.tip.bus-rides.current-year.more', $domain))->setDesc('On the move! You\'ve taken %count% bus ride so far — %count_difference% more than last year. 🚍✨|On the move! You\'ve taken %count% bus rides so far — %count_difference% more than last year. 🚍✨'),
            (new Message('trips.tip.bus-rides.current-year.more.user', $domain))->setDesc('On the move! %user% has taken %count% bus ride so far — %count_difference% more than last year. 🚍✨|On the move! %user% has taken %count% bus rides so far — %count_difference% more than last year. 🚍✨'),
            (new Message('trips.tip.bus-rides.current-year.less', $domain))->setDesc('Riding a little less? %count% bus trip — %count_difference% fewer than last year. 🏙️|Riding a little less? %count% bus trips — %count_difference% fewer than last year. 🏙️'),
            (new Message('trips.tip.bus-rides.current-year.less.user', $domain))->setDesc('Riding a little less? %count% bus trip — %count_difference% fewer than last year. 🏙️|Riding a little less? %count% bus trips — %count_difference% fewer than last year. 🏙️'),
            (new Message('trips.tip.bus-rides.last-year.same', $domain))->setDesc('No surprises — %count% bus ride, same as the year before.|No surprises — %count% bus rides, same as the year before.'),
            (new Message('trips.tip.bus-rides.last-year.same.user', $domain))->setDesc('No surprises — %count% bus ride, same as the year before.|No surprises — %count% bus rides, same as the year before.'),
            (new Message('trips.tip.bus-rides.last-year.more', $domain))->setDesc('Last year, you took %count% bus ride — %count_difference% more than the year before! 🏁🚌|Last year, you took %count% bus rides — %count_difference% more than the year before! 🏁🚌'),
            (new Message('trips.tip.bus-rides.last-year.more.user', $domain))->setDesc('Last year, %user% took %count% bus ride — %count_difference% more than the year before! 🏁🚌|Last year, %user% took %count% bus rides — %count_difference% more than the year before! 🏁🚌'),
            (new Message('trips.tip.bus-rides.last-year.less', $domain))->setDesc('You hopped on %count% bus — %count_difference% fewer than the previous year.|You hopped on %count% buses — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.bus-rides.last-year.less.user', $domain))->setDesc('%user% hopped on %count% bus — %count_difference% fewer than the previous year.|%user% hopped on %count% buses — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.bus-rides.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve taken %count% bus ride|Over the past %years% years, you\'ve taken %count% bus rides'),
            (new Message('trips.tip.bus-rides.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has taken %count% bus ride|Over the past %years% years, %user% has taken %count% bus rides'),
            (new Message('trips.tip.bus-rides.all-time', $domain))->setDesc('Since %year%, you\'ve taken %count% bus ride|Since %year%, you\'ve taken %count% bus rides'),
            (new Message('trips.tip.bus-rides.all-time.user', $domain))->setDesc('Since %year%, %user% has taken %count% bus ride|Since %year%, %user% has taken %count% bus rides'),
            (new Message('trips.tip.bus-rides.selected-year', $domain))->setDesc('In %year%, you\'ve taken %count% bus ride|In %year%, you\'ve taken %count% bus rides'),
            (new Message('trips.tip.bus-rides.selected-year.user', $domain))->setDesc('In %year%, %user% has taken %count% bus ride|In %year%, %user% has taken %count% bus rides'),
            (new Message('trips.tip.bus-rides.previous-year.additional', $domain))->setDesc('that\'s a lot of city-hopping and scenic routes! 🏙️🚌🌍'),

            // Restaurant Reservations
            (new Message('trips.tip.restaurant-reservations.current-year.same', $domain))->setDesc('Keeping your dining habits steady — %count% reservation, just like last year.|Keeping your dining habits steady — %count% reservations, just like last year.'),
            (new Message('trips.tip.restaurant-reservations.current-year.same.user', $domain))->setDesc('Keeping your dining habits steady — %count% reservation, just like last year.|Keeping your dining habits steady — %count% reservations, just like last year.'),
            (new Message('trips.tip.restaurant-reservations.current-year.more', $domain))->setDesc('Dining out in style! You\'ve made %count% restaurant reservation — %count_difference% more than last year. 🍷✨|Dining out in style! You\'ve made %count% restaurant reservations — %count_difference% more than last year. 🍷✨'),
            (new Message('trips.tip.restaurant-reservations.current-year.more.user', $domain))->setDesc('Dining out in style! %user% has made %count% restaurant reservation — %count_difference% more than last year. 🍷✨|Dining out in style! %user% has made %count% restaurant reservations — %count_difference% more than last year. 🍷✨'),
            (new Message('trips.tip.restaurant-reservations.current-year.less', $domain))->setDesc('Eating out a bit less? %count% reservation — %count_difference% fewer than last year. 🥂|Eating out a bit less? %count% reservations — %count_difference% fewer than last year. 🥂'),
            (new Message('trips.tip.restaurant-reservations.current-year.less.user', $domain))->setDesc('Eating out a bit less? %count% reservation — %count_difference% fewer than last year. 🥂|Eating out a bit less? %count% reservations — %count_difference% fewer than last year. 🥂'),
            (new Message('trips.tip.restaurant-reservations.last-year.same', $domain))->setDesc('No surprises — %count% restaurant reservation, the same as the year before.|No surprises — %count% restaurant reservations, the same as the year before.'),
            (new Message('trips.tip.restaurant-reservations.last-year.same.user', $domain))->setDesc('No surprises — %count% restaurant reservation, the same as the year before.|No surprises — %count% restaurant reservations, the same as the year before.'),
            (new Message('trips.tip.restaurant-reservations.last-year.more', $domain))->setDesc('Last year, you booked %count% restaurant reservation — %count_difference% more than the year before! 🍽️🎉|Last year, you booked %count% restaurant reservations — %count_difference% more than the year before! 🍽️🎉'),
            (new Message('trips.tip.restaurant-reservations.last-year.more.user', $domain))->setDesc('Last year, %user% booked %count% restaurant reservation — %count_difference% more than the year before! 🍽️🎉|Last year, %user% booked %count% restaurant reservations — %count_difference% more than the year before! 🍽️🎉'),
            (new Message('trips.tip.restaurant-reservations.last-year.less', $domain))->setDesc('You made %count% reservation — %count_difference% fewer than the previous year. 🍕|You made %count% reservations — %count_difference% fewer than the previous year. 🍕'),
            (new Message('trips.tip.restaurant-reservations.last-year.less.user', $domain))->setDesc('%user% made %count% reservation — %count_difference% fewer than the previous year. 🍕|%user% made %count% reservations — %count_difference% fewer than the previous year. 🍕'),
            (new Message('trips.tip.restaurant-reservations.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve made %count% restaurant reservation — that\'s a lot of great meals and special moments! 🍷🍝✨|Over the past %years% years, you\'ve made %count% restaurant reservations — that\'s a lot of great meals and special moments! 🍷🍝✨'),
            (new Message('trips.tip.restaurant-reservations.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has made %count% restaurant reservation — that\'s a lot of great meals and special moments! 🍷🍝✨|Over the past %years% years, %user% has made %count% restaurant reservations — that\'s a lot of great meals and special moments! 🍷🍝✨'),
            (new Message('trips.tip.restaurant-reservations.all-time', $domain))->setDesc('Since %year%, you\'ve made %count% restaurant reservation — that\'s a lot of great meals and special moments! 🍷🍝✨|Since %year%, you\'ve made %count% restaurant reservations — that\'s a lot of great meals and special moments! 🍷🍝✨'),
            (new Message('trips.tip.restaurant-reservations.all-time.user', $domain))->setDesc('Since %year%, %user% has made %count% restaurant reservation — that\'s a lot of great meals and special moments! 🍷🍝✨|Since %year%, %user% has made %count% restaurant reservations — that\'s a lot of great meals and special moments! 🍷🍝✨'),
            (new Message('trips.tip.restaurant-reservations.selected-year', $domain))->setDesc('In %year%, you\'ve made %count% restaurant reservation — that\'s a lot of great meals and special moments! 🍷🍝✨|In %year%, you\'ve made %count% restaurant reservations — that\'s a lot of great meals and special moments! 🍷🍝✨'),
            (new Message('trips.tip.restaurant-reservations.selected-year.user', $domain))->setDesc('In %year%, %user% has made %count% restaurant reservation — that\'s a lot of great meals and special moments! 🍷🍝✨|In %year%, %user% has made %count% restaurant reservations — that\'s a lot of great meals and special moments! 🍷🍝✨'),

            // Train Rides
            (new Message('trips.tip.train-rides.current-year.same', $domain))->setDesc('Keeping on track! %count% train ride, just like last year.|Keeping on track! %count% train rides, just like last year.'),
            (new Message('trips.tip.train-rides.current-year.same.user', $domain))->setDesc('Keeping on track! %count% train ride, just like last year.|Keeping on track! %count% train rides, just like last year.'),
            (new Message('trips.tip.train-rides.current-year.more', $domain))->setDesc('All aboard! You\'ve taken %count% train ride so far — %count_difference% more than last year. 🚄✨|All aboard! You\'ve taken %count% train rides so far — %count_difference% more than last year. 🚄✨'),
            (new Message('trips.tip.train-rides.current-year.more.user', $domain))->setDesc('All aboard! %user% has taken %count% train ride so far — %count_difference% more than last year. 🚄✨|All aboard! %user% has taken %count% train rides so far — %count_difference% more than last year. 🚄✨'),
            (new Message('trips.tip.train-rides.current-year.less', $domain))->setDesc('Slowing down a bit? %count% train ride — %count_difference% fewer than last year. 🚉|Slowing down a bit? %count% train rides — %count_difference% fewer than last year. 🚉'),
            (new Message('trips.tip.train-rides.current-year.less.user', $domain))->setDesc('Slowing down a bit? %count% train ride — %count_difference% fewer than last year. 🚉|Slowing down a bit? %count% train rides — %count_difference% fewer than last year. 🚉'),
            (new Message('trips.tip.train-rides.last-year.same', $domain))->setDesc('No surprises — %count% train ride, same as the year before.|No surprises — %count% train rides, same as the year before.'),
            (new Message('trips.tip.train-rides.last-year.same.user', $domain))->setDesc('No surprises — %count% train ride, same as the year before.|No surprises — %count% train rides, same as the year before.'),
            (new Message('trips.tip.train-rides.last-year.more', $domain))->setDesc('Last year, you rode the rails %count% time — %count_difference% more than the year before! 🚂🏁|Last year, you rode the rails %count% times — %count_difference% more than the year before! 🚂🏁'),
            (new Message('trips.tip.train-rides.last-year.more.user', $domain))->setDesc('Last year, %user% has rode the rails %count% time — %count_difference% more than the year before! 🚂🏁|Last year, %user% has rode the rails %count% times — %count_difference% more than the year before! 🚂🏁'),
            (new Message('trips.tip.train-rides.last-year.less', $domain))->setDesc('You took %count% train trip — %count_difference% fewer than the previous year. 🌍|You took %count% train trips — %count_difference% fewer than the previous year. 🌍'),
            (new Message('trips.tip.train-rides.last-year.less.user', $domain))->setDesc('%user% took %count% train trip — %count_difference% fewer than the previous year. 🌍|%user% took %count% train trips — %count_difference% fewer than the previous year. 🌍'),
            (new Message('trips.tip.train-rides.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve taken %count% train ride|Over the past %years% years, you\'ve taken %count% train rides'),
            (new Message('trips.tip.train-rides.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has taken %count% train ride|Over the past %years% years, %user% has taken %count% train rides'),
            (new Message('trips.tip.train-rides.all-time', $domain))->setDesc('Since %year%, you\'ve taken %count% train ride|Since %year%, you\'ve taken %count% train rides'),
            (new Message('trips.tip.train-rides.all-time.user', $domain))->setDesc('Since %year%, %user% has taken %count% train ride|Since %year%, %user% has taken %count% train rides'),
            (new Message('trips.tip.train-rides.selected-year', $domain))->setDesc('In %year%, you\'ve taken %count% train ride|In %year%, you\'ve taken %count% train rides'),
            (new Message('trips.tip.train-rides.selected-year.user', $domain))->setDesc('In %year%, %user% has taken %count% train ride|In %year%, %user% has taken %count% train rides'),
            (new Message('trips.tip.train-rides.previous-year.additional', $domain))->setDesc('that\'s a lot of scenic views and city-hopping! 🌆🚆🌍'),

            // Events
            (new Message('trips.tip.events.current-year.same', $domain))->setDesc('Keeping things consistent — %count% event, just like last year.|Keeping things consistent — %count% events, just like last year.'),
            (new Message('trips.tip.events.current-year.same.user', $domain))->setDesc('Keeping things consistent — %count% event, just like last year.|Keeping things consistent — %count% events, just like last year.'),
            (new Message('trips.tip.events.current-year.more', $domain))->setDesc('Living your best life! You\'ve attended %count% event so far — %count_difference% more than last year. 🎉|Living your best life! You\'ve attended %count% events so far — %count_difference% more than last year. 🎉'),
            (new Message('trips.tip.events.current-year.more.user', $domain))->setDesc('Living the best life! %user% has attended %count% event so far — %count_difference% more than last year. 🎉|Living the best life! %user% has attended %count% events so far — %count_difference% more than last year. 🎉'),
            (new Message('trips.tip.events.current-year.less', $domain))->setDesc('Taking it easy? %count% event attended — %count_difference% fewer than last year.|Taking it easy? %count% events attended — %count_difference% fewer than last year.'),
            (new Message('trips.tip.events.current-year.less.user', $domain))->setDesc('Taking it easy? %count% event attended — %count_difference% fewer than last year.|Taking it easy? %count% events attended — %count_difference% fewer than last year.'),
            (new Message('trips.tip.events.last-year.same', $domain))->setDesc('No surprises — %count% event, same as the year before.|No surprises — %count% events, same as the year before.'),
            (new Message('trips.tip.events.last-year.same.user', $domain))->setDesc('No surprises — %count% event, same as the year before.|No surprises — %count% events, same as the year before.'),
            (new Message('trips.tip.events.last-year.more', $domain))->setDesc('Last year, you enjoyed %count% event — %count_difference% more than the year before!|Last year, you enjoyed %count% events — %count_difference% more than the year before!'),
            (new Message('trips.tip.events.last-year.more.user', $domain))->setDesc('Last year, %user% enjoyed %count% event — %count_difference% more than the year before!|Last year, %user% enjoyed %count% events — %count_difference% more than the year before!'),
            (new Message('trips.tip.events.last-year.less', $domain))->setDesc('You attended %count% event — %count_difference% fewer than the previous year.|You attended %count% events — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.events.last-year.less.user', $domain))->setDesc('%user% attended %count% event — %count_difference% fewer than the previous year.|%user% attended %count% events — %count_difference% fewer than the previous year.'),
            (new Message('trips.tip.events.previous-year', $domain))->setDesc('Over the past %years% years, you\'ve attended %count% event — that\'s a lot of amazing experiences and unforgettable moments!|Over the past %years% years, you\'ve attended %count% events — that\'s a lot of amazing experiences and unforgettable moments!'),
            (new Message('trips.tip.events.previous-year.user', $domain))->setDesc('Over the past %years% years, %user% has attended %count% event — that\'s a lot of amazing experiences and unforgettable moments!|Over the past %years% years, %user% has attended %count% events — that\'s a lot of amazing experiences and unforgettable moments!'),
            (new Message('trips.tip.events.all-time', $domain))->setDesc('Since %year%, you\'ve attended %count% event — that\'s a lot of amazing experiences and unforgettable moments!|Since %year%, you\'ve attended %count% events — that\'s a lot of amazing experiences and unforgettable moments!'),
            (new Message('trips.tip.events.all-time.user', $domain))->setDesc('Since %year%, %user% has attended %count% event — that\'s a lot of amazing experiences and unforgettable moments!|Since %year%, %user% has attended %count% events — that\'s a lot of amazing experiences and unforgettable moments!'),
            (new Message('trips.tip.events.selected-year', $domain))->setDesc('In %year%, you\'ve attended %count% event — that\'s a lot of amazing experiences and unforgettable moments!|In %year%, you\'ve attended %count% events — that\'s a lot of amazing experiences and unforgettable moments!'),
            (new Message('trips.tip.events.selected-year.user', $domain))->setDesc('In %year%, %user% has attended %count% event — that\'s a lot of amazing experiences and unforgettable moments!|In %year%, %user% has attended %count% events — that\'s a lot of amazing experiences and unforgettable moments!'),
        ];
    }

    /**
     * @param array[] $translations an array containing such keys:
     * ```php
     * $array = [
     *     self::CATEGORY_YEAR_TO_DATE => [],
     *     self::CATEGORY_LAST_YEAR => [],
     *     self::CATEGORY_PREVIOUS_YEAR => [],
     *     self::CATEGORY_ALL_TIME => [],
     *     self::CATEGORY_SELECTED_YEAR => [],
     * ];
     * ```
     * @param Translation|null $additional an additional message that is displayed when a certain condition is met
     * (e.g. for the number of reservations)
     */
    private function translateBlock(User $user, int $period, ?int $change, array $translations, ?Translation $additional = null): ?string
    {
        switch ($period) {
            case PeriodDatesHelper::YEAR_TO_DATE:
                return $this->translateTip($user, $change, $translations[self::CATEGORY_YEAR_TO_DATE]);

            case PeriodDatesHelper::LAST_YEAR:
                return $this->translateTip($user, $change, $translations[self::CATEGORY_LAST_YEAR]);

            case PeriodDatesHelper::LAST_3_YEARS:
            case PeriodDatesHelper::LAST_5_YEARS:
            case PeriodDatesHelper::LAST_10_YEARS:
                return $this->translateTip($user, null, $translations[self::CATEGORY_PREVIOUS_YEAR], $additional);

            case PeriodDatesHelper::ALL_TIME:
                return $this->translateTip($user, null, $translations[self::CATEGORY_ALL_TIME], $additional);
        }

        $currentYear = (new \DateTime())->format('Y');

        if (strlen((string) $period) === 4 && $period < $currentYear - 1 && $period >= 2004) {
            return $this->translateTip($user, null, $translations[self::CATEGORY_SELECTED_YEAR], $additional);
        }

        return null;
    }

    private function translateLocationBlock(User $user, PeriodDatesResult $datesResult, ?int $diff, Plural $current, ?Plural $prev, array $params = []): ?string
    {
        $params = array_merge($params, [
            '%user%' => $user->getUserName(),
            '%numberVisitedPlural%' => $current,
            '%numberVisitedPrevPlural%' => $prev,
            '%numberYearsPlural%' => $this->getNumberYearsPlural($datesResult->getCurrentPeriod()),
            '%year%' => $datesResult->getYear(),
        ]);

        return $this->translateBlock($user, $datesResult->getCurrentPeriod(), $diff,
            [
                self::CATEGORY_YEAR_TO_DATE => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.this-year.same.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.this-year.same.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.this-year.inc.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.this-year.inc.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.this-year.dec.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.this-year.dec.user', $params),
                ],
                self::CATEGORY_LAST_YEAR => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.last-year.same.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.last-year.same.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.last-year.inc.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.last-year.inc.user', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.last-year.dec.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.last-year.dec.user', $params),
                ],
                self::CATEGORY_PREVIOUS_YEAR => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.prev-year.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.prev-year.user', $params),
                ],
                self::CATEGORY_ALL_TIME => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.all-time.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.all-time.user', $params),
                ],
                self::CATEGORY_SELECTED_YEAR => [
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.selected-year.you', $params),
                    new Translation(/** @Ignore */ 'travel-summary.tip.visits.selected-year.user', $params),
                ],
            ]
        );
    }

    /**
     * @param array[] $translations an array containing such keys:
     * ```php
     * $array = [
     *     self::CATEGORY_YEAR_TO_DATE => [],
     *     self::CATEGORY_LAST_YEAR => [],
     *     self::CATEGORY_PREVIOUS_YEAR => [],
     *     self::CATEGORY_ALL_TIME => [],
     *     self::CATEGORY_SELECTED_YEAR => [],
     * ];
     * ```
     */
    private function translateStatisticsTab(User $user, int $period, array $translations): ?string
    {
        switch ($period) {
            case PeriodDatesHelper::YEAR_TO_DATE:
                $category = self::CATEGORY_YEAR_TO_DATE;

                break;

            case PeriodDatesHelper::LAST_YEAR:
                $category = self::CATEGORY_LAST_YEAR;

                break;

            case PeriodDatesHelper::LAST_3_YEARS:
            case PeriodDatesHelper::LAST_5_YEARS:
            case PeriodDatesHelper::LAST_10_YEARS:
                $category = self::CATEGORY_PREVIOUS_YEAR;

                break;

            case PeriodDatesHelper::ALL_TIME:
                $category = self::CATEGORY_ALL_TIME;

                break;
        }

        if (isset($category)) {
            return $this->t(!$user->isAgent() ? $translations[$category][0] : $translations[$category][1]);
        }

        $currentYear = (new \DateTime())->format('Y');

        if (strlen((string) $period) === 4 && $period < $currentYear - 1 && $period >= 2004) {
            return $this->t(!$user->isAgent() ? $translations[self::CATEGORY_SELECTED_YEAR][0] : $translations[self::CATEGORY_SELECTED_YEAR][1]);
        }

        return null;
    }

    /**
     * @param Translation[] $trans array of messages for your own account and for another user's account
     * @param Translation|null $additional an additional message that is displayed when a certain condition is met
     * (e.g. for the number of reservations)
     */
    private function translateTip(User $user, ?int $change, array $trans, ?Translation $additional = null): string
    {
        if ($change === null) {
            $message[] = $this->t(!$user->isAgent() ? $trans[0] : $trans[1]);

            if ($additional !== null && $additional->getParam('isVisible')) {
                $message[] = ' — ' . $this->t($additional);
            } elseif ($additional !== null && !$additional->getParam('isVisible')) {
                $message[] = '.';
            }

            return implode('', $message);
        } elseif ($change === 0) {
            return $this->t(!$user->isAgent() ? $trans[0] : $trans[1]);
        } elseif ($change > 0) {
            return $this->t(!$user->isAgent() ? $trans[2] : $trans[3]);
        } else {
            return $this->t(!$user->isAgent() ? $trans[4] : $trans[5]);
        }
    }

    private function getNumberYearsPlural(int $period): ?Translation
    {
        $years = [
            PeriodDatesHelper::LAST_3_YEARS => 3,
            PeriodDatesHelper::LAST_5_YEARS => 5,
            PeriodDatesHelper::LAST_10_YEARS => 10,
        ];

        if (!isset($years[$period])) {
            return null;
        }

        return new Plural(/** @Ignore */ 'travel-summary.tip.plural.years', $years[$period], [
            '%years%' => $this->localizer->formatNumber($years[$period]),
        ]);
    }

    private function getNumberYears(int $period): ?int
    {
        $years = [
            PeriodDatesHelper::LAST_3_YEARS => 3,
            PeriodDatesHelper::LAST_5_YEARS => 5,
            PeriodDatesHelper::LAST_10_YEARS => 10,
        ];

        return $years[$period] ?? null;
    }

    private function t(Translation $translation): string
    {
        if ($translation->getParam('default') === true) {
            $params = it($translation->getParams())->mapIndexed(function ($param, $key) {
                if (is_numeric($param) && !in_array($key, ['%count%', '%year%'])) {
                    $param = $this->localizer->formatNumber(abs($param));
                }

                return $param;
            })->toArrayWithKeys();

            return $this->translator->trans($translation->getKey(), $params, self::TRANSLATION_DOMAIN);
        }

        if ($translation instanceof Plural) {
            $forms = $this->tips[$translation->getKey()];
            $count = $translation->getCount();

            if (is_string($forms)) {
                $forms = [$forms, $forms . 's'];
            }

            $message = $count === 1 ? $forms[0] : $forms[1];
        } else {
            $message = $this->tips[$translation->getKey()];
        }

        return strtr($message,
            it($translation->getParams())->mapIndexed(function ($param, $key) {
                if ($param instanceof Translation) {
                    return $this->t($param);
                } elseif (is_numeric($param) && !in_array($key, ['%count%', '%year%'])) {
                    $param = $this->localizer->formatNumber(abs($param));
                }

                return $param;
            })->toArrayWithKeys(),
        );
    }
}
