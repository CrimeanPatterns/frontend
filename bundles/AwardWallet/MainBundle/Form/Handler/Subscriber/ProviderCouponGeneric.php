<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\ProviderCouponModel;
use AwardWallet\MainBundle\Form\Transformer\ProviderCouponFormTransformer;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\Manager\AccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProviderCouponGeneric implements EventSubscriberInterface
{
    /**
     * @var AccountManager
     */
    private $accountManager;
    /**
     * @var ProviderCouponFormTransformer
     */
    private $dataTransformer;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var AwTokenStorage
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        AuthorizationChecker $authorizationChecker,
        AwTokenStorage $tokenStorage,
        AccountManager $accountManager,
        ProviderCouponFormTransformer $dataTransformer,
        FormHandlerHelper $formHandlerHelper,
        EntityManagerInterface $entityManager
    ) {
        $this->accountManager = $accountManager;
        $this->dataTransformer = $dataTransformer;
        $this->formHandlerHelper = $formHandlerHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.providercoupon.pre_handle' => ['preHandle'],
            'form.generic.providercoupon.on_valid' => ['onValid'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var ProviderCouponModel $model */
        $model = $form->getNormData();
        /** @var Providercoupon $coupon */
        $coupon = $model->getEntity();

        if (!$coupon->getProvidercouponid()) {
            $coupon->setDescription('');
        }
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var ProviderCouponModel $model */
        $model = $form->getNormData();
        /** @var Providercoupon $coupon */
        $coupon = $model->getEntity();

        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new ImpersonatedException();
        }

        $this->formHandlerHelper->copyProperties($model, $coupon, $this->dataTransformer->getProperties());
        $connection = $this->tokenStorage->getBusinessUser()->getConnectionWith($coupon->getOwner()->getUser());

        if (null !== $connection && !$coupon->getOwner()->isBusiness()) {
            $coupon->addUserAgent($connection);
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $coupon)) {
            throw new AccessDeniedException();
        }

        if (!$coupon->getProvidercouponid()) {
            $this->entityManager->persist($coupon);
            $this->entityManager->flush();
        }
    }
}
