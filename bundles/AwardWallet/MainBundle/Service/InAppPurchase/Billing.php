<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\AwPlus as ConsumableAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit1;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit10;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit3;
use AwardWallet\MainBundle\Service\InAppPurchase\Consumable\Credit5;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlus as SubscriptionAwPlus;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusDiscounted;
use AwardWallet\MainBundle\Service\InAppPurchase\Subscription\AwPlusWeek;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;

class Billing implements TranslationContainerInterface
{
    private EntityManagerInterface $em;

    private Manager $cartManager;

    private LoggerInterface $logger;

    private Mailer $mailer;

    private array $paymentTypeNames;

    private RecurringManager $recurringManager;

    public function __construct(
        EntityManagerInterface $em,
        Manager $cartManager,
        LoggerInterface $paymentLogger,
        Mailer $mailer,
        RecurringManager $recurringManager
    ) {
        global $arPaymentTypeName;
        $this->em = $em;
        $this->cartManager = $cartManager;
        $this->logger = $paymentLogger;
        $this->mailer = $mailer;
        $this->paymentTypeNames = $arPaymentTypeName;
        $this->recurringManager = $recurringManager;
    }

    /**
     * @return bool true if upgraded else false
     */
    public function processing(PurchaseInterface $purchase, bool $updateReceipt = false): bool
    {
        $user = $purchase->getUser();

        if ($user->isBusiness()) {
            throw new \InvalidArgumentException('Business account not allowed');
        }

        $this->log(sprintf('processing transaction %s', $purchase), $user);
        $cart = $this->findPaidCart($purchase);

        if ($cart) {
            $this->log(sprintf('transaction already exists, cartId: %d', $cart->getCartid()), $user);
        } else {
            $this->log('transaction not found in orders', $user);
        }

        // upgrade
        if (
            !$cart
            && !$purchase->isCanceled()
        ) {
            $this->tryUpgrade($purchase);

            return true;
        // refund
        } elseif ($cart && $purchase->isCanceled()) {
            $this->cancel($cart, $purchase);
        } elseif ($purchase->isAppStorePurchase()) {
            if ($updateReceipt) {
                $this->updateReceipts($purchase, $cart);
            }

            if ($cart) {
                $this->migrateIosCart($cart, $purchase);
            }
        }

        return false;
    }

    public function tryUpgrade(PurchaseInterface $purchase, bool $sendEmails = true): Cart
    {
        if ($purchase->isCanceled()) {
            throw new \InvalidArgumentException('The purchase has been canceled');
        }

        $user = $purchase->getUser();
        $this->log(sprintf('try upgrade user, %s', strval($purchase)), $user);

        $this->cartManager->setUser($user);
        $cart = $this->cartManager->createNewCart();

        $this->addCartItems($cart, $purchase);
        $cart->setPaymenttype($purchase->getPaymentType());
        $cart->setBillingtransactionid($purchase->getTransactionId());
        $cart->setPurchaseToken($purchase->getPurchaseToken());

        if ($purchase->isAppStorePurchase()) {
            $cart->setAppleTransactionID($purchase->getSecondaryTransactionId());
        }

        if ($purchase instanceof AbstractSubscription && $purchase->isRecurring()) {
            $cart->setSource(Cart::SOURCE_RECURRING);
        } else {
            $cart->setSource(Cart::SOURCE_USER);
        }

        $this->cartManager->markAsPayed($cart, null, $purchase->getPurchaseDate(), !$sendEmails);
        $this->updateReceipts($purchase, $cart);

        if ($user->isAwPlus()) {
            // cart processed
            $this->log(sprintf('upgrade complete, cartId: %d', $cart->getCartid()), $user, [
                'PaymentType' => $this->paymentTypeNames[$purchase->getPaymentType()],
                'CartID' => $cart->getCartid(),
            ]);

            $message = $this->mailer->getMessage(
                'mobile_billing',
                $this->mailer->getEmail('support'),
                'In-App Purchase complete, ' . $this->paymentTypeNames[$purchase->getPaymentType()]
            );
            $message->setBody("User: {$user->getId()}, {$user->getFullName()}, CartID: {$cart->getCartid()}\n\n" . strval($purchase));
            $this->mailer->send($message, [Mailer::OPTION_FIX_BODY => false]);
        } else {
            $this->log(sprintf('user not upgraded, cartId: %d', $cart->getCartid()), $user, [
                'PaymentType' => $this->paymentTypeNames[$purchase->getPaymentType()],
                'CartID' => $cart->getCartid(),
            ]);
        }

        return $cart;
    }

