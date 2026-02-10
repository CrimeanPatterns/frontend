<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\Location;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoyaltyLocationVoter extends AbstractVoter
{
    /**
     * @var AccountVoter
     */
    private $accountVoter;
    /**
     * @var CouponVoter
     */
    private $couponVoter;

    /**
     * CardImageVoter constructor.
     */
    public function __construct(
        ContainerInterface $container,
        AccountVoter $accountVoter,
        CouponVoter $couponVoter
    ) {
        parent::__construct($container);

        $this->accountVoter = $accountVoter;
        $this->couponVoter = $couponVoter;
    }

    public function edit(TokenInterface $token, Location $location)
    {
        return $this->checkContainerAccess($token, $location, ['edit', 'edit']);
    }

    public function delete(TokenInterface $token, Location $location)
    {
        return $this->checkContainerAccess($token, $location, ['edit', 'edit']);
    }

    public function view(TokenInterface $token, Location $location)
    {
        return $this->checkContainerAccess($token, $location, ['edit', 'edit']);
    }

    protected function getAttributes()
    {
        return [
            'VIEW' => [$this, 'view'],
            'DELETE' => [$this, 'delete'],
            'EDIT' => [$this, 'edit'],
        ];
    }

    protected function getClass()
    {
        return Location::class;
    }

    protected function checkContainerAccess(TokenInterface $token, Location $location, array $locationContainerVoters)
    {
        [$accountVoter, $couponVoter] = $locationContainerVoters;

        if ($account = $location->getAccount()) {
            return $this->accountVoter->$accountVoter($token, $account);
        }

        if ($coupon = $location->getProviderCoupon()) {
            return $this->couponVoter->$couponVoter($token, $coupon);
        }

        if ($subaccount = $location->getSubAccount()) {
            return $this->accountVoter->$accountVoter($token, $subaccount->getAccountid());
        }

        return false;
    }
}
