<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\OtherSettingsModel;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OtherSettingsGeneric implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FormHandlerHelper
     */
    private $helper;

    public function __construct(
        EntityManager $em,
        FormHandlerHelper $helper
    ) {
        $this->em = $em;
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.other_settings.on_valid' => ['onValid'],
            'form.generic.other_settings.pre_handle' => ['preHandle'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->helper->isSubmitted($form, $request)) {
            $this->helper->throwIfImpersonated();
        }
    }

    public function onValid(HandlerEvent $event)
    {
        /** @var OtherSettingsModel $model */
        $model = $event->getForm()->getData();
        /** @var Usr $user */
        $user = $model->getEntity();

        if ($user->isAwPlus()) {
            // reason for inversion, new labels, see #22853
            $user->setSplashAdsDisabled(!$model->isSplashAdsDisabled());
            $user->setLinkAdsDisabled(!$model->isLinkAdsDisabled());
            $user->setListAdsDisabled(!$model->isListAdsDisabled());
            $user->setIsBlogPostAds($model->isBlogPostAds());
            $this->em->flush();
        }
    }
}
