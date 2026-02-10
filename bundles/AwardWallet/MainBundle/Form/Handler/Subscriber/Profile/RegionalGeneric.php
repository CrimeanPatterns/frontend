<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\RegionalModel;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RegionalGeneric implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents()
    {
        return [
            //            'form.generic.profile_regional.pre_handle' => ['preHandle'],
            'form.generic.profile_regional.on_valid' => ['onValid', -1],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        /** @var RegionalModel $model */
        $model = $event->getForm()->getData();

        // safe credentialsChanged check on cloned account
        if (
            $model->isModelChanged()
            && $this->authorizationChecker->isGranted('USER_IMPERSONATED')
        ) {
            throw new ImpersonatedException();
        }

        /** @var Usr $user */
        $user = $model->getEntity();
        $user
            ->setRegion($model->getRegion())
            ->setLanguage($model->getLanguage());

        if ($this->authorizationChecker->isGranted('ROLE_STAFF')) {
            $user->setCurrency($model->getCurrency());
        }

        $this->entityManager->flush($user);
    }
}
