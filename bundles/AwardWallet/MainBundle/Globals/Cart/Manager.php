<?php

namespace AwardWallet\MainBundle\Globals\Cart;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\Billingaddress;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwBusinessCredit;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\CartItem\OneCard;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\BusinessPaymentEvent;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Event\RefundEvent;
use AwardWallet\MainBundle\Event\UserPlusChangedEvent;
use AwardWallet\MainBundle\Form\Type\AwPlusGiftType;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\Cart\OrderConfirmation;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Manager implements TranslationContainerInterface
{
    public const PAYPAL_FEE = 0.029;

    /**
     * @var Usr
     */
    protected $user;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var BookingRequestManager
     */
    protected $bookingRequestManager;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var ExpirationCalculator
     */
    private $expirationCalculator;

    /** @var LoggerInterface */
    private $logger;

    /** @var SessionInterface */
    private $session;
    /**
     * @var CartUserSource
     */
    private $cartUserSource;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        BookingRequestManager $bookingManager,
        Mailer $mailer,
        EventDispatcherInterface $eventDispatcher,
        LocalizeService $localizer,
        ExpirationCalculator $expirationCalculator,
        LoggerInterface $paymentLogger,
        SessionInterface $session,
        CartUserSource $cartUserSource,
        UsrRepository $usrRepository
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->bookingRequestManager = $bookingManager;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
        $this->localizer = $localizer;
        $this->expirationCalculator = $expirationCalculator;
        $this->logger = $paymentLogger;
        $this->session = $session;
        $this->cartUserSource = $cartUserSource;
        $this->usrRepository = $usrRepository;
    }

    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): Usr
    {
        if ($this->user) {
            return $this->user;
        }

        return $this->cartUserSource->getCartOwner();
    }

    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;

        return $this;
    }

    public function createNewCart(?CartUserInfo $cartUserInfo = null): Cart
    {
        if ($cartUserInfo === null) {
            $this->cartUserSource->clearUser();
            $user = $this->getUser();
        } else {
            $user = $this->usrRepository->find($cartUserInfo->getCartOwnerId());
            $this->cartUserSource->setUser($cartUserInfo);
        }

        $cart = new Cart();
        $user->addCart($cart);
        $this->em->persist($cart);
        $this->logger->info("created new cart: {$cart->getCartid()}" . ($cart->getUser() ? " for user {$cart->getUser()->getId()}" : ' for anonymous user'));

        return $cart;
    }

    /**
     * get current unpaid cart.
     */
    public function getCart(?int $cartId = null): Cart
    {
        $user = $this->getUser();

        $result = $user->getCarts()->filter(function ($cart) use ($cartId) {
            /** @var Cart $cart */
            return !$cart->isPaid() && ($cartId === null || $cart->getCartid() === $cartId);
        })->last();

        if ($result === false) {
            throw new \Exception("No unpaid carts " . ($cartId ? " with cartId $cartId" : "") . " for user {$user->getId()}");
        }

        return $result;
    }

    public function save(?Cart $cart = null)
    {
        if ($cart
            && empty($cart->getCartid())
            && !empty($cart->getPaymenttype())
            && !empty($cart->getBillingtransactionid())
        ) {
            $criteria = Criteria::create()
                ->where(
                    Criteria::expr()->andX(
                        Criteria::expr()->eq('paymenttype', $cart->getPaymentType()),
                        Criteria::expr()->eq('billingtransactionid', $cart->getBillingtransactionid())
                    )
                );
            $carts = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->matching($criteria);

            if ($carts->count() > 0) {
                throw new \RuntimeException("Attempting to add a duplicate purchase");
            }
        }

        $this->em->flush();

        return $this;
    }

    public function markAsPayed(
        ?Cart $cart = null,
        ?Billingaddress $address = null,
        ?\DateTime $payDate = null,
        $dontSendUser = false
    ) {
        $changePayDate =
            isset($cart)
            && !is_null($cart->getCartid())
            && !is_null($cart->getPaydate())
            && !is_null($payDate)
            && $payDate->getTimestamp() !== $cart->getPaydate()->getTimestamp();
        $cart = $cart ?? $this->getCart();
        $user = $cart->getUser();
        $this->save($cart);
        $accountLevelBefore = $user->getAccountlevel();

        // booking
        $abInvoiceId = $cart->getBookingInvoiceId();

        if ($abInvoiceId) {
            $invoice = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbInvoice::class)->find($abInvoiceId);

            if ($invoice) {
                // TODO: remove dependency on auth
                $this->bookingRequestManager
                    ->markAsPaid($invoice, (float) $cart->getTotalPrice(), AbInvoice::PAYMENTTYPE_CREDITCARD);
            }
        }

        // set pay date
        if (empty($payDate)) {
            $payDate = new \DateTime();
        }
        $cart->setPaydate($payDate);

        if (isset($address)) {
            $cart->saveBillingAddress($address);
        }

        // set referal
        if ($this->session->has(ReferalListener::SESSION_REF_KEY)) {
            $cart->setCamefrom((int) $this->session->get(ReferalListener::SESSION_REF_KEY));
        }

        $this->save($cart);
        $awPlusExpirationData = $this->expirationCalculator->getAccountExpiration($user->getId());
        // 1 week after the expiration date is given to top up the card
        $isUpgradeAwPlus =
            !empty($awPlusExpirationData['date'])
            && $awPlusExpirationData['date'] > strtotime('-1 week');

        if ($cart->isAwPlus() && $isUpgradeAwPlus) {
            $expirationDate = new \DateTime('@' . $awPlusExpirationData['date']);

            if ($accountLevelBefore != ACCOUNT_LEVEL_BUSINESS) {
                $user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
            }

            $user->setPlusExpirationDate($expirationDate);
            $user->setFailedRecurringPayments(0);

            if ($cart->getAT201Item() !== null) {
                $data = $this->expirationCalculator->getAccountExpiration($user->getId(), AT201SubscriptionInterface::class);
                $user->setAt201ExpirationDate(new \DateTime('@' . $data['date']));
            }

            $this->em->flush($user);
        }

        if ($cart->isNewSubscription()) {
            $subscriptionItem = $cart->getSubscriptionItem();
            $price = $subscriptionItem->getPrice();
            $days = SubscriptionPeriod::DURATION_TO_DAYS[$subscriptionItem->getDuration()];
            $user->setSubscriptionPrice($price);
            $user->setSubscriptionPeriod($days);
            $user->setFirstSubscriptionCartItem($subscriptionItem);
            $this->em->flush($user);
            $this->logger->info("set user {$user->getId()} subscription price to {$price} and period {$days}, subscription cart item: {$subscriptionItem->getCartitemid()}");
        }

        if ($cart->isNewSubscription() && in_array($cart->getPaymenttype(), [Cart::PAYMENTTYPE_ANDROIDMARKET, Cart::PAYMENTTYPE_APPSTORE])) {
            $this->logger->info("this is mobile subscription", ["UserID" => $user->getId()]);
            $user->setSubscription(Usr::SUBSCRIPTION_MOBILE);
            $this->em->flush($user);
        }

        if ($cart->isSubscription()) {
            $subscriptionItem = $cart->getSubscriptionItem();
            $this->logger->info("this is subscription. cartitem: {$subscriptionItem->getCartitemid()}");
            $user->setLastSubscriptionCartItem($subscriptionItem);
            $this->em->flush($user);
        }

        // business balance update
        if ($user->isBusiness()) {
            /** @var AwBusinessCredit[] $awBusinessCreditItems */
            $awBusinessCreditItems = $cart->getItemsByType([
                AwBusinessCredit::TYPE,
            ]);

            foreach ($awBusinessCreditItems as $awBusinessCredit) {
                $event = new BusinessPaymentEvent($awBusinessCredit->getPrice(), $user);
                $this->eventDispatcher->dispatch($event, 'aw.business.payment');
            }
        }

        $this->eventDispatcher->dispatch(new CartMarkPaidEvent($cart), CartMarkPaidEvent::NAME);

        // send mail
        if (
            !$changePayDate
            && $cart->allowSendMailPaymentComplete()
            // and payment was made in last 14 days, commit message: send mail only if payment was made in last 14 days
            && $cart->getPaydate()->getTimestamp() > (time() - 14 * 24 * 60 * 60)
        ) {
            $this->sendMailPaymentComplete($cart, $dontSendUser);
        }

        if ($cart->isAwPlus() && $isUpgradeAwPlus && $accountLevelBefore == ACCOUNT_LEVEL_FREE) {
            $this->eventDispatcher->dispatch(new UserPlusChangedEvent($user->getId()), UserPlusChangedEvent::NAME);
        }

        return $this;
    }

    public function addAwSubscriptionItem(Cart $cart, \DateTime $subscriptionStartDate, $calcScheduledDate = true): CartItem
    {
        if ($calcScheduledDate) {
            $scheduledDate = $this->getScheduledDate($cart, $subscriptionStartDate);
        } else {
            $scheduledDate = null;
        }
        $item1 = new AwPlusSubscription();
        $item1->setStartDate($subscriptionStartDate);
        $item1->setScheduledDate($scheduledDate);

        if (!empty($scheduledDate)) {
            $item1->setDescription($this->translator->trans('cart.item.type.awplus-subscription.scheduled', [
                '%startDate%' => $this->localizer->formatDateTime($subscriptionStartDate, 'short', null),
            ]));
        }
        $cart->addItem($item1);

        // no more early supporter discount
        //        if ($discounted) {
        //            $item2 = new Discount();
        //            $item2->setPrice(AwPlusSubscription::EARLY_SUPPORTER_DISCOUNT * -1);
        //            $item2->setName($this->translator->trans('early-supporter.discount'));
        //            $item2->setId(Discount::ID_EARLY_SUPPORTER);
        //            $item2->setScheduledDate($scheduledDate);
        //            $cart->addItem($item2);
        //        }

        return $item1;
    }

    public function addAT201SubscriptionItem(Cart $cart, string $duration, $calcScheduledDate = true): CartItem
    {
        switch ($duration) {
            case SubscriptionPeriod::DURATION_1_MONTH:
                $item = new AT201Subscription1Month();

                break;

            case SubscriptionPeriod::DURATION_6_MONTHS:
                $item = new AT201Subscription6Months();

                break;

            case SubscriptionPeriod::DURATION_1_YEAR:
                $item = new AT201Subscription1Year();

                break;

            default:
                throw new \InvalidArgumentException("Invalid duration: $duration");
        }

        $scheduledDate = null;

        if ($calcScheduledDate) {
            ['date' => $expirationDate] = $this->expirationCalculator->getAccountExpiration($cart->getUser()->getUserid(), AT201SubscriptionInterface::class);

            if (date("Y-m-d", $expirationDate) > date("Y-m-d", time())) {
                $scheduledDate = new \DateTime('@' . $expirationDate);
            }
        }

        $item->setScheduledDate($scheduledDate);

        if (!in_array($cart->getPaymenttype(), [Cart::PAYMENTTYPE_CREDITCARD, Cart::PAYMENTTYPE_STRIPE_INTENT])) {
            $cart->setPaymenttype(Cart::PAYMENTTYPE_CREDITCARD);
        }

        $cart->addItem($item);

        return $item;
    }

    public function addPromo500k(Cart $cart, \DateTime $subscriptionStartDate)
    {
        $item2 = new Discount();
        $item2->setPrice(-1 * AwPlusSubscription::PROMO_500K_DISCOUNT);
        $item2->setName($this->translator->trans(/** @Desc("AwardWallet 19th birthday discount") */ "promo-19-years"));
        $item2->setId(Discount::ID_PROMO_500K);
        $item2->setScheduledDate($this->getScheduledDate($cart, $subscriptionStartDate));
        $cart->addItem($item2);
    }

    public function addPromoEmiles(Cart $cart)
    {
        $item = new Discount();
        $item->setPrice(-1 * AwPlusSubscription::PROMO_EMILES_DISCOUNT);
        $item->setName($this->translator->trans(/** @Desc("eMiles Promo Discount") */ 'discount.promo-emiles'));
        $item->setId(Discount::ID_PROMO_EMILES);
        $cart->addItem($item);
    }

    public function addPercentDiscount(Cart $cart, int $percent, int $discountId, float $price): Cart
    {
        $item = (new Discount())
            ->setId($discountId)
            ->setPrice(-1 * round($price * ($percent / 100), 2))
            ->setName($this->translator->trans('discount') . ' ' . $this->translator->trans('percent_bonus', ['%bonus%' => $percent]));

        return $cart->addItem($item);
    }

    public function fillCart(
        Cart $cart,
        Usr $user,
        $onecards,
        $giveAWPlus,
        ?\DateTime $subscriptionStartDate = null
    ) {
        if (empty($subscriptionStartDate)) {
            $subscriptionStartDate = $this->getSubscriptionStartDate($user);
        }

        $cart->clear();

        if ($giveAWPlus) {
            $this->addAwSubscriptionItem($cart, $subscriptionStartDate);
        }

        if ($onecards > 0) {
            $item3 = new OneCard();
            $item3->setCnt($onecards);
            $cart->addItem($item3);
        }

        $cart->setCalcDate(new \DateTime());

        $this->save($cart);
    }

    public function giveGiftAwplus(Cart $cart, Usr $giver, Usr $recipient, int $payType, string $giftDescription): Cart
    {
        $cart->setUser($recipient);
        $subscriptionStartDate = $this->getSubscriptionStartDate($recipient);

        if (AwPlusGiftType::PAY_TYPE_ONE_YEAR === $payType) {
            $cart->addItem(new AwPlus1Year());
            $payTypeName = $this->translator->trans('cart.item.type.awplus-1-year');
        } else {
            $this->addAwSubscriptionItem($cart, $subscriptionStartDate);
            $payTypeName = $this->translator->trans('cart.item.type.awplus-subscription');
        }

        $giftItem = new AwPlusGift();
        $giftItem
            ->setId($giver->getId())
            ->setName($payTypeName)
            ->setDescription($giftDescription);
        $cart->addItem($giftItem);
        $cart->setCalcDate(new \DateTime());
        $this->save($cart);

        if (AwPlusGiftType::PAY_TYPE_ONE_YEAR === $payType) {
            $cart->getItemsByType([AwPlus1Year::TYPE])->first()->setName($this->translator->trans('gift-awplus-1year', ['%email%' => $recipient->getEmail()]));
        } else {
            $cart->getItemsByType([AwPlusSubscription::TYPE])->first()->setName($this->translator->trans('gift-awplus-yearly-subscription', ['%email%' => $recipient->getEmail()]));
        }
        $this->save($cart);

        $this->cartUserSource->setUser(new CartUserInfo($recipient->getId(), $giver->getId(), false));

        return $cart;
    }

    public function addBalanceWatchCredit(Cart $cart, int $count, ?float $price = null)
    {
        $this->logger->info('Add BalanceWatchCredit', [
            'userId' => $cart->getUser()->getUserid(),
            'cartId' => $cart->getCartid(),
            'count' => $count,
            'price' => $price,
        ]);

        $balanceWatchCreditItem = (new BalanceWatchCredit())->setCnt($count);

        if (!is_null($price)) {
            $balanceWatchCreditItem->setPrice($price);
        }

        $cart->addItem($balanceWatchCreditItem);
        $discount = $balanceWatchCreditItem->calcDiscount();

        if ((float) $discount > 0) {
            $cartDiscount = new Discount();
            $cartDiscount->setPrice(-1 * $discount);
            $cart->addItem($cartDiscount);
        }

        $cart->setCalcDate(new \DateTime());
    }

    public function getSubscriptionStartDate(Usr $user)
    {
        $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());

        if ($expiration['date'] < time()) {
            $expiration['date'] = time();
        }

        $subscriptionStartDate = new \DateTime();
        $subscriptionStartDate->setTimestamp($expiration['date']);

        return $subscriptionStartDate;
    }

    public function refund(Cart $cart)
    {
        $this->logger->info("refunding cart {$cart->getCartid()}", ["UserID" => $cart->getUser() ? $cart->getUser()->getId() : null]);
        $this->eventDispatcher->dispatch(new RefundEvent($cart), RefundEvent::NAME);
        $user = $cart->getUser();

        if ($user !== null) {
            if ($cart->getItems()->contains($user->getFirstSubscriptionCartItem())) {
                $user->setFirstSubscriptionCartItem(null);
            }

            if ($cart->getItems()->contains($user->getLastSubscriptionCartItem())) {
                $user->setLastSubscriptionCartItem(null);
            }
        }

        $this->em->remove($cart);
        $this->em->flush();

        if ($user === null) {
            return;
        }

        $data = $this->expirationCalculator->getAccountExpiration($user->getId());

        if ($data['date'] <= time() && !$user->isFree()) {
            // could not inject PlusManager here to use checkExpirationAndDowngrade - circular reference
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

    public function giveAwPlusTrial($trialClass = AwPlusTrial::class): Cart
    {
        $cart = $this->createNewCart();
        $cart->addItem(new $trialClass());
        $this->markAsPayed($cart);

        return $cart;
    }

    /**
     * return name that will be displayed in user billing history, like 'AWARDWALLET' or 'BOOKYOURAWARD'.
     *
     * @return string
     */
    public function getMerchant(Cart $cart)
    {
        $booker = $this->getBooker($cart);

        if (!empty($booker)) {
            return $booker->getBookerInfo()->getMerchantName();
        } else {
            return AW_MERCHANT;
        }
    }

    /**
     * returns booking invoice, if there is one in cart.
     *
     * @return AbInvoice|null
     */
    public function getBookingInvoice(Cart $cart)
    {
        if ($cart->hasItemsByType([Booking::TYPE])) {
            $invoiceId = $cart->getBookingInvoiceId();

            if ($invoiceId) {
                return $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbInvoice::class)->find($invoiceId);
            }
        }

        return null;
    }

    /**
     * returns booker, if cart contains booking request, or returns null.
     *
     * @return Usr|null
     */
    public function getBooker(Cart $cart)
    {
        $invoice = $this->getBookingInvoice($cart);

        if (!empty($invoice)) {
            return $invoice->getMessage()->getRequest()->getBooker();
        }

        return null;
    }

    public function sendMailPaymentComplete(Cart $cart, $dontSendUser = false)
    {
        $mailer = $this->mailer;

        if ($cart->hasItemsByType([AwPlusGift::TYPE])) {
            $giftItem = $cart->getItemsByType([AwPlusGift::TYPE])->first();
            $user = $this->em->getRepository(Usr::class)->find($giftItem->getId());
        } else {
            $user = $cart->getUser();
        }
        $userTemplate = new OrderConfirmation($user, false);
        $infoTemplate = new OrderConfirmation($user, false);
        $infoTemplate->setEmail("money@awardwallet.com");
        $userTemplate->cart = $infoTemplate->cart = $cart;

        if ($invId = $cart->getBookingInvoiceId()) {
            $invoice = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbInvoice::class)->find($invId);

            if ($invoice) {
                $bookerInfo = $invoice->getMessage()->getRequest()->getBooker()->getBookerInfo();
                $userTemplate->merchant = $infoTemplate->merchant = $bookerInfo;
            }
        }

        $message = $mailer->getMessageByTemplate($userTemplate);

        if (!$dontSendUser) {
            $mailer->send($message, [Mailer::OPTION_SKIP_DONOTSEND => true]);
        }

        // to info@aw
        $infoTemplate->developerInfo = "User: " . $user->getFullName() . " (" . $user->getUserid() . ")";
        $message = $mailer->getMessageByTemplate($infoTemplate);
        $mailer->send($message, [Mailer::OPTION_SKIP_STAT => true]);
    }

    public function isChangingPaymentMethod(Cart $cart): bool
    {
        $cartRepo = $this->em->getRepository(Cart::class);

        return $cart->isAwPlusSubscription() && $cartRepo->getActiveAwSubscription($cart->getUser()) !== null;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('order.subject'))->setDesc('%siteName% Order ID: %orderId%'),
            (new Message('purchase.one-card-credits'))->setDesc('AwardWallet OneCard Credits'),
            (new Message('purchase.coupon'))->setDesc('Coupon "%couponName%"'),
            (new Message('promo-n-users-discount'))->setDesc('AwardWallet %n% members promotion'),
        ];
    }

    /**
     * @param int $subscriptionType - one of Usr::SUBSCRIPTION_TYPE_*
     * @param string $duration - one of SubscriptionPeriod::DURATION_*
     */
    public function addSubscription(int $subscriptionType, string $duration)
    {
        if ($subscriptionType === Usr::SUBSCRIPTION_TYPE_AWPLUS && $duration === SubscriptionPeriod::DURATION_1_YEAR) {
            $item = $this->addAwSubscriptionItem($this->getCart(), new \DateTime(), false);
        } elseif ($subscriptionType === Usr::SUBSCRIPTION_TYPE_AWPLUS && $duration === SubscriptionPeriod::DURATION_6_MONTHS) {
            $item = new AwPlusSubscription6Months();
            $item->setStartDate(new \DateTime());
            $this->getCart()->addItem($item);
        } elseif ($subscriptionType === Usr::SUBSCRIPTION_TYPE_AT201) {
            $item = $this->addAT201SubscriptionItem($this->getCart(), $duration, false);
        } else {
            throw new \Exception("Unknown subscription type: $subscriptionType for $duration");
        }

        $item->setPrice(SubscriptionPrice::getPrice($subscriptionType, $duration));
    }

    private function getScheduledDate(Cart $cart, \DateTime $subscriptionStartDate)
    {
        $expiration = $this->expirationCalculator->getAccountExpiration($cart->getUser()->getId());

        if (
            $cart->getUser()->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS
            && date("Y-m-d", $expiration['date']) > date("Y-m-d", time())
        ) {
            $scheduledDate = $subscriptionStartDate;
        } else {
            $scheduledDate = null;
        }

        return $scheduledDate;
    }
}
