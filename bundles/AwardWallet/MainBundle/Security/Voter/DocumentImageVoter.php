<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Entity\DocumentImage;
use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DocumentImageVoter extends AbstractVoter
{
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
        CouponVoter $couponVoter,
        SiteVoter $siteVoter
    ) {
        parent::__construct($container);

        $this->couponVoter = $couponVoter;
        $this->siteVoter = $siteVoter;
    }

    public function edit(TokenInterface $token, DocumentImage $documentImage)
    {
        return $this->checkCouponAccess($token, $documentImage, 'edit');
    }

    public function delete(TokenInterface $token, DocumentImage $documentImage)
    {
        return $this->checkCouponAccess($token, $documentImage, 'delete');
    }

    public function view(TokenInterface $token, DocumentImage $documentImage)
    {
        return $this->checkCouponAccess($token, $documentImage, 'read');
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
        return DocumentImage::class;
    }

    protected function checkCouponAccess(TokenInterface $token, DocumentImage $documentImage, $couponVoter)
    {
        if ($this->siteVoter->isImpersonated($token, $documentImage)) {
            return false;
        }

        if ($coupon = $documentImage->getProviderCoupon()) {
            return $this->couponVoter->$couponVoter($token, $coupon);
        }

        /** @var Usr $user */
        $user = $token->getUser();
        /** @var Usr $cardUser */
        $cardUser = $documentImage->getUser();

        return $user && $cardUser && $user->getId() === $cardUser->getId();
    }
}
