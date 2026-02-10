<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\CardImage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CardImageVoter extends AbstractVoter
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
     * @var SiteVoter
     */
    private $siteVoter;

    /**
     * CardImageVoter constructor.
     */
    public function __construct(
        ContainerInterface $container,
        AccountVoter $accountVoter,
        CouponVoter $couponVoter,
        SiteVoter $siteVoter
    ) {
        parent::__construct($container);

        $this->accountVoter = $accountVoter;
        $this->couponVoter = $couponVoter;
        $this->siteVoter = $siteVoter;
    }

    public function edit(TokenInterface $token, CardImage $cardImage)
    {
        return $this->checkContainerAccess($token, $cardImage, ['edit', 'edit']);
    }

    public function delete(TokenInterface $token, CardImage $cardImage)
    {
        return $this->checkContainerAccess($token, $cardImage, ['delete', 'delete']);
    }

    public function view(TokenInterface $token, CardImage $cardImage)
    {
        return $this->checkContainerAccess($token, $cardImage, ['readNumber', 'read']);
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
        return CardImage::class;
    }

    protected function checkContainerAccess(TokenInterface $token, CardImage $cardImage, array $cardImageContainerVoters)
    {
        [$accountVoter, $couponVoter] = $cardImageContainerVoters;

        if ($account = $cardImage->getAccount()) {
            return $this->accountVoter->$accountVoter($token, $account);
        }

        if ($coupon = $cardImage->getProviderCoupon()) {
            if ($coupon->isDocument() && $this->siteVoter->isImpersonated($token, $cardImage)) {
                return false;
            }

            return $this->couponVoter->$couponVoter($token, $coupon);
        }

        if ($subaccount = $cardImage->getSubAccount()) {
            return $this->accountVoter->$accountVoter($token, $subaccount->getAccountid());
        }

        return
            ($user = $token->getUser())
            && ($cardUser = $cardImage->getUser())
            && ($user->getUserId() === $cardUser->getUserid());
    }
}
