<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\BalanceWatchCreditsTransaction;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusGift;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Event\CartMarkPaidEvent;
use AwardWallet\MainBundle\Event\RefundEvent;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\MainBundle\Service\BusinessTransaction\BalanceWatchProcessor;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BalanceWatchManager implements TranslationContainerInterface
{
    // exception check local password [American Airlines, Bank Of America, Capital One]
    public const EXCLUDED_PROVIDER_LOCAL_PASSWORD = [1, 75, 104];

    public const ALLOW_PROVIDER_STATE = [PROVIDER_ENABLED, PROVIDER_CHECKING_WITH_MAILBOX, PROVIDER_TEST];

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var AwTokenStorage */
    private $tokenStorage;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var TranslatorInterface */
    private $translator;
    /**
     * @var BackgroundCheckScheduler
     */
    private $scheduler;

    /** @var Manager */
    private $cartManager;

    private Query $query;

    private Notifications $notifications;

    private Stopper $stopper;

    private BalanceWatchProcessor $balanceWatchProcessor;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        BackgroundCheckScheduler $scheduler,
        Manager $cartManager,
        BalanceWatchProcessor $balanceWatchProcessor,
        Query $query,
        Notifications $notifications,
        Stopper $stopper
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->scheduler = $scheduler;
        $this->cartManager = $cartManager;
        $this->query = $query;
        $this->notifications = $notifications;
        $this->stopper = $stopper;
        $this->balanceWatchProcessor = $balanceWatchProcessor;
    }

    public function startBalanceWatch(Account $account, AccountModel $model, ?bool $byOwner = null): bool
    {
        $user = null === $byOwner
            ? $this->tokenStorage->getBusinessUser()
            : $account->getUser();

        if (!$account->getProviderid()->getCancheck()) {
            throw new \BadMethodCallException('We can not run an Balance Watch for this Provider');
        }

        if ((!$user->isBusiness() && $user->getBalanceWatchCredits() <= 0)
            || ($user->isBusiness() && $user->getBusinessInfo()->getBalance() < BalanceWatchCredit::PRICE)) {
            throw new \OutOfBoundsException('This operation is not available to the ' . ($user->isBusiness() ? 'BUSINESS' : 'USER') . ', reason - insufficient funds');
        }

        if ($account->isBalanceWatchDisabled()) {
            throw new \OutOfBoundsException('This operation is not available to the ACCOUNT, reason - balance watch disabled');
        }

        if ($user->isBusiness()) {
            $this->balanceWatchProcessor->balanceWatch(Constants::EVENT_START_MONITORED, $this->tokenStorage->getUser(), $account);
        } else {
            $balanceWatchCreditTransaction = (new BalanceWatchCreditsTransaction($user, BalanceWatchCreditsTransaction::TYPE_SPEND, Constants::TRANSACTION_COST))
                ->setAccount($account);
            $this->entityManager->persist($balanceWatchCreditTransaction);

            $user->setBalanceWatchCredits($user->getBalanceWatchCredits() - Constants::TRANSACTION_COST);
            $this->entityManager->persist($user);
        }

        $account->setBalanceWatchStartDate(new \DateTime());
        $this->entityManager->persist($account);

        $excludeLogin2Regions = ['US', 'USA', 'United States', 'English', 'Login2', '', null];
        $balanceWatch = (new BalanceWatch())
            ->setAccount($account)
            ->setProvider($account->getProviderid())
            ->setPointsSource($model->getPointsSource())
            ->setExpectedPoints($model->getExpectedPoints())
            ->setTransferFromProvider($model->getTransferFromProvider())
            ->setTransferRequestDate($model->getTransferRequestDate())
            ->setSourceProgramRegion(
                !empty($model->getSourceProgramRegion()) && !in_array($account->getLogin2(), $excludeLogin2Regions)
                    ? $model->getSourceProgramRegion()
                    : null
            )
            ->setTargetProgramRegion(
                !empty($account->getLogin2()) && !in_array($account->getLogin2(), $excludeLogin2Regions)
                    ? $account->getLogin2()
                    : null
            )
            ->setIsBusiness($user->isBusiness());
        $this->tokenStorage->getUser()->getUserid() === $account->getUser()->getUserid() || true === $byOwner ?: $balanceWatch->setPayerUser($this->tokenStorage->getUser());
        $this->entityManager->persist($balanceWatch);

        $this->entityManager->flush();

        $this->notifications->sendAccountNotification($account, Constants::EVENT_START_MONITORED, $balanceWatch);
        $this->scheduler->schedule($account->getAccountid());

        $logContext = ['AccountID' => $account->getAccountid()];

        if (true === $byOwner) {
            $logContext['byOwner'] = true;
            $logContext['UserIdStarted'] = $this->tokenStorage->getUser()->getUserid();
        }
        $this->logger->info('BalanceWatch - START', $logContext);

        return true;
    }

    public function onRefund(RefundEvent $event)
    {
        $cart = $event->getCart();

        if ($cart->getUser() === null) {
            return;
        }

        if ($cart->isAwPlusSubscription() && $cart->hasItemsByType([BalanceWatchCredit::TYPE])) {
            $this->logger->info('BalanceWatchManager refund', ['subscriptionCartId' => $cart->getCartid()]);

            $user = $cart->getUser();
            $balanceWatchCreditTransaction = (new BalanceWatchCreditsTransaction($user, BalanceWatchCreditsTransaction::TYPE_REFUND, 1));
            $this->entityManager->persist($balanceWatchCreditTransaction);

            $user->setBalanceWatchCredits($user->getBalanceWatchCredits() - 1);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function onCartMarkPaid(CartMarkPaidEvent $event): void
    {
        $cart = $event->getCart();

        if (!$cart->isAwPlusSubscription() && $cart->hasItemsByType([BalanceWatchCredit::TYPE])) {
            $this->addBalanceWatchTransaction($cart, BalanceWatchCreditsTransaction::TYPE_PURCHASE);
        }

        if ($cart->isAwPlusSubscription()
            && !$cart->hasItemsByType([BalanceWatchCredit::TYPE, AwPlusGift::TYPE])
            && empty($cart->getScheduledTotal())
            && Cart::PAYMENTTYPE_BUSINESS_BALANCE !== $cart->getPaymenttype()
            && $cart->getAT201Item() === null
        ) {
            $this->cartManager->addBalanceWatchCredit($cart, Constants::GIFT_COUNT, 0);
            $this->cartManager->save($cart);

            $this->addBalanceWatchTransaction($cart, BalanceWatchCreditsTransaction::TYPE_GIFT);
        }
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('buy'))->setDesc('Buy'),
            (new Message('account.balancewatch.awplus-upgrade'))->setDesc('Balance Watch credits are only available to AwardWallet Plus members. By upgrading to Plus you support our development efforts, which makes the service better. We appreciate your support!'),
            (new Message('account.balancewatch.credits-no-available-label'))->setDesc('No Credits Available'),
            (new Message('account.balancewatch.credits-no-available-notice'))->setDesc('At the moment you don\'t have any Balance Watch Credits available. In order to use this feature, you need to purchase some credits.'),
            (new Message('account.balancewatch.credits-no-available-notice-business'))->setDesc('Currently, you do not have enough money on your account to enable Balance Watch. Every time you enable Balance Watch we charge your account $5, please add some funds (at least $5) to your account to enable Balance Watch.'),
            (new Message('account.balancewatch.no-available'))->setDesc('No Available'),
            (new Message('account.balancewatch.not-available'))->setDesc('Not Available'),
            (new Message('account.balancewatch.not-available-password-local'))->setDesc('Balance Watch is not available on this account because this account password is stored locally.'),
            (new Message('account.balancewatch.not-available-account-disabled'))->setDesc('Balance Watch is not available on this account because this account is currently disabled.'),
            (new Message('account.balancewatch.not-available-account-error'))->setDesc('Balance Watch is not available on this account because this account received an error last time we attempted updating it.'),
            (new Message('account.balancewatch.not-available-not-cancheck'))->setDesc('Balance Watch is not available on this account because we are not able to update this type of account.'),
            (new Message('account.balancewatch.balance-change.message-short', 'email'))->setDesc('A change of %balanceChange% has been detected on %providerName% account %accountLogin%! The original balance was %balanceFrom% and the new balance is now %balanceTo%.'),
            (new Message('account.balancewatch.timeout.message-short', 'email'))->setDesc('%providerName% account %accountLogin% was being monitored for changes, we did not detect any changes to this account so it was reverted to a normal background updating schedule.'),
            (new Message('account.balancewatch.update-error.message-short', 'email'))->setDesc('%providerName% account %accountLogin% was being monitored for changes. However, we received an error while updating this account.'),
            (new Message('less-1-hour-ago'))->setDesc('Less than 1 hour ago'),
            (new Message('more-than-24-hours-ago'))->setDesc('More than 24 hours ago'),

            (new Message('account.balancewatch.force-label'))->setDesc('Enable “Balance Watch”'),
            (new Message('account.balancewatch.force-notice'))->setDesc('If enabled, we will frequently update this account in the background until we detect a change in the balance. For more info please read %link_faq_on%this FAQ%link_faq_off%. You should get a push notification if a change is detected. Please %link_notify_on%click here%link_notify_off% to verify that push notifications are working for you.'),
            (new Message('account.balancewatch.source-points'))->setDesc('Source of %currency%'),
            (new Message('account.balancewatch.source-points.transfer'))->setDesc('Transfer'),
            (new Message('account.balancewatch.source-points.purchase'))->setDesc('Purchase'),
            (new Message('account.balancewatch.transfer-from'))->setDesc('Transfer from'),
            (new Message('account.balancewatch.transfer-from.notice'))->setDesc('Please start entering the name of a provider from which you initiated the transfer and then select that provider from the options that show up.'),
            (new Message('account.balancewatch.expected-number-miles'))->setDesc('Expected number of %currency%'),
            (new Message('account.balancewatch.expected-number-miles-notice'))->setDesc('If you choose an %expectedNumberCaption%, we will only trigger the alert for this account if we see your balance increase by at least this number of points.'),
            (new Message('account.balancewatch.points-purchase'))->setDesc('%currency% purchased'),
            (new Message('account.balancewatch.transfer-requested'))->setDesc('Transfer requested'),
            (new Message('account.balancewatch.purchase-requested'))->setDesc('Purchase requested'),
            (new Message('add-funds'))->setDesc('Add Funds'),
            (new Message('business.balancewatch.transaction-start'))->setDesc('Balance Watch enabled on a %link_account_on%%providerName% account %accountLogin%%link_account_off% (for %userFullname%)'),
            (new Message('business.balancewatch.transaction-refund'))->setDesc('Balance Watch credit for a failed update on a %link_account_on%%providerName% account %accountLogin%%link_account_off% (for %userFullname%)'),
        ];
    }

    private function addBalanceWatchTransaction(Cart $cart, int $type)
    {
        $this->logger->info('BalanceWatchManager markAsPaid - start', ['userBalanceWatchCredits' => $cart->getUser()->getBalanceWatchCredits(), 'type' => $type]);

        if (BalanceWatchCreditsTransaction::TYPE_PURCHASE === $type) {
            $balanceWatchCreditCartItem = ['count' => 0, 'amount' => 0];

            foreach ($cart->getItemsByType([BalanceWatchCredit::TYPE]) as $bwCreditItem) {
                $balanceWatchCreditCartItem['count'] += $bwCreditItem->getCnt();
                $balanceWatchCreditCartItem['amount'] += BalanceWatchCredit::COUNT_PRICE[$bwCreditItem->getCnt()] ?? $bwCreditItem->getCnt();
            }
            $amount = $balanceWatchCreditCartItem['amount'];
        } elseif (BalanceWatchCreditsTransaction::TYPE_GIFT === $type) {
            $amount = 0;
            $balanceWatchCreditCartItem = ['count' => Constants::GIFT_COUNT, 'amount' => $amount];
        } else {
            throw new \BadMethodCallException('Unsupported type: ' . $type);
        }

        $summaryCredits = $cart->getUser()->getBalanceWatchCredits() + $balanceWatchCreditCartItem['count'];
        $cart->getUser()->setBalanceWatchCredits($summaryCredits);
        $this->entityManager->persist($cart->getUser());

        $this->logger->info('BalanceWatchManager markAsPaid user credit - update userCredit', ['userBalanceWatchCredits' => $summaryCredits, 'type' => $type]);
        $balanceWatchCreditTransaction = (new BalanceWatchCreditsTransaction($cart->getUser(), $type, $amount))->setBalance($summaryCredits);
        $this->entityManager->persist($balanceWatchCreditTransaction);

        $this->entityManager->flush();
        $this->logger->info('BalanceWatchManager markAsPaid - end', ['amount' => $amount, 'balance' => $summaryCredits, 'type' => $type]);
    }
}
