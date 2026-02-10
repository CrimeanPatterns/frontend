<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\BusinessModel;
use AwardWallet\MainBundle\Manager\UserManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BusinessGeneric implements EventSubscriberInterface
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
        ];
    }

    /**
     * @throws \AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException
     */
    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();

        /** @var BusinessModel $model */
        $model = $form->getData();
        /** @var Usr $formUser */
        $formUser = $model->getEntity();

        $formUser->setLogin($model->getLogin());
        $formUser->setCompany($model->getCompany());

        if ($form->offsetExists('AvatarDelete') && $form->get('AvatarDelete')->getData() == 1) {
            $formUser->setPictureext(null);
            $formUser->setPicturever(null);
        } elseif ($form->offsetExists('Avatar') && $form->get('Avatar')->getData()) {
            $file = $form->get('Avatar')->getData();

            if ($file instanceof UploadedFile) {
                $this->userManager->saveUploadedAvatarFile($formUser, $file);
            }
        }

        $this->entityManager->flush($event->getData());
        $event->setData($formUser);
    }
}
