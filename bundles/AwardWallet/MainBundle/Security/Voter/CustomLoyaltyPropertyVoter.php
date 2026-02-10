<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\CustomLoyaltyProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CustomLoyaltyPropertyVoter extends AbstractVoter
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

    public function delete(TokenInterface $token, CustomLoyaltyProperty $customLoyaltyProperty)
    {
        return $this->checkContainerAccess($token, $customLoyaltyProperty, ['delete', 'delete']);
    }

    public function view(TokenInterface $token, CustomLoyaltyProperty $customLoyaltyProperty)
    {
        return $this->checkContainerAccess($token, $customLoyaltyProperty, ['readNumber', 'read']);
    }

    protected function getAttributes()
    {
        return [
            'VIEW' => [$this, 'view'],
            'DELETE' => [$this, 'delete'],
        ];
    }

    protected function getClass()
    {
        return CustomLoyaltyProperty::class;
    }

    protected function checkContainerAccess(TokenInterface $token, CustomLoyaltyProperty $customLoyaltyProperty, array $customLoyaltyPropertyContainerVoters)
    {
        [$accountVoter, $couponVoter] = $customLoyaltyPropertyContainerVoters;

        if ($account = $customLoyaltyProperty->getAccount()) {
            return $this->accountVoter->$accountVoter($token, $account);
        }

        if ($coupon = $customLoyaltyProperty->getProviderCoupon()) {
            return $this->couponVoter->$couponVoter($token, $coupon);
        }

        if ($subaccount = $customLoyaltyProperty->getSubAccount()) {
            return $this->accountVoter->$accountVoter($token, $subaccount->getAccountid());
        }

        return false;
    }
}
