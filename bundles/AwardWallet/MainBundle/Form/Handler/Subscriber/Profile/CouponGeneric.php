<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\CouponModel;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\CouponApplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CouponGeneric implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    private CouponApplier $couponApplier;

    private Manager $cartManager;

    public function __construct(EntityManagerInterface $em, CouponApplier $couponApplier, Manager $cartManager)
    {
        $this->entityManager = $em;
        $this->couponApplier = $couponApplier;
        $this->cartManager = $cartManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.profile_coupon.pre_handle' => ['preHandle'],
            'form.generic.profile_coupon.on_valid' => ['onValid'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        /** @var CouponModel $model */
        $model = $form->getNormData();
        $model->setIp($request->getClientIp());
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var CouponModel $model */
        $model = $form->getData();
        /** @var Cart $cart */
        $cart = $form->getData()->getEntity();
        $coupon = $this->entityManager->getRepository(Coupon::class)->findOneBy(['code' => $model->getCoupon()]);
        $cart->setCoupon($coupon);

        if (!$coupon->getFirsttimeonly()) {
            $this->couponApplier->applyCouponToCart($coupon, $cart);
        }

        $this->cartManager->save($cart);
    }
}
