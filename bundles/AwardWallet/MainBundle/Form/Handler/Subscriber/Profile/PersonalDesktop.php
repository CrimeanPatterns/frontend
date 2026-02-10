<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\PersonalModel;
use AwardWallet\MainBundle\Manager\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonalDesktop implements EventSubscriberInterface
{
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(UserManager $userManager, TranslatorInterface $translator)
    {
        $this->userManager = $userManager;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.desktop.personal.on_valid' => ['onValid', 0],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        /** @var PersonalModel $model */
        $model = $form->getData();
        /** @var Usr $formUser */
        $formUser = $model->getEntity();

        $formUser->setLogin($model->getLogin());
        $formUser->setFirstname($model->getFirstname());
        $formUser->setMidname($model->getMidname());
        $formUser->setLastname($model->getLastname());

        if ($form->offsetExists('AvatarDelete') && $form->get('AvatarDelete')->getData() == 1) {
            $formUser->setPictureext(null);
            $formUser->setPicturever(null);
        } elseif ($form->offsetExists('Avatar') && $form->get('Avatar')->getData()) {
            $file = $form->get('Avatar')->getData();

            if ($file instanceof UploadedFile) {
                $this->userManager->saveUploadedAvatarFile($formUser, $file);
            }
        }

        $event->setData($formUser);
    }
}
