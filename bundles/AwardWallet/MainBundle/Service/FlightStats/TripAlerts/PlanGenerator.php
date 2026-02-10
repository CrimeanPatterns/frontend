<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

class PlanGenerator
{
    public const LOG_FIELDS = ['IATACode', 'FlightNumber', 'FsCode', 'FsFlightNumber', 'DepCode', 'ArrCode', 'DepDateGmt', 'ArrDateGmt', 'time', 'distance', 'speed', 'warning'];

    /**
     * @var Statement
     */
    private $query;
    /**
     * @var TripConverter
     */
    private $converter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, TripConverter $converter, LoggerInterface $tripAlertsLogger)
    {
        $this->query = $connection->prepare("
            select distinct
                COALESCE(al.Code, p.IATACode) as IATACode,
                ts.FlightNumber,
                ts.DepCode,
                ts.ScheduledDepDate,
                ts.AirlineName,
                ts.TripSegmentID,
                dac.Lat as DepLat,
                dac.Lng as DepLng,
                dac.TimeZoneLocation as DepTimeZone,
                ts.ArrCode,
                ts.ScheduledArrDate,
                aac.Lat as ArrLat,
                aac.Lng as ArrLng,
                aac.TimeZoneLocation as ArrTimeZone
            from
                Trip t
                left join Provider p on t.ProviderID = p.ProviderID
                join TripSegment ts on t.TripID = ts.TripID
                left join Airline al on ts.AirlineID = al.AirlineID 
                join AirCode dac on ts.DepCode = dac.AirCode
                join AirCode aac on ts.ArrCode = aac.AirCode
            where
                t.UserID = :userId
                and t.Hidden = 0
                and ts.Hidden = 0
                and t.UserAgentID is null
                and (p.IATACode is not null or al.Code is not null)
                and ts.DepCode is not null and ts.ArrCode is not null
                and ts.DepCode <> '' and ts.ArrCode <> ''
                and ts.FlightNumber is not null and ts.FlightNumber <> 'n/a' and ts.FlightNumber <> ''
                and ts.ScheduledDepDate >= :startDate
            order by
                ts.ScheduledDepDate
            limit
                :limit
        ");
        $this->converter = $converter;
        $this->logger = $tripAlertsLogger;
        $this->emptyLogValues = array_combine(self::LOG_FIELDS, array_fill(0, count(self::LOG_FIELDS), null));
    }

    /**
     * @param int $userId
     * @param int $startDate
     * @param int $limit
     * @return PlanGeneratorResponse
     */
    public function generate($userId, $startDate = null, $limit = null)
    {
        if (empty($limit)) {
            $limit = 100;
        }

        if (empty($startDate)) {
            $startDate = time();
        }

        $limit = intval($limit);
        $this->query->bindParam('limit', $limit, \PDO::PARAM_INT);
        $userId = intval($userId);
        $this->query->bindParam('userId', $userId, \PDO::PARAM_INT);
        $startDate = date("Y-m-d H:i:s", $startDate);
        $this->query->bindParam('startDate', $startDate, \PDO::PARAM_STR);
        $this->query->execute();
        $flights = [];
        $validSegments = [];
        $invalidSegments = [];
        $lastAirports = [];

        $segments = $this->query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($segments as $index => $segment) {
            $segments[$index]['DepDateTime'] = (new \DateTime($segment['ScheduledDepDate'], new \DateTimeZone($segment['DepTimeZone'])))->getTimestamp();
            $segments[$index]['ArrDateTime'] = (new \DateTime($segment['ScheduledArrDate'], new \DateTimeZone($segment['ArrTimeZone'])))->getTimestamp();
        }
        usort($segments, function ($a, $b) {
            return $a['DepDateTime'] - $b['DepDateTime'];
        });
        $this->logger->debug("loaded segments", ["userId" => $userId, "startDate" => $startDate, "limit" => $limit, "segments" => $segments]);

        foreach ($segments as $segment) {
            $flight = $this->converter->convert($segment);

            if (empty($flight)) {
                $invalidSegments[] = $segment;
                $this->logger->debug("failed to convert segment", ["userId" => $userId, "segment" => $segment]);

                continue;
            }

            if (isset($lastSegment)) {
                $segment['distance'] = round(Geo::distance($lastSegment['ArrLat'], $lastSegment['ArrLng'], $segment['DepLat'], $segment['DepLng']));
                $segment['time'] = round(($segment['DepDateTime'] - $lastSegment['ArrDateTime']) / 3600, 1);

                if ($segment['time'] != 0) {
                    $segment['speed'] = round($segment['distance'] / $segment['time'], 1);
                } else {
                    $segment['speed'] = 0;
                }
                $segment['warning'] = $this->checkSegment($segment, $lastSegment, $lastAirports);
            }
            $segment['DepDateGmt'] = date("Y-m-d H:i", $segment['DepDateTime']);
            $segment['ArrDateGmt'] = date("Y-m-d H:i", $segment['ArrDateTime']);
            $segment['FsCode'] = $flight->getBookedAirlineCode();
            $segment['FsFlightNumber'] = $flight->getFlightNumber();

            if (empty($segment['warning'])) {
                $flights[] = $flight;
                $validSegments[] = $segment;
                $lastSegment = $segment;
                $lastAirports[$segment['DepCode']] = $segment['DepDateTime'];
            } else {
                $invalidSegments[] = $segment;
            }
        }

        return new PlanGeneratorResponse($flights, $validSegments, $invalidSegments);
    }

    private function checkSegment(array $segment, array $lastSegment, array $lastAirports)
    {
        //        if($lastSegment['DepCode'] == $segment['DepCode'] && $lastSegment['ArrCode'] == $segment['ArrCode'])
        //            return "repeating segment detected, skip";

        //        if($segment['time'] == 0)
        //            return "zero time, skip";
        //
        //        if ($segment['speed'] > 300)
        //            return "going to fast, skip";

        if (
            ($segment['DepDateTime'] >= $lastSegment['DepDateTime'] && $segment['DepDateTime'] <= $lastSegment['ArrDateTime'])
            || ($segment['ArrDateTime'] >= $lastSegment['DepDateTime'] && $segment['ArrDateTime'] <= $lastSegment['ArrDateTime'])
        ) {
            return "overlapping with previous segment";
        }

        if (isset($lastAirports[$segment['DepCode']]) && ($segment['DepDateTime'] - $lastAirports[$segment['DepCode']]) <= 3600 * 12) {
            return 'departure airport repeated within 12h';
        }

        return null;
    }
}
