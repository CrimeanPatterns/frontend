<?php

namespace AwardWallet\MainBundle\Service\Billing;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\Cart\AwPlusUpgradableInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Service\BusinessTransaction\AwPlusProcessor;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleProvider;
use AwardWallet\MainBundle\Service\InAppPurchase\Billing;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\LoggerContext;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PlusManager
{
    public const LIMIT_UPGRADE_SKIPPED = 4;
    public const SESSION_KEY_SHOW_UPGRADE_POPUP = 'ShowUpgradePopup';
    // how many days we wait for subscription next payment, before downgrading user
    public const SUBSCRIPTION_GRACE_PERIOD = 16;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Manager
     */
    private $cartManager;
    /**
     * @var PaypalSoapApi
     */
    private $paypalSoap;
    /**
     * @var PaypalRestApi
     */
    private $paypalRest;

    /**
     * @var CartRepository
     */
    private $cartRep;
    /**
     * @var ProviderRegistry
     */
    private $registry;
    /**
     * @var Billing
     */
    private $billing;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var ExpirationCalculator
     */
    private $expirationCalculator;
    private AwPlusProcessor $awPlusProcessor;

    public function __construct(
        Logger $paymentLogger, EntityManagerInterface $em, Manager $cartManager,
        AwPlusProcessor $awPlusProcessor,
        ProviderRegistry $registry, Billing $billing,
        EventDispatcherInterface $eventDispatcher,
        ExpirationCalculator $expirationCalculator
    ) {
        $this->logger = $paymentLogger;
        $this->em = $em;
        $this->cartManager = $cartManager;
        $this->cartRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class);
        $this->registry = $registry;
        $this->billing = $billing;
        $this->eventDispatcher = $eventDispatcher;
        $this->expirationCalculator = $expirationCalculator;
        $this->awPlusProcessor = $awPlusProcessor;
    }

    public function correctExpirationDate(Usr $user, $newDate, $reason)
    {
        if (is_empty($user->getPlusExpirationDate()) && is_empty($newDate)) {
            return false;
        }

        $oldDateText = $this->formatDateTime($user->getPlusExpirationDate());
        $newDateText = date('Y-m-d', $newDate);

        if (!is_empty($user->getPlusExpirationDate()) && !is_empty($newDate) && $oldDateText == $newDateText) {
            return false;
        }

        $context = ["UserID" => $user->getId(), "Old" => $oldDateText, "Reason" => $reason];

        if (empty($newDate)) {
            $user->setPlusExpirationDate(null);
        } else {
            $user->setPlusExpirationDate(new \DateTime('@' . $newDate));
        }

        $context["New"] = $newDateText;
        $this->logger->warning("correcting expiration date", $context);
        $this->em->flush();

        return true;
    }

    public function recalcExpirationDateAndAccountLevel(Usr $user): bool
    {
        $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());

        if (!$this->correctExpirationDate($user, $expiration['date'], "expiration recalculated")) {
            return false;
        }

        if (!$user->isFree()) {
            $this->checkExpirationAndDowngrade($user);

            return true;
        }

        $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());
        $hasPlusAfterCorrection = $expiration['date'] > time();

        if (!$hasPlusAfterCorrection) {
            return true;
        }

        $this->logger->info("user has plus after correction, will upgrade", ["UserID" => $user->getId()]);

        throw new \Exception('has plus after correction');

        return true;
    }

    /**
     * @param string|null $period - one of SubscriptionPeriod::DURATION_ constants
     */
    public function copySubscriptionCartContents(Cart $oldCart, Manager $cartManager, ?float $total = null, ?string $period = null)
    {
        $user = $oldCart->getUser();

        if (
            // we have active subscription and ipn callback values matches this subscription parameters
            // and matches default price/period for this subscription price
            $user->getSubscriptionPrice() !== null
            && $user->getSubscriptionType() !== null
            && $total !== null && abs($total - $user->getSubscriptionPrice()) < 0.005
            && $period !== null && (SubscriptionPeriod::DAYS_TO_DURATION[$user->getSubscriptionPeriod()] ?? null) === $period
            && abs(SubscriptionPrice::getPrice($user->getSubscriptionType(), $period) - $total) < 0.005
        ) {
            $this->logger->info("we have subscription {$user->getSubscriptionPrice()} for {$user->getSubscriptionPeriod()} for this user, will create new cart with standard subscription");
            $cartManager->addSubscription($user->getSubscriptionType(), SubscriptionPeriod::DAYS_TO_DURATION[$user->getSubscriptionPeriod()]);

            return;
        }

        $newCart = $cartManager->getCart();

        //        $oldDiscount = $oldCart->getDiscount();
        //        if ($oldDiscount === null || !in_array($oldDiscount->getId(), [Discount::ID_PROMO_500K, Discount::ID_COUPON])) {
        //            $cartManager->addAwSubscriptionItem($newCart, new \DateTime(), $newCart->getUser()->canUpgradeWithDiscount(), false);
        //            return;
        //        }

        foreach ($oldCart->getItems() as $item) {
            if (!($item instanceof AwPlusUpgradableInterface || $item instanceof Discount)) {
                continue;
            }
            $newItem = clone $item;
            $newItem->setScheduledDate(null);
            $newCart->addItem($newItem);
        }

        // correct old carts with zero price and apply discount logic if applicable
        $discount = $newCart->getDiscount();

        if (
            $newCart->getPlusItem() instanceof AwPlusSubscription
            && $discount === null
        ) {
            $newCart->clear();
            $cartManager->addAwSubscriptionItem($newCart, new \DateTime(), false);
        }

        $oldGift = $oldCart->getItemsByType([AwPlusGift::TYPE])->first();

        if ($oldGift) {
            $this->logger->info("copying gift item", ["GiftID" => $oldGift->getId(), "UserID" => $oldCart->getUser()->getId(), "CartID" => $oldCart->getCartid()]);
            $newGift = clone $oldGift;
            $newCart->addItem($newGift);
        }

        // we will correct total only for old carts, not for promo500k subscriptions
        if ($total === null && !$oldCart->isAwPlusSubscription()) {
            $total = $oldCart->getTotalPrice();
        }

        $this->correctCartContentsByTotal($newCart, $total);
    }

    public function repeatCartSubscription(Cart $cart, $transactionId, $total, $source, string $period)
    {
        $this->logger->pushProcessor(function (array $record) use ($cart, $transactionId, $total) {
            $record['context']['CartID'] = $cart->getCartid();
            $record['context']['TransactionID'] = $transactionId;
            $record['context']['UserID'] = $cart->getUser()->getId();
            $record['context']['ReceivedTotal'] = $total;

            return $record;
        });

        try {
            $this->logger->info("repeating subscription from cart");
            $this->cartManager->setUser($cart->getUser());
            $newCart = $this->cartManager->createNewCart();
            $newCart->setUser($cart->getUser());
            $newCart->setPaymenttype($cart->getPaymenttype());
            $newCart->setCreditcardnumber($cart->getCreditcardnumber());
            $newCart->setCreditcardtype($cart->getCreditcardtype());
            $newCart->setBillingtransactionid($transactionId);
            $newCart->setSource($source);

            $this->copySubscriptionCartContents($cart, $this->cartManager, $total, $period);

            $this->cartManager->markAsPayed($newCart);
        } finally {
            $this->logger->popProcessor();
        }
    }

    public function checkExpirationAndDowngrade(Usr $user, bool $clearSubscription = true, bool $clearPlusExpirationDate = true): bool
    {
        if (!$this->userNeedsDowngrade($user)) {
            return false;
        }

        // last chance to fetch missed transactions
        $this->upgradeByBusiness($user);
        $this->upgradeByMobileIAP($user);

        if (!$this->userNeedsDowngrade($user)) {
            return false;
        }

        if (!empty($user->getPaypalrecurringprofileid())) {
            if ($user->getFailedRecurringPayments() == 0) {
                $this->logger->warning("Downgrading user with recurring payment and no failures - review required", ["UserID" => $user->getId()]);
            }

            $this->logger->warning("downgrading user with recurring payment", ["UserID" => $user->getId()]);
        }

        $this->downgrade($user, $clearSubscription, $clearPlusExpirationDate);

        return true;
    }

    public function getBusinessConnections(Usr $user)
    {
        $q = $this->em->createQuery("
            SELECT
                au
            FROM
                AwardWallet\MainBundle\Entity\Useragent ua
                JOIN ua.clientid business
			    JOIN AwardWallet\MainBundle\Entity\Useragent au with ua.agentid = au.clientid and au.agentid = ua.clientid
            WHERE
                business.accountlevel = " . ACCOUNT_LEVEL_BUSINESS . "
                AND ua.agentid = :user
                AND ua.isapproved = 1
                and au.isapproved = 1
                AND ua.keepUpgraded = 1
        ");

        return $q->execute(['user' => $user->getId()]);
    }

    public function incrementUpgradeSkipCount(Usr $user): ?bool
    {
        if ($user->getUpgradeSkippedCount() > self::LIMIT_UPGRADE_SKIPPED) {
            return null;
        }

        $user->setUpgradeSkippedCount(1 + $user->getUpgradeSkippedCount());
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

    public function calculateAt201ExpirationDate(Usr $user): void
    {
        $data = $this->expirationCalculator->getAccountExpiration($user->getId(), AT201SubscriptionInterface::class);
        $user->setAt201ExpirationDate(
            is_null($data['lastItemType']) || $data['date'] <= time()
                ? null
                : new \DateTime('@' . $data['date'])
        );
        $this->em->flush();
    }

    private function userNeedsDowngrade(Usr $user): bool
    {
        if (empty($expireData)) {
            $expireData = $this->expirationCalculator->getAccountExpiration($user->getId());
        }

        if ($user->getSubscription() !== null && $expireData['lastItemType'] !== null) {
            $this->logger->info("user has active subscription " . Usr::SUBSCRIPTION_NAMES[$user->getSubscription()] . ", give it a chance to fire", ["UserID" => $user->getId()]);
            // paypal will attempt to charge user 3 times with 5 days inteval, so we wait 15 days
            $expireData['date'] = strtotime("+" . self::SUBSCRIPTION_GRACE_PERIOD . " day", $expireData['date']);
        }

        return $expireData['date'] <= time() && !$user->isFree();
    }

    private function upgradeByMobileIAP(Usr $user)
    {
        $this->logger->debug(__METHOD__, ["UserID" => $user->getId()]);

        $beforeExpirationDate = $this->getExpirationDate($user);

        // check googleplay
        /** @var GooglePlay $googleProvider */
        $googleProvider = $this->registry->getProvider('android-v3');
        $googleProvider->scanSubscriptions($user, $this->billing);

        if ($this->getExpirationDate($user) > $beforeExpirationDate) {
            $this->logger->info('was upgraded via google play', LoggerContext::get($user));

            return true;
        }

        // check appstore
        /** @var AppleProvider $appleProvider */
        $appleProvider = $this->registry->getProvider('ios');
        $appleProvider->scanSubscriptions($user, $this->billing);

        if ($this->getExpirationDate($user) > $beforeExpirationDate) {
            $this->logger->info("was upgraded via apple appstore", LoggerContext::get($user));

            return true;
        }

        return false;
    }

    private function correctCartContentsByTotal(Cart $newCart, ?float $total = null): void
    {
        if ($total === null) {
            $total = $this->getExpectedTotal($newCart);
        }
        $actualTotal = $newCart->getTotalPrice();

        if (abs($actualTotal - $total) >= 0.01) {
            if ($newCart->getDiscount()) {
                $this->logger->warning("unexpected price, trying to remove discount", ["ActualTotal" => $actualTotal]);
                $newCart->removeItem($newCart->getDiscount());
                $this->em->flush();
                $actualTotal = $newCart->getTotalPrice();
            }

            if (round($actualTotal - $total) == OneCard::PRICE && $newCart->hasItemsByType([OneCard::TYPE])) {
                $this->logger->warning("unexpected price, trying to remove onecard", ["ActualTotal" => $actualTotal]);
                $newCart->removeItemsByType([OneCard::TYPE]);
                $this->em->flush();
                $actualTotal = $newCart->getTotalPrice();
            }

            if (abs($actualTotal - $total) >= 0.01) {
                $this->logger->critical("unexpected price for ipn, correcting", ["ActualTotal" => $actualTotal, "Total" => $total, "OldPlusValue" => $newCart->getPlusItem()->getPrice()]);
                $newCart->getPlusItem()->setPrice($total);
                $newCart->getPlusItem()->setDiscount(0);
                $this->em->flush();
            }
        }
    }

    private function getExpectedTotal(Cart $cart)
    {
        $result = $cart->getTotalPrice();

        $discount = $cart->getDiscount();

        if (!empty($discount) && $discount->getId() == Discount::ID_PROMO_500K) {
            $wasPaid = $this->em->getConnection()->executeQuery("
                select 
                    1 
                from 
                    CartItem ci
                    join Cart c on ci.CartID = c.CartID
                where 
                    c.PayDate is not null
                    and ci.TypeID = " . Discount::TYPE . "
                    and c.UserID = :userId
                    and ci.ID = " . Discount::ID_PROMO_500K . "
                    and ci.ScheduledDate is null
                ",
                ["userId" => $cart->getUser()->getId()]
            )->fetchColumn();

            $this->logger->info("subscription was paid at least once?", ["wasPaid" => $wasPaid]);

            if ($wasPaid) {
                $result += abs($discount->getPrice());
                $this->logger->info("increasing total because subscription was paid", ["discount" => $discount->getPrice()]);
            }
        }

        $this->logger->info("calculated expected total", ["ExpectedTotal" => $result]);

        return $result;
    }

    private function downgrade(Usr $user, bool $clearSubscription, bool $clearPlusExpirationDate)
    {
        $this->logger->debug(__METHOD__, ["UserID" => $user->getId()]);
        $plusChanged = false;

        if (!$user->isFree()) {
            $user->setAccountlevel(ACCOUNT_LEVEL_FREE);
            $this->logger->info("user was downgraded to free account", ["UserID" => $user->getId()]);
            $plusChanged = true;
        }

        if ($clearSubscription && $user->getSubscription() !== null) {
            $this->logger->info("removed user subscription", ["UserID" => $user->getId()]);
            $user->clearSubscription();
        }

        if ($clearPlusExpirationDate && $user->getPlusExpirationDate() !== null) {
            $this->logger->info("removed plus expiration date", ["UserID" => $user->getId()]);
            $user->setPlusExpirationDate(null);
        }

        $this->em->flush();

        if ($plusChanged) {
            $this->eventDispatcher->dispatch(new UserPlusChangedEvent($user->getId()), UserPlusChangedEvent::NAME);
        }
    }

    private function upgradeByBusiness(Usr $user)
    {
        $this->logger->debug(__METHOD__, ["UserID" => $user->getId()]);

        foreach ($this->getBusinessConnections($user) as $agent) {
            /** @var Useragent $agent */
            $this->logger->debug("user connected to business " . $agent->getAgentid()->getId(), ["UserID" => $user->getId()]);

            if ($this->awPlusProcessor->upgradeToAwPlus($agent, true)) {
                $this->logger->info("user upgraded by business " . $agent->getAgentid()->getId(), ["UserID" => $user->getId()]);

                return true;
            }
        }

        return false;
    }

    private function getExpirationDate(Usr $user)
    {
        return $this->expirationCalculator->getAccountExpiration($user->getId())['date'];
    }

    private function formatDateTime(?\DateTime $date = null)
    {
        if (empty($date)) {
            return 'none';
        } else {
            return $date->format("Y-m-d");
        }
    }
}
