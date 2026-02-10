<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Repository\ProvidercouponRepository;
use AwardWallet\MainBundle\Service\ExpirationDate\ExpirationDate;
use AwardWallet\MainBundle\Service\ExpirationDate\Expire;
use AwardWallet\MainBundle\Service\ExpirationDate\Template\AccountExpireEvent;
use AwardWallet\MainBundle\Service\ExpirationDate\Template\PassportExpireEvent;
use AwardWallet\MainBundle\Timeline\Formatter\CallableFormatHandler;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\AbstractTrip;
use AwardWallet\MainBundle\Timeline\Item\Checkin;
use AwardWallet\MainBundle\Timeline\Item\Checkout;
use AwardWallet\MainBundle\Timeline\Item\Date;
use AwardWallet\MainBundle\Timeline\Item\Dropoff;
use AwardWallet\MainBundle\Timeline\Item\Event;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\LayoverInterface;
use AwardWallet\MainBundle\Timeline\Item\ParkingEnd;
use AwardWallet\MainBundle\Timeline\Item\ParkingStart;
use AwardWallet\MainBundle\Timeline\Item\Pickup;
use AwardWallet\MainBundle\Timeline\Item\PlanEnd;
use AwardWallet\MainBundle\Timeline\Item\PlanStart;
use AwardWallet\MainBundle\Timeline\Item\Taxi;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\MainBundle\Timeline\Util\TripHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;

/**
 * @Route("/iCal")
 */
class ICalendarController extends AbstractController
{
    protected const TWIG_TEMPLATE_NAMESPACE = 'AwardWallet\\MainBundle\\Service\\ExpirationDate\\Template';

    protected $UserTimeOffset;

    protected Environment $twig;
    private $passports = [];
    private $accounts = [];
    private $userAgent;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private LocalizeService $localizeService;
    private TripHelper $tripHelper;
    private LoggerInterface $loggerStat;
    private AwTokenStorageInterface $tokenStorage;
    private string $requiresChannel;
    private string $host;
    private string $locale;
    private UsrRepository $usrRepository;
    private UseragentRepository $useragentRepository;
    private ProvidercouponRepository $providercouponRepository;

    public function __construct(
        Environment $twig,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        LocalizeService $localizeService,
        TripHelper $tripHelper,
        LoggerInterface $loggerStat,
        AwTokenStorageInterface $tokenStorage,
        string $requiresChannel,
        string $host,
        string $locale,
        UsrRepository $usrRepository,
        UseragentRepository $useragentRepository,
        ProvidercouponRepository $providercouponRepository
    ) {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->localizeService = $localizeService;
        $this->tripHelper = $tripHelper;
        $this->loggerStat = $loggerStat;
        $this->tokenStorage = $tokenStorage;
        $this->requiresChannel = $requiresChannel;
        $this->host = $host;
        $this->locale = $locale;
        $this->usrRepository = $usrRepository;
        $this->useragentRepository = $useragentRepository;
        $this->providercouponRepository = $providercouponRepository;
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/{action}.{_format}", name="aw_icalendar_ajax", methods={"POST"}, options={"expose"=true}, requirements={"action" = "manage|getPopupData|manageAcc|getPopupDataAcc", "_format" = "json"})
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function ajaxAction($action, Request $request, AuthorizationCheckerInterface $authorizationChecker)
    {
        if ($authorizationChecker->isGranted('ROLE_USER') === false) {
            throw new AccessDeniedException();
        }

        switch ($action) {
            case "getPopupData":
                return $this->getPopupData(!empty($request->query->get('new')));

                break;

            case "manage":
                return $this->manageLink($request);

                break;

            case "getPopupDataAcc":
                return $this->getPopupData(!empty($request->query->get('new')), true);

                break;

            case "manageAcc":
                return $this->manageLink($request, true);

                break;
        }
    }

    /**
     * @Route("/{code}", name="aw_icalendar_itinerarycalendar", requirements={"code" = "[a-z\d]{32}"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function itineraryCalendarAction($code, Manager $timelineManager)
    {
        $userAgent = null;
        $user = $this->usrRepository->findOneBy(["itinerarycalendarcode" => $code]);

        if (empty($user)) {
            $userAgent = $this->useragentRepository->findOneBy(["itinerarycalendarcode" => $code]);

            if (empty($userAgent)) {
                throw new NotFoundHttpException();
            }
            $user = $userAgent->getAgentid();
        }

        $q = $this->getDoctrine()->getConnection()->executeQuery(
            "
			SELECT u.UserID, 0 as UserAgentID, u.FirstName, 0 as Offset
			FROM Usr u
			WHERE u.ItineraryCalendarCode = ?
			UNION
			SELECT ua.AgentID as UserID, ua.UserAgentID, ua.FirstName, 0 as Offset
			FROM UserAgent ua
			INNER JOIN Usr u on ua.AgentID = u.UserID
			WHERE ua.ItineraryCalendarCode = ?",
            [$code, $code],
            [\PDO::PARAM_STR, \PDO::PARAM_STR]
        );
        $result = $q->fetch();

        if (!$result) {
            return new \Symfony\Component\HttpFoundation\Response("", 404);
        }

        $queryOptions = (new QueryOptions())
            ->setUser($user)
            ->setUserAgent($userAgent)
            ->setWithDetails(true)
            ->setFormat('icalendar');
        /** @var ItemInterface[] $data */
        $formatter = fn (array $items) => $this->formatEvents($this->formatData($items, $result["UserID"], $result["UserAgentID"]));
        $timelineManager->addFormatHandler('icalendar', new CallableFormatHandler($formatter));
        $events = $timelineManager->query($queryOptions);
        $this->UserTimeOffset = $result["Offset"];
        $response = $this->render(
            "@AwardWalletMain/ICalendar/itineraries.ics.twig",
            [
                "events" => $events,
                "CalendarID" => $result["UserID"] . "-" . $result["UserAgentID"] . "-awardwallet.com",
                "firstName" => $result["FirstName"],
            ]
        );
        $response->headers->add([
            'Content-Type' => 'text/calendar',
            //			'Content-Disposition' => 'inline; filename=calendar.ics'
        ]);

        return $response;
    }

