<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfStep extends AbstractStep
{
    public const ID = 'csrf';

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $csrfTokenManager;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface $logger
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->logger = $logger;
    }

    protected function doCheck(Credentials $credentials): void
    {
        $csrfToken = $credentials->getStepData()->getCsrfToken();

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            $this->logger->warning('CSRF token is invalid', $this->getLogContext($credentials));
            $this->throwErrorException('Invalid CSRF token');
        }

        $this->logger->info('CSRF token is valid', $this->getLogContext($credentials));
    }
}
