<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Aircraft;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAircraftsCommand extends Command
{
    protected static $defaultName = 'aw:update-aircrafts';

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
        $this
            ->setDescription('Update Aircrafts from FS')
            ->addOption('delete', 'r', InputOption::VALUE_NONE, 'delete old records');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiId = $this->appId;
        $apiKey = $this->appKey;
        $em = $this->entityManager;
        $aircraftRepo = $em->getRepository(Aircraft::class);

        $client = new Client();

        $added = $updated = $flushCounter = 0;

        $response = $client->get("https://api.flightstats.com/flex/equipment/rest/v1/json/all?appId={$apiId}&appKey={$apiKey}");
        $json = json_decode($response->getBody()->getContents());

        if ($json && count($json->equipment)) {
            $aircrafts = $json->equipment;

            foreach ($aircrafts as $aircraft) {
                $ac = $aircraftRepo->findOneBy(['IataCode' => $aircraft->iata]);

                if (!$ac) {
                    $ac = new Aircraft();
                    $ac->setIataCode($aircraft->iata);
                    $ac->setName($aircraft->name);
                    $ac->setShortName($aircraft->name);
                    $ac->setTurboProp($aircraft->turboProp);
                    $ac->setJet($aircraft->jet);
                    $ac->setWideBody($aircraft->widebody);
                    $ac->setRegional($aircraft->regional);
                    $ac->setIcon('');
                    $added++;
                } else {
                    $updated++;
                }

                $ac->setUpdatedAt(new \DateTime());
                $em->persist($ac);
                $flushCounter++;

                if ($flushCounter % 100 === 0) {
                    $em->flush();
                    $em->clear();
                    $output->writeln("Processed {$flushCounter}");
                }
            }
        }

        $em->flush();

        $countOld = $em->getConnection()->executeQuery('SELECT count(*) as cnt FROM Aircraft WHERE UpdatedAt = DATE_SUB(NOW(), INTERVAL 60 DAY)')->fetch()['cnt'];

        $total = count($json->equipment);
        $percentage = round($countOld / $total * 100);
        $info = "Update successfuly. Total: {$total}, added: {$added}, updated: {$updated}, old: {$countOld} ({$percentage}%).";
        $output->writeln($info);
        $this->logger->info($info);

        if ($input->getOption('delete') || ($percentage > 0 && $percentage < 5)) {
            $em->getConnection()->executeQuery('DELETE FROM Aircraft WHERE UpdateAt = DATE_SUB(NOW(), INTERVAL 60 DAY)');
            $output->writeln("Deleted {$countOld} old records.");
        }

        return 0;
    }
}
