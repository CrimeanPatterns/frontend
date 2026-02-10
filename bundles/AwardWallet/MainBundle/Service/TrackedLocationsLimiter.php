<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TrackedLocationsLimiter
{
    private int $loyaltyLocationMaxTracked;
    private int $loyaltyLocationMaxTrackedStaff;
    private ApiVersioningService $apiVersioning;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        int $loyaltyLocationMaxTracked,
        int $loyaltyLocationMaxTrackedStaff,
        ApiVersioningService $apiVersioning,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->loyaltyLocationMaxTracked = $loyaltyLocationMaxTracked;
        $this->loyaltyLocationMaxTrackedStaff = $loyaltyLocationMaxTrackedStaff;
        $this->apiVersioning = $apiVersioning;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getMaxTrackedLocations(): int
    {
        return
            (
                $this->authorizationChecker->isGranted('ROLE_STAFF')
                && $this->apiVersioning->supports(MobileVersions::STAFF_TRACKED_LOCATIONS_INCREASED_MAX)
            ) ?
                $this->loyaltyLocationMaxTrackedStaff :
                $this->loyaltyLocationMaxTracked;
    }
}
