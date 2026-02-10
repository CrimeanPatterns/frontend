<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\AbstractItineraryMatcher;
use AwardWallet\MainBundle\Service\ProviderNameResolver;
use AwardWallet\MainBundle\Timeline\SegmentMapItem;
use AwardWallet\MainBundle\Timeline\SegmentMapSourceInterface;
use AwardWallet\Schema\Itineraries\Bus as SchemaBusRide;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\Ferry as SchemaFerry;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\Train as SchemaTrainRide;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TripRepository extends EntityRepository implements ItineraryRepositoryInterface, SegmentMapSourceInterface
{
    /**
     * @var ProviderNameResolver
     */
    private $providerNameResolver;

    public function TripsSQL($filter = [])
    {
        $filter[] = "t.TripID = ts.TripID";
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT   t.TripID AS ID                                    ,
			         'T'      AS Kind                                  ,
			         t.Category                                        ,
			         COALESCE(p.Name, tp.Name) AS ProviderName         ,
			         MAX(CONCAT(COALESCE(p.ShortName, p.Name, tp.Name, ts.AirLineName),' ', COALESCE(ts.FlightNumber,''), ' from ', COALESCE(IF(ts.DepCode = '', NULL, ts.DepCode), IF(ts.DepName = '', NULL, ts.DepName)))) AS ProviderNameMobile,
			         t.TravelPlanID                                    ,
			         t.Cancelled                                       ,
			         MIN(ts.DepDate) AS StartDate                      ,
			         MAX(ts.ArrDate) AS EndDate                        ,
			         t.Hidden                                          ,
			         t.AccountID                                       ,
			         t.Parsed                                          ,
			         COALESCE(a.ProviderID, t.ProviderID) AS ProviderID,
			         COALESCE(a.UserID, t.UserID)         AS UserID    ,
			         t.UserAgentID                                     ,
			         t.Moved                                           ,
			         t.RecordLocator AS ConfirmationNumber			   ,
			         t.ConfFields,
			         10 as SortIndex
			FROM     Trip t
			         LEFT OUTER JOIN Account a
			         ON       a.AccountID = t.AccountID
			         LEFT OUTER JOIN Provider p
			         ON       a.ProviderID = p.ProviderID
			         LEFT OUTER JOIN Provider tp
			         ON       t.ProviderID = tp.ProviderID,
			                  TripSegment ts 
			{$filterStr}
			GROUP BY ID          ,
			         Kind        ,
			         Category    ,
			         ProviderName,
			         TravelPlanID,
			         Cancelled,
			         Hidden      ,
			         AccountID      ,
			         Parsed      ,
			         ProviderID,
			         UserID      ,
			         UserAgentID      ,
			         t.Moved     ,
			         t.RecordLocator,
			         t.ConfFields
		";
        $s = str_ireplace('[StartDate]', 'ts.DepDate', $s);

        return $s;
    }

    public function TripSegmentsSourceSQL($filter = [])
    {
        $filterStr = (count($filter) > 0) ? "WHERE " . implode(" AND ", $filter) : "";
        $s = "
			SELECT   t.TripID AS ID                                    ,
			         'T'      AS Kind                                  ,
                     concat('S.', ts.TripSegmentID) as SourceID        ,
                     t.MailDate
              FROM TripSegment ts LEFT JOIN Trip t ON t.TripID = ts.TripID
			{$filterStr}
		";
        $s = str_ireplace('[StartDate]', 'ts.DepDate', $s);

        return $s;
    }

    public function getFlighStatusFilters()
    {
        $filters = \Cache::getInstance()->get('extensionMobileFilters');

        if ($filters === false) {
            $filters = [];

            $root = realpath(__DIR__ . '/../../../../..');
            $files = glob($root . '/engine/*/extensionMobile.js');

            foreach ($files as $filename) {
                $provider = basename(dirname($filename));
                $content = file_get_contents($filename);

                if (preg_match('/match:\s*(\/.+\/[A-z]+),/U', $content, $matches) && !preg_match('/fakeflightStatus:/U', $content)) {
                    if (!empty($matches[1])) {
                        $filters[$provider] = $matches[1];
                    }
                }
            }

            \Cache::getInstance()->set('extensionMobileFilters', $filters, 2 * 60);
        }

        return $filters;
    }

    public function getPhones($tripId)
    {
        return null;
    }

    public function getCountTripsByUser($userID)
    {
        // @TODO here to count all trips for user
        return 0;
    }

    public function getDetailsCountTripsByUser($userID)
    {
        if ($userID instanceof \AwardWallet\MainBundle\Entity\Usr) {
            $usr = $userID;
            $userID = $usr->getUserid();
        }
        $connection = $this->getEntityManager()->getConnection();
        $userAgentRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $userRep = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $contacts = $userAgentRep->getOtherUsers($userID);
        $all = [
            'UserName' => 'All',
            'UserAgentID' => null,
            'Count' => $this->getCountTripsByUser($userID),
        ];
        $otherCount = 0;

        foreach ($contacts as $k => $v) {
            $r = 0; // @TODO here supposed to count all trips for subaccount
            $contacts[$k]['Count'] = $r;
            $otherCount += $r;
        }

        if (!isset($usr)) {
            $usr = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userID);
        }

        array_unshift($contacts, [
            'FirstName' => $usr->getFirstname(),
            'LastName' => $usr->getLastname(),
            'UserName' => $usr->getFullName(),
            'UserID' => $userID,
            'UserAgentID' => null,
            'ClientID' => null,
            'AccountLevel' => $usr->getAccountlevel(),
            'Company' => $usr->getCompany(),
            'Count' => $all['Count'] - $otherCount,
        ]);
        array_unshift($contacts, $all);

        return $contacts;
    }

    /**
     * @return SegmentMapItem[]
     */
    public function getTimelineMapItems(Usr $user, ?Useragent $useragent = null): array
    {
        $conditions = [
            't.UserID = :userid',
        ];
        $params['userid'] = $user->getUserid();

        if ($useragent) {
            $conditions[] = 't.UserAgentID = :useragentid';
            $params['useragentid'] = $useragent->getUseragentid();
        } else {
            $conditions[] = 't.UserAgentID IS NULL';
        }

        $conditions = implode(' AND ', $conditions);

        $stmt = $this->_em->getConnection()->prepare(
            "
            SELECT
                r.TripSegmentID as id,
                r.DepDate as startDate,
                r.ArrDate as endDate,
                r.Hidden OR t.Hidden as deleted,
                'T' as type,
                CONCAT('T.', r.TripID) as shareId,
                gtDep.TimeZoneLocation as depTimezone,
                gtArr.TimeZoneLocation as arrTimezone
            FROM TripSegment r
            left join GeoTag gtDep on r.DepGeoTagID = gtDep.GeoTagID
            left join GeoTag gtArr on r.ArrGeoTagID = gtArr.GeoTagID
            JOIN Trip t ON t.TripID = r.TripID
            WHERE
                {$conditions}"
        );

        $stmt->execute($params);
        $utcTimezone = new \DateTimeZone('UTC');

        $result = [];

        foreach ($stmt->fetchAll(AbstractQuery::HYDRATE_ARRAY) as $row) {
            $row['startDate'] = new \DateTime(
                $row['startDate'],
                $row['depTimezone'] === null ?
                    $utcTimezone :
                    DateTimeUtils::timezoneFromString($row['depTimezone'])
            );
            $row['endDate'] = new \DateTime(
                $row['endDate'],
                $row['arrTimezone'] === null ?
                    $utcTimezone :
                    DateTimeUtils::timezoneFromString($row['arrTimezone'])
            );
            unset(
                $row['arrTimezone'],
                $row['depTimezone']
            );

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     * @api
     */
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->providerNameResolver = $container->get(ProviderNameResolver::class);
    }

    public function findMatchingCandidates(Usr $owner, $schemaItinerary): array
    {
        switch (true) {
            case $schemaItinerary instanceof SchemaFlight:
                return $this->findMatchingCandidatesForFlight($owner, $schemaItinerary);

            case $schemaItinerary instanceof SchemaTrainRide:
                return $this->findMatchingCandidatesForTrainRide($owner, $schemaItinerary);

            case $schemaItinerary instanceof SchemaBusRide:
                return $this->findMatchingCandidatesForBusRide($owner, $schemaItinerary);

            case $schemaItinerary instanceof SchemaCruise:
                return $this->findMatchingCandidatesForCruise($owner, $schemaItinerary);

            case $schemaItinerary instanceof SchemaFerry:
                return $this->findMatchingCandidatesForFerry($owner, $schemaItinerary);

            case $schemaItinerary instanceof SchemaTransfer:
                return $this->findMatchingCandidatesForTransfer($owner, $schemaItinerary);

            default:
                throw new \InvalidArgumentException("Wrong type " . get_class($schemaItinerary));
        }
    }

    public function getFutureCriteria(): Criteria
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->gt('tripSegments.arrdate', new \DateTime()));

        return $criteria;
    }

    public function findWithAirports($id): ?object
    {
        /** @var Trip $trip */
        $trip = $this->find($id);

        if (!$trip) {
            return null;
        }

        foreach ($trip->getSegments() as $segment) {
            $departureAirport = $this->findAircodeByCodeOrName($segment->getDepcode(), $segment->getDepname());

            if (null !== $departureAirport) {
                $segment->setDepartureAirport($departureAirport);
            }

            $arrivalAirport = $this->findAircodeByCodeOrName($segment->getArrcode(), $segment->getArrname());

            if (null !== $arrivalAirport) {
                $segment->setArrivalAirport($arrivalAirport);
            }
        }

        return $trip;
    }

    /**
     * @throws LooseConditionsException
     */
    private function findMatchingCandidatesForFlight(Usr $owner, SchemaFlight $schemaFlight): array
    {
        $builder = $this->createQueryBuilder('flight');
        $builder->join('flight.segments', 'segments');
        $builder->where('flight.user = :user');
        $builder->andWhere("flight.category = " . Trip::CATEGORY_AIR);
        $builder->setParameter('user', $owner);

        $searchConditions = [];
        $numbers = [];
        $params = [];

        foreach ($schemaFlight->segments as $flightSegment) {
            if ($flightSegment->marketingCarrier->confirmationNumber !== null) {
                $numbers[] = $flightSegment->marketingCarrier->confirmationNumber;
            } elseif (null !== $schemaFlight->travelAgency && !empty($schemaFlight->travelAgency->confirmationNumbers)) {
                $numbers = array_merge($numbers, array_map(function (ConfNo $number) {
                    return $number->number;
                }, $schemaFlight->travelAgency->confirmationNumbers));
            } elseif (null !== $flightSegment->operatingCarrier && $flightSegment->operatingCarrier->confirmationNumber !== null) {
                $numbers[] = $flightSegment->operatingCarrier->confirmationNumber;
            } elseif (null !== $flightSegment->marketingCarrier->flightNumber) {
                $searchConditions[] = $builder->expr()->eq('segments.flightNumber', ":param" . count($params));
                $params[] = $flightSegment->marketingCarrier->flightNumber;
            }
        }

        if (null !== $schemaFlight->issuingCarrier && $schemaFlight->issuingCarrier->confirmationNumber !== null) {
            $numbers[] = $schemaFlight->issuingCarrier->confirmationNumber;
        }

        if (!empty($numbers)) {
            $numbers = array_map([AbstractItineraryMatcher::class, 'filterConfirmationNumber'], $numbers);
            $searchConditions[] = $builder->expr()->andX(
                $builder->expr()->in("filterConfirmationNumber(segments.marketingAirlineConfirmationNumber)", ':numbers'),
                $builder->expr()->eq("segments.hidden", 0)
            );
            $searchConditions[] = $builder->expr()->andX(
                $builder->expr()->in("filterConfirmationNumber(segments.operatingAirlineConfirmationNumber)", ":numbers"),
                $builder->expr()->eq("segments.hidden", 0)
            );
            $searchConditions[] = $builder->expr()->in("filterConfirmationNumber(flight.travelAgencyConfirmationNumbers)", ':numbers');
            $searchConditions[] = $builder->expr()->in("filterConfirmationNumber(flight.issuingAirlineConfirmationNumber)", ":numbers");
            $builder->setParameter("numbers", $numbers);
        }

        foreach ($schemaFlight->segments as $n => $segment) {
            $searchConditions[] = $builder->expr()->andX(
                $builder->expr()->eq("segments.scheduledDepDate", ":depDate$n"),
                $builder->expr()->eq("segments.scheduledArrDate", ":arrDate$n"),
                $builder->expr()->eq("segments.depcode", ":depCode$n"),
                $builder->expr()->eq("segments.arrcode", ":arrCode$n")
            );
            $builder->setParameter("depDate$n", new \DateTime($segment->departure->localDateTime));
            $builder->setParameter("arrDate$n", new \DateTime($segment->arrival->localDateTime));
            $builder->setParameter("depCode$n", $segment->departure->airportCode);
            $builder->setParameter("arrCode$n", $segment->arrival->airportCode);
        }

        if (empty($searchConditions)) {
            throw new LooseConditionsException("Searching conditions are too loose! Aborting.");
        }
        $builder->andWhere($builder->expr()->orX(...$searchConditions));

        foreach ($params as $index => $value) {
            $builder->setParameter("param" . $index, $value);
        }

        return $builder->getQuery()->getResult();
    }

    private function findMatchingCandidatesForTrainRide(Usr $owner, SchemaTrainRide $schemaTrainRide): array
    {
        $confirmationNumbers = [];
        $scheduleNumbers = [];
        $departureDates = [];
        $arrivalDates = [];
        $departureStationCodes = [];
        $arrivalStationCodes = [];
        $departureStationNames = [];
        $arrivalStationNames = [];

        foreach ($schemaTrainRide->segments as $trainRideSegment) {
            $scheduleNumbers[] = $trainRideSegment->scheduleNumber;
            $departureDates[] = $trainRideSegment->departure->localDateTime;
            $departureStationCodes[] = $trainRideSegment->departure->stationCode;
            $departureStationNames[] = $trainRideSegment->departure->name;
            $arrivalDates[] = $trainRideSegment->arrival->localDateTime;
            $arrivalStationCodes[] = $trainRideSegment->arrival->stationCode;
            $arrivalStationNames[] = $trainRideSegment->arrival->name;
        }

        if (null !== $schemaTrainRide->confirmationNumbers) {
            $confirmationNumbers = array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaTrainRide->confirmationNumbers);
        }

        if (null !== $schemaTrainRide->travelAgency && !empty($schemaTrainRide->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaTrainRide->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('trainRide');
        $builder->join('trainRide.segments', 'segments');
        $builder->where('trainRide.user = :user');
        $builder->andWhere("trainRide.category = " . Trip::CATEGORY_TRAIN);
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('segments.flightNumber', ':scheduleNumbers'),
            $builder->expr()->in('segments.depdate', ':departureDates'),
            $builder->expr()->in('segments.depcode', ':departureStationCodes'),
            $builder->expr()->in('segments.depname', ':departureStationNames'),
            $builder->expr()->in('segments.arrdate', ':arrivalDates'),
            $builder->expr()->in('segments.arrcode', ':arrivalStationCodes'),
            $builder->expr()->in('segments.arrname', ':arrivalStationNames'),
            $builder->expr()->in('trainRide.confirmationNumber', ':confirmationNumbers'),
            $builder->expr()->in('trainRide.travelAgencyConfirmationNumbers', ':confirmationNumbers')
        ));
        $builder->setParameter('scheduleNumbers', array_unique(array_filter($scheduleNumbers)));
        $builder->setParameter('confirmationNumbers', array_unique(array_filter($confirmationNumbers)));
        $builder->setParameter('departureDates', array_unique($departureDates));
        $builder->setParameter('departureStationCodes', array_unique(array_filter($departureStationCodes)));
        $builder->setParameter('departureStationNames', array_unique($departureStationNames));
        $builder->setParameter('arrivalDates', array_unique($arrivalDates));
        $builder->setParameter('arrivalStationCodes', array_unique(array_filter($arrivalStationCodes)));
        $builder->setParameter('arrivalStationNames', array_unique($arrivalStationNames));

        return $builder->getQuery()->getResult();
    }

    private function findMatchingCandidatesForBusRide(Usr $owner, SchemaBusRide $schemaBusRide): array
    {
        $confirmationNumbers = [];
        $scheduleNumbers = [];
        $departureDates = [];
        $arrivalDates = [];
        $departureStationCodes = [];
        $arrivalStationCodes = [];
        $departureStationNames = [];
        $arrivalStationNames = [];

        foreach ($schemaBusRide->segments as $busRideSegment) {
            $scheduleNumbers[] = $busRideSegment->scheduleNumber;
            $departureDates[] = $busRideSegment->departure->localDateTime;
            $departureStationCodes[] = $busRideSegment->departure->stationCode;
            $departureStationNames[] = $busRideSegment->departure->name;
            $arrivalDates[] = $busRideSegment->arrival->localDateTime;
            $arrivalStationCodes[] = $busRideSegment->arrival->stationCode;
            $arrivalStationNames[] = $busRideSegment->arrival->name;
        }

        if (null !== $schemaBusRide->confirmationNumbers) {
            $confirmationNumbers = array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaBusRide->confirmationNumbers);
        }

        if (null !== $schemaBusRide->travelAgency && !empty($schemaBusRide->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaBusRide->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('busRide');
        $builder->join('busRide.segments', 'segments');
        $builder->where('busRide.user = :user');
        $builder->andWhere("busRide.category = " . Trip::CATEGORY_BUS);
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('segments.flightNumber', ':scheduleNumbers'),
            $builder->expr()->in('segments.depdate', ':departureDates'),
            $builder->expr()->in('segments.depcode', ':departureStationCodes'),
            $builder->expr()->in('segments.depname', ':departureStationNames'),
            $builder->expr()->in('segments.arrdate', ':arrivalDates'),
            $builder->expr()->in('segments.arrcode', ':arrivalStationCodes'),
            $builder->expr()->in('segments.arrname', ':arrivalStationNames'),
            $builder->expr()->in('busRide.confirmationNumber', ':confirmationNumbers'),
            $builder->expr()->in('busRide.travelAgencyConfirmationNumbers', ':confirmationNumbers')
        ));
        $builder->setParameter('scheduleNumbers', array_unique(array_filter($scheduleNumbers)));
        $builder->setParameter('confirmationNumbers', array_unique(array_filter($confirmationNumbers)));
        $builder->setParameter('departureDates', array_unique($departureDates));
        $builder->setParameter('departureStationCodes', array_unique($departureStationCodes));
        $builder->setParameter('departureStationNames', array_unique(array_filter($departureStationNames)));
        $builder->setParameter('arrivalDates', array_unique($arrivalDates));
        $builder->setParameter('arrivalStationCodes', array_unique(array_filter($arrivalStationCodes)));
        $builder->setParameter('arrivalStationNames', array_unique($arrivalStationNames));

        return $builder->getQuery()->getResult();
    }

    private function findMatchingCandidatesForCruise(Usr $owner, SchemaCruise $schemaCruise): array
    {
        $confirmationNumbers = [];
        $departureDates = [];
        $arrivalDates = [];
        $departurePortCodes = [];
        $arrivalPortCodes = [];
        $departurePortNames = [];
        $arrivalPortNames = [];

        foreach ($schemaCruise->segments as $cruiseSegment) {
            $departureDates[] = $cruiseSegment->departure->localDateTime;
            $departurePortCodes[] = $cruiseSegment->departure->stationCode;
            $departurePortNames[] = $cruiseSegment->departure->name;
            $arrivalDates[] = $cruiseSegment->arrival->localDateTime;
            $arrivalPortCodes[] = $cruiseSegment->arrival->stationCode;
            $arrivalPortNames[] = $cruiseSegment->arrival->name;
        }

        if (null !== $schemaCruise->confirmationNumbers) {
            $confirmationNumbers = array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaCruise->confirmationNumbers);
        }

        if (null !== $schemaCruise->travelAgency && !empty($schemaCruise->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaCruise->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('cruise');
        $builder->join('cruise.segments', 'segments');
        $builder->where('cruise.user = :user');
        $builder->andWhere("cruise.category = " . Trip::CATEGORY_CRUISE);
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('segments.depdate', ':departureDates'),
            $builder->expr()->in('segments.depcode', ':departurePortCodes'),
            $builder->expr()->in('segments.depname', ':departurePortNames'),
            $builder->expr()->in('segments.arrdate', ':arrivalDates'),
            $builder->expr()->in('segments.arrcode', ':arrivalPortCodes'),
            $builder->expr()->in('segments.arrname', ':arrivalPortNames'),
            $builder->expr()->in('cruise.confirmationNumber', ':confirmationNumbers'),
            $builder->expr()->in('cruise.travelAgencyConfirmationNumbers', ':confirmationNumbers')
        ));
        $builder->setParameter('confirmationNumbers', array_unique(array_filter($confirmationNumbers)));
        $builder->setParameter('departureDates', array_unique($departureDates));
        $builder->setParameter('departurePortCodes', array_unique($departurePortCodes));
        $builder->setParameter('departurePortNames', array_unique(array_filter($departurePortNames)));
        $builder->setParameter('arrivalDates', array_unique($arrivalDates));
        $builder->setParameter('arrivalPortCodes', array_unique(array_filter($arrivalPortCodes)));
        $builder->setParameter('arrivalPortNames', array_unique($arrivalPortNames));

        return $builder->getQuery()->getResult();
    }

    private function findMatchingCandidatesForFerry(Usr $owner, SchemaFerry $schemaFerry): array
    {
        $confirmationNumbers = [];
        $departureDates = [];
        $arrivalDates = [];
        $departurePortCodes = [];
        $arrivalPortCodes = [];
        $departurePortNames = [];
        $arrivalPortNames = [];

        foreach ($schemaFerry->segments as $FerrySegment) {
            $departureDates[] = $FerrySegment->departure->localDateTime;
            $departurePortCodes[] = $FerrySegment->departure->stationCode;
            $departurePortNames[] = $FerrySegment->departure->name;
            $arrivalDates[] = $FerrySegment->arrival->localDateTime;
            $arrivalPortCodes[] = $FerrySegment->arrival->stationCode;
            $arrivalPortNames[] = $FerrySegment->arrival->name;
        }

        if (null !== $schemaFerry->confirmationNumbers) {
            $confirmationNumbers = array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaFerry->confirmationNumbers);
        }

        if (null !== $schemaFerry->travelAgency && !empty($schemaFerry->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaFerry->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('Ferry');
        $builder->join('Ferry.segments', 'segments');
        $builder->where('Ferry.user = :user');
        $builder->andWhere("Ferry.category = " . Trip::CATEGORY_FERRY);
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('segments.depdate', ':departureDates'),
            $builder->expr()->in('segments.depcode', ':departurePortCodes'),
            $builder->expr()->in('segments.depname', ':departurePortNames'),
            $builder->expr()->in('segments.arrdate', ':arrivalDates'),
            $builder->expr()->in('segments.arrcode', ':arrivalPortCodes'),
            $builder->expr()->in('segments.arrname', ':arrivalPortNames'),
            $builder->expr()->in('Ferry.confirmationNumber', ':confirmationNumbers'),
            $builder->expr()->in('Ferry.travelAgencyConfirmationNumbers', ':confirmationNumbers')
        ));
        $builder->setParameter('confirmationNumbers', array_unique(array_filter($confirmationNumbers)));
        $builder->setParameter('departureDates', array_unique($departureDates));
        $builder->setParameter('departurePortCodes', array_unique($departurePortCodes));
        $builder->setParameter('departurePortNames', array_unique(array_filter($departurePortNames)));
        $builder->setParameter('arrivalDates', array_unique($arrivalDates));
        $builder->setParameter('arrivalPortCodes', array_unique(array_filter($arrivalPortCodes)));
        $builder->setParameter('arrivalPortNames', array_unique($arrivalPortNames));

        return $builder->getQuery()->getResult();
    }

    private function findMatchingCandidatesForTransfer(Usr $owner, SchemaTransfer $schemaTransfer): array
    {
        $confirmationNumbers = [];
        $departureDates = [];
        $arrivalDates = [];
        $departureAirportCodes = [];
        $arrivalAirportCodes = [];
        $departureAirportNames = [];
        $arrivalAirportNames = [];

        foreach ($schemaTransfer->segments as $transferSegment) {
            $departureDates[] = $transferSegment->departure->localDateTime;
            $departureAirportCodes[] = $transferSegment->departure->airportCode;
            $departureAirportNames[] = $transferSegment->departure->name;
            $arrivalDates[] = $transferSegment->arrival->localDateTime;
            $arrivalAirportCodes[] = $transferSegment->arrival->airportCode;
            $arrivalAirportNames[] = $transferSegment->arrival->name;
        }

        if (null !== $schemaTransfer->confirmationNumbers) {
            $confirmationNumbers = array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaTransfer->confirmationNumbers);
        }

        if (null !== $schemaTransfer->travelAgency && !empty($schemaTransfer->travelAgency->confirmationNumbers)) {
            $confirmationNumbers = array_merge($confirmationNumbers, array_map(function (ConfNo $number) {
                return $number->number;
            }, $schemaTransfer->travelAgency->confirmationNumbers));
        }
        $builder = $this->createQueryBuilder('transfer');
        $builder->join('transfer.segments', 'segments');
        $builder->where('transfer.user = :user');
        $builder->andWhere("transfer.category = " . Trip::CATEGORY_TRANSFER);
        $builder->setParameter('user', $owner);
        $builder->andWhere($builder->expr()->orX(
            $builder->expr()->in('segments.depdate', ':departureDates'),
            $builder->expr()->in('segments.depcode', ':departureAirportCodes'),
            $builder->expr()->in('segments.depname', ':departureAirportNames'),
            $builder->expr()->in('segments.arrdate', ':arrivalDates'),
            $builder->expr()->in('segments.arrcode', ':arrivalAirportCodes'),
            $builder->expr()->in('segments.arrname', ':arrivalAirportNames'),
            $builder->expr()->in('transfer.confirmationNumber', ':confirmationNumbers'),
            $builder->expr()->in('transfer.travelAgencyConfirmationNumbers', ':confirmationNumbers')
        ));
        $builder->setParameter('confirmationNumbers', array_unique(array_filter($confirmationNumbers)));
        $builder->setParameter('departureDates', array_unique($departureDates));
        $builder->setParameter('departureAirportCodes', array_unique($departureAirportCodes));
        $builder->setParameter('departureAirportNames', array_unique(array_filter($departureAirportNames)));
        $builder->setParameter('arrivalDates', array_unique($arrivalDates));
        $builder->setParameter('arrivalAirportCodes', array_unique(array_filter($arrivalAirportCodes)));
        $builder->setParameter('arrivalAirportNames', array_unique($arrivalAirportNames));

        return $builder->getQuery()->getResult();
    }

    private function findAircodeByCodeOrName($code = '', $name = ''): ?Aircode
    {
        $aircodeRepository = $this->getEntityManager()->getRepository(Aircode::class);

        if ($code) {
            /** @var Aircode $arrivalAirport */
            $arrivalAirport = $aircodeRepository->findOneBy(['aircode' => $code]);

            if (null !== $arrivalAirport) {
                return $arrivalAirport;
            }
        }

        if ($name) {
            /** @var Aircode $arrivalAirport */
            $arrivalAirport = $aircodeRepository->findOneBy(['airname' => $name]);

            if (null !== $arrivalAirport) {
                return $arrivalAirport;
            }
        }

        return null;
    }
}
