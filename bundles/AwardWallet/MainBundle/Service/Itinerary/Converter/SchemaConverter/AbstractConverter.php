<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Service\LogProcessor;
use Psr\Log\LoggerInterface;

abstract class AbstractConverter
{
    protected LoggerInterface $logger;

    protected LogProcessor $logProcessor;

    protected BaseConverter $baseConverter;

    protected Helper $helper;

    public function __construct(
        LoggerFactory $loggerFactory,
        BaseConverter $baseConverter,
        Helper $helper
    ) {
        $this->logProcessor = $loggerFactory->createProcessor();
        $this->logger = $loggerFactory->createLogger($this->logProcessor);
        $this->baseConverter = $baseConverter;
        $this->helper = $helper;
    }
}
