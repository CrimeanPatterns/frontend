<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\DoctrineRetryHelper;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\Model\ImportFlight;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\ObjectSerializer;
use AwardWallet\MainBundle\Service\Overlay\SegmentsFinder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Monolog\Logger;

class SubscriptionManager
{
    public const START_MONITORING_DAYS = 14;

    /**
     * @var PlanGenerator
     */
    private $generator;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Statement
     */
    private $updateCalcDateQuery;
    /**
     * @var Statement
     */
    private $updateUserQuery;
    /**
     * @var Subscriber
     */
    private $subscriber;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var SegmentsFinder
     */
    private $segmentsFinder;
    private DoctrineRetryHelper $doctrineRetryHelper;

    public function __construct(
        PlanGenerator $generator,
        Subscriber $subscriber,
        Logger $tripAlertsLogger,
        Connection $connection,
        SegmentsFinder $segmentsFinder,
        DoctrineRetryHelper $doctrineRetryHelper
    ) {
        $this->generator = $generator;
        $this->logger = $tripAlertsLogger;
        $this->updateCalcDateQuery = $connection->prepare("
            update Usr set TripAlertsCalcDate = now() where UserID = :userId
        ");
        $this->updateUserQuery = $connection->prepare("update Usr set 
             TripAlertsHash = :hash, 
             TripAlertsStartDate = :startDate, 
             TripAlertsEndDate = :endDate, 
             TripAlertsUpdateDate = :updateDate,
             TripAlertsMonitorable = :monitorable
         where
             UserID = :userId");
        $this->subscriber = $subscriber;
        $this->connection = $connection;
        $this->segmentsFinder = $segmentsFinder;
        $this->doctrineRetryHelper = $doctrineRetryHelper;
    }

    /**
     * @param array $user ['UserID' => 123, 'TripAlertsStartDate' => '2016-03-03', 'TripAlertsHash' => 'ababab..', 'HasMobileDevices' => 1/0]
     * @return SubscriptionResponse
     */
    public function update(array $user, $dryRun, $force = false)
    {
        $result = new SubscriptionResponse();

        // gmt
        $startDate = time() - DateTimeUtils::SECONDS_PER_DAY; // -1 day to grab segment in local times, timezones, you know
        $flights = [];

        if (!empty($user['TripAlertsStartDate'])) {
            // gmt or local
            $startDate = min($startDate, strtotime($user['TripAlertsStartDate']));
        }

        if (!empty($user['HasMobileDevices'])) {
            $response = $this->generator->generate($user['UserID'], $startDate);
            $flights = $response->flights;
            $result->validSegments = $response->validSegments;
            $result->invalidSegments = $response->invalidSegments;

            if (empty($flights)) {
                $this->logger->info("no flights found", ['userId' => $user['UserID']]);
            }
        }

        $hash = sha1(json_encode(ObjectSerializer::sanitizeForSerialization($flights)));
        $hashChanged = $hash != $user['TripAlertsHash'];

        if ($hashChanged || $force) {
            // why we want to discard old flights on update ?
            // let them stay in flightstats
            // if you uncomment this block - you need to fix local/gmt timezone bug below
            //            if (!empty($user['TripAlertsStartDate'])) {
            //                $startDate = time();
            //                $oldCount = count($flights);
            //                $flights = array_filter($flights, function (ImportFlight $flight) use ($startDate) {
            //                    // BUG: compared local timezone with gmt
            //                    return strtotime($flight->getDeparture()->getDateTime()) >= $startDate;
            //                });
            //
            //                if ($oldCount != count($flights)) {
            //                    $this->logger->warning("discarded " . ($oldCount - count($flights)) . " flights");
            //                    $hash = sha1(json_encode(ObjectSerializer::sanitizeForSerialization($flights)));
            //                }
            //            }

            /** @var ImportFlight[] $flights */
            $flights = array_values($flights);
            $params = [
                'hash' => $hash,
                // local datetime
                'startDate' => empty($flights) ? null : $flights[0]->getDeparture()->getDateTime(),
                'endDate' => empty($flights) ? null : $flights[count($flights) - 1]->getArrival()->getDateTime(),
                'updateDate' => date("Y-m-d H:i:s"),
                'monitorable' => null,
                'userId' => $user['UserID'],
            ];

            if (!$dryRun) {
                $params['monitorable'] = intval($this->subscriber->subscribe($flights, $user['UserID']));
                $this->updateUserQuery->execute($params);
                $result->monitorable = !empty($params['monitorable']);
                $result->subscribeCalled = true;

                $segmentIds = $this->setMonitored($result->validSegments, $result->monitorable);

                // some segments can be subscribed as copies of valid segments
                // do not mark them as invalid then
                if ($result->monitorable) {
                    $result->invalidSegments = array_filter($result->invalidSegments, function (array $segment) use ($segmentIds) {
                        return !in_array($segment['TripSegmentID'], $segmentIds);
                    });
                }
                $this->setMonitored($result->invalidSegments, false);
            }
            $this->logger->info("updating subscription status", $params);
        } else {
            $this->logger->debug("hash not changed", ["userId" => $user['UserID'], 'hash' => $hash]);
        }

        if (empty($user['HasMobileDevices']) && $hashChanged) {
            $this->logger->info("no mobile devices, will not monitor", ['userId' => $user['UserID']]);
        }

        if (!$dryRun) {
            $this->updateCalcDateQuery->execute(["userId" => $user['UserID']]);
        }

        return $result;
    }

    public static function convertUserEntityToArray(Usr $user)
    {
        return [
            'UserID' => $user->getUserid(),
            'TripAlertsStartDate' => $user->getTripAlertsStartDate() ? $user->getTripAlertsStartDate()->format('Y-m-d H:i:s') : null,
            'TripAlertsHash' => $user->getTripAlertsHash(),
            'HasMobileDevices' => $user->hasMobileDevices(),
        ];
    }

    public function setMonitored($segments, bool $monitored): array
    {
        $result = [];

        if (!count($segments)) {
            return $result;
        }

        $segmentIds = array_map(function ($segment) {return $segment['TripSegmentID']; }, $segments);
        $this->updateSegments($segmentIds, $monitored);
        $result = $segmentIds;

        if ($monitored) {
            foreach ($segments as $segment) {
                $copies = $this->segmentsFinder->find($segment['IATACode'], $segment['FlightNumber'], $segment['DepCode'], strtotime($segment['ScheduledDepDate']));
                $copies = array_map(function ($segment) {return $segment['TripSegmentID']; }, $copies);
                $copies = array_diff($copies, $segmentIds);

                if (!empty($copies)) {
                    $this->updateSegments($copies, $monitored);
                    $result = array_merge($result, $copies);
                }
            }
        }

        return $result;
    }

    private function updateSegments(array $segmentIds, bool $monitored)
    {
        $segmentIdsStr = implode(", ", $segmentIds);
        $this->doctrineRetryHelper->execute(function () use ($segmentIdsStr, $monitored) {
            $this->connection->executeQuery("
            update TripSegment
            set TripAlertsUpdateDate = :date
            where TripSegmentID in ({$segmentIdsStr})
        ", ["date" => $monitored ? date("Y-m-d H:i:s") : null]);
        });
    }
}
