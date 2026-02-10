<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class TripLoaderFactory
{
    private Connection $connection;

    private CurrencyConverter $currencyConverter;

    private TripAnalyzer $tripAnalyzer;

    private LongHaulDetector $longHaulDetector;

    private LoggerInterface $logger;

    public function __construct(
        Connection $connection,
        CurrencyConverter $currencyConverter,
        TripAnalyzer $tripAnalyzer,
        LongHaulDetector $longHaulDetector,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->currencyConverter = $currencyConverter;
        $this->tripAnalyzer = $tripAnalyzer;
        $this->longHaulDetector = $longHaulDetector;
        $this->logger = $logger;
    }

    public function createTripLoader(bool $isLoggingEnabled = false): TripLoader
    {
        return new TripLoader(
            $this->connection->createQueryBuilder(),
            $this->currencyConverter,
            $this->tripAnalyzer,
            $this->longHaulDetector,
            $isLoggingEnabled ? $this->logger : null
        );
    }
}
