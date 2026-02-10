<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\PasswordModel;
use AwardWallet\MainBundle\Manager\UserManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersonalGeneric implements EventSubscriberInterface
{
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * ProfilePersonalFormHandler constructor.
     */
    public function __construct(
        FormHandlerHelper $formHandlerHelper,
        UserManager $userManager,
        EntityManager $entityManager
    ) {
        $this->formHandlerHelper = $formHandlerHelper;
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.desktop.personal.pre_handle' => ['preHandle'],
            'form.desktop.personal.on_valid' => ['onValid', -1],

            'form.mobile.personal.pre_handle' => ['preHandle'],
            'form.mobile.personal.on_valid' => ['onValid', -1],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }

        /** @var PasswordModel $model */
        $model = $form->getNormData();
        $model->setIp($request->getClientIp());
    }

    public function onValid(HandlerEvent $event)
    {
        $this->entityManager->flush($event->getData());
        $this->userManager->refreshToken();
    }
}
