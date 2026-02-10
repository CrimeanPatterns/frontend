<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\Event\CancelRecurringEvent;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GooglePlayCancelListener
{
    private Provider $googlePlayProvider;

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    public function __construct(Provider $googlePlayProvider, LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->googlePlayProvider = $googlePlayProvider;
        $this->logger = $logger;
        $this->em = $em;
    }

    public function cancelRecurring(CancelRecurringEvent $event): void
    {
        $user = $event->getUser();
        $userHasMobileSubscription = $user->getSubscription() == Usr::SUBSCRIPTION_MOBILE;
        $userHasActiveSubscription = $user->isAwPlus()
            && $userHasMobileSubscription
            && $user->getSubscriptionType() == Usr::SUBSCRIPTION_TYPE_AWPLUS
            && ($activeAwSubscription = $this->em->getRepository(Cart::class)->getActiveAwSubscription($user))
            && $activeAwSubscription->getPaymenttype() == Cart::PAYMENTTYPE_ANDROIDMARKET;

        if (!$userHasActiveSubscription) {
            $this->logger->info(sprintf(
                'user %d has no active Google Play subscription',
                $user->getId()
            ));

            if ($userHasMobileSubscription) {
                $this->logger->info(sprintf(
                    'user has mobile subscription, but we can not cancel it, user %d',
                    $user->getId()
                ));
                $event->stopPropagation();
            }

            return;
        }

        $platformProductId = $this->googlePlayProvider->getPlatformProductIdByCart($activeAwSubscription);

        if (is_null($platformProductId)) {
            $event->stopPropagation();
            $this->logger->error(sprintf(
                'unable to find platform product id for cart %d, user %d',
                $activeAwSubscription->getCartid(),
                $user->getId()
            ));

            return;
        }

        $productId = $this->googlePlayProvider->getProductId($platformProductId);

        if (is_null($productId)) {
            $event->stopPropagation();
            $this->logger->error(sprintf(
                'unable to find product id for platform product id %s, user %d, cart %d',
                $platformProductId,
                $user->getId(),
                $activeAwSubscription->getCartid()
            ));

            return;
        }

        if (!AbstractSubscription::isSubscription($productId)) {
            $event->stopPropagation();
            $this->logger->error(sprintf(
                'product %s is not a subscription, user %d, cart %d',
                $productId,
                $user->getId(),
                $activeAwSubscription->getCartid()
            ));

            return;
        }

        try {
            $this->googlePlayProvider->cancelGooglePlaySubscription($platformProductId, $activeAwSubscription->getPurchaseToken());
            $this->logger->info(sprintf(
                'Google Play subscription canceled for user %d, cart %d',
                $user->getId(),
                $activeAwSubscription->getCartid()
            ));
        } catch (\Google\Exception $e) {
            $this->logger->critical('failed to cancel Google Play subscription', ['exception' => $e]);
        }

        $event->stopPropagation();
    }
}
