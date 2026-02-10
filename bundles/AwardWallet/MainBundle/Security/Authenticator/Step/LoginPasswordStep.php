<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\PasswordChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class LoginPasswordStep extends AbstractStep
{
    public const ID = 'login_password';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var PasswordChecker
     */
    protected $passwordChecker;

    public function __construct(
        LoggerInterface $logger,
        PasswordChecker $passwordChecker
    ) {
        $this->logger = $logger;
        $this->passwordChecker = $passwordChecker;
    }

    protected function doCheck(Credentials $credentials): void
    {
        try {
            $this->passwordChecker->checkPasswordUnsafe($credentials->getUser(), $credentials->getStepData()->getPassword() ?? '');
        } catch (BadCredentialsException $badCredentialsException) {
            $this->logger->warning('Credentials check failed', $this->getLogContext($credentials));
            $this->throwErrorException($badCredentialsException->getMessage());
        }

        $this->logger->info('Credentials check passed', $this->getLogContext($credentials));
    }
}
