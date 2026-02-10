<?php

namespace AwardWallet\MainBundle\Service\Billing;

use PayPal\Api\Payment;
use PayPal\Rest\ApiContext;
use Psr\Log\LoggerInterface;

class PaypalTransactionsSource
{
    private const PAYPAL_DATE_FORMAT = 'Y-m-d\TH:i:s\Z';
    private const PAGE_SIZE = 20;

    private ApiContext $apiContext;

    private \Memcached $memcached;

    private LoggerInterface $logger;

    public function __construct(PaypalRestApi $paypalRestApi, \Memcached $memcached, LoggerInterface $logger)
    {
        $this->apiContext = $paypalRestApi->getApiContext();
        $this->memcached = $memcached;
        $this->logger = $logger;
    }

    /**
     * @return Payment[]
     */
    public function getTransactions(int $startDate, int $endDate): iterable
    {
        $currentDate = $startDate;
        $step = 3600;

        if (($currentDate + $step) > $endDate) {
            $step = $endDate - $startDate;
        }

        do {
            [$payments, $currentDate, $step] = $this->fetchPage($currentDate, $step);

            foreach ($payments as $payment) {
                yield $payment;
            }
        } while ($currentDate < $endDate);
    }

    /**
     * @return Payment[]
     */
    private function searchTransactions(array $params): array
    {
        $this->logger->info("searching transaction: " . json_encode($params));
        $cacheKey = implode('_', $params);
        $result = $this->memcached->get($cacheKey);

        if ($result !== false) {
            return $result;
        }

        $response = Payment::all($params, $this->apiContext);
        $this->memcached->set($cacheKey, $response->payments, 86400 * 7);

        return $response->payments;
    }

    private function fetchPage(int $currentDate, int $step): array
    {
        $try = 0;

        do {
            $params = ['start_time' => date(self::PAYPAL_DATE_FORMAT, $currentDate), 'end_time' => date(self::PAYPAL_DATE_FORMAT, $currentDate + $step), 'count' => self::PAGE_SIZE];
            $payments = $this->searchTransactions($params);

            if (count($payments) < self::PAGE_SIZE) {
                return [$payments, $currentDate + $step, $this->calcNewStep($step, count($payments))];
            }
            $step = intdiv($step, 2);
            $this->logger->info("too much results (" . count($payments) . "), decreasing step to $step");
            $try++;
        } while ($try < 10);

        throw new \Exception("failed to adjust step");
    }

    private function calcNewStep(int $currentStep, int $currentResultCount): int
    {
        if ($currentResultCount < intdiv(self::PAGE_SIZE, 4)) {
            $result = $currentStep * 2;
            $this->logger->debug("increasing step to $result");

            return $result;
        }

        return $currentStep;
    }
}
