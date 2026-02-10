<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\API\ApiException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class UpdateWorker implements ConsumerInterface
{
    private SubscriptionManager $manager;

    private Statement $userQuery;

    private Logger $logger;

    private ProducerInterface $producer;

    private \Memcached $memcached;

    private EntityManagerInterface $em;

    /**
     * @var int
     */
    private $messageCount = 0;

    public function __construct(
        SubscriptionManager $manager,
        Connection $connection,
        Logger $tripAlertsLogger,
        ProducerInterface $tripAlertsUpdaterProducer,
        \Memcached $memcached,
        EntityManagerInterface $em
    ) {
        $this->manager = $manager;
        $this->logger = $tripAlertsLogger;
        $this->userQuery = $connection->prepare("select 
            u.UserID, 
            u.TripAlertsStartDate, 
            u.TripAlertsHash, 
            u.TripAlertsUpdateDate,
            u.TripAlertsCalcDate,
            d.MobileDeviceID as HasMobileDevices
        from Usr u 
        left outer join MobileDevice d on u.UserID = d.UserID and d.DeviceType in (" . implode(", ", MobileDevice::TYPES_MOBILE) . ") and d.Tracked = 1
        where u.UserID = :userId
        limit 1");
        $this->producer = $tripAlertsUpdaterProducer;
        $this->memcached = $memcached;
        $this->em = $em;
    }

    public function execute(AMQPMessage $msg)
    {
        $message = json_decode($msg->body);

        if (empty($message->userId) || empty($message->version) || $message->version != 1) {
            $this->logger->warning("invalid message for " . __CLASS__, ["msg" => $msg->body]);

            return true;
        }

        if (!isset($message->time)) {
            $message->time = time() - 60;
        }

        $this->userQuery->execute(["userId" => $message->userId]);
        $user = $this->userQuery->fetch(\PDO::FETCH_ASSOC);

        if (empty($user)) {
            $this->logger->info("user not found", ["UserID" => $message->userId]);

            return true;
        }

        $currentTime = microtime(true);

        if (($currentTime - $message->time) < 30) {
            $this->logger->debug("delaying update request, too young", ["userId" => $message->userId, "currentTime" => $currentTime, "messageTime" => $message->time]);
            $this->producer->publish($msg->body, '', ['application_headers' => ['x-delay' => ['I', 5000]]]);

            return true;
        }

        if (!empty($user['TripAlertsCalcDate']) && strtotime($user['TripAlertsCalcDate']) > $message->time) {
            $this->logger->debug("ignoring update request, already calculated", ["userId" => $message->userId, "calcDate" => $user['TripAlertsCalcDate'], "messageDate" => strtotime("Y-m-d H:i:s", $message->time)]);

            return true;
        }

        $lockName = "tripalerts_updater_" . $message->userId;
        $locked = $this->memcached->add($lockName, gethostname() . "_" . getmypid(), 300);

        if (!$locked) {
            $this->logger->debug("delaying update request, not locked", ["userId" => $message->userId, "currentTime" => $currentTime, "messageTime" => $message->time]);
            $this->producer->publish($msg->body, '', ['application_headers' => ['x-delay' => ['I', 2000]]]);

            return true;
        }

        try {
            $result = $this->manager->update($user, false);
            $this->logger->info("subscription updated", ["userId" => $message->userId, "updateResult" => $result]);
        } catch (ApiException $exception) {
            file_put_contents("/tmp/taResponse", $exception->getResponseBody());
            $this->logger->info("tripalerts api exception: " . $exception->getMessage(), ["responseBody" => $exception->getResponseBody()]);
        } catch (DeadlockException $exception) {
            $this->logger->notice($exception->getMessage());

            return false;
        }
        $this->memcached->delete($lockName);

        $this->messageCount++;

        if (($this->messageCount % 100) == 0) {
            $this->logger->info("memory usage", ["memory" => round(memory_get_usage(true) / 1024 / 1024), "processed_messages" => $this->messageCount, "worker" => "TripAlertsUpdate"]);
            $this->em->clear();
        }

        return true;
    }

    public static function createMessage($userId)
    {
        return json_encode(["version" => 1, "userId" => (int) $userId, "time" => time()]);
    }
}
