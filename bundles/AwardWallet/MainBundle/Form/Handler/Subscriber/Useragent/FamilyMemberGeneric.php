<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Useragent;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\FamilyMemberModel;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\Manager\UseragentManager;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

class FamilyMemberGeneric implements EventSubscriberInterface
{
    /**
     * @var UseragentManager
     */
    private $agentManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var UseragentRepository
     */
    private $useragentRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;

    /**
     * UseragentDesktop constructor.
     */
    public function __construct(
        UseragentManager $agentManager,
        TranslatorInterface $translator,
        UseragentRepository $useragentRepository,
        LoggerInterface $logger,
        EntityManager $entityManager,
        FormHandlerHelper $formHandlerHelper
    ) {
        $this->agentManager = $agentManager;
        $this->translator = $translator;
        $this->useragentRepository = $useragentRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->formHandlerHelper = $formHandlerHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'form.family_member.generic.pre_handle' => ['preHandle'],
            'form.family_member.generic.on_valid' => ['onValid'],
        ];
    }

    /**
     * @throws ImpersonatedException
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var FamilyMemberModel $model */
        $model = $form->getData();
        /** @var Useragent $formAgent */
        $formAgent = $model->getEntity();

        if (
            !empty($formAgent->getEmail())
            && ($formAgent->getEmail() !== $model->getEmail())
        ) {
            $this->useragentRepository->cancelInvite($formAgent, $formAgent->getEmail());

            $this->logger->info('Cancel invite', [
                'UserID' => $formAgent->getAgentid(),
                'Email' => $formAgent->getEmail(),
                'UserAgentID' => $formAgent->getUseragentid(),
            ]);
        }

        $formAgent->setFirstname(Useragent::cleanName($model->getFirstname()));
        $formAgent->setMidname(Useragent::cleanName($model->getMidname()));
        $formAgent->setLastname(Useragent::cleanName($model->getLastname()));
        $formAgent->setAlias($model->getAlias());
        $formAgent->setEmail($model->getEmail());
        $formAgent->setSendemails($model->getSendemails());
        $formAgent->setNotes($model->getNotes());

        if ($form->offsetExists('avatarRemove') && 1 == $form->get('avatarRemove')->getData()) {
            $formAgent->setPictureext(null);
            $formAgent->setPicturever(null);
        } elseif ($form->offsetExists('avatar') && $form->get('avatar')->getData()) {
            $file = $form->get('avatar')->getData();

            if ($file instanceof UploadedFile) {
                $this->agentManager->saveUploadedAvatarFile($formAgent, $file);
            }
        }

        $this->entityManager->flush($formAgent);
    }
}
