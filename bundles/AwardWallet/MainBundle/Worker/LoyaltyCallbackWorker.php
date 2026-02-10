<?php

namespace AwardWallet\MainBundle\Worker;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class LoyaltyCallbackWorker implements ConsumerInterface
{
    /** @var Logger */
    private $logger;
    /** @var Converter */
    private $converter;
    /** @var \Memcached */
    private $memcached;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var AppProcessor
     */
    private $appProcessor;
    /**
     * @var int
     */
    private $memoryReportTime = 0;
    private $messageCount = 0;
    private ProducerInterface $loyaltyCallbackDelayedProducer;

    public function __construct(
        Logger $logger,
        Converter $converter,
        \Memcached $memcached,
        EntityManagerInterface $em,
        AppProcessor $appProcessor,
        ProducerInterface $loyaltyCallbackDelayedProducer
    ) {
        $this->logger = $logger;
        $this->converter = $converter;
        $this->memcached = $memcached;
        $this->em = $em;
        $this->appProcessor = $appProcessor;
        $this->loyaltyCallbackDelayedProducer = $loyaltyCallbackDelayedProducer;
    }

    public function execute(AMQPMessage $msg)
    {
        $this->logger->pushProcessor(function (array $record): array {
            $record['extra']['worker'] = 'LoyaltyCallback';

            return $record;
        });

        try {
            $this->appProcessor->setNewRequestId();

            if ((time() - $this->memoryReportTime) > 60) {
                $this->logger->info("memory usage",
                    ["memory" => round(memory_get_usage(true) / 1024 / 1024), "processed_messages" => $this->messageCount]);
                $this->memoryReportTime = time();
            }
            $this->messageCount++;
            // trying to conserve memory and prevent
            // A new entity was found through the relationship 'AwardWallet\MainBundle\Entity\Accountproperty#subaccountid' that was not configured to cascade persist operations for entity:
            //        $this->count++;
            //        if($this->count > 100) {
            //            $this->logger->info("cleanup");
            $this->em->clear();
            //            $this->count = 0;
            //        }

            /** @var CheckAccountCallback $callback */
            try {
                $callback = $this->converter->deserialize($msg->getBody(), CheckCallback::class);

                if ($callback instanceof CheckAccountCallback) {
                    $this->processAccount($callback);

                    return true;
                }

                if ($callback instanceof CheckConfirmationCallback) {
                    $this->processConfirmation($callback);

                    return true;
                }
            } catch (\Throwable $e) {
                $logFile = tempnam(sys_get_temp_dir(), "lcwError");
                $this->logger->critical(TraceProcessor::filterMessage($e), ["logFile" => $logFile, "exception" => $e]);

                if ($e instanceof DriverException) {
                    if ($e->getErrorCode() === 2006) {
                        exit(1);
                    }
                }
                file_put_contents($logFile, $msg->getBody());
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

                exit(1);
            }

            $this->logger->critical('LoyaltyCallbackWorker Broken body');

            return true;
        } finally {
            $this->logger->popProcessor();
        }
    }

    private function processAccount(CheckAccountCallback $callback)
    {
        $failures = $this->converter->processCallbackPackage($callback);

        if (count($failures) > 0) {
            $this->logger->warning("got account saving failures: " . count($failures));
            $retry = new CheckAccountCallback();
            $retry->setResponse($failures);
            $this->loyaltyCallbackDelayedProducer->publish($this->converter->serialize($retry));
        }
    }

    private function processConfirmation(CheckConfirmationCallback $callback)
    {
        $response = $callback->getResponse()[0];

        /** @var CheckConfirmationRequest $request */
        $request = $this->memcached->get('check_confirmation_request_' . $response->getRequestid());

        if ($request === false) {
            $this->logger->notice('No requestId in ' . $response->getRequestid() . ' memcached');

            return;
        }

        global $allowUserID;
        $allowUserID = $request->getUserId();

        $this->saveInCache($response->getRequestid(), $this->converter->processCheckConfirmationResponse($response, $request));
        $allowUserID = null;
    }

    private function saveInCache(string $requestId, $data)
    {
        $cacheKey = sprintf('check_confirmation_result_%s', $requestId);

        if ($data instanceof ProcessingReport) {
            $touchedEntities = array_merge($data->getAdded(), $data->getUpdated(), $data->getRemoved());
            $data = array_map(function (Itinerary $itinerary) {
                return $itinerary->getIdString();
            }, $touchedEntities);
        }

        $this->memcached->set($cacheKey, $data, 300);
    }
}
