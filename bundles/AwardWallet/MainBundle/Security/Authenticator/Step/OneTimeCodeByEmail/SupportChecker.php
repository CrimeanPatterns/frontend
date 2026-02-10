<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\LocationChangedChecker;
use Psr\Log\LoggerInterface;

class SupportChecker
{
    public const BYPASS_EMAIL_OTC_GROUP = "Bypass security code";
    public const TEST_IP_ADDRESS_COOKIE_NAME = "TestIpAddress";
    public const ENABLE_EMAIL_OTC_COOKIE_NAME = "EnableEmailOtcCheck";

    /**
     * @var LocationChangedChecker
     */
    private $locationChangedChecker;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var bool
     */
    private $enabled;

    public function __construct(
        LocationChangedChecker $locationChangedChecker,
        LoggerInterface $logger,
        bool $enabled
    ) {
        $this->locationChangedChecker = $locationChangedChecker;
        $this->logger = $logger;
        $this->enabled = $enabled;
    }

    public function supports(Credentials $credentials, array $logContext): bool
    {
        if ($this->isUserInBypassingGroup($credentials->getUser())) {
            $this->logger->info('User in bypassing group "' . self::BYPASS_EMAIL_OTC_GROUP . '"', $logContext);

            return false;
        }

        if (!empty($credentials->getRequest()->cookies->get(self::TEST_IP_ADDRESS_COOKIE_NAME))) {
            $this->logger->info('Request has forcing "' . self::TEST_IP_ADDRESS_COOKIE_NAME . '" cookie', $logContext);

            return true;
        }

        if (!$this->enabled && empty($credentials->getRequest()->cookies->get(self::ENABLE_EMAIL_OTC_COOKIE_NAME))) {
            $this->logger->info('email otc step is disabled', $logContext);

            return false;
        }

        if ($this->isLocationChanged($credentials, $logContext)) {
            return true;
        }

        $this->logger->info('Email OTC check skipped', $logContext);

        return false;
    }

    protected function isUserInBypassingGroup(Usr $user): bool
    {
        return $user
            ->getGroups()
            ->exists(function ($_, Sitegroup $sitegroup) { return $sitegroup->getGroupname() == self::BYPASS_EMAIL_OTC_GROUP; });
    }

    private function isLocationChanged(Credentials $credentials, array $logContext): bool
    {
        if ($this->locationChangedChecker->isLocationChanged($credentials, $logContext, $last, $current)) {
            $this->logger->warning("Required email-OTC check for user, location changed", \array_merge($logContext, [
                "current_ip" => $current,
                "last_ip" => $last,
            ]));

            return true;
        }

        return false;
    }
}
