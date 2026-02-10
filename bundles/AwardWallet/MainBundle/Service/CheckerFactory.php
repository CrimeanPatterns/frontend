<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\Common\Parsing\ParsingConstants;
use Psr\Log\LoggerInterface;

class CheckerFactory
{
    private LoggerInterface $logger;

    public function __construct(ParsingConstants $parsingConstants, LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getAccountChecker(string $providerCode, bool $requireFiles = false, $accountInfo = null)
    {
        \StatLogger::setLogger($this->logger);

        return \GetAccountChecker($providerCode, $requireFiles, $accountInfo);
    }

    public function getRewardAvailabilityChecker(string $providerCode, bool $requireFiles = false, $accountInfo = null)
    {
        \StatLogger::setLogger($this->logger);

        return \GetRewardAvailabilityChecker($providerCode, $requireFiles, $accountInfo, 'Parser');
    }
}
