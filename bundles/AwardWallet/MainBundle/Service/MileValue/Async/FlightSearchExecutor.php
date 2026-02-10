<?php

namespace AwardWallet\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\Service\MileValue\BestPriceSelector;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\PriceSourceInterface;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Service\SocksMessaging\SendLogsToChannelHandler;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class FlightSearchExecutor implements ExecutorInterface
{
    /**
     * @var PriceSourceInterface[]
     */
    private $priceSources;

    private LoggerInterface $logger;

    private SocksClient $messaging;

    private BestPriceSelector $bestPriceSelector;

    public function __construct(iterable $mileValuePriceSources, LoggerInterface $logger, SocksClient $messaging, BestPriceSelector $bestPriceSelector)
    {
        $this->priceSources = $mileValuePriceSources;
        $this->logger = $logger;
        $this->messaging = $messaging;
        $this->bestPriceSelector = $bestPriceSelector;
    }

    /**
     * @param FlightSearchTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $this->logger->pushHandler(new SendLogsToChannelHandler(Logger::INFO, $task->getResponseChannel(), $this->messaging));

        try {
            $prices = [];

            foreach ($this->priceSources as $priceSource) {
                $sourceClass = get_class($priceSource);
                $sourceName = basename(str_replace('\\', '/', $sourceClass));

                if (!in_array($sourceClass, $task->getPriceSources())) {
                    $this->logger->info("skipping source {$sourceName}");

                    continue;
                }
                $this->logger->info("searching in " . $sourceName);
                $found = $priceSource->search($task->getRoutes(), $task->getClassOfService(), $task->getPassengers());
                $this->logger->info("found " . count($found) . " results in {$sourceName}");
                $prices = array_merge($prices, $found);
            }

            $this->logger->info("search complete, sorting");
            $priceInfos = $this->bestPriceSelector->getBestPriceList($prices, $task->getRoutes(), $task->getDuration() * 3600, $task->getClassOfService());

            $this->logger->info("sending results (" . count($priceInfos) . ")");

            while (count($priceInfos) > 0) {
                $this->messaging->publish($task->getResponseChannel(), ["type" => "results", "results" => array_splice($priceInfos, 0, 50)]);
            }

            $this->logger->info("done");
        } finally {
            $this->logger->popHandler();
        }

        return new Response();
    }
}
