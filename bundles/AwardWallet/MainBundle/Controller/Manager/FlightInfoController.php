<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\FlightInfo;
use AwardWallet\MainBundle\Globals\Paginator\Paginator;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo as FlightInfoService;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Doctrine\DBAL\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/manager/flightInfo")
 */
class FlightInfoController extends AbstractController
{
    public const perPage = 20;

    public const STATES = [
        FlightInfo::STATE_NEW => 'New',
        FlightInfo::STATE_CHECKED => 'Checked',
        FlightInfo::STATE_DONE => 'Done',
        FlightInfo::STATE_ERROR => 'Error',
    ];

    public const FLIGHT_STATES = [
        FlightInfo::FLIGHTSTATE_UNKNOWN => 'Unknown',
        FlightInfo::FLIGHTSTATE_SCHEDULE => 'Schedule',
        FlightInfo::FLIGHTSTATE_DEPART => 'Depart',
        FlightInfo::FLIGHTSTATE_ARRIVE => 'Arrive',
        FlightInfo::FLIGHTSTATE_CANCEL => 'Cancel',
        FlightInfo::FLIGHTSTATE_ERROR_AIRLINE => 'Error Airline',
        FlightInfo::FLIGHTSTATE_ERROR_NUMBER => 'Error Number',
        FlightInfo::FLIGHTSTATE_ERROR_DEPARTURE => 'Error Departure',
        FlightInfo::FLIGHTSTATE_ERROR_ARRIVAL => 'Error Arrival',
        FlightInfo::FLIGHTSTATE_ERROR_NOT_FOUND => 'Error Not Found',
        FlightInfo::FLIGHTSTATE_ERROR_NOT_EXISTS => 'Error Not Exists',
        FlightInfo::FLIGHTSTATE_ERROR_OTHER => 'Error Other',
    ];

    private FlightInfoService $flightInfo;

    public function __construct(FlightInfoService $flightInfo)
    {
        $this->flightInfo = $flightInfo;
    }

    /**
     * @Route("/", name="aw_manager_flightinfo_index", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/index.html.twig")
     * @return array
     */
    public function indexAction(Request $request)
    {
        $year = $request->get('year', false);
        $month = $request->get('month', false);
        $day = $request->get('day', false);
        $level = $request->get('level', false);

        [$year, $month, $day, $maxYear, $minYear, $maxDay, $isFullYear, $isFullMonth] = $this->calendar($year, $month, $day);

        if ($isFullYear) {
            $date = new \DateTime(implode('-', [$year, '01', '01']));
            $startDate = $date->format('Y-01-01 00:00:00');
            $endDate = $date->format('Y-12-31 23:59:59');
            $header = $date->format('Y');
        } elseif ($isFullMonth) {
            $date = new \DateTime(implode('-', [$year, $month, '01']));
            $startDate = $date->format('Y-m-01 00:00:00');
            $endDate = $date->format('Y-m-t 23:59:59');
            $header = $date->format('M Y');
        } else {
            $date = new \DateTime(implode('-', [$year, $month, $day]));
            $startDate = $date->format('Y-m-d 00:00:00');
            $endDate = $date->format('Y-m-d 23:59:59');
            $header = $date->format('M j, Y');
        }

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        $q = $connection->executeQuery("
            SELECT
              p.Kind                                                                                         AS providerKind,
              p.ShortName                                                                                    AS providerName,
              p.ProviderID                                                                                   AS providerId,
              t.Category                                                                                     AS tripCategory,
              (ts.DepCode <> '' AND ts.ArrCode <> '' AND ts.FlightNumber <> '' AND ts.FlightNumber <> 'n/a') AS filled,
              (t.Hidden OR ts.Hidden)                                                                        AS hidden,
              t.Cancelled                                                                                    AS cancelled,
              fl.State                                                                                       AS state,
              count(*)                                                                                       AS allCnt,
              count(DISTINCT ts.FlightInfoID)                                                                AS uniqueCnt,
              count(ts.FlightInfoID)                                                                         AS bindCnt
            from TripSegment ts
              join Trip t on ts.TripID = t.TripID
              " . ($level ? 'join Usr u on t.UserID = u.UserID' : '') . "
              left join FlightInfo fl on fl.FlightInfoID = ts.FlightInfoID
              left join Provider p on p.ProviderID = t.ProviderID
            WHERE ts.DepDate >= :start_date AND ts.DepDate <= :end_date
              " . ($level ? 'and u.AccountLevel = :level' : '') . "
            GROUP BY
              tripCategory, providerId, filled, hidden, cancelled, state
            ORDER BY providerKind, providerName;
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':level' => $level,
        ]);

