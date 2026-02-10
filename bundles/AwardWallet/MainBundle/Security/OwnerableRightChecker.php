<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\UserOwnedInterface;
use AwardWallet\MainBundle\Security\Voter\SiteVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnerableRightChecker
{
    private $siteVoter;
    private $usrRepository;

    public function __construct(SiteVoter $siteVoter, UsrRepository $usrRepository)
    {
        $this->siteVoter = $siteVoter;
        $this->usrRepository = $usrRepository;
    }

    /**
     * @param UserOwnedInterface|Account|Providercoupon $object
     * @param array $rights
     * @return bool
     */
    public function check(TokenInterface $token, UserOwnedInterface $object, $rights = [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
    {
        $businessUser = $this->usrRepository->getBusinessByUser($token->getUser());

        if ($object->getUseragentid()) {
            $connection = $object->getUseragentid();

            if ($connection->isFamilyMember()) {
                if ($this->siteVoter->isBusiness()) {
                    if ($connection->getAgentid() == $businessUser) {
                        return true;
                    }

                    $user = $object->getUseragentid()->getAgentid();
                    $connection = $businessUser->getConnectionWith($user);
                } else {
                    return $connection->getAgentid() == $businessUser;
                }
            }

            return $connection && $connection->getAgentid() == $businessUser && in_array($connection->getAccesslevel(), $rights) && $connection->getIsapproved();
        }

        $connection = $businessUser->getConnectionWith($object->getUserid());

        if ($object->getUserid()->getUserid() == $businessUser->getUserid() || $connection && $connection->getIsapproved()) {
            return true;
        }

        return false;
    }
}
