<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Service\AppBot\AT201Notifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AT201CartPaidListener
{
    /** @var LoggerInterface */
    private $logger;

    /** @var AT201Notifier */
    private $notifier;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        LoggerInterface $logger,
        AT201Notifier $notifier,
        EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->em = $em;
    }

    public function onCartMarkPaid(CartMarkPaidEvent $event): void
    {
        $cart = $event->getCart();
        $user = $cart->getUser();
        $currentSubscriptionType = $user->getSubscriptionType();
        $newSubscriptionType = null;
        $at201item = $cart->getAT201Item();

        if ($at201item !== null) {
            $newSubscriptionType = Usr::SUBSCRIPTION_TYPE_AT201;
        } elseif ($cart->isAwPlusSubscription()) {
            $newSubscriptionType = Usr::SUBSCRIPTION_TYPE_AWPLUS;
        }

        // Для случаев старых подписок не обнулять поле SubscriptionType
        if ($newSubscriptionType === null && $user->getSubscription() !== null) {
            return;
        }

        $siteGroup = $this->em->getRepository(Sitegroup::class)->findOneBy(['groupname' => 'AT201']);

        if ($at201item !== null && !$user->hasRole('ROLE_' . $siteGroup->getCode())) {
            $user->addGroup($siteGroup);
            $this->notifier->subscribed($cart);
        } elseif (null !== $at201item) {
            $this->notifier->recurringPayment($cart);
        }

        $user->setSubscriptionType($newSubscriptionType);
        $this->em->persist($user);
        $this->em->flush();
    }
}