    public function cancel(Cart $cart, PurchaseInterface $purchase): void
    {
        if (!$purchase->isCanceled()) {
            throw new \InvalidArgumentException('The purchase has not been canceled');
        }

        $user = $cart->getUser();
        $this->log(sprintf('In-App Purchase cancel, cartId: %d, %s', $cart->getCartid(), strval($purchase)), $user, [
            'PaymentType' => $this->paymentTypeNames[$purchase->getPaymentType()],
            'CartID' => $cart->getCartid(),
        ]);
        $message = $this->mailer->getMessage(
            'mobile_billing',
            $this->mailer->getEmail('support'),
            'In-App Purchase cancel, ' . $this->paymentTypeNames[$purchase->getPaymentType()]
        );
        $message->setBody("User: {$user->getUserid()}, {$user->getFullName()}, CartID: {$cart->getCartid()}\n\n" . strval($purchase));
        $this->mailer->send($message, [Mailer::OPTION_FIX_BODY => false]);
        $this->cartManager->refund($cart);

        if ($user->getSubscription() === Usr::SUBSCRIPTION_MOBILE) {
            $this->logger->info("clearing user subscription because refund was issued");
            $user->clearSubscription();
            $this->em->flush();
        }

        $this->updateReceipts($purchase);
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('pay-subscription.product.description', 'mobile'))
                ->setDesc('AwardWallet Plus gives you the following additional features: %list_on% %item_on%Display expiration date of your rewards%item_off%%item_on%Display additional reward account properties%item_off%%item_on%Parallel account updates (5X faster)%item_off% %list_off%'),

            (new Message('pay-subscription-discount.product.description', 'mobile'))
                ->setDesc('AwardWallet Plus gives you the following additional features: %list_on% %item_on%Display expiration date of your rewards%item_off%%item_on%Display additional reward account properties%item_off%%item_on%Parallel account updates (5X faster)%item_off% %list_off%'),

