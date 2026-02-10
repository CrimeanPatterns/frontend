<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;

class BusinessAdminStep extends AbstractStep
{
    public const ID = 'business_admin';
    /**
     * @var string
     */
    protected $businessHost;
    /**
     * @var UsrRepository
     */
    protected $usrRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        string $businessHost,
        UsrRepository $usrRepository,
        LoggerInterface $logger
    ) {
        $this->businessHost = $businessHost;
        $this->usrRepository = $usrRepository;
        $this->logger = $logger;
    }

    protected function supports(Credentials $credentials): bool
    {
        $isBusinessHost = strcmp($credentials->getRequest()->getHost(), $this->businessHost) == 0;

        if ($isBusinessHost) {
            $this->logger->info('Business host is detected', $this->getLogContext($credentials));

            return true;
        }

        $this->logger->info('No business host is detected', $this->getLogContext($credentials));

        return false;
    }

    protected function doCheck(Credentials $credentials): void
    {
        $businessUser = $this->usrRepository->getBusinessByUser($credentials->getUser());

        if (!$businessUser) {
            $this->logger->warning('No business accounts were found for user', $this->getLogContext($credentials));
            $this->throwErrorException("You are not an administrator of any business account");
        }

        $this->logger->info('Business account was found', $this->getLogContext($credentials, ['business_user' => $businessUser->getUserid()]));
    }
}
