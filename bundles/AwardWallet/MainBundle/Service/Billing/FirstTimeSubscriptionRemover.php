<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\Billing\Event\CancelRecurringEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FirstTimeSubscriptionRemover
{
    private Manager $cartManager;

    private ExpirationCalculator $expirationCalculator;

    private EntityManagerInterface $em;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Manager $cartManager,
        ExpirationCalculator $expirationCalculator,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->cartManager = $cartManager;
        $this->expirationCalculator = $expirationCalculator;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onCancelRecurring(CancelRecurringEvent $event): void
    {
        $cart = $this->findFirstTimeSubscription($event->getUser());

        if ($cart) {
            $user = $cart->getUser();

            foreach ($cart->getItems() as $item) {
                if (
                    ($item instanceof AwPlus && !$item->isAwPlusSubscription())
                    || $item instanceof Discount
                ) {
                    $cart->removeItem($item);
                }
            }

            $this->cartManager->save($cart);
            $data = $this->expirationCalculator->getAccountExpiration($user->getId());

            if ($data['date'] <= time() && !$user->isFree()) {
                $user->setAccountlevel(ACCOUNT_LEVEL_FREE);
                $user->setPlusExpirationDate(null);
                $this->em->flush($user);
                $this->eventDispatcher->dispatch(new UserPlusChangedEvent($user->getId()), UserPlusChangedEvent::NAME);
            } else {
                $expirationDate = new \DateTime('@' . $data['date']);
                $user->setPlusExpirationDate($expirationDate);
                $this->em->flush($user);
            }
        }
    }

    private function findFirstTimeSubscription(Usr $user): ?Cart
    {
        $carts = $user->getCarts();

        foreach ($carts as $cart) {
            if ($cart->isFirstTimeSubscriptionPending()) {
                return $cart;
            }
        }

        return null;
    }
}
