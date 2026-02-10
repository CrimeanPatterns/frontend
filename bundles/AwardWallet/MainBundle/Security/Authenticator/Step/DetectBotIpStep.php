<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\BotIpDetector;
use Psr\Log\LoggerInterface;

class DetectBotIpStep extends AbstractStep
{
    public const ID = 'bot_ip_detector';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var BotIpDetector
     */
    private $botIpDetector;

    public function __construct(
        BotIpDetector $botIpDetector,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->botIpDetector = $botIpDetector;
    }

    public function supports(Credentials $credentials): bool
    {
        return true;
    }

    public function doCheck(Credentials $credentials): void
    {
        $botIps = $this->botIpDetector->getBotIps();

        if (in_array($credentials->getRequest()->getClientIp(), $botIps)) {
            $this->logger->warning('bot IP check failed', $this->getLogContext($credentials));
            $this->throwErrorException("Bad credentials");
        }

        $this->logger->info('bot IP check passed', $this->getLogContext($credentials));
    }
}
