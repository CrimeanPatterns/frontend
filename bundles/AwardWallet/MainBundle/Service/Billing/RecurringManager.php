<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\Event\CancelRecurringEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RecurringManager
{
    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function cancelRecurringPayment(Usr $user, bool $onlyPayPalCall = false, bool $suppressErrors = false)
    {
        $profileId = $user->getPaypalrecurringprofileid();

        if (empty($profileId) && empty($user->getSubscription())) {
            return false;
        }

        $context = [
            'UserID' => $user->getId(),
            'ProfileID' => $profileId,
            'Subscription' => $user->getSubscription(),
            'SubscriptionType' => $user->getSubscriptionType(),
            'ActiveSubscriptionCartID' => $user->getActiveSubscriptionCart() ? $user->getActiveSubscriptionCart()->getCartid() : null,
        ];
        $this->logger->info('cancelling recurring payment', $context);
        $event = new CancelRecurringEvent($user);

        try {
            $this->eventDispatcher->dispatch($event, CancelRecurringEvent::NAME);
        } catch (\Exception $e) {
            $this->logger->critical(
                'failed to cancel recurring payment profile, CANCEL THROUGH PAYPAL: ' . $e->getMessage(),
                array_merge($context, ['exception' => $e])
            );

            if (!$suppressErrors) {
                throw $e;
            }
        }

        if (!$event->isPropagationStopped()) {
            $this->logger->critical(
                sprintf('Unknown subscription type: %s, I don`t know how to cancel', $user->getSubscription() ?? 'null'),
                $context
            );
            // throw new \Exception('Unknown subscription type: ' . $user->getSubscription() . ', I don`t know how to cancel');
        }

        if ($onlyPayPalCall === false) {
            $user->clearSubscription();
            $this->em->persist($user);
            $this->em->flush();
        }

        return true;
    }
}
