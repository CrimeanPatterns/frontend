<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Manager\NDRManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SparkPostCommand extends Command
{
    protected static $defaultName = 'aw:sparkpost:import-suppression';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $lastResponse;

    private NDRManager $ndrManager;
    private \Memcached $memcached;

    public function __construct(
        LoggerInterface $logger,
        NDRManager $ndrManager,
        \Memcached $memcached,
        $sparkpostApiKey
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->ndrManager = $ndrManager;
        $this->memcached = $memcached;
        $this->apiKey = $sparkpostApiKey;
    }

    protected function configure()
    {
        $this
            ->setDescription('import supression list from spark post')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("importing suppression list from sparkpost");

        $bounces = $this->callAPI("GET", "/suppression-list?limit=500");

        if (!isset($bounces['results'])) {
            // ignore single errors
            if (!$this->memcached->add("aw_spark_error", time(), 45 * 60)) {
                throw new \Exception("invalid data: " . $this->lastResponse);
            } else {
                $this->logger->warning("invalid data", ["data" => $this->lastResponse]);
            }
        }

        $this->logger->info("got " . count($bounces['results']) . " bounces from api");

        foreach ($bounces['results'] as $bounce) {
            $this->logger->warning("bounce", ["bounce" => $bounce, "email" => $bounce['recipient']]);
            $date = strtotime($bounce['updated']);

            if ($this->ndrManager->ndrExists($bounce['recipient'], $date)) {
                $this->logger->info("bounce already recorded", ["email" => $bounce['recipient']]);

                if (!$this->callAPI("DELETE", "/suppression-list/" . urlencode($bounce['recipient']))) {
                    return 1;
                }
            } elseif (time() > ($date + 3600 * 6)) {
                $this->logger->warning("delete bounce anyway, but record it", ["email" => $bounce['recipient']]);
                $this->ndrManager->recordNDR($bounce['recipient'], $bounce['updated'], false, $bounce['description'] ?? '');

                if (!$this->callAPI("DELETE", "/suppression-list/" . urlencode($bounce['recipient']))) {
                    return 2;
                }
            } else {
                $this->logger->info("too early, will wait", ["email" => $bounce['recipient']]);
            }
        }

        $this->logger->info("done");

        return 0;
    }

    private function callAPI($method, $url): ?array
    {
        $info = [];
        $this->lastResponse = curlRequest(
            "https://api.sparkpost.com/api/v1" . $url,
            180,
            [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->apiKey}",
                    "Content-Type: application/json",
                ],
                CURLOPT_FAILONERROR => false,
            ],
            $info,
            $errorCode
        );

        if ($errorCode != 0) {
            $this->logger->warning("sparkpost api error: Curl error $errorCode");

            return null;
        }

        return json_decode($this->lastResponse, true);
    }
}
