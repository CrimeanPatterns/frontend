<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TwoFactorAuthChecker
{
    private CacheManager $cacheManager;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Counter $counter;

    private UsrRepository $userRep;

    public function __construct(
        CacheManager $cacheManager,
        AuthorizationCheckerInterface $authorizationChecker,
        Counter $counter,
        UsrRepository $userRep
    ) {
        $this->cacheManager = $cacheManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->counter = $counter;
        $this->userRep = $userRep;
    }

    public function isNeedTwoFactorAuth(Usr $user): bool
    {
        return !$this->authorizationChecker->isGranted('ROLE_STAFF')
            && !is_null($this->getTwoFactorAuthData($user));
    }

    public function getPopupData(Usr $user): ?array
    {
        return $this->getTwoFactorAuthData($user);
    }

    public function resetCache(Usr $user): void
    {
        $this->cacheManager->invalidateTags([
            Tags::getNeedTwoFactorAuthKey($user->getId()),
        ]);
    }

    private function getTwoFactorAuthData(Usr $user): ?array
    {
        return $this->cacheManager->load(new CacheItemReference(
            Tags::getNeedTwoFactorAuthKey($user->getId()),
            Tags::getNeedTwoFactorAuthTags($user->getId()),
            function () use ($user) {
                if ($user->getGoogleAuthSecret()) {
                    return null;
                }

                $business = $this->getBusiness($user);

                if (!$business) {
                    return null;
                }

                $usersCount = (int) $this->counter->getConnections($business->getId());

                if ($usersCount <= 1) {
                    return null;
                }

                $userAgent = $user->findUserAgent($business->getId());

                return [
                    'company' => $business->getCompany(),
                    'userAgentId' => $userAgent ? $userAgent->getId() : null,
                    'isBusinessAdmin' => !is_null($this->userRep->getBusinessByUser($user, [ACCESS_ADMIN], false)),
                ];
            }
        ));
    }

    private function getBusiness(Usr $personalUser): ?Usr
    {
        return $this->userRep->getBusinessByUser($personalUser, [
            ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY,
        ], false);
    }
}