    /**
     * @Route("/accExpire/{code}", name="aw_icalendar_accexpirecalendar", requirements={"code" = "[a-z\d]{32}"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function accExpireCalendarAction($code, ExpirationDate $expirationDate)
    {
        $userAgent = null;
        $user = $this->usrRepository->findOneBy(["accexpirecalendarcode" => $code]);

        if (empty($user)) {
            $userAgent = $this->useragentRepository->findOneBy(["accexpirecalendarcode" => $code]);

            if (empty($userAgent)) {
                throw new NotFoundHttpException();
            }
            $user = $userAgent->getAgentid();
        }

        $expirationDate->setUsersIds([$user->getUserid()]);

        if (isset($userAgent)) {
            $this->userAgent = $userAgent->getUseragentid();
            $result = [
                'UserID' => $userAgent->getAgentid(),
                'UserAgentID' => $userAgent->getUseragentid(),
                'FirstName' => $userAgent->getFirstname(),
            ];
        } else {
            $this->userAgent = null;
            $result = [
                'UserID' => $user->getUserid(),
                'UserAgentID' => 0,
                'FirstName' => $user->getFirstname(),
            ];
        }

        $stmt = $expirationDate->getStmt(ExpirationDate::MODE_CALENDAR);

        $this->passports = [];
        $this->accounts = [];

        foreach (
            stmtObj($stmt, Expire::class)
                ->filter([$this, 'filter'])
                ->groupAdjacentBy(function (Expire $value, Expire $lastValue) {
                    return $value->UserAgentID === $lastValue->UserAgentID ? 0 : -1;
                })
                ->map(function ($group) use ($expirationDate) {
                    return it($group)
                        ->flatMap(\Closure::fromCallable([$expirationDate, 'prepareExpire']))
                        ->toArray();
                })
                ->filterBySizeGt(0) as $programs
        ) {
            $this->accounts = array_merge($this->accounts, $programs);
        }
        $events = array_merge(
            $this->createAccountEvents($this->accounts, $user),
            $this->createPassportEvents($this->passports, $user)
        );

        $this->UserTimeOffset = 0;

        $response = $this->render(
            "@AwardWalletMain/ICalendar/accExpire.ics.twig",
            [
                "events" => $events,
                "CalendarID" => $result["UserID"] . "-" . $result["UserAgentID"] . "-accExpire-awardwallet.com",
                "firstName" => $result["FirstName"],
            ]
        );
        $response->headers->add([
            'Content-Type' => 'text/calendar',
        ]);

        return $response;
    }

    public function filter(Expire $expire): bool
    {
        if ($expire->UserAgentID !== $this->userAgent) {
            return false;
        }

        if ($expire->isExpiredPassport()) {
            $template = new PassportExpireEvent();
            $template->passport = $this->providercouponRepository->find($expire->ID);
            $this->passports[] = $template;

            return false;
        }

        return true;
    }

    protected function addEvent(&$events, $event)
    {
        foreach ($events as $e) {
            if ($e["Kind"] == $event["Kind"]
                && $e["DateStart"] == $event["DateStart"]
                && $e["Location"] == $event["Location"]
            ) {
                return false;
            }
        }
        $events[] = $event;

        return true;
    }

    protected function formatDate(int $date, bool $allday = false): string
    {
        $format = $allday ? "Ymd" : "Ymd\THis\Z";

        return date($format, $date);
    }

    protected function getURL($shareCode, $timePlan)
    {
        if (empty($shareCode)) {
            return "";
        }

        if ($timePlan) {
            return $this->requiresChannel . "://"
                . $this->host
                . $this->router->generate('aw_travelplan_shared', ['shareCode' => $shareCode]);
        } else {
            return $this->requiresChannel . "://"
                . $this->host
                . $this->router->generate('aw_timeline_shared', ['shareCode' => $shareCode]);
        }
    }

    protected function formatEvents(array $events): array
    {
        // format meets RFC 5545
        return array_map(function ($event) {
            $format = "Ymd\THis";
            $allday = strpos($event["Description"], 'Agenda') !== false ? true : false;
            $new = [
                "DTSTAMP" => date($format),
                "UID" => $event["Agent"] . $event["Kind"] . $event["DateStart"] . $event["Id"] . '-@awardwallet.com',
                "LOCATION" => $event["Location"],
                "SUMMARY" => $event["Summary"],
                "STATUS" => "CONFIRMED",
                "TRANSP" => $allday ? "TRANSPARENT" : "OPAQUE",
                "DESCRIPTION" => $event["Description"],
                "URL" => $event["URL"],
            ];

            if ($allday) {
                ArrayInsert($new, "DTSTAMP", false, [
                    "DTSTART;VALUE=DATE" => $this->formatDate($event["DateStart"], $allday), ]);

                //						"DTSTART;TZID=" . $event["DateStartTimeZone"] => $this->formatDate($event["DateStart"])));
                if (isset($event["DateEnd"])) {
                    if (is_string($event["DateEndTimeZone"])) {
                        ArrayInsert($new, "DTSTAMP", false, [
                            "DTEND;VALUE=DATE" => $this->formatDate($event["DateEnd"], $allday), ]);
                    }
                    //							"DTEND;TZID=" . $event["DateEndTimeZone"] => $this->formatDate($event["DateEnd"])));
                }
            } else {
                ArrayInsert($new, "DTSTAMP", false, [
                    "DTSTART" => $this->formatDate($event["DateStart"]), ]);

                //						"DTSTART;TZID=" . $event["DateStartTimeZone"] => $this->formatDate($event["DateStart"])));
                if (isset($event["DateEnd"])) {
                    ArrayInsert($new, "DTSTAMP", false, [
                        "DTEND" => $this->formatDate($event["DateEnd"]), ]);
                    //							"DTEND;TZID=" . $event["DateEndTimeZone"] => $this->formatDate($event["DateEnd"])));
                }
            }

            $result = [];

            foreach ($new as $name => $value) {
                $result[] = [
                    "Name" => $name,
                    "Value" => $value,
                ];
            }

            return $result;
        }, $events);
    }

    /**
     * @param ItemInterface[] $data
     */
    protected function formatData(array $data, int $userID, ?int $userAgentID = 0): array
    {
        $result = [];
        $agent = ($userAgentID > 0) ? $userAgentID : $userID;

        $inTravelPlan = false;
        $tpDescription = null;
        $tpDateStart = null;
        $tpDateEnd = null;
        $tpDateStartTZ = null;
        $tpDateEndTZ = null;
        $tpUrl = '';
        $prevRow = null;
        $rowStartPlan = null; // for debug

        foreach ($data as $row) {
            if ($row instanceof LayoverInterface || $row instanceof Date) {
                continue;
            }

            if ($row instanceof PlanStart) {
                $rowStartPlan = $row;
                $inTravelPlan = true;
                $tpDescription = 'Agenda:';

                $tpDateStart = strtotime(date_format($row->getLocalDate() ?? $row->getStartDate(), "Y-m-d H:i"));
                $tpDateStartTZ = 'UTC';

                $tpUrl = $this->getURL($row->getPlan()->getEncodedShareCode(), true);

                continue;
            }

            if ($row instanceof PlanEnd) {
                $inTravelPlan = false;

                if ($prevRow !== null) {
                    $tpDateEnd = strtotime("+1 day", strtotime(date_format($prevRow->getEndDate() ?? $prevRow->getStartDate(), "Y-m-d H:i")));
                    $tpDateEndTZ = 'UTC';
                } else {
                    // should be no such
                    $tpDateEnd = strtotime("+1 day", strtotime(date_format($row->getStartDate(), "Y-m-d H:i")));
                    $tpDateEndTZ = 'UTC';
                }

                if (empty($tpDescription) && empty($tpDateStart)) {
                    // something went wrong
                    $this->loggerStat->notice("ICalendar-travel: something went wrong",
                        [
                            "Agent" => $agent,
                            "PlanID" => $row->getPlan()->getId(),
                            "Summary" => $row->getPlan()->getName(),
                            'DateStart' => $tpDateStart,
                            'DateEnd' => $tpDateEnd,
                        ]);
                    $rowStartPlan = null; // debug

                    continue;
                }
                $rowStartPlan = null; // debug
                $event = [
                    "Kind" => "P",
                    "Description" => $tpDescription,
                    "Id" => $row->getPlan()->getId(),
                    "DateStart" => $tpDateStart,
                    "DateEnd" => $tpDateEnd,
                    "DateStartTimeZone" => $tpDateStartTZ,
                    "DateEndTimeZone" => $tpDateEndTZ,
                    "Location" => "",
                    "TravelPlan" => $row->getPlan()->getId(),
                    "Summary" => $row->getPlan()->getName(),
                    "Agent" => $agent,
                    "URL" => $tpUrl,
                ];
                $this->addEvent($result, $event);

                continue;
            }
            /** @var AbstractItinerary $row */
            /** @var Itinerary $itinerary */
            $itinerary = $row->getItinerary();
            $travelPlanId = $itinerary->getTravelPlan() ? $itinerary->getTravelPlan()->getTravelPlanId() : null;
            $shareCode = $itinerary->getEncodedShareCode();
            $url = $this->getURL($shareCode, false);
            $flight = null;

            switch (true) {
                case $row instanceof AbstractTrip:
                    /** @var Trip $itinerary */
                    /** @var Tripsegment $source */
                    $source = $row->getSource();

                    if ($itinerary->getCategory() === Trip::CATEGORY_AIR) {
                        $flightName = $this->tripHelper->resolveFlightName($row);
                        $flight =
                            (StringUtils::isNotEmpty($flightName->iataCode) ? $flightName->iataCode : '') .
                            (StringUtils::isNotEmpty($flightName->flightNumber) ? $flightName->flightNumber : '');
                        $description = sprintf(
                            'Confirmation # %s\n%s flight %s\nDeparture: %s (%s) at %s\nArrival: %s (%s) at %s',
                            $row->getConfNo(),
                            $source->getAirlinename(),
                            $flight,
                            $source->getDepAirportName(false),
                            $source->getDepcode(),
                            $this->localizeService->formatTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')',
                            $source->getArrAirportName(false),
                            $source->getArrcode(),
                            $this->localizeService->formatTime($row->getEndDate()) . ' (' . date_format($row->getEndDate(), "T") . ')'
                        );
                    } else {
                        switch ($itinerary->getCategory()) {
                            case Trip::CATEGORY_TRAIN:
                                $category = 'Train Ride';

                                break;

                            case Trip::CATEGORY_BUS:
                                $category = 'Bus Ride';

                                break;

                            case Trip::CATEGORY_CRUISE:
                                $category = 'Cruise';

                                break;

                            case Trip::CATEGORY_FERRY:
                                $category = 'Ferry Ride';

                                break;

                            default:
                                $category = '';

                                break;
                        }
                        $description = sprintf('Confirmation # %s\n%s \n%s to %s ', $row->getConfNo(), $category, $source->getDepname(), $source->getArrname());
                    }

                    // added events (start|end) for long cruise
                    if (isset($category) && $category === 'Cruise'
                        && $row->getStartDate()->getTimestamp() && $row->getEndDate()->getTimestamp()
                        && abs($row->getStartDate()->getTimestamp() - $row->getEndDate()->getTimestamp()) > 60 * 60 * 23
                    ) {
                        $event = [
                            "Kind" => "T" . $itinerary->getCategory(),
                            "Description" => $description,
                            "Id" => $source->getTripsegmentid(),
                            "DateStart" => strtotime("-30 min", $row->getStartDate()->getTimestamp()),
                            "DateEnd" => $row->getStartDate()->getTimestamp(),
                            "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                            "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                            "Location" => $source->getDepname(),
                            "Summary" => ("Cruise to " . $source->getArrName() . ' (embarkation)'),
                            "TravelPlan" => $travelPlanId,
                            "Agent" => $agent,
                            "URL" => $url,
                        ];
                        $this->addEvent($result, $event);

                        $event = [
                            "Kind" => "T" . $itinerary->getCategory(),
                            "Description" => $description,
                            "Id" => $source->getTripsegmentid(),
                            "DateStart" => $row->getEndDate()->getTimestamp(),
                            "DateEnd" => strtotime("+30 min", $row->getEndDate()->getTimestamp()),
                            "DateStartTimeZone" => $row->getEndDate()->getTimezone()->getName(),
                            "DateEndTimeZone" => $row->getEndDate()->getTimezone()->getName(),
                            "Location" => $source->getArrName(),
                            "Summary" => ("Cruise to " . $source->getArrName() . ' (desembarkation)'),
                            "TravelPlan" => $travelPlanId,
                            "Agent" => $agent,
                            "URL" => $url,
                        ];
                        $this->addEvent($result, $event);
                    }

                    $event = [
                        "Kind" => "T" . $itinerary->getCategory(),
                        "Description" => $description,
                        "Id" => $source->getTripsegmentid(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => $row->getEndDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getEndDate()->getTimezone()->getName(),
                        "Location" => $source->getDepname(),
                        "Summary" => (($itinerary->getCategory() == Trip::CATEGORY_AIR) ? sprintf('%s -> %s, %s flight %s', $source->getDepcode(), $source->getArrcode(), $source->getAirlinename(), $flight ?? $source->getFlightnumber()) : "Trip to " . $source->getArrName()),
                        "TravelPlan" => $travelPlanId,
                        "Agent" => $agent,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    $event = [
                        "Kind" => "T" . $itinerary->getCategory(),
                        "Description" => $description,
                        "Id" => $source->getTripsegmentid(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => $row->getEndDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getEndDate()->getTimezone()->getName(),
                        "Location" => $source->getDepname(),
                        "Summary" => (($itinerary->getCategory() == Trip::CATEGORY_AIR) ? sprintf('%s -> %s, %s flight %s', $source->getDepcode(), $source->getArrcode(), $source->getAirlinename(), $flight ?? $source->getFlightnumber()) : "Trip to " . $source->getArrName()),
                        "TravelPlan" => $travelPlanId,
                        "Agent" => $agent,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        if ($itinerary->getCategory() == Trip::CATEGORY_AIR) {
                            $tpDescription .= '\n' . $source->getAirlinename() . ' flight ' . ($flight ?? $source->getFlightnumber()) . ' ' . $source->getDepcode() . '-' . $source->getArrcode() . ' on ' . $this->localizeService->formatDateTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                        } else {
                            $tpDescription .= '\n' . $event['Summary'] . ' on ' . $this->localizeService->formatDateTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                        }
                    }

                    break;

                case $row instanceof Checkin:
                    /** @var Reservation $source */
                    $source = $row->getSource();
                    $event = [
                        "Kind" => "R",
                        "Description" => sprintf('Confirmation # %s\n%s', $row->getConfNo(), $source->getHotelname()),
                        "Id" => $itinerary->getId(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => strtotime("+30 min", $row->getStartDate()->getTimestamp()),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "Location" => $source->getAddress(),
                        "TravelPlan" => $travelPlanId,
                        "Summary" => "Check-in to " . $source->getHotelname(),
                        "Agent" => $agent,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        $tpDescription .= '\nCheck-in to ' . $source->getHotelname() . ' in ' . $source->getAddress() . ' on ' . $this->localizeService->formatDate($row->getStartDate()) . ' around ' . $this->localizeService->formatTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                    }

                    break;

                case $row instanceof Checkout:
                    /** @var Reservation $source */
                    $source = $row->getSource();
                    $event = [
                        "Kind" => "R",
                        "Description" => sprintf('Confirmation # %s\n%s', $row->getConfNo(), $source->getHotelname()),
                        "Id" => $source->getId(),
                        "DateStart" => strtotime("-30 min", $row->getStartDate()->getTimestamp()),
                        "DateEnd" => $row->getStartDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "Location" => $source->getAddress(),
                        "TravelPlan" => $travelPlanId,
                        "Summary" => "Check-out from " . $source->getHotelname(),
                        "Agent" => $agent,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        $tpDescription .= '\nCheck-out from ' . $source->getHotelname() . ' in ' . $source->getAddress() . ' on ' . $this->localizeService->formatDate($row->getStartDate()) . ' around ' . $this->localizeService->formatTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                    }

                    break;

                case $row instanceof Pickup:
                    /** @var Rental $source */
                    $source = $row->getSource();
                    $rentalCompany = null !== $source->getRentalCompanyName() ? " ({$source->getRentalCompanyName()})" : '';

                    if ($source->getPickuplocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getPickuplocation(), $m)) {
                        $summary = sprintf('Rental pickup%s %s', $rentalCompany, $m[1]);
                    } else {
                        $summary = sprintf('Rental pickup%s', $rentalCompany);
                    }
                    $event = [
                        "Kind" => "L",
                        "Description" => sprintf(
                            'Confirmation # %s\n%s\%s\n%s',
                            $row->getConfNo(),
                            $rentalCompany,
                            $source->getPickupphone(),
                            null !== $source->getCarModel() ? $source->getCarModel() . " rented" : "Car rented"
                        ),
                        "Id" => $source->getId(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => strtotime("+30 min", $row->getStartDate()->getTimestamp()),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "TravelPlan" => $travelPlanId,
                        "Location" => $source->getPickuplocation(),
                        "Summary" => $summary,
                        "Agent" => $agent,
                        "RentalCompany" => $rentalCompany,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        $tpDescription .= '\nPick-up rental car from ' . $rentalCompany . ' on ' . $this->localizeService->formatDate($row->getStartDate()) . ' around ' . $this->localizeService->formatTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                    }

                    break;

                case $row instanceof Dropoff:
                    /** @var Rental $source */
                    $source = $row->getSource();
                    $rentalCompany = null !== $source->getRentalCompanyName() ? " ({$source->getRentalCompanyName()})" : '';

                    if ($source->getDropofflocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getDropofflocation(), $m)) {
                        $summary = sprintf('Rental dropoff%s %s', $rentalCompany, $m[1]);
                    } else {
                        $summary = sprintf('Rental dropoff%s', $rentalCompany);
                    }
                    $event = [
                        "Kind" => "L",
                        "Description" => sprintf(
                            'Confirmation # %s\n%s\%s\n%s',
                            $row->getConfNo(),
                            $rentalCompany,
                            $source->getDropoffphone(),
                            null !== $source->getCarModel() ? $source->getCarModel() . " rented" : "Car rented"
                        ),
                        "Id" => $source->getId(),
                        "DateStart" => strtotime("-30 min", $row->getStartDate()->getTimestamp()),
                        "DateEnd" => $row->getStartDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "TravelPlan" => $travelPlanId,
                        "Location" => $source->getDropofflocation(),
                        "Summary" => $summary,
                        "Agent" => $agent,
                        "RentalCompany" => $rentalCompany,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        $tpDescription .= '\nDrop-off rental car at ' . $rentalCompany . ' on ' . $this->localizeService->formatDate($row->getStartDate()) . ' around ' . $this->localizeService->formatTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                    }

                    break;

                case $row instanceof Event:
                    /** @var Restaurant $source */
                    $source = $row->getSource();

                    if ($source->getEventtype() === 1) {
                        if (null !== $source->getGuestCount()) {
                            $summary = "Restaurant reservation for {$source->getGuestCount()} @ '{$source->getName()}'";
                        } else {
                            $summary = "Restaurant reservation @ " . $source->getName();
                        }
                    } else {
                        $summary = $source->getName();
                    }
                    $event = [
                        "Kind" => "E",
                        "Description" => "",
                        "Id" => $source->getId(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "TravelPlan" => $travelPlanId,
                        "Location" => $source->getAddress(),
                        "Summary" => $summary,
                        "Agent" => $agent,
                        "URL" => $url,
                    ];

                    if ($row->getEndDate() !== null) {
                        $event['DateEnd'] = $row->getEndDate()->getTimestamp();
                        $event['DateEndTimeZone'] = $row->getEndDate()->getTimezone()->getName();
                    } else {
                        $event['DateEnd'] = strtotime("+30 min", $event['DateStart']);
                        $event['DateEndTimeZone'] = $event['DateStartTimeZone'];
                    }
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        $tpDescription .= '\n' . $summary . ' on ' . $this->localizeService->formatDateTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                    }

                    break;

                case $row instanceof Taxi:
                    /** @var Rental $source */
                    $source = $row->getSource();
                    $rentalCompany = null !== $source->getRentalCompanyName() ? " ({$source->getRentalCompanyName()})" : '';

                    $description = sprintf('Confirmation # %s%s\nTransfer%s', $row->getConfNo(), $source->getPickupphone(), $rentalCompany);

                    if ($source->getPickuplocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getPickuplocation(), $m)) {
                        $description .= sprintf('\n%s', $m[1]);
                    } elseif ($source->getPickuplocation()) {
                        $description .= sprintf('\n%s', $source->getPickuplocation());
                    }

                    if ($source->getDropofflocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getDropofflocation(), $m)) {
                        $description .= sprintf(' to %s', $m[1]);
                    } elseif ($source->getDropofflocation()) {
                        $description .= sprintf(' to %s', $source->getDropofflocation());
                    }

                    $summary = sprintf('Transfer%s', $rentalCompany);

                    if ($source->getDropofflocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getDropofflocation(), $m)) {
                        $summary .= sprintf(' to %s', $m[1]);
                    } elseif ($source->getDropofflocation()) {
                        $summary .= sprintf(' to %s', $source->getDropofflocation());
                    } elseif ($source->getPickuplocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getPickuplocation(), $m)) {
                        $summary .= sprintf(' from %s', $m[1]);
                    } elseif ($source->getPickuplocation()) {
                        $summary .= sprintf(' from %s', $source->getPickuplocation());
                    }

                    $event = [
                        "Kind" => "T",
                        "Description" => $description,
                        "Id" => $source->getId(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => $row->getEndDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getEndDate()->getTimezone()->getName(),
                        "TravelPlan" => $travelPlanId,
                        "Location" => $source->getPickuplocation(),
                        "Summary" => $summary,
                        "Agent" => $agent,
                        "RentalCompany" => $rentalCompany,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    if ($inTravelPlan) {
                        $tpDescription .= '\n' . $event['Summary'] . ' on ' . $this->localizeService->formatDate($row->getStartDate()) . ' around ' . $this->localizeService->formatTime($row->getStartDate()) . ' (' . date_format($row->getStartDate(), "T") . ')';
                    }

                    break;

                case $row instanceof ParkingStart:
                    /** @var Parking $source */
                    $source = $row->getSource();
                    $parkingCompany = null !== $source->getParkingCompanyName() ? " {$source->getParkingCompanyName()}" : '';

                    if ($source->getLocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getLocation(), $m)) {
                        $summary = sprintf('Park @%s %s', $parkingCompany, $m[1]);
                    } else {
                        $summary = sprintf('Park @%s', $parkingCompany);
                    }
                    $event = [
                        "Kind" => "P",
                        "Description" => sprintf('Confirmation # %s', $row->getConfNo()) . (($source->getPhone()) ? sprintf('\n%s:%s', $parkingCompany, $source->getPhone()) : ''),
                        "Id" => $source->getId(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => $row->getStartDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "TravelPlan" => $travelPlanId,
                        "Location" => $source->getLocation(),
                        "Summary" => $summary,
                        "Agent" => $agent,
                        "ParkingCompany" => $parkingCompany,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    break;

                case $row instanceof ParkingEnd:
                    /** @var Parking $source */
                    $source = $row->getSource();
                    $parkingCompany = null !== $source->getParkingCompanyName() ? " {$source->getParkingCompanyName()}" : '';

                    if ($source->getLocation() && preg_match("#\(\s*([A-Z]{3})\s*\)#", $source->getLocation(), $m)) {
                        $summary = sprintf('Car pick up%s %s', $parkingCompany, $m[1]);
                    } else {
                        $summary = sprintf('Car pick up%s', $parkingCompany);
                    }
                    $event = [
                        "Kind" => "P",
                        "Description" => sprintf('Confirmation # %s', $row->getConfNo()) . (($source->getPhone()) ? sprintf('\n%s:%s', $parkingCompany, $source->getPhone()) : ''),
                        "Id" => $source->getId(),
                        "DateStart" => $row->getStartDate()->getTimestamp(),
                        "DateEnd" => $row->getStartDate()->getTimestamp(),
                        "DateStartTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "DateEndTimeZone" => $row->getStartDate()->getTimezone()->getName(),
                        "TravelPlan" => $travelPlanId,
                        "Location" => $source->getLocation(),
                        "Summary" => $summary,
                        "Agent" => $agent,
                        "ParkingCompany" => $parkingCompany,
                        "URL" => $url,
                    ];
                    $this->addEvent($result, $event);

                    break;
            }
            $prevRow = clone $row;
        }

        return $result;
    }

    protected function getPopupData(bool $newDesign = false, ?bool $isAccExpire = false)
    {
        $userID = $this->tokenStorage->getBusinessUser()->getUserid();

        if ($isAccExpire) {
            $fieldCode = 'AccExpireCalendarCode';
            $agreePopup = '@AwardWalletMain/ICalendar/agreePopupAcc.html.twig';
        } else {
            $fieldCode = 'ItineraryCalendarCode';
            $agreePopup = '@AwardWalletMain/ICalendar/agreePopup.html.twig';
        }
        $q = $this->getDoctrine()->getConnection()->executeQuery(
            "
			SELECT
				0 as AgentID,
				case when AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . " then Company else concat( FirstName, ' ', LastName ) end as Username,
				{$fieldCode} as Code
			FROM Usr WHERE UserID = ?
			UNION
			SELECT UserAgentID as AgentID, concat( FirstName, ' ', LastName ) as Username, {$fieldCode} as Code
			FROM UserAgent WHERE AgentID = ? and ClientID is null",
            [$userID, $userID],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        );
        $list = $q->fetchAll(\PDO::FETCH_ASSOC);
        $result = ["ShowNote" => true, "Agents" => [], "Count" => count($list)];
        $html = "";
        $first = "first ";

        if (count($list) > 0) {
            $last = end($list)["AgentID"];
        } else {
            $last = -1;
        }

        foreach ($list as $agent) {
            if (stripos($agent["Code"], "-") === 0) {
                $agent["Code"] = "0";
            }
            $result["Agents"][$agent["AgentID"]] = $agent;

            if ($agent["Code"] != "") {
                $result["ShowNote"] = false;
            }
            $name = $agent["Username"];
            $ID = $agent["AgentID"];
            $classes = "li black $first";
            $first = "";

            if ($agent["AgentID"] == $last) {
                $classes .= "last";
            } else {
                $classes .= "bottomDots";
            }
            $html .= "
				<div class=\"{$classes}\">
					<div class=\"liIcon\"></div>
					<div class=\"liText\">
						<div>
							<div style=\"float: left;\"><a href=\"javascript:showCalendarLink({$ID})\">{$name}</a></div>
							<div class=\"clear\"></div>
						</div>
					</div>
				</div>
			";
        }

        if ($newDesign) {
            $result['Content'] = $this->renderView('@AwardWalletMain/ICalendar/importPopup.html.twig', ['users' => $list]);
            $result['LinkDialog'] = $this->renderView('@AwardWalletMain/ICalendar/linkPopup.html.twig');
            $result['AgreeDialog'] = $this->renderView($agreePopup);
        } else {
            $result["Content"] = $html;
        }

        return $this->render('@AwardWalletMain/content.json.twig', [
            'response' => $result,
        ]);
    }

    protected function manageLink(Request $request, ?bool $isAccExpire = false)
    {
        if ($isAccExpire) {
            $fieldCode = 'AccExpireCalendarCode';
        } else {
            $fieldCode = 'ItineraryCalendarCode';
        }
        $action = $request->get('operation');
        $agent = $request->get('agent');
        $user = $this->tokenStorage->getBusinessUser()->getUserid();
        $removed = false;

        switch ($action) {
            case "new":
                $code = md5($this->tokenStorage->getBusinessUser()->getLogin() . rand(1, 10000000));

                break;

            case "remove":
                $code = preg_replace("/^./", "-", md5($this->tokenStorage->getBusinessUser()->getLogin() . rand(1, 10000000)));
                $removed = true;

                break;

            default:
                return $this->render('@AwardWalletMain/content.json.twig', [
                    'response' => ["error" => "Invalid action"],
                ]);
        }

        if ($agent <= 0) {
            $result["result"] = $this->getDoctrine()->getConnection()->executeUpdate(
                "
				UPDATE Usr set {$fieldCode} = ?
				WHERE UserID = ?",
                [$code, $user],
                [\PDO::PARAM_STR, \PDO::PARAM_INT]
            );
            $result["Code"] = $code;
        } else {
            $result["result"] = $this->getDoctrine()->getConnection()->executeUpdate(
                "
				UPDATE UserAgent set {$fieldCode} = ?
				WHERE UserAgentID = ? AND AgentID = ? AND ClientID IS NULL",
                [$code, $agent, $user],
                [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
            );
            $result["Code"] = $removed ? "0" : $code;
        }

        return $this->render('@AwardWalletMain/content.json.twig', [
            'response' => $result,
        ]);
    }

    private function createAccountEvents(array $programs, ?Usr $user): array
    {
        $events = [];

        foreach ($programs as $accExp) {
            $template = new AccountExpireEvent();
            $template->account = $accExp;
            $template->daysBeforeAlarm = ExpirationDate::BALANCE_NOTIFICATION_DAYS_LAST_WEEK;

            $templateVars = get_object_vars($template);

            if (!isset($templateVars['lang']) || empty($templateVars['lang'])) {
                $templateVars['lang'] = $user ? $user->getLanguage() : $this->locale;
            }

            if (!isset($templateVars['locale']) || empty($templateVars['locale'])) {
                $templateVars['locale'] = $user ? $user->getLocale() : $this->locale;
            }

            $data = $this->twig->mergeGlobals($templateVars);
            $twigTemplate = $this->getTwigTemplate($template);
            //            $daysAgos = array_merge(ExpirationDate::BALANCE_NOTICES_DAYS, [0]);
            //            foreach ($daysAgos as $daysAgo) {
            //                if ($daysAgo === 0) {
            unset($data['account']['PointsExpire']); // condition for title
            //                }// if
            $subject = $twigTemplate->renderBlock('subject', $data);
            $descr = $twigTemplate->renderBlock('descr', $data);
            //            $alarms = $twigTemplate->renderBlock('alarms', $data);
            $this->clearDescription($descr);
            //            $dateExpire = strtotime(
            //                '-' . $daysAgo . ' days',
            //                strtotime($accExp["Expire"]->getValue()->ExpirationDate)
            //            );
            $dateExpire = strtotime($accExp["Expire"]->getValue()->ExpirationDate);
            $params = [
                'dateCreate' => date("Ymd\THis"),
                'dateStart' => $this->formatDate($dateExpire, true),
                'uid' => $accExp["Expire"]->getValue()->Kind . $accExp["Expire"]->getValue()->ID . '-' . $dateExpire . 'acc-@awardwallet.com',
                'title' => $subject,
                'descr_text' => preg_replace("/[ ]+/", ' ', $descr),
                'descr_html' => preg_replace("/\s+/", ' ', $descr),
                //                    'alarms' => $alarms
            ];
            $events[] = $twigTemplate->renderBlock('content', $params);
            //            } // foreach ($daysAgos as $daysAgo)
        }

        return $events;
    }

    private function createPassportEvents(array $templates, ?Usr $user): array
    {
        $events = [];

        foreach ($templates as $template) {
            if (!$template->passport->getExpirationdate()) {
                continue;
            }
            //            $monthsAgos = array_merge(ExpirationDate::PASSPORT_NOTICES_MONTHS, [0]);
            //            foreach ($monthsAgos as $monthsAgo) {
            $monthsAgo = 0;
            $template->expiresInMonths = $monthsAgo;
            $template->weeksBeforeAlarm = array_map(function ($s) {
                return round($s * 52 / 12);
            }, ExpirationDate::PASSPORT_NOTICES_MONTHS);

            $templateVars = get_object_vars($template);

            if (!isset($templateVars['lang']) || empty($templateVars['lang'])) {
                $templateVars['lang'] = $user ? $user->getLanguage() : $this->locale;
            }

            if (!isset($templateVars['locale']) || empty($templateVars['locale'])) {
                $templateVars['locale'] = $user ? $user->getLocale() : $this->locale;
            }

            $data = $this->twig->mergeGlobals($templateVars);
            $twigTemplate = $this->getTwigTemplate($template);
            $subject = $twigTemplate->renderBlock('subject', $data);
            $descr = $twigTemplate->renderBlock('descr', $data);
            //                $alarms = $twigTemplate->renderBlock('alarms', $data);
            $this->clearDescription($descr);

            $dateExpire = strtotime(
                "-" . $monthsAgo . ' months',
                $template->passport->getExpirationdate()->getTimestamp()
            );
            $params = [
                'dateCreate' => date("Ymd\THis"),
                'dateStart' => $this->formatDate($dateExpire, true),
                'uid' => 'P' . $template->passport->getProvidercouponid() . '-' . $dateExpire . 'acc-@awardwallet.com',
                'title' => $subject,
                'descr_text' => preg_replace("/[ ]+/", ' ', $descr),
                'descr_html' => preg_replace("/\s+/", ' ', $descr),
                //                    'alarms' => $alarms
            ];
            $events[] = $twigTemplate->renderBlock('content', $params);
            //            }// foreach ($monthsAgos as $monthsAgo)
        }

        return $events;
    }

    private function clearDescription(string &$descr)
    {
        // kostyl
        $descr = str_replace(['&#039;', '&#39;'], '\'', $descr);
        $descr = preg_replace(
            "/<a href=([\"'])([^\"']+)\\1[^>]*>([^<]+)<\/a>/",
            "$3 ($2)",
            $descr); // modify links clear tag

        // delete double links
        if (preg_match_all("/( \(https?:\/\/[^\s]+\))/", $descr, $matches)) {
            $links = array_unique($matches[1]);

            foreach ($links as $link) {
                $pos = strrpos($descr, $link);
                $p1 = substr($descr, 0, $pos);
                $p2 = substr($descr, $pos);
                $descr = str_replace($link, '', $p1) . $p2;
            }
        }
    }

    /**
     * @return \Twig_TemplateWrapper
     */
    private function getTwigTemplate($template)
    {
        $class = \get_class($template);

        if (\strpos($class, self::TWIG_TEMPLATE_NAMESPACE) !== 0) {
            throw new \LogicException('Template class and twig should be placed in ' . self::TWIG_TEMPLATE_NAMESPACE . ' namespace');
        }

        $template = '@ExpirationDateTemplate' . \substr($class, \strlen(self::TWIG_TEMPLATE_NAMESPACE)) . '.twig';

        return $this->twig->load($template);
    }
}
