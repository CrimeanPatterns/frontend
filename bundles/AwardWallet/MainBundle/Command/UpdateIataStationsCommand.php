<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Stationcode;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateIataStationsCommand extends Command
{
    protected static $defaultName = 'aw:update-stations';

    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private $iataCodesApiKey;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        $iataCodesApiKey
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->iataCodesApiKey = $iataCodesApiKey;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('update stations from iatacodes.org')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'dry run')
            ->addOption('delete', 'r', InputOption::VALUE_NONE, 'delete old records if there are less 5%');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiKey = $this->iataCodesApiKey;

        $em = $this->entityManager;

        $client = new Client();

        $verify = false; // issue with validating certificate from ubuntu
        $response = $client->request('GET', "https://iatacodes.org/api/v6/airports?api_key={$apiKey}", ['verify' => $verify]);

        $json = json_decode($response->getBody()->getContents());

        unset($response);
        unset($client);

        $stationCodeRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Stationcode::class);
        $added = $updated = $notRBS = $flushCounter = 0;

        /*        {
              "code": "BPO",
              "name": "Bo'ao Railway Station",
              "country_code": "CN",
              "icao": "",
              "city_code": "BPO",
              "lat": null,
              "lng": null,
              "alternatenames": "Bo'ao Railway Station",
              "timezone": "Asia/Shanghai",
              "gmt": 8,
              "is_rail_road": 1,
              "is_bus_station": 0,
              "tch_code": null,
              "popularity": 0,
              "phone": "",
              "website": "",
              "geoname_id": 0,
              "routes": 0,
              "type": "rail_station"
            },
        */
        foreach ($json->response as $obj) {
            if ($obj->is_rail_road !== 1 && $obj->is_bus_station !== 1
                && $obj->type !== 'rail_station' && $obj->type !== 'bus_station') {// not rail or bus station
                $notRBS++;

                continue;
            }

            /** @var Stationcode $stationCode */
            $stationCode = $stationCodeRep->findOneBy(['stationcode' => $obj->code]);

            if (!$stationCode) {
                $stationCode = new Stationcode();
                $added++;
            } else {
                $updated++;
            }

            if (!$input->getOption("dry-run")) {
                $stationCode->setStationcode($obj->code);
                $stationCode->setStationname($obj->name);
                $stationCode->setCountrycode($obj->country_code);

                if ($obj->is_bus_station === 1 || $obj->type === 'bus_station') {
                    $stationCode->setType(Stationcode::TYPE_BUS);
                } elseif ($obj->is_rail_road === 1 || $obj->type === 'rail_station') {
                    $stationCode->setType(Stationcode::TYPE_RAIL);
                }
                $stationCode->setIcaoCode($obj->icao);
                $stationCode->setCitycode($obj->city_code);
                $stationCode->setLat($obj->lat);
                $stationCode->setLng($obj->lng);
                $stationCode->setLatOriginal($obj->lat);
                $stationCode->setLngOriginal($obj->lng);
                $stationCode->setAlternatenames($obj->alternatenames);
                $stationCode->setTimeZoneLocation($obj->timezone);
                $stationCode->setGmt($obj->gmt);
                $stationCode->setLastupdatedate(new \DateTime());

                $em->persist($stationCode);

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

        $total = $added + $updated + $notRBS;
        $countTotal = $em->getConnection()->executeQuery('SELECT count(*) as cnt FROM StationCode')->fetch()['cnt'];
        $countOld = $em->getConnection()->executeQuery('SELECT count(*) as cnt FROM StationCode WHERE LastUpdateDate <= DATE_SUB(NOW(), INTERVAL 1 HOUR) OR LastUpdateDate is NULL')->fetch()['cnt'];

        if (!empty($countTotal)) {
            $percentage = round($countOld / $countTotal * 100);
            $output->writeln("want to delete: $countOld ($percentage%)");

            if (!$input->getOption('dry-run') && ($input->getOption('delete') || $percentage < 5)) {// 5 percentage or delete option
                $em->getConnection()->executeQuery('DELETE FROM StationCode WHERE LastUpdateDate <= DATE_SUB(NOW(), INTERVAL 1 HOUR) OR LastUpdateDate is NULL');
                $output->writeln("Deleted {$countOld} old records. ({$percentage}%)");
            } else {
                $output->writeln("Deleting old records disabled. {$countOld} old records. ({$percentage}%)");
            }
        }

        $log = "Stations updated successfuly! Total: {$total}, Added: {$added}, Updated: {$updated}, Not Rail or Bus Station IATA: {$notRBS}";
        $this->logger->info($log);
        $output->writeln($log);

        return 0;
    }
}
