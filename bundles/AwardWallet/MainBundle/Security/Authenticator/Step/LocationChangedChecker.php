<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Globals\Geo;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;

class LocationChangedChecker
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Geo
     */
    private $geo;

    public function __construct(
        LoggerInterface $logger,
        Geo $geo
    ) {
        $this->logger = $logger;
        $this->geo = $geo;
    }

    public function isLocationChanged(Credentials $credentials, array $logContext, ?array &$last = null, ?array &$current = null): bool
    {
        $request = $credentials->getRequest();
        $user = $credentials->getUser();
        $lastIp = $user->getLastlogonip();

        if (empty($lastIp)) {
            $lastIp = $user->getRegistrationip();
        }

        $last = $this->geo->getLocationByIp($lastIp);
        $current = $this->geo->getLocationByIp($request->getClientIp());
        $matchLast = $this->geo->isUserMatchLocations($user, $current, $last);

        if ($matchLast) {
            $this->logger->info("Location not changed", \array_merge($logContext, [
                "current_ip" => $current,
                "last_ip" => $last,
            ]));

            return false;
        }

        return true;
    }
}
