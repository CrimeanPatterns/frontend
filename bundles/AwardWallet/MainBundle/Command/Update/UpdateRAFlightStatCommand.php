<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UpdateRAFlightStatCommand extends Command
{
    private LoggerInterface $logger;

    private Connection $unbufConnection;

    private Connection $connection;

    private EntityManagerInterface $em;

    private AppBot $appBot;

    private \Memcached $cache;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        Connection $unbufConnection,
        AppBot $appBot,
        \Memcached $memcached
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->connection = $em->getConnection();
        $this->unbufConnection = $unbufConnection;
        $this->appBot = $appBot;
        $this->cache = $memcached;
    }

    public function configure()
    {
        $this->setName('aw:update-ra-flight-stat')
            ->setDescription("Update RAFlightStat - get from MileValue pairs provider-mileAirline (default period - since yesterday 00:00)")
            ->addOption('startDate', 'a', InputOption::VALUE_OPTIONAL, 'start day: format Y-m-d',
                date("Y-m-d", strtotime("-1 day")))
            ->addOption('endDate', 'b', InputOption::VALUE_OPTIONAL, 'end day: format Y-m-d');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $uxStart = null;
        $uxEnd = null;

        $start = $input->getOption('startDate');

        if (!empty($start)) {
            if (preg_match("/^\d{4}\-\d{2}\-\d{2}$/", $start) !== 1
                || empty($uxStart = strtotime($start))
            ) {
                $this->logger->info("RAFLightStat [update command]: wrong start date");

                return 0;
            }
        }

        $end = $input->getOption('endDate');

        if (!empty($end)) {
            if (preg_match("/^\d{4}\-\d{2}\-\d{2}$/", $end) !== 1
                || empty($uxEnd = strtotime($end))
            ) {
                $this->logger->info("RAFLightStat [update command]: wrong end date");

                return 0;
            }
        }

        $this->logger->debug("start: " . var_export($uxStart, true) . (isset($uxStart) ? '//' . date("Y-m-d H:i",
            $uxStart) : ''));
        $this->logger->debug("end  : " . var_export($uxEnd, true) . (isset($uxEnd) ? '-' . date("Y-m-d H:i",
            $uxEnd) : ''));

        $batcher = new BatchUpdater($this->connection);

        $sqlMileValue = /** @lang MySQL */
            "
            SELECT 
                Provider.Code, 
                MileValue.MileAirlines, 
                MileValue.CreateDate 
            FROM MileValue 
            INNER JOIN Provider ON Provider.ProviderID = MileValue. ProviderID
            WHERE MileValue.MileAirlines IS NOT NULL AND Provider.Kind = 1
            ";
        $where = [];
        $paramMileValue = [];

        if (isset($uxStart)) {
            $where[] = 'MileValue.CreateDate >= ?';
            $paramMileValue[] = date('Y-m-d', $uxStart);
        }

        if (isset($uxEnd)) {
            $where[] = 'MileValue.CreateDate < ?';
            $paramMileValue[] = date('Y-m-d', $uxEnd);
        }

        if (!empty($where)) {
            $sqlMileValue .= ' AND ' . implode(' AND ', $where);
        }

        $q = $this->unbufConnection->executeQuery($sqlMileValue, $paramMileValue);

        $sql = /** @lang MySQL */
            'INSERT INTO RAFlightStat (Provider, Carrier, FirstBook, LastBook) VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        LastBook = IF (LastBook IS NULL OR ? > LastBook, ? , LastBook),
                        FirstBook = IF (FirstBook IS NULL OR ? < FirstBook, ? , FirstBook)';

        $total_upd = it($q)
            ->chunk(5000)
            ->map(function (array $rows) use ($sql, $batcher) {
                $params = $collection = $notify = [];

                foreach ($rows as $row) {
                    $airlines = explode(',', $row['MileAirlines']);
                    $airlines = array_map(function ($s) {
                        $s = trim($s);

                        if (!preg_match("/^([A-Z\d][A-Z]|[A-Z][A-Z\d])$/", $s)) {
                            return '';
                        }

                        return $s;
                    }, $airlines);
                    $airlines = array_filter(array_unique($airlines));

                    foreach ($airlines as $airline) {
                        $date = date('Y-m-d', strtotime($row['CreateDate']));

                        if ($this->isNewParams($collection, $row['Code'], $airline, $date)) {
                            $collection[$row['Code']][$airline][] = $date;
                            $params[] = [$row['Code'], $airline, $date, $date, $date, $date, $date, $date];
                            $notify[] = ['provider' => $row['Code'], 'carrier' => $airline, 'date' => $date];
                        }
                    }
                }

                if (!empty($params)) {
                    $this->sendNotify($notify);
                    $batcher->batchUpdate($params, $sql, 0);
                }

                return count($params);
            })
            ->sum();
        $output->writeln($total_upd);

        return 0;
    }

    private function isNewParams(array $collection, $code, $airline, $date): bool
    {
        if (!array_key_exists($code, $collection)) {
            return true;
        }

        if (!array_key_exists($airline, $collection[$code])) {
            return true;
        }

        return !in_array($date, $collection[$code][$airline]);
    }

    private function sendNotify(array $data): void
    {
        $message = [
            'text' => '',
            'blocks' => [],
        ];

        $blocksPart = [];
        $counter = 0;

        foreach ($data as $item) {
            $cacheKey = strtolower('raflightstat_book_' . $item['provider'] . '_' . $item['carrier']);
            $this->logger->info('RAFlightStatNotify items ' . $cacheKey, ['type' => 'book']);

            if (false !== $this->cache->get($cacheKey)) {
                $this->logger->info('RAFlightStatNotify cache exists ' . $cacheKey, ['type' => 'book']);

                continue;
            }

            /** @var Provider $provider */
            $provider = $this->em->getRepository(Provider::class)->findOneBy(['code' => $item['provider']]);

            /** @var Provider $carrier */
            $carrier = $this->em->getRepository(Provider::class)->findOneBy(['IATACode' => $item['carrier']]);

            if (null === $carrier) {
                $airline = $this->em->getRepository(Airline::class)->findOneBy(['code' => $item['carrier']]);

                if (null !== $airline) {
                    $carrierName = $airline->getName();
                    $carrierIataCode = $airline->getCode();
                    $carrierAlliance = false;
                }
            } else {
                $carrierName = $carrier->getDisplayname();
                $carrierIataCode = $carrier->getIATACode();
                $carrierAlliance = $carrier->getAllianceid() ? $carrier->getAllianceid()->getName() : false;
            }

            if (null === $provider || empty($carrierName)) {
                $this->logger->info('RAFlightStatNotify provider not found [' . $item['provider'] . '] - [' . $item['carrier'] . ']',
                    ['type' => 'book']);

                continue;
            }

            $isExists = false !== $this->connection->fetchOne(/** @lang MySql */ 'SELECT 1 FROM RAFlightStat WHERE Provider = ? AND Carrier = ? AND FirstBook IS NOT NULL',
                [$provider->getCode(), $carrierIataCode],
                [\PDO::PARAM_STR, \PDO::PARAM_STR]
            );

            if ($isExists) {
                $this->logger->info('RAFlightStatNotify exists ' . $cacheKey, ['type' => 'book']);

                continue;
            }

            if (isset($blocksPart[$counter]) && count($blocksPart[$counter]) == 20) {
                $counter++;
            }
            $blocksPart[$counter][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => implode("\n", [
                        '*Alert: Mile Value - First Booking*',
                        'Provider: ' . $provider->getDisplayname() . ' [code: ' . $provider->getCode() . ']',
                        'Provider Alliance: ' . ($provider->getAllianceid() ? $provider->getAllianceid()
                            ->getName() : '[null]'),
                        'Carrier: ' . $carrierName . ' [iata code: ' . $carrierIataCode . ']',
                        'Carrier Alliance: ' . ($carrierAlliance ?: '[null]'),
                        'First Book: ' . date('m/d/Y', strtotime($item['date'])),
                    ]),
                ],
            ];
            $blocksPart[$counter][] = [
                'type' => 'divider',
            ];

            $this->cache->set($cacheKey, true, 86400 * 30);
        }

        foreach ($blocksPart as $num => $part) {
            $message['blocks'] = $part;
            //            $this->logger->info('part: #' . $num);
            //            $this->logger->info(var_export($message, true));
            $this->appBot->send(Slack::CHANNEL_AW_AWARD_ALERTS, $message);
        }
    }
}
