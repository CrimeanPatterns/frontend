<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class IpLockoutStep extends AbstractStep
{
    public const ID = 'ip_locker';

    /**
     * @var AntiBruteforceLockerService
     */
    protected $locker;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        AntiBruteforceLockerService $locker,
        LoggerInterface $logger
    ) {
        $this->locker = $locker;
        $this->logger = $logger;
    }

    public function onFail(Request $request, AuthenticationException $exception): void
    {
        $this->locker->checkForLockout($request->getClientIp());
    }

    protected function doCheck(Credentials $credentials): void
    {
        // hotfixing bruteforce attack, feel free to remove
        // rewrite to auto-throttling
        //        sleep(3);

        $error = $this->locker->checkForLockout($credentials->getRequest()->getClientIp(), true);

        if (StringUtils::isNotEmpty($error)) {
            $this->logger->warning('IP antibruteforce check failed', $this->getLogContext($credentials));
            $this->throwErrorException($error);
        }

        $this->logger->info('IP antibruteforce check passed', $this->getLogContext($credentials));
    }
}