        $stat = [
            'flights' => [],
            'hidden' => [],
            'cancelled' => [],
            'broken' => [],
            'nonflights' => [],
        ];

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if (empty($row['providerName'])) {
                $row['providerName'] = 'Custom Segments';
            }

            if ($row['tripCategory'] != TRIP_CATEGORY_AIR) {
                $key = 'nonflights';
            } elseif ($row['hidden']) {
                $key = 'hidden';
            } elseif ($row['cancelled']) {
                $key = 'cancelled';
            } elseif ($row['filled']) {
                $key = 'flights';
            } else {
                $key = 'broken';
            }

            if (!array_key_exists($row['providerId'], $stat[$key])) {
                $stat[$key][$row['providerId']] = $row;
                $stat[$key][$row['providerId']]['states'] = [
                    FlightInfo::STATE_NEW => 0,
                    FlightInfo::STATE_CHECKED => 0,
                    FlightInfo::STATE_DONE => 0,
                    FlightInfo::STATE_ERROR => 0,
                ];

                if (!is_null($row['state'])) {
                    $stat[$key][$row['providerId']]['states'][$row['state']] += $row['allCnt'];
                }
            } else {
                $stat[$key][$row['providerId']]['allCnt'] += $row['allCnt'];
                $stat[$key][$row['providerId']]['uniqueCnt'] += $row['uniqueCnt'];
                $stat[$key][$row['providerId']]['bindCnt'] += $row['bindCnt'];

                if (!is_null($row['state'])) {
                    $stat[$key][$row['providerId']]['states'][$row['state']] += $row['allCnt'];
                }
            }
        }

        $statAll = [];

        foreach ($stat as $key => $s) {
            $statAll[$key]['allCnt'] = 0;
            $statAll[$key]['uniqueCnt'] = 0;
            $statAll[$key]['realUniqueCnt'] = 0;
            $statAll[$key]['bindCnt'] = 0;
            $statAll[$key]['states'] = [
                FlightInfo::STATE_NEW => 0,
                FlightInfo::STATE_CHECKED => 0,
                FlightInfo::STATE_DONE => 0,
                FlightInfo::STATE_ERROR => 0,
            ];

            foreach ($s as $row) {
                $statAll[$key]['allCnt'] += $row['allCnt'];
                $statAll[$key]['uniqueCnt'] += $row['uniqueCnt'];
                $statAll[$key]['bindCnt'] += $row['bindCnt'];

                foreach ($statAll[$key]['states'] as $state => &$counter) {
                    $counter += $row['states'][$state];
                }
            }
        }

        $q = $connection->executeQuery("
            SELECT
              t.Category                                                                                     AS tripCategory,
              (ts.DepCode <> '' AND ts.ArrCode <> '' AND ts.FlightNumber <> '' AND ts.FlightNumber <> 'n/a') AS filled,
              (t.Hidden OR ts.Hidden)                                                                        AS hidden,
              t.Cancelled                                                                                    AS cancelled,
              count(DISTINCT ts.FlightInfoID)                                                                AS uniqueCnt
            from TripSegment ts
              join Trip t on ts.TripID = t.TripID
              " . ($level ? 'join Usr u on t.UserID = u.UserID' : '') . "
              left join FlightInfo fl on fl.FlightInfoID = ts.FlightInfoID
            WHERE ts.DepDate >= :start_date AND ts.DepDate <= :end_date
              " . ($level ? 'and u.AccountLevel = :level' : '') . "
            GROUP BY tripCategory, filled, hidden, cancelled
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':level' => $level,
        ]);

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['tripCategory'] != TRIP_CATEGORY_AIR) {
                $key = 'nonflights';
            } elseif ($row['hidden']) {
                $key = 'hidden';
            } elseif ($row['cancelled']) {
                $key = 'cancelled';
            } elseif ($row['filled']) {
                $key = 'flights';
            } else {
                $key = 'broken';
            }
            $statAll[$key]['realUniqueCnt'] += $row['uniqueCnt'];
        }

        $headers = [
            'flights' => 'Normal Segments',
            'hidden' => 'Deleted Segments or Trips',
            'cancelled' => 'Cancelled Trips',
            'broken' => 'Segments with invalid fields: Departure, Arrival, Carrier or Flight Number',
            'nonflights' => 'Non-flight Trips',
        ];

        $menu = [
            'flights' => 'Normal',
            'hidden' => 'Deleted',
            'cancelled' => 'Cancelled',
            'broken' => 'Broken',
            'nonflights' => 'Non-flights',
        ];

        return [
            'stat' => $stat,
            'statAll' => $statAll,
            'headers' => $headers,
            'menu' => $menu,
            'states' => self::STATES,
            'header' => $header,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'level' => $level,
            'days' => $maxDay,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
        ];
    }

    /**
     * @Route("/segments-list", name="aw_manager_flightinfo_segments_list", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/segmentsList.html.twig")
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function segmentsListAction(Request $request)
    {
        $providerRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        $year = $request->get('year', false);
        $month = $request->get('month', false);
        $day = $request->get('day', false);
        $providerId = $request->get('provider');
        $level = $request->get('level', false);
        $state = $request->get('state', false);
        $type = $request->get('type', false);

        [$year, $month, $day, $maxYear, $minYear, $maxDay, $isFullYear, $isFullMonth] = $this->calendar($year, $month, $day);

        if ($isFullYear) {
            $date = new \DateTime(implode('-', [$year, '01', '01']));
            $startDate = $date->format('Y-01-01 00:00:00');
            $endDate = $date->format('Y-12-31 23:59:59');
            $header = $date->format('Y');
        } elseif ($isFullMonth) {
            $date = new \DateTime(implode('-', [$year, $month, '01']));
            $startDate = $date->format('Y-m-01 00:00:00');
            $endDate = $date->format('Y-m-t 23:59:59');
            $header = $date->format('M Y');
        } else {
            $date = new \DateTime(implode('-', [$year, $month, $day]));
            $startDate = $date->format('Y-m-d 00:00:00');
            $endDate = $date->format('Y-m-d 23:59:59');
            $header = $date->format('M j, Y');
        }

        if ($providerId) {
            if ($providerId == 'all') {
                $providerName = 'All Providers';
            } else {
                $provider = $providerRep->find($providerId);

                if (!$provider) {
                    $this->createNotFoundException();
                }
                $providerName = $provider->getShortname();
            }
        } else {
            $providerId = '';
            $providerName = 'Custom Segments';
        }

        if ($state === false) {
            $state = 'all';
        }

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        $q = $connection->executeQuery("
            select
              ts.TripSegmentID as segmentId,
              DATE_FORMAT(ts.DepDate, '%M %e, %Y') as departureDate,
              ts.AirlineName as segmentAirline,
              ts.FlightNumber as segmentFlight,
              ts.DepCode as segmentDeparture,
              ts.ArrCode as segmentArrival,
              fi.FlightInfoID as flightinfoId,
              fi.Airline as flightinfoAirline,
              fi.FlightNumber as flightinfoFlight,
              fi.DepCode as flightinfoDeparture,
              fi.ArrCode as flightinfoArrival,
              fi.State as flightinfoState,
              fi.FlightState as flightinfoFlightState,
              fi.CreateDate as flightinfoCreate,
              fi.UpdateDate as flightinfoUpdate,
              fi.ChecksCount as flightinfoChecks,
              fi.SubscribesCount as flightinfoSubscribes,
              fi.UpdatesCount as flightinfoUpdates,
              fi.ErrorsCount as flightinfoErrors,
              t.TripID as tripId,
              t.UserAgentID as userAgent,
              t.AirlineName as tripAirline,
              t.Category AS tripCategory,
              t.ShareCode as shareCode,
              p.ProviderID AS providerId,
              p.Kind AS providerKind,
              p.ShortName AS providerName,
              p.IATACode AS providerIATA,
              u.UserID as userId,
              u.Login as userLogin,
              u.AccountLevel as userLevel,
              (ts.DepCode <> '' AND ts.ArrCode <> '' AND ts.FlightNumber <> '' AND ts.FlightNumber <> 'n/a') AS filled,
              (t.Hidden OR ts.Hidden) AS hidden,
              t.Cancelled AS cancelled
            from TripSegment ts
              join Trip t on ts.TripID = t.TripID
              join Usr u on t.UserID = u.UserID
              left join FlightInfo fi on ts.FlightInfoID = fi.FlightInfoID
              left join Provider p on p.ProviderID = t.ProviderID
            where ts.DepDate >= :start_date AND ts.DepDate <= :end_date
              " . ($providerId ? ($providerId == 'all' ? "" : "and t.ProviderID = :provider") : "and t.ProviderID is null") . "
              " . ($state !== 'all' ? "and fi.State = :state" : "") . "
              " . ($level ? 'and u.AccountLevel = :level' : '') . "
              " . ($type == 'nonflights' ? "and t.Category <> :category" : "") . "
              " . (($type && $type != 'nonflights') ? "and t.Category = :category" : "") . "
              " . ($type == 'hidden' ? "having hidden = 1" : "") . "
              " . ($type == 'cancelled' ? "having hidden = 0 and cancelled = 1" : "") . "
              " . ($type == 'flights' ? "having hidden = 0 and cancelled = 0 and filled = 1" : "") . "
              " . ($type == 'broken' ? "having hidden = 0 and cancelled = 0 and filled = 0" : "") . "
            order by departureDate, ts.FlightInfoID desc, segmentDeparture, segmentArrival, segmentFlight
            limit 1000
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':provider' => $providerId,
            ':state' => $state,
            ':level' => $level,
            ':category' => TRIP_CATEGORY_AIR,
        ]);

        $data = $q->fetchAll();

        $q = $connection->executeQuery("
            select count(*) as cnt,
              p.ProviderID AS providerId,
              p.ShortName AS providerName,
              p.IATACode AS providerIATA
            from TripSegment ts
              join Trip t on ts.TripID = t.TripID
              left join Provider p on p.ProviderID = t.ProviderID
            where ts.DepDate >= :start_date AND ts.DepDate <= :end_date
            group by providerId
            order by providerName
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        $providers = $q->fetchAll();

        return [
            'headerDate' => $header,
            'providerName' => $providerName,
            'data' => $data,
            'state' => $state,
            'provider' => $providerId,
            'providers' => $providers,
            'level' => $level,
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'days' => $maxDay,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
        ];
    }

    /**
     * @Route("/requests-list", name="aw_manager_flightinfo_requests_list", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/requestsList.html.twig")
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function requestsListAction(Request $request)
    {
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);

        $year = $request->get('year', false);
        $month = $request->get('month', false);
        $day = $request->get('day', false);
        $airline = $request->get('airline');
        $level = $request->get('level', false);
        $state = $request->get('state', false);
        $type = $request->get('type', false);

        [$year, $month, $day, $maxYear, $minYear, $maxDay, $isFullYear, $isFullMonth] = $this->calendar($year, $month, $day);

        if ($isFullYear) {
            $date = new \DateTime(implode('-', [$year, '01', '01']));
            $startDate = $date->format('Y-01-01 00:00:00');
            $endDate = $date->format('Y-12-31 23:59:59');
            $header = $date->format('Y');
        } elseif ($isFullMonth) {
            $date = new \DateTime(implode('-', [$year, $month, '01']));
            $startDate = $date->format('Y-m-01 00:00:00');
            $endDate = $date->format('Y-m-t 23:59:59');
            $header = $date->format('M Y');
        } else {
            $date = new \DateTime(implode('-', [$year, $month, $day]));
            $startDate = $date->format('Y-m-d 00:00:00');
            $endDate = $date->format('Y-m-d 23:59:59');
            $header = $date->format('M j, Y');
        }

        if ($airline) {
            if ($airline == 'all') {
                $airlineName = 'All Airlines';
            } else {
                $Airlines = $airlineRep->findBy(['code' => $airline]);

                if (!$Airlines) {
                    $this->createNotFoundException();
                }
                $airlineName = $Airlines[0]->getName();
            }
        } else {
            $airline = 'all';
            $airlineName = 'All Airlines';
        }

        if ($state === false) {
            $state = 'all';
        }

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        $q = $connection->executeQuery("
            select
              DATE_FORMAT(fi.FlightDate, '%M %e, %Y') as departureDate,
              fi.FlightInfoID as flightinfoId,
              fi.Airline as flightinfoAirline,
              fi.FlightNumber as flightinfoFlight,
              fi.DepCode as flightinfoDeparture,
              fi.ArrCode as flightinfoArrival,
              fi.State as flightinfoState,
              fi.FlightState as flightinfoFlightState,
              fi.CreateDate as flightinfoCreate,
              fi.UpdateDate as flightinfoUpdate,
              fi.ChecksCount as flightinfoChecks,
              fi.SubscribesCount as flightinfoSubscribes,
              fi.UpdatesCount as flightinfoUpdates,
              fi.ErrorsCount as flightinfoErrors,
              count(distinct ts.TripSegmentID) as segmentsCount,
              fi.Properties as properties,
              u.AccountLevel as userLevel,
              (ts.DepCode <> '' AND ts.ArrCode <> '' AND ts.FlightNumber <> '' AND ts.FlightNumber <> 'n/a') AS filled,
              (t.Hidden OR ts.Hidden) AS hidden,
              t.Cancelled AS cancelled
            from FlightInfo fi
              left join TripSegment ts on ts.FlightInfoID = fi.FlightInfoID
              left join Trip t on ts.TripID = t.TripID
              left join Usr u on t.UserID = u.UserID
              left join Provider p on p.ProviderID = t.ProviderID
            where fi.FlightDate >= :start_date AND fi.FlightDate <= :end_date
              " . ($airline == 'all' ? "" : "and fi.Airline = :airline") . "
              " . ($state !== 'all' ? "and fi.State = :state" : "") . "
              " . ($level ? 'and u.AccountLevel = :level' : '') . "
              " . ($type == 'nonflights' ? "and t.Category <> :category" : "") . "
              " . (($type && $type != 'nonflights') ? "and t.Category = :category" : "") . "
            group by fi.FlightInfoID
              " . ($type == 'hidden' ? "having hidden = 1" : "") . "
              " . ($type == 'cancelled' ? "having hidden = 0 and cancelled = 1" : "") . "
              " . ($type == 'flights' ? "having hidden = 0 and cancelled = 0 and filled = 1" : "") . "
              " . ($type == 'broken' ? "having hidden = 0 and cancelled = 0 and filled = 0" : "") . "
            order by fi.FlightInfoID desc
            limit 100
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':airline' => $airline,
            ':state' => $state,
            ':level' => $level,
            ':category' => TRIP_CATEGORY_AIR,
        ]);

        $data = $q->fetchAll();

        $ids = [];

        foreach ($data as &$d) {
            $ids[] = $d['flightinfoId'];
            $d['diff'] = '';
            $d['same'] = '';
            $properties = @unserialize($d['properties']);

            if (!is_array($properties)) {
                continue;
            }

            if (array_key_exists('sita_aero.flight_info', $properties) && array_key_exists('flight_stats.flight_status', $properties)) {
                $diff = [];
                $same = [];
                $info = $properties['info'];
                $sa = $properties['sita_aero.flight_info'];
                $fs = $properties['flight_stats.flight_status'];

                foreach ($info as $key => $value) {
                    if ($key == 'Aircraft') {
                        continue;
                    }
                    $saValue = $sa[$key] ?? null;
                    $fsValue = $fs[$key] ?? null;

                    if (in_array($key, ['DepDate', 'ArrDate', 'DepDateUtc', 'ArrDateUtc'])) {
                        $saValue = (new \DateTime($saValue))->getTimestamp();
                        $fsValue = (new \DateTime($fsValue))->getTimestamp();
                    }

                    if ($saValue != $fsValue) {
                        $diff[] = $key;
                    } else {
                        $same[] = $key;
                    }
                }
                $d['diff'] = implode(', ', $diff);
                $d['same'] = implode(', ', $same);
            }
        }
        $ids = implode(',', $ids);

        $q = $connection->executeQuery("
            select count(*) as cnt,
              fi.Airline AS code,
              a.Name AS name
            from FlightInfo fi
              left join Airline a on fi.Airline = a.Code
            where fi.FlightDate >= :start_date AND fi.FlightDate <= :end_date
            group by fi.Airline
            order by a.Name
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        $airlines = $q->fetchAll();

        return [
            'headerDate' => $header,
            'airlineName' => $airlineName,
            'data' => $data,
            'state' => $state,
            'airline' => $airline,
            'airlines' => $airlines,
            'level' => $level,
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'days' => $maxDay,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
            'config' => $this->flightInfo->getConfig(),
            'ids' => $ids,
        ];
    }

    /**
     * @Route("/logstat", name="aw_manager_flightinfo_logstat", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/logstatList.html.twig")
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function logstatListAction(Request $request)
    {
        $year = $request->get('year', false);
        $month = $request->get('month', false);
        $day = $request->get('day', false);

        [$year, $month, $day, $maxYear, $minYear, $maxDay, $isFullYear, $isFullMonth] = $this->calendar($year, $month, $day);

        if ($isFullYear) {
            $date = new \DateTime(implode('-', [$year, '01', '01']));
            $startDate = $date->format('Y-01-01 00:00:00');
            $endDate = $date->format('Y-12-31 23:59:59');
            $header = $date->format('Y');
        } elseif ($isFullMonth) {
            $date = new \DateTime(implode('-', [$year, $month, '01']));
            $startDate = $date->format('Y-m-01 00:00:00');
            $endDate = $date->format('Y-m-t 23:59:59');
            $header = $date->format('M Y');
        } else {
            $date = new \DateTime(implode('-', [$year, $month, $day]));
            $startDate = $date->format('Y-m-d 00:00:00');
            $endDate = $date->format('Y-m-d 23:59:59');
            $header = $date->format('M j, Y');
        }

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        $q = $connection->executeQuery("
            select
              fil.Service as service,
              fil.State as state,
              count(*) as cnt
            from FlightInfoLog fil
            where fil.CreateDate >= :start_date AND fil.CreateDate <= :end_date
            group by fil.Service, fil.State
            order by fil.CreateDate desc, fil.State asc
        ", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        $rows = $q->fetchAll();

        $data = [];

        foreach ($rows as $row) {
            if (!array_key_exists($row['service'], $data)) {
                $data[$row['service']] = [0, 0, 0, 0];
            }
            $data[$row['service']][$row['state']] = $row['cnt'];
        }

        return [
            'headerDate' => $header,
            'data' => $data,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'days' => $maxDay,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
        ];
    }

    /**
     * @Route("/view/{id}", name="aw_manager_flightinfo_view", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/view.html.twig")
     * @return array
     */
    public function viewAction($id, Request $request)
    {
        $fiRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfo::class);

        $flightInfo = $fiRep->find($id);

        if (empty($flightInfo)) {
            throw $this->createNotFoundException();
        }

        $schedule = $flightInfo->getSchedule();
        $config = $this->flightInfo->getConfig();

        foreach ($schedule as $i => &$task) {
            $task['task'] = $task[0];
            $task['published'] = $task[1] ?? '';
            $task['finalized'] = $task[2] ?? '';
            $task['result'] = $task[3] ?? '';

            if (array_key_exists($task[0], $config)) {
                $task['config'] = $config[$task[0]];
            }
        }

        foreach ($schedule as $t) {
            if (array_key_exists($t[0], $config) && !isset($t[1])) {
                unset($config[$t[0]]);
            }
        }

        $diff = [];

        if ($flightInfo->isLoaded()) {
            $properties = $flightInfo->getProperties();
            $diff['info'] = $properties['info'];

            foreach ($properties as $service => $values) {
                if (strpos($service, ':log') !== false) {
                    continue;
                }

                if ($service == 'info') {
                    continue;
                }
                $diff[$service] = $values;
            }
        }

        return [
            'flightInfo' => $flightInfo,
            'logs' => $this->flightInfo->getLogs($flightInfo),
            'schedule' => $schedule,
            'config' => $config,
            'diff' => $diff,
            'diffCols' => array_keys($diff),
        ];
    }

    /**
     * @Route("/update-all", name="aw_manager_flightinfo_update_all", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/updateAll.html.twig")
     */
    public function updateAllAction()
    {
        $updated = $this->flightInfo->bindAll();
        $scheduled = $this->flightInfo->scheduleAll();

        return [
            'updated' => $updated,
            'scheduled' => $scheduled,
        ];
    }

    /**
     * @Route("/update", name="aw_manager_flightinfo_update", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(Request $request, ItineraryTracker $diffTracker)
    {
        $flightInfoRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfo::class);

        $ajax = $request->get('ajax');
        $id = $request->get('id');
        $ids = $request->get('ids');
        $rule = $request->get('rule');

        if ($ids) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) && $id) {
            $ids = [$id];
        }

        if (!empty($ids) && $rule) {
            foreach ($ids as $id) {
                $flightInfo = $flightInfoRep->find($id);

                if ($flightInfo) {
                    $published = $flightInfo->getPublishedTasks();

                    if (!in_array($rule, $published)) {
                        $flightInfo->scheduleTask($rule, false);
                        $flightInfo->publishTask($rule);
                    }
                    $this->flightInfo->update($flightInfo, $rule);
                    $this->flightInfo->schedule($flightInfo);

                    if ($flightInfo->isLoaded()) {
                        $oldProperties = [];

                        foreach ($flightInfo->getSegments() as $segment) {
                            $key = 'T.' . $segment->getTripid()->getId();

                            if (!array_key_exists($key, $oldProperties)) {
                                $oldProperties[$key] = ['changes' => $diffTracker->getProperties($key), 'userId' => $segment->getTripid()->getUser()->getUserid()];
                            }
                        }
                        $this->flightInfo->updateTripSegments($flightInfo);

                        foreach ($oldProperties as $key => $data) {
                            $diffTracker->recordChanges($data['changes'], $key, $data['userId'], true);
                        }
                    }
                }
            }
        }

        if ($ajax) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirect($request->server->get("HTTP_REFERER"));
    }

    /**
     * @Route("/schedule", name="aw_manager_flightinfo_schedule", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function scheduleAction(Request $request)
    {
        $flightInfoRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfo::class);

        $ajax = $request->get('ajax');
        $id = $request->get('id');
        $rule = $request->get('rule');

        if ($id && $rule) {
            $flightInfo = $flightInfoRep->find($id);

            if ($flightInfo) {
                $flightInfo->scheduleTask($rule, false);
                $this->getDoctrine()->getManager()->flush($flightInfo);
                $this->flightInfo->schedule($flightInfo);
            }
        }

        if ($ajax) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirect($request->server->get("HTTP_REFERER"));
    }

    /**
     * @Route("/relink", name="aw_manager_flightinfo_relink", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function relinkAction(Request $request)
    {
        $tsRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);

        $ajax = $request->get('ajax');
        $id = $request->get('id');

        if ($id) {
            $segment = $tsRep->find($id);

            if ($segment) {
                $this->flightInfo->applyToTripsegmentAndCopies($segment);
            }
        }

        if ($ajax) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirect($request->server->get("HTTP_REFERER"));
    }

    /**
     * @Route("/relink-broken", name="aw_manager_flightinfo_relink_broken", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function relinkAllAction(Request $request, RouterInterface $router)
    {
        $tsRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);

        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();

        $q = $connection->executeQuery("
            select ts.TripSegmentID
            from TripSegment ts
            join Trip t on ts.TripID = t.TripID
            left join FlightInfo fi on ts.FlightInfoID = fi.FlightInfoID
            where (fi.FlightState = :state_airline or fi.FlightState = :state_number)
              and ts.DepCode <> ''
              and ts.ArrCode <> ''
              and ts.FlightNumber <> ''
              and ts.FlightNumber <> 'n/a'
              and t.Category = :category
            order by DepDate
        ", [
            ':state_airline' => FlightInfo::FLIGHTSTATE_ERROR_AIRLINE,
            ':state_number' => FlightInfo::FLIGHTSTATE_ERROR_NUMBER,
            ':category' => TRIP_CATEGORY_AIR,
        ]);

        while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
            $segment = $tsRep->find($row['TripSegmentID']);

            if ($segment) {
                $this->flightInfo->applyToTripsegmentAndCopies($segment);
            }
        }

        return $this->redirect($router->generate('aw_manager_flightinfo_index'));
    }

    /**
     * @Route("/create-alias", name="aw_manager_flightinfo_create_alias", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/createAlias.html.twig")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAliasAction(Request $request)
    {
        $tsRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class);
        $airlineRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class);
        $airlineAliasRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AirlineAlias::class);

        $id = $request->get('id');

        if ($id) {
            $segment = $tsRep->find($id);

            if ($segment) {
                $airlineName = $segment->getAirlineName();

                if (empty($airlineName)) {
                    $airlineName = $segment->getTripid()->getAirlinename();
                }
            }
        }

        if (empty($airlineName)) {
            return $this->redirect($request->server->get("HTTP_REFERER"));
        }

        if ($request->getMethod() === Request::METHOD_POST) {
            return $this->redirect($request->server->get("HTTP_REFERER"));
        }

        return [
        ];
    }

    /**
     * @Route("/list-logs", name="aw_manager_flightinfo_list_logs", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfo/listLogs.html.twig")
     */
    public function listLogsAction(Request $request, Paginator $paginator)
    {
        $fiLogRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoLog::class);

        $month = $request->get('month');
        $day = $request->get('day');

        if (empty($month) && empty($day)) {
            $day = (new \DateTime())->format('Y-m-d');
        }

        if ($month) {
            $month = new \DateTime($month);

            $headerDate = $month->format('M Y');
            $query = $fiLogRep->createQueryBuilder('fi')->select('fi')->where('fi.CreateDate >= :start_date')->andWhere('fi.CreateDate < :end_date')->orderBy('fi.CreateDate', 'desc')
                ->setParameter('start_date', $month->format('Y-m-00'))
                ->setParameter('end_date', $month->add(\DateInterval::createFromDateString('+1 month'))->format('Y-m-00'));
            $pagination = $paginator->paginate($query, $request->query->get('page', 1), self::perPage);
            $data = $pagination->getItems();
        } elseif ($day) {
            $day = new \DateTime($day);

            $headerDate = $day->format('M j, Y');
            $query = $fiLogRep->createQueryBuilder('fi')->select('fi')->where('fi.CreateDate >= :start_date')->andWhere('fi.CreateDate < :end_date')->orderBy('fi.CreateDate', 'desc')
                ->setParameter('start_date', $day->format('Y-m-d 00:00:00'))
                ->setParameter('end_date', $day->format('Y-m-d 23:59:59'));
            $pagination = $paginator->paginate($query, $request->query->get('page', 1), self::perPage);
            $data = $pagination->getItems();
        } else {
            throw $this->createNotFoundException();
        }

        return [
            'headerDate' => $headerDate,
            'data' => $data,
            'pagination' => $pagination,
        ];
    }

    /**
     * @return array
     */
    private function calendar($year, $month, $day)
    {
        $currentDate = new \DateTime();
        $currentYear = intval($currentDate->format('Y'));
        $maxYear = $currentYear + 2;
        $minYear = 2000;
        $currentMonth = intval($currentDate->format('m'));
        $maxMonth = 12;
        $minMonth = 1;
        $currentDay = intval($currentDate->format('d'));
        $maxDay = intval($currentDate->format('t'));
        $minDay = 1;

        $isFullYear = false;
        $isFullMonth = false;

        if ($year === false) {
            $year = $currentYear;
            $month = $currentMonth;
            $day = $currentDay;
        } else {
            if ($year < $minYear) {
                $year = $minYear;
            }

            if ($year > $maxYear) {
                $year = $maxYear;
            }

            if ($month === false) {
                if ($year == $currentYear) {
                    $month = $currentMonth;
                } else {
                    $month = $minMonth;
                }
                $isFullMonth = true;
                $day = 0;
            } elseif ($month == 0) {
                $isFullYear = true;
                $day = 0;
            } else {
                if ($month < $minMonth) {
                    $month = $minMonth;
                }

                if ($month > $maxMonth) {
                    $month = $maxMonth;
                }

                if ($day === false) {
                    $isFullMonth = true;
                    $day = 0;
                } elseif ($day == 0) {
                    $isFullMonth = true;
                } else {
                    $maxDay = intval((new \DateTime(implode('-', [$year, $month, '01'])))->format('t'));

                    if ($day < $minDay) {
                        $day = $minDay;
                    }

                    if ($day > $maxDay) {
                        $day = $maxDay;
                    }
                }
            }
        }

        return [$year, $month, $day, $maxYear, $minYear, $maxDay, $isFullYear, $isFullMonth];
    }
}
