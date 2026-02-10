<?php

namespace AwardWallet\MainBundle\Worker;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\Persistence\ManagerRegistry;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class FlightInfoUpdaterWorker implements ConsumerInterface
{
    public const CACHE_KEY = 'flight_info_updater';
    public const TIME_LIMIT = 30; // sec
    public const QUEUE_WAIT_LIMIT = 600; // sec

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var FlightInfo
     */
    private $flightInfoService;

    /**
     * @var ItineraryTracker
     */
    private $diffTracker;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var \Memcached
     */
    private $memcache;

    /** @var \Doctrine\Persistence\ObjectManager */
    private $em;

    private $flightInfoRep;

    public function __construct(ManagerRegistry $doctrine, FlightInfo $flightInfoService, ItineraryTracker $diffTracker, \Memcached $memcache, ProducerInterface $producer, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->flightInfoService = $flightInfoService;
        $this->diffTracker = $diffTracker;
        $this->producer = $producer;
        $this->memcache = $memcache;

        $this->em = $doctrine->getManager();
        $this->flightInfoRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\FlightInfo::class);
    }

    public function execute(AMQPMessage $message)
    {
        $this->em->clear();
        $task = @unserialize($message->body);

        if (empty($task) || !is_array($task)) {
            $flightInfoId = intval($message->body);
            $this->error("old task format", ['flightInfoId' => $flightInfoId]);

            return true;
        }
        $flightInfoId = $task['id'];
        $ruleName = $task['rule'];
        $scheduled = new \DateTime($task['schedule']);

        $this->info("start update", ['flightInfoId' => $flightInfoId, 'memory' => memory_get_usage(true)]);

        try {
            $flightInfo = $this->flightInfoRep->find($flightInfoId);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), ['flightInfoId' => $flightInfoId]);
        }

        if (empty($flightInfo)) {
            $this->error("not found FlightInfo record", ['flightInfoId' => $flightInfoId]);

            return true;
        }

        if (!$this->lock($flightInfoId)) {
            $this->warning("locked by other worker", ['flightInfoId' => $flightInfoId]);

            return true;
        }

        $this->info("found FlightInfo record", [
            'flightInfoId' => $flightInfoId,
            'iata' => $flightInfo->getAirline(),
            'flightNumber' => $flightInfo->getFlightNumber(),
            'flightDate' => $flightInfo->getFlightDate()->format('Y-m-d'),
            'depCode' => $flightInfo->getDepCode(),
            'arrCode' => $flightInfo->getArrCode(),
        ]);

        $this->info("processing", ['flightInfoId' => $flightInfoId]);

        if ($scheduled->getTimestamp() - time() > self::QUEUE_WAIT_LIMIT) {
            $this->error("slow worker", ['flightInfoId' => $flightInfoId, 'scheduled' => $scheduled->format('c'), 'wait' => $scheduled->getTimestamp() - time()]);
        }

        $oldProperties = [];
        $tripsUpdated = $propertiesChanged = 0;
        $updated = $this->flightInfoService->update($flightInfo, $ruleName);

        if ($updated) {
            $tripIds = [];

            foreach ($updated as $updatedFlightInfo) {
                $this->info("updating FlightInfo record", [
                    'flightInfoId' => $updatedFlightInfo->getFlightInfoID(),
                    'iata' => $updatedFlightInfo->getAirline(),
                    'flightNumber' => $updatedFlightInfo->getFlightNumber(),
                    'flightDate' => $updatedFlightInfo->getFlightDate()->format('Y-m-d'),
                    'depCode' => $updatedFlightInfo->getDepCode(),
                    'arrCode' => $updatedFlightInfo->getArrCode(),
                ]);
                /** @var TripSegment[] $segments */
                $segments = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->findBy(['flightinfoid' => $flightInfo]);

                foreach ($segments as $segment) {
                    $tripIds[] = $segment->getTripid()->getId();
                    $key = 'T.' . $segment->getTripid()->getId();

                    if (!array_key_exists($key, $oldProperties)) {
                        $oldProperties[$key] = ['changes' => $this->diffTracker->getProperties($key), 'userId' => $segment->getTripid()->getUser()->getUserid()];
                    }
                }
                $tripsUpdated = count($oldProperties);
                $this->flightInfoService->updateTripSegments($updatedFlightInfo);
            }
            $this->updateTripLastSync($tripIds);

            try {
                foreach ($oldProperties as $key => $data) {
                    $propertiesChanged += $this->diffTracker->recordChanges($data['changes'], $key, $data['userId'], true);
                }
            } catch (DriverException $e) {
                if (preg_match('#Lost connection to MySQL server during query#ims', $e->getMessage())) {
                    $this->logger->warning("mysql gone away, restarting");

                    exit;
                } else {
                    throw $e;
                }
            }
        }

        $this->warning("processing done", ['flightInfoId' => $flightInfoId, 'update' => $flightInfo->getUpdateDate() ? $flightInfo->getUpdateDate()->format('c') : false, 'tripsUpdated' => $tripsUpdated, 'propertiesChanged' => $propertiesChanged]);

        $isNextUpdate = $this->flightInfoService->schedule($flightInfo);

        if ($isNextUpdate) {
            $this->warning("schedule next update", ['flightInfoId' => $flightInfoId]);
        } else {
            $this->warning("stop updating", ['flightInfoId' => $flightInfoId]);
        }

        $this->info("end update", ['flightInfoId' => $flightInfoId, 'memory' => memory_get_usage(true)]);
        $this->unlock($flightInfoId);

        return true;
    }

    private function lock($flightInfoId)
    {
        return $this->memcache->add(self::CACHE_KEY . '_' . $flightInfoId, 1, self::TIME_LIMIT);
    }

    private function unlock($flightInfoId)
    {
        $this->memcache->delete(self::CACHE_KEY . '_' . $flightInfoId);
    }

    private function info($message, $extra = [])
    {
        $this->logger->info('FlightInfoUpdater: ' . $message, $extra);
    }

    private function warning($message, $extra = [])
    {
        $this->logger->warning('FlightInfoUpdater: ' . $message, $extra);
    }

    private function error($message, $extra = [])
    {
        $this->logger->error('FlightInfoUpdater: ' . $message, $extra);
    }

    private function updateTripLastSync(array $tripIds) // similarly - Service/Overlay/Writer.php
    {
        if (empty($tripIds)) {
            return null;
        }
        $tripIds = array_unique($tripIds);
        $conn = $this->doctrine->getConnection();
        $conn->executeUpdate('UPDATE Trip SET LastParseDate = NOW() WHERE TripID IN (?) AND Modified = 0', [$tripIds], [$conn::PARAM_INT_ARRAY]);
    }
}
