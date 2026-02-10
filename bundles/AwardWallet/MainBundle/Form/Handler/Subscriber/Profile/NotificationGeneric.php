<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserNotificationPreferencesUpdatedEvent;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Form\Transformer\Profile\NotificationTransformer;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class NotificationGeneric implements EventSubscriberInterface
{
    private EntityManager $em;

    private LoggerInterface $logger;

    private FormHandlerHelper $helper;

    private NotificationTransformer $transformer;

    private EventDispatcherInterface $eventDispatcher;

    private $beforeModel;

    public function __construct(
        EntityManager $em,
        LoggerInterface $logger,
        FormHandlerHelper $helper,
        NotificationTransformer $transformer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->transformer = $transformer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.notification.on_valid' => ['onValid'],
            'form.generic.notification.pre_handle' => ['preHandle'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var Usr $user */
        $user = $form->getData();
        $request = $event->getRequest();

        //        if ($this->helper->isSubmitted($form, $request)) {
        //            $this->helper->throwIfImpersonated();
        //        }
        $this->beforeModel = $this->transformer->transform($user);
    }

    public function onValid(HandlerEvent $event)
    {
        /** @var NotificationModel $model */
        $model = $event->getForm()->getData();
        /** @var Usr $user */
        $user = $model->getEntity();
        $request = $event->getRequest();

        $this->transformer->transformToEntity($user, $model, $request);
        $this->em->flush();
        $this->logChanges($this->beforeModel, $model);
    }

    protected function logChanges(NotificationModel $before, NotificationModel $after)
    {
        $beforeArr = $this->toArray($before);
        $afterArr = $this->toArray($after);
        $unchecked = $checked = $changes = [];

        foreach ($beforeArr as $beforeName => $beforeValue) {
            if (!isset($afterArr[$beforeName])) {
                continue;
            }
            $afterValue = $afterArr[$beforeName];

            if ($beforeValue !== $afterValue) {
                $data = [
                    'name' => $beforeName,
                    'old' => $beforeValue,
                    'new' => $afterValue,
                ];

                if (is_bool($beforeValue) && is_bool($afterValue)) {
                    if ($afterValue) {
                        $checked[] = $data;
                    } else {
                        $unchecked[] = $data;
                    }
                } else {
                    $changes[] = $data;
                }
            }
        }

        /** @var Usr $user */
        $user = $after->getEntity();

        if (sizeof($unchecked) > 0) {
            $this->logger->info('uncheck settings', [
                'UserID' => $user->getUserid(),
                '_aw_notifications_module' => 'unchecked',
                'settings' => $unchecked,
            ]);
        }

        if (sizeof($checked) > 0) {
            $this->logger->info('check settings', [
                'UserID' => $user->getUserid(),
                '_aw_notifications_module' => 'checked',
                'settings' => $checked,
            ]);
        }

        if (sizeof($changes) > 0) {
            $this->logger->info('change settings', [
                'UserID' => $user->getUserid(),
                '_aw_notifications_module' => 'changes',
                'settings' => $changes,
            ]);
        }

        $logged = false;

        if (!$before->isEmailDisableAll() && $after->isEmailDisableAll()) {
            $this->logger->info('mail_unsubscribe', ['userid' => $user->getUserid(), 'source' => 'profile_disable_all']);
            $logged = true;
        }

        if (!$logged && $before->isEmailOffers() && !$after->isEmailOffers()) {
            $this->logger->info('mail_unsubscribe', ['userid' => $user->getUserid(), 'source' => 'profile_disable_offers']);
        }

        if (count($checked) > 0 || count($unchecked) > 0 || count($changes) > 0) {
            $this->eventDispatcher->dispatch(new UserNotificationPreferencesUpdatedEvent($user));
        }
    }

    private function toArray(NotificationModel $model)
    {
        $reflection = new \ReflectionClass($model);
        $accessor = PropertyAccess::createPropertyAccessor();
        $props = [];

        foreach ($reflection->getProperties() as $prop) {
            $props[$prop->getName()] = $accessor->getValue($model, $prop->getName());
        }

        return $props;
    }
}
