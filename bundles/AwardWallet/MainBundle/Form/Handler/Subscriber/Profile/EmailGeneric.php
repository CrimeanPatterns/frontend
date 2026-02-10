<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\EmailModel;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\EmailChanged;
use AwardWallet\MainBundle\Manager\UserManager;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticationRequestListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailGeneric implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    private UserManager $userManager;

    private FormHandlerHelper $formHandlerHelper;

    private Mailer $mailer;

    public function __construct(
        EntityManagerInterface $em,
        UserManager $userManager,
        FormHandlerHelper $formHandlerHelper,
        Mailer $mailer
    ) {
        $this->entityManager = $em;
        $this->userManager = $userManager;
        $this->formHandlerHelper = $formHandlerHelper;
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.profile_email.pre_handle' => ['preHandle'],
            'form.generic.profile_email.on_valid' => ['onValid'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }

        /** @var Usr $user */
        $user = $form->getData();
        /** @var EmailModel $model */
        $model = $form->getNormData();
        $model
            ->setIp($request->getClientIp())
            ->setReauthRequired($form->getConfig()->getOption('reauthRequired', true));
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();
        /** @var EmailModel $model */
        $model = $form->getData();
        /** @var Usr $user */
        $user = $form->getData()->getEntity();
        $request->attributes->set(MobileReauthenticationRequestListener::REQUEST_RESET_ATTRIBUTE, true);

        if ($user->getEmail() !== $model->getEmail()) {
            $template = new EmailChanged($user);
            $template->emailFrom = $user->getEmail();
            $template->emailTo = $model->getEmail();

            $user->setEmailverified(EMAIL_UNVERIFIED);
            $user->setEmail($model->getEmail());
            $user->setResetpasswordcode(null);
            $user->setResetpassworddate(null);

            $this->entityManager->flush($user);
            $this->userManager->refreshToken();

            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send($message);
        }
    }
}