            (new Message('pay-subscription.product.description.long.v2', 'mobile'))
                ->setDesc('
                    %p_on%AwardWallet Plus is a subscription-based service, the subscription length is 1 year and when you subscribe it will be set to auto-renew every year thereafter.
                    The cost of the subscription is %price% per year. AwardWallet Plus gives you the following additional features:%p_off%
                    %list_on% 
                        %item_on%Displaying additional reward account properties%item_off%
                        %item_on%Showing all of your loyalty account expirations where expiration is known%item_off%
                        %item_on%When you update all of your accounts they are updated in parallel, up to 5x faster%item_off%
                        %item_on%More comprehensive monitoring and tracking of your flights%item_off%
                    %list_off%
                    %p_on%AwardWallet Plus subscriptions purchased from the app will be charged to your %store_name% account and will automatically renew within 24 hours prior to the end of the current subscription period, unless auto-renewal is disabled beforehand.
                     To manage your subscriptions or to disable auto-renewal, after purchase, go to your %store_name% account settings. No refunds will be issued after the annual subscription fee has been charged. 
                     Any unused portion of a free trial period will be forfeited once a subscription is purchased.
                     %p_off%
                     %p_on%For more details please feel free to review our %link_1%Terms of Use%link_off% and our %link_2%Privacy Policy%link_off%.%p_off%
                 '),
        ];
    }

    private function addCartItems(Cart $cart, PurchaseInterface $purchase): void
    {
        switch (true) {
            case $purchase instanceof ConsumableAwPlus:
                $item = new AwPlus();
                $item->setPrice(4.99);
                $cart->addItem($item);

                break;

            case $purchase instanceof AwPlusDiscounted:
                $this->cartManager->addAwSubscriptionItem($cart, $purchase->getPurchaseDate(), false);

                break;

            case $purchase instanceof AwPlusWeek:
                $item = new AwPlusWeekSubscription();
                $cart->addItem($item);

                break;

            case $purchase instanceof SubscriptionAwPlus:
                $this->cartManager->addAwSubscriptionItem($cart, $purchase->getPurchaseDate(), false);

                break;

            case $purchase instanceof Credit1:
                $this->cartManager->addBalanceWatchCredit($cart, 1);

                break;

            case $purchase instanceof Credit3:
                $this->cartManager->addBalanceWatchCredit($cart, 3);

                break;

            case $purchase instanceof Credit5:
                $this->cartManager->addBalanceWatchCredit($cart, 5);

                break;

            case $purchase instanceof Credit10:
                $this->cartManager->addBalanceWatchCredit($cart, 10);

                break;
        }
    }

    private function updateReceipts(PurchaseInterface $purchase, ?Cart $cart = null): void
    {
        $user = $purchase->getUser();
        $userToken = $purchase->getUserToken();
        $purchaseToken = $purchase->getPurchaseToken();
        $this->log('try update receipts', $user, [
            'userToken' => $userToken,
            'purchaseToken' => $purchaseToken,
        ]);

        if (
            !empty($userToken)
            && $purchase->isAppStorePurchase()
            && $purchase instanceof AbstractSubscription
            && $userToken !== $user->getIosReceipt()
        ) {
            $this->log('update ios app receipt', $user, [
                'receipt' => $userToken,
            ]);
            $user->setIosReceipt($userToken);

            // TODO: Somewhere entity detached, need fix
            if ($this->em->getUnitOfWork()->getEntityState($user) === UnitOfWork::STATE_MANAGED) {
                $this->em->flush($user);
                $this->log('ios app receipt updated via entity manager', $user);
            } else {
                $this->em->getConnection()->executeUpdate('UPDATE Usr SET IosReceipt = ? WHERE UserID = ?', [
                    $userToken, $user->getUserid(),
                ]);
                $this->log('ios app receipt updated via update query', $user);
            }
        }

        if ($cart && !empty($purchaseToken) && $purchaseToken !== $cart->getPurchaseToken()) {
            $this->log('update purchase receipt', $user, [
                'receipt' => $purchaseToken,
                'cartId' => $cart->getCartid(),
            ]);
            $cart->setPurchaseToken($purchaseToken);
            $this->cartManager->save($cart);
        }
    }

    private function migrateIosCart(Cart $cart, PurchaseInterface $purchase): void
    {
        if (!empty($cart->getAppleTransactionID())) {
            return;
        }

        $this->log(sprintf('migrate ios cart, cartId: %s', $cart->getCartid()), $cart->getUser(), [
            'cartId' => $cart->getCartid(),
            'cartTransactionId' => $cart->getBillingtransactionid(),
            'purchaseTransactionId' => $purchase->getTransactionId(),
            'purchaseAppleTransactionId' => $purchase->getSecondaryTransactionId(),
        ]);

        $this->updateIosTransactionId($cart, $purchase);
        $cart->setAppleTransactionID($purchase->getSecondaryTransactionId());
        $this->cartManager->save($cart);
    }

    private function findPaidCart(PurchaseInterface $purchase): ?Cart
    {
        $cartRep = $this->em->getRepository(Cart::class);
        $e = Criteria::expr();
        $criteria = Criteria::create()
            ->where(
                $e->andX(
                    $e->neq('paydate', null),
                    $e->eq('paymenttype', $purchase->getPaymentType())
                )
            );

        if ($purchase->isAppStorePurchase()) {
            $criteria->andWhere(
                $e->orX(
                    $e->andX(
                        $e->neq('appleTransactionID', null),
                        $e->eq('appleTransactionID', $purchase->getSecondaryTransactionId())
                    ),
                    $e->andX(
                        $e->neq('billingtransactionid', null),
                        $e->eq('billingtransactionid', $purchase->getTransactionId())
                    )
                )
            );
        } else {
            $criteria->andWhere(
                $e->andX(
                    $e->neq('billingtransactionid', null),
                    $e->eq('billingtransactionid', $purchase->getTransactionId())
                )
            );
        }

        $carts = $cartRep->matching($criteria);

        $this->log('found by payment type and transaction id: ' . $carts->count(), $purchase->getUser());

        if ($carts->count() > 0) {
            return $carts->first();
        }

        if (!($purchase instanceof CartMatcherInterface)) {
            return null;
        }

        $carts = $purchase->getUser()->getCarts()->filter(function ($cart) use ($purchase) {
            /** @var Cart $cart */
            return $purchase->match($cart, $this->logger);
        });

        $this->log('found via smart matching: ' . $carts->count(), $purchase->getUser());

        if ($carts->count() > 0) {
            /** @var Cart $cart */
            $cart = $carts->first();

            if (
                !empty($purchase->getTransactionId())
                && $cart->getBillingtransactionid() !== $purchase->getTransactionId()
                && $purchase->isAppStorePurchase()
            ) {
                $this->updateIosTransactionId($cart, $purchase);
            }

            return $cart;
        }

        return null;
    }

    private function updateIosTransactionId(Cart $cart, PurchaseInterface $purchase)
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('paymenttype', $purchase->getPaymentType()),
                    Criteria::expr()->eq('billingtransactionid', $purchase->getTransactionId())
                )
            );

        $cartRep = $this->em->getRepository(Cart::class);
        $carts = $cartRep->matching($criteria);

        if ($carts->count() === 0) {
            $this->log(sprintf('ios, update transaction id, cartId: %s', $cart->getCartid()), $cart->getUser(), [
                'from' => $cart->getBillingtransactionid(),
                'to' => $purchase->getTransactionId(),
            ]);
            $cart->setBillingtransactionid($purchase->getTransactionId());
            $this->cartManager->save($cart);
        }
    }

    private function log(string $message, ?Usr $user = null, array $extraData = []): void
    {
        $this->logger->info($message, array_merge(
            LoggerContext::get($user),
            $extraData
        ));
    }
}
