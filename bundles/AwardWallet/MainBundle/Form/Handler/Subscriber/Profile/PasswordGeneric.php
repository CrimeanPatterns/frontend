<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\PasswordModel;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticationRequestListener;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PasswordGeneric implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;

    public function __construct(EntityManager $em, UserManager $userManager, FormHandlerHelper $formHandlerHelper)
    {
        $this->entityManager = $em;
        $this->userManager = $userManager;
        $this->formHandlerHelper = $formHandlerHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.profile_password.pre_handle' => ['preHandle'],
            'form.generic.profile_password.on_valid' => ['onValid'],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        $data = $event->getForm()->getData();
        $request = $event->getRequest();
        $request->attributes->set(MobileReauthenticationRequestListener::REQUEST_RESET_ATTRIBUTE, true);

        $this->userManager->changePassword($data->getEntity(), $data->getPass());
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
        $model
            ->setIp($request->getClientIp())
            ->setOldPasswordRequired($form->getConfig()->getOption('type_old_password', true));
    }
}
