<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\PlanGenerator;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\Subscriber;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\SubscriptionManager;
use Aws\CloudWatch\CloudWatchClient;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TripAlertsCommand extends Command
{
    protected static $defaultName = 'aw:trip-alerts';

    /**
     * @var PlanGenerator
     */
    private $generator;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var SymfonyStyle
     */
    private $io;
    /**
     * @var int
     */
    private $checkedUsers = 0;
    /**
     * @var int
     */
    private $subscribeCalls = 0;
    /**
     * @var array
     */
    private $failedUsers = [];
    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;
    private Connection $connection;
    private CloudWatchClient $cloudWatchClient;

    public function __construct(
        Subscriber $subscriber,
        PlanGenerator $generator,
        SubscriptionManager $subscriptionManager,
        Connection $connection,
        CloudWatchClient $cloudWatchClient
    ) {
        parent::__construct();

        $this->subscriber = $subscriber;
        $this->generator = $generator;
        $this->subscriptionManager = $subscriptionManager;
        $this->connection = $connection;
        $this->cloudWatchClient = $cloudWatchClient;
    }

    protected function configure()
    {
        $this
                  ->setDescription('test FlightStats Trip Alerts API')
                ->addArgument("action", InputArgument::REQUIRED, 'subscribe | stats | cancel | get | subscribe-nearest | manage-subscriptions | update-subscriptions | put-metrics')
                  ->addOption('userId', 'u', InputOption::VALUE_REQUIRED)
                  ->addOption('startUserId', null, InputOption::VALUE_REQUIRED)
                  ->addOption('min-segments', null, InputOption::VALUE_REQUIRED, 'minimum number of segments, to subscribe', 1)
                  ->addOption('days', null, InputOption::VALUE_REQUIRED, 'days before first segment', SubscriptionManager::START_MONITORING_DAYS)
                  ->addOption('recalc-days', null, InputOption::VALUE_REQUIRED, 'recalc subscription after this number days', 1)
                  ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'how many users to process', 15000)
                  ->addOption('failed-only', null, InputOption::VALUE_NONE, 'update only failed subscriptions')
                  ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry-run, do not apply modifications')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        switch ($input->getArgument("action")) {
            case "subscribe":
                $response = $this->generator->generate($input->getOption("userId"), null);
                $output->writeln(implode("\n", array_map(function ($flight) { return (string) $flight; }, $response->flights)));
                $success = $this->subscriber->subscribe($response->flights, $input->getOption("userId"));
                $this->subscriptionManager->setMonitored($response->validSegments, $success);
                $this->subscriptionManager->setMonitored($response->invalidSegments, false);

                break;

            case "cancel":
                $success = $this->subscriber->subscribe([], $input->getOption("userId"));

                if ($success) {
                    $this->subscriptionManager->setMonitored(
                        $this->getMonitoredSegmentsByUsers([$input->getOption("userId")]),
                        false
                    );
                }

                break;

            case "subscribe-nearest":
                $users = $this->getUsersWithNearestFlights($input->getOption('limit'), false, $input->getOption('min-segments'), $input->getOption('days'));
                $output->writeln("subscribing " . count($users) . " users");
                $this->checkSubscriptions($users, $input->getOption('dry-run'));

                break;

            case "manage-subscriptions":
                $users = $this->getUsersWithSubscriptions(100000000, null);
                $this->io->writeln("subscribed: " . count($users));
                $subscribed = count($users);
                $users = $this->getUsersWithSubscriptions($input->getOption('limit'), $input->getOption('userId'), $input->getOption('recalc-days'));
                $existingSubscriptions = count($users);
                $addedSubscriptions = 0;
                $this->checkSubscriptions($users, $input->getOption('dry-run'));

                if ($subscribed < $input->getOption('limit')) {
                    $toAdd = $input->getOption('limit') - $subscribed;
                    $users = $this->getUsersWithNearestFlights($toAdd, true, $input->getOption('min-segments'), $input->getOption('days'));
                    $addedSubscriptions = count($users);
                    $this->checkSubscriptions($users, $input->getOption('dry-run'));
                }
                $this->io->writeln("processed $existingSubscriptions existing users, added: $addedSubscriptions, total users: " . ($existingSubscriptions + $addedSubscriptions));

                break;

            case "update-subscriptions":
                $users = $this->getUsersWithSubscriptions($input->getOption('limit'), $input->getOption('userId'), $input->getOption('startUserId'), 0, $input->getOption('failed-only'));
                $this->checkSubscriptions($users, $input->getOption('dry-run'), true);

                break;

            case "get":
                $output->writeln((string) $this->subscriber->get($input->getOption("userId")));

                break;

            case "stats":
                $this->io->writeln("calculating stats");
                $users = $this->getUsersWithNearestFlights(100000000, true, $input->getOption('min-segments'), $input->getOption('days'));
                $this->io->writeln("want to monitor: " . count($users));
                $users = $this->getUsersWithSubscriptions(100000000, null);
                $this->io->writeln("subscribed: " . count($users));

                break;

            case "put-metrics":
                $this->putMetrics();

                break;

            default:
                throw new \Exception("Invalid action");
        }

        if (!empty($this->failedUsers)) {
            $this->io->warning("failed users: " . implode(", ", $this->failedUsers));
        }
        $this->io->writeln("done, processed {$this->checkedUsers} users, failed: " . count($this->failedUsers) . ", updated {$this->subscribeCalls} subscriptions");

        return 0;
    }

    private function checkSubscriptions($users, $dryRun, $force = false)
    {
        $this->io->title("checking subscriptions of " . count($users) . " users");

        foreach ($users as $user) {
            $this->checkedUsers++;
            $result = $this->subscriptionManager->update($user, $dryRun, $force);

            if (!empty($result->validSegments)) {
                $this->io->table(
                    PlanGenerator::LOG_FIELDS,
                    $result->validSegments
                );
            }

            if (!empty($result->invalidSegments)) {
                $this->io->table(
                    PlanGenerator::LOG_FIELDS,
                    $result->invalidSegments
                );
            }

            if ($result->monitorable === false) {
                $this->failedUsers[] = $user['UserID'];
            }

            if ($result->subscribeCalled) {
                $this->subscribeCalls++;
            }
        }
    }

    private function getUsersWithSubscriptions($limit, $userId, ?int $startUserId = null, $updatedDaysAgo = 0, $failedOny = false)
    {
        return $this->connection->executeQuery("
        select  
            u.UserID, 
            u.TripAlertsHash,
            u.TripAlertsStartDate,
            u.TripAlertsUpdateDate,
            count(d.MobileDeviceID) as HasMobileDevices
        from 
            Usr u
            left outer join MobileDevice d on u.UserID = d.UserID and d.DeviceType in (" . implode(", ", MobileDevice::TYPES_MOBILE) . ") and d.Tracked = 1 
        where
            " . (!empty($userId) ? "u.UserID = " . intval($userId) : "u.TripAlertsHash is not null") . "
            " . ($startUserId !== null ? " and u.UserID >= " . intval($startUserId) : "") . "
            " . (!empty($failedOny) ? " and u.TripAlertsMonitorable = 0 and u.TripAlertsStartDate is not null" : "") . "
            and (u.TripAlertsCalcDate < adddate(now(), -" . intval($updatedDaysAgo) . ") or u.TripAlertsCalcDate is null)
        group by
            u.UserID, 
            u.TripAlertsHash,
            u.TripAlertsStartDate,
            u.TripAlertsUpdateDate
        limit $limit
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getUsersWithNearestFlights($limit, $excludeWithSubscription, $minSegments, $days)
    {
        $this->io->writeln("loading up to $limit users with nearest flights" . ($excludeWithSubscription ? ", excluding active subscriptions" : ""));

        return $this->connection->executeQuery("
        select 
            t.UserID, 
            u.TripAlertsHash,
            u.TripAlertsStartDate,
            u.TripAlertsUpdateDate,
            count(distinct ts.TripSegmentID), 
            min(ts.DepDate),
            1 as HasMobileDevices
        from 
            Trip t
            join TripSegment ts on t.TripID = ts.TripID
            join Provider p on t.ProviderID = p.ProviderID
            join Usr u on t.UserID = u.UserID
            join MobileDevice md on t.UserID = md.UserID
        where
            t.ProviderID is not null
            and ts.DepCode is not null and ts.ArrCode is not null
            and ts.DepCode <> '' and ts.ArrCode <> ''
            and ts.FlightNumber is not null and ts.FlightNumber <> 'n/a' and ts.FlightNumber <> ''
            and t.Hidden = 0
            and t.UserAgentID is null
            and p.IATACode is not null
            and ts.ScheduledDepDate > now()
            and md.DeviceType in (" . implode(", ", MobileDevice::TYPES_MOBILE) . ")
            " . ($excludeWithSubscription ? " and u.TripAlertsHash is null" : "") . "
            and md.Tracked = 1
        group by 
            t.UserID,				
            u.TripAlertsHash,
            u.TripAlertsStartDate,
            u.TripAlertsUpdateDate
        having 
            count(distinct ts.TripSegmentID) > :minSegments and min(ts.DepDate) < adddate(now(), :days)
        limit 
            $limit
        ", ["minSegments" => $minSegments, "days" => $days])->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getMonitoredSegmentsByUsers(array $users)
    {
        $userIds = implode(", ", $users);

        return $this->connection->executeQuery("
        select 
          ts.TripSegmentID
        from 
          TripSegment ts
        join 
          Trip tr on ts.TripID = tr.TripID
        where tr.UserID in ({$userIds}) and ts.TripAlertsUpdateDate is not null
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function putMetrics()
    {
        $this->io->writeln("putting metrics");

        $q = $this->connection->executeQuery("
        select
          coalesce(TripAlertsMonitorable, 0) as Monitorable,
          count(UserID) as Users
        from 
          Usr 
        where
          TripAlertsHash is not null
        group by
          Monitorable
        ");

        $metricData = it($q->fetchAll(FetchMode::ASSOCIATIVE))
            ->map(function (array $row) {
                return [
                    'MetricName' => "subscriptions",
                    'Dimensions' => [
                        ['Name' => 'monitorable', 'Value' => $row['Monitorable']],
                    ],
                    'Timestamp' => time(),
                    'Value' => $row['Users'],
                ];
            })
            ->toArray();

        $this->cloudWatchClient->putMetricData([
            'Namespace' => 'AW/FlighStats',
            'MetricData' => $metricData,
        ]);

        $this->io->writeln("done, put " . count($metricData) . " metrics");
    }
}
