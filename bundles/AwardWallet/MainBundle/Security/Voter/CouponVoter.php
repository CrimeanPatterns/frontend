<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Providercoupon;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CouponVoter extends AbstractVoter
{
    public function read(TokenInterface $token, Providercoupon $coupon)
    {
        return $this->fullRights($token, $coupon, [
            ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER,
            ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL,
            ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS,
        ]);
    }

    public function edit(TokenInterface $token, Providercoupon $coupon)
    {
        return $this->fullRights($token, $coupon);
    }

    public function delete(TokenInterface $token, Providercoupon $coupon)
    {
        return $this->fullRights($token, $coupon);
    }

    public function share(TokenInterface $token, Providercoupon $coupon)
    {
        return !$coupon->getProvidercouponid() || $token->getUser()->getUserid() == $coupon->getUserid()->getUserid();
    }

    protected function getAttributes()
    {
        return [
            'READ' => [$this, 'read'],
            'EDIT' => [$this, 'edit'],
            'DELETE' => [$this, 'delete'],
            'SHARE' => [$this, 'share'],
        ];
    }

    protected function getClass()
    {
        return '\\AwardWallet\\MainBundle\\Entity\\Providercoupon';
    }

    private function fullRights(TokenInterface $token, Providercoupon $coupon, $rights = [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
    {
        $user = $this->getBusinessUser($token);

        if (empty($user)) {
            return false;
        }

        if ($user->getUserid() == $coupon->getUserid()->getUserid()) {
            return true;
        }

        $useragent = $coupon->getUseragentByUser($user);

        if (!sizeof($useragent)) {
            return false;
        }
        /** @var Useragent $useragent */
        $useragent = $useragent->first();

        return in_array($useragent->getAccesslevel(), $rights);
    }
}
