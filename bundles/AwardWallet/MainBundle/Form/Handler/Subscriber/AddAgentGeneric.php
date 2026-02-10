<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\AddAgentModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailerExceptionInterface;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddAgentGeneric implements EventSubscriberInterface
{
    private AwTokenStorageInterface $tokenStorage;
    private ConnectionManager $connectionManager;
    private FormHandlerHelper $formHandlerHelper;
    private TranslatorInterface $translator;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        ConnectionManager $connectionManager,
        FormHandlerHelper $formHandlerHelper,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->connectionManager = $connectionManager;
        $this->formHandlerHelper = $formHandlerHelper;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.add_agent.pre_handle' => ['preHandle'],
            'form.generic.add_agent.on_valid' => ['onValid'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }

        /** @var AddAgentModel $model */
        $model = $form->getNormData();
        $model
            ->setIp($request->getClientIp())
            ->setInviter($user = $this->tokenStorage->getBusinessUser())
            ->setPlatform($this->authorizationChecker->isGranted('SITE_MOBILE_AREA') ? 'mobile' : 'web');
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var AddAgentModel $model */
        $model = $form->getNormData();

        try {
            $this->connectionManager->saveAgent(
                $this->tokenStorage->getBusinessUser(),
                $model->getEntity()
                    ->setFirstname($model->getFirstname())
                    ->setLastname($model->getLastname())
                    ->setEmail($model->getEmail())
                    ->setSendemails(!empty($model->getEmail())),
                $model->isInvite()
            );
        } catch (MailerExceptionInterface $_) {
            // error propagated by header
        }
    }

    public function createExceptionHandler(callable $formResponseGenerator): \Closure
    {
        return function (HandlerEvent $event) use ($formResponseGenerator) {
            $exception = $event->getException();

            if (!$exception instanceof UniqueConstraintViolationException) {
                return;
            }

            $form = $event->getForm();
            /** @var AddAgentModel $model */
            $model = $form->getNormData();
            $fullName = Useragent::getFullNameForNameParts(
                $model->getFirstname(),
                null,
                $model->getLastname()
            );

            $form->addError(new FormError(
                $this->translator->trans('user.already.registered', ['%name%' => $fullName], 'validators')
            ));

            $event->setResponse($formResponseGenerator());
        };
    }
}
