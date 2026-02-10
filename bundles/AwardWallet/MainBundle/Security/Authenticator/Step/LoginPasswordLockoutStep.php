<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;

class LoginPasswordLockoutStep extends AbstractStep
{
    public const ID = 'login_password_lockout';

    /**
     * @var AntiBruteforceLockerService
     */
    protected $locker;
    /**
     * @var AntiBruteforceLockerService
     */
    protected $loginLocker;
    /**
     * @var AntiBruteforceLockerService
     */
    protected $passwordLocker;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        AntiBruteforceLockerService $loginLocker,
        AntiBruteforceLockerService $passwordLocker,
        LoggerInterface $logger
    ) {
        $this->loginLocker = $loginLocker;
        $this->passwordLocker = $passwordLocker;
        $this->logger = $logger;
    }

    protected function doCheck(Credentials $credentials): void
    {
        $user = $credentials->getUser();

        if ($user) {
            $error = $this->loginLocker->checkForLockout($user->getUsername());

            if (StringUtils::isNotEmpty($error)) {
                $this->logger->warning('Login antibruteforce check failed', $this->getLogContext($credentials));
                $this->throwErrorException($error);
            }

            $this->logger->info('Login antibruteforce check passed', $this->getLogContext($credentials));

            $password = $credentials->getStepData()->getPassword();

            if (is_scalar($password) && StringUtils::isNotEmpty($password)) {
                $error = $this->passwordLocker->checkForLockout($password);

                if (StringUtils::isNotEmpty($error)) {
                    $this->logger->warning('Password antibruteforce check failed', $this->getLogContext($credentials));
                    $this->throwErrorException('Bad credentials');
                }
            }

            $this->logger->info('Password antibruteforce check passed', $this->getLogContext($credentials));
        }
    }
}
