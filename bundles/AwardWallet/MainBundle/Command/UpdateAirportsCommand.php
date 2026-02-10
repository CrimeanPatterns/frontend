<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAirportsCommand extends Command
{
    protected static $defaultName = 'aw:update-airports';

    private $airNames = [
        'YQY' => 'J.A. Douglas McCurdy Sydney Airport', // changed from 'Sydney Airport'
    ];

    private $hardcoded = [
        // IBB Aeropuerto de General Villamil added by user request
        [
            'fs' => 'IBB',
            'iata' => 'IBB',
            'icao' => '',
            'faa' => '',
            'name' => 'Aeropuerto de General Villamil',
            'city' => 'Puerto Villamil',
            'cityCode' => null,
            'countryCode' => 'EC',
            'countryName' => 'Ecuador',
            'regionName' => 'South America',
            'timeZoneRegionName' => 'Pacific/Galapagos',
            'latitude' => -0.945218,
            'longitude' => -90.954479,
            'classification' => 5,
            'active' => true,
        ],
        // GOX - Mopa International Airport - open: 23 oct 2022
        [
            'fs' => 'GOX',
            'iata' => 'GOX',
            'icao' => 'VOGA',
            'faa' => '',
            'name' => 'Mopa International Airport',
            'city' => 'Mopa',
            'cityCode' => null,
            'countryCode' => 'IN',
            'countryName' => 'India',
            'timeZoneRegionName' => 'Asia/Kolkata',
            'latitude' => 15.7302,
            'longitude' => 73.8631,
            'classification' => 5,
            'active' => true,
        ],
    ];

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private string $appId;
    private string $appKey;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        string $flightInfoAppId,
        string $flightInfoAppKey
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->appId = $flightInfoAppId;
        $this->appKey = $flightInfoAppKey;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('update airports from Flightstats')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run')
            ->addOption('delete', 'r', InputOption::VALUE_NONE, 'delete old records if there are less 5%');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiId = $this->appId;
        $apiKey = $this->appKey;

        /** @var EntityManager $em */
        $em = $this->entityManager;

        $client = new Client();
        $response = $client->get("https://api.flightstats.com/flex/airports/rest/v1/json/active?appId={$apiId}&appKey={$apiKey}");
        $json = json_decode($response->getBody()->getContents());

        unset($response);
        unset($client);

        $airCodeRep = $em->getRepository(Aircode::class);
        $added = $updated = $woIata = $flushCounter = $invalidType = 0;

        if (!empty($this->hardcoded)) {
            $json->airports = array_merge(json_decode(json_encode($this->hardcoded)), $json->airports);
        }

        foreach ($json->airports as $airport) {
            if (!isset($airport->iata)) {
                $woIata++;

                continue;
            }

            // #22372
            if ($airport->iata === 'EIKK') {
                $airport->iata = 'KKY';
            }

            if (strlen($airport->iata) > 3) {
                throw new \Exception("IATA code {$airport->iata} is not three-character code");
            }

            if (isset($airport->name) && preg_match('/( Station|Railway Service|Heliport)$/', $airport->name) > 0) {
                $invalidType++;

                continue;
            }

            $stateName = '';

            if (isset($airport->stateCode) && isset($airport->countryCode)) {
                $stateStmt = $em->getConnection()->prepare('SELECT s.Name as name FROM State s INNER JOIN Country c ON c.CountryID = s.CountryID WHERE s.Code = :stateCode AND c.Code = :countryCode');
                $stateStmt->bindValue('stateCode', $airport->stateCode, \PDO::PARAM_STR);
                $stateStmt->bindValue('countryCode', $airport->countryCode, \PDO::PARAM_STR);
                $stateStmt->execute();
                $state = $stateStmt->fetch();

                if ($state) {
                    $stateName = $state['name'];
                }
            }

            /** @var Aircode $aircode */
            $aircode = $airCodeRep->findOneBy(['aircode' => $airport->iata]);

            if (!$aircode) {
                $aircode = new Aircode();
                $added++;
            } else {
                $updated++;
            }

            if (!$input->getOption("dry-run")) {
                $aircode->setAircode($airport->iata);
                $aircode->setCitycode($airport->cityCode ?? '');
                $aircode->setCityname($airport->city);
                $aircode->setStatename($stateName);
                $aircode->setCountrycode($airport->countryCode);
                $aircode->setCountryname($airport->countryName);
                $aircode->setState($airport->stateCode ?? '');
                $aircode->setFs($airport->fs);
                $aircode->setClassification($airport->classification);

                if (isset($airport->street1)) {
                    $aircode->setAddressline($airport->street1);
                }

                if (isset($airport->faa)) {
                    $aircode->setFaa($airport->faa);
                }

                if (isset($airport->icao)) {
                    $aircode->setIcaoCode($airport->icao);
                }

                $aircode->setAirname($this->airNames[$airport->iata] ?? $airport->name);
                $aircode->setLat($airport->latitude);
                $aircode->setLng($airport->longitude);

                // fix for Spain airports
                if (
                    $airport->countryCode == 'ES'
                    && (!isset($airport->stateCode) || $airport->stateCode != 'CI')
                ) {
                    $aircode->setCountryname('Spain');
                }

                $aircode->setTimeZoneLocation($airport->timeZoneRegionName);
                $aircode->setLastupdatedate(new \DateTime());

                $em->persist($aircode);

                $flushCounter++;

                if ($flushCounter % 1000 == 0) {
                    $em->flush();
                    $em->clear();
                    $output->writeln("{$flushCounter} records processed...");
                }
            }
        }

        if (!$input->getOption('dry-run')) {
            $em->flush();
        }

        $total = $added + $updated + $woIata;
        $countTotal = $em->getConnection()->executeQuery('SELECT count(*) as cnt FROM AirCode')->fetch()['cnt'];
        $countOld = $em->getConnection()->executeQuery('SELECT count(*) as cnt FROM AirCode WHERE LastUpdateDate <= DATE_SUB(NOW(), INTERVAL 1 HOUR) OR LastUpdateDate is NULL')->fetch()['cnt'];

        $percentage = round($countOld / $countTotal * 100);
        $output->writeln("want to delete: $countOld ($percentage%)");

        if (!$input->getOption('dry-run') && ($input->getOption('delete') || $percentage < 5)) {// 5 percentage or delete option
            $em->getConnection()->executeQuery('DELETE FROM AirCode WHERE LastUpdateDate <= DATE_SUB(NOW(), INTERVAL 1 HOUR) OR LastUpdateDate is NULL');
            $output->writeln("Deleted {$countOld} old records. ({$percentage}%)");
        } else {
            $output->writeln("Deleting old records disabled. {$countOld} old records. ({$percentage}%)");
        }

        $log = "Airports updated successfully! Total: {$total}, Added: {$added}, Updated: {$updated}, Without IATA: {$woIata}, Invalid type: {$invalidType}";
        $this->logger->info($log);
        $output->writeln($log);

        return 0;
    }
}
