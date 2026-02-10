<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\BonusConversion;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AwReferralIncomeManager;
use AwardWallet\MainBundle\Globals\Cart\Manager;
use AwardWallet\MainBundle\Globals\DateUtils;
use Codeception\Module\Aw;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

/**
 * @group frontend-unit
 */
class AwReferralIncomeManagerTest extends BaseUserTest
{
    /**
     * @var UsrRepository
     */
    protected $userRep;
    /**
     * @var Manager
     */
    protected $cartManager;
    /**
     * @var int
     */
    protected $inviterId;

    public function _before()
    {
        parent::_before();

        $this->cartManager = $this->container->get('aw.manager.cart');
        $this->userRep = $this->container->get('doctrine')->getRepository(Usr::class);
        $this->inviterId = $this->user->getUserid();
    }

    public function _after()
    {
        parent::_after();

        $this->cartManager = null;
        $this->userRep = null;
        $this->inviter = null;
    }

    /**
     * Inactive inviter with one invitee with past payments should receive bonus for first payment only with lower bonus multiplier.
     */
    public function testInactiveInviterWithOneInviteeWithPastPaymentsShouldReceiveBonusForFirstPaymentOnlyWithLowerBonusMultiplier()
    {
        $baseDate = new \DateTimeImmutable(AwReferralIncomeManager::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE);
        $invitee = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-2 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-1 year')),
            $this->createSubscriptionScheduledPayment($invitee, $baseDate->modify('-1 year')),
            $this->createSubscriptionAT201Payment($invitee, $baseDate->modify('-1 year')),
        );
        $referralManager = $this->awBonusManager([$this->inviterId]);

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalBonusByUser($invitee->getUserid())
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 2,
            $referralManager->getTotalReferralIncomeByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalReferralBonusBalanceByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalReferralBonusEligibleIncomePointsByUser($this->inviterId)
        );

        assertSame(
            (float) AwPlusSubscription::PRICE,
            $referralManager->getTotalReferralBonusEligibleIncomeByUser($this->inviterId)
        );
    }

    /**
     * Inactive inviter with one invitee with regular payments should receive bonus for first payment only with lower bonus multiplier.
     */
    public function testInactiveInviterWithOneInviteeWithRegularPaymentsShouldReceiveBonusForFirstPaymentOnlyWithLowerBonusMultiplier()
    {
        $baseDate = new \DateTimeImmutable(AwReferralIncomeManager::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE);
        $invitee = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-3 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-2 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('+1 day')),
            $this->createSubscriptionScheduledPayment($invitee, $baseDate->modify('+1 day')),
            $this->createSubscriptionAT201Payment($invitee, $baseDate->modify('+1 day')),
        );

        $referralManager = $this->awBonusManager([$this->inviterId]);

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalBonusByUser($invitee->getUserid())
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 3,
            $referralManager->getTotalReferralIncomeByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalReferralBonusBalanceByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalReferralBonusEligibleIncomePointsByUser($this->inviterId)
        );

        assertSame(
            (float) AwPlusSubscription::PRICE,
            $referralManager->getTotalReferralBonusEligibleIncomeByUser($this->inviterId)
        );
    }

    /**
     * Inactive inviter with multiple invitees with regular payments should receive bonus for first payment only with lower bonus multiplier.
     */
    public function testInactiveInviterWithMultipleInviteesWithRegularPaymentsShouldReceiveBonusForFirstPaymentOnlyWithLowerBonusMultiplier()
    {
        $baseDate = new \DateTimeImmutable(AwReferralIncomeManager::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE);
        $inviteesCount = 3;
        $referralManager = $this->awBonusManager([$this->inviterId]);

        foreach (range(1, $inviteesCount) as $_) {
            $invitee = $this->createAwUser();
            $this->aw->inviteUser($this->inviterId, $invitee->getUserid());
            $this->createPayments(
                $this->createSubscriptionPayment($invitee, $baseDate->modify('-3 year')),
                $this->createSubscriptionPayment($invitee, $baseDate->modify('-2 year')),
                $this->createSubscriptionPayment($invitee, $baseDate->modify('+1 day')),
                $this->createSubscriptionScheduledPayment($invitee, $baseDate->modify('+1 day')),
                $this->createSubscriptionAT201Payment($invitee, $baseDate->modify('+1 day')),
            );

            assertEquals(
                (int) round(AwPlusSubscription::PRICE * 15),
                $referralManager->getTotalBonusByUser($invitee->getUserid())
            );
        }

        assertSame(
            round(AwPlusSubscription::PRICE * 3 * $inviteesCount, 2), $referralManager->getTotalReferralIncomeByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * $inviteesCount * 15),
            $referralManager->getTotalReferralBonusBalanceByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * $inviteesCount * 15),
            $referralManager->getTotalReferralBonusEligibleIncomePointsByUser($this->inviterId)
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * $inviteesCount,
            $referralManager->getTotalReferralBonusEligibleIncomeByUser($this->inviterId)
        );
    }

    /**
     * Inviter with one invitee with past and present payments should receive bonus for all past payments with higher bonus multiplier.
     */
    public function testInviterWithOneInviteeWithPastAndPresentPaymentsShouldReceiveBonusForAllPastPaymentsWithHigherBonusMultiplier()
    {
        $baseDate = new \DateTimeImmutable(AwReferralIncomeManager::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE);
        $invitee = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-3 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-2 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('+1 day')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('+1 year')),
            $this->createSubscriptionScheduledPayment($invitee, $baseDate->modify('+1 year')),
            $this->createSubscriptionAT201Payment($invitee, $baseDate->modify('+1 year')),
        );
        $referralManager = $this->awBonusManager([-1]);

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25),
            $referralManager->getTotalBonusByUser($invitee->getUserid())
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 4,
            $referralManager->getTotalReferralIncomeByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25),
            $referralManager->getTotalReferralBonusBalanceByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25),
            $referralManager->getTotalReferralBonusEligibleIncomePointsByUser($this->inviterId)
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 2,
            $referralManager->getTotalReferralBonusEligibleIncomeByUser($this->inviterId)
        );
    }

    /**
     * Inviter with redeemed points with one invitee with past and present payments should receive bonus for all past payments with higher bonus multiplier.
     */
    public function testInviterWithRedeemedPointsWithOneInviteeWithPastAndPresentPaymentsShouldReceiveBonusForAllPastPaymentsWithHigherBonusMultiplier()
    {
        $this->db->haveInDatabase('BonusConversion', [
            'Airline' => 'Some airline',
            'Points' => 300,
            'Miles' => 20,
            'CreationDate' => DateUtils::toSQLDateTime(new \DateTime()),
            'Processed' => 1,
            'UserID' => $this->inviterId,
        ]);

        $baseDate = new \DateTimeImmutable(AwReferralIncomeManager::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE);
        $invitee = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-3 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('-2 year')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('+1 day')),
            $this->createSubscriptionPayment($invitee, $baseDate->modify('+1 year')),
            $this->createSubscriptionScheduledPayment($invitee, $baseDate->modify('+1 year')),
            $this->createSubscriptionAT201Payment($invitee, $baseDate->modify('+1 year')),
        );
        $referralManager = $this->awBonusManager([-1]);

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25),
            $referralManager->getTotalBonusByUser($invitee->getUserid())
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 4,
            $referralManager->getTotalReferralIncomeByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25 - 300),
            $referralManager->getTotalReferralBonusBalanceByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25),
            $referralManager->getTotalReferralBonusEligibleIncomePointsByUser($this->inviterId)
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 2,
            $referralManager->getTotalReferralBonusEligibleIncomeByUser($this->inviterId)
        );
    }

    /**
     * Inviter with multiple invitees with past and present payments should receive bonus for all past payments with higher bonus multiplier plus bonus for first present payment.
     */
    public function testInviterWithMultipleInviteesWithPastAndPresentPaymentsShouldReceiveBonusForAllPastPaymentsWithHigherBonusMultiplierPlusBonusForFirstPresentPayment()
    {
        $baseDate = new \DateTimeImmutable(AwReferralIncomeManager::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE);
        $referralManager = $this->awBonusManager([-1]);

        $invitee1 = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee1->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee1, $baseDate->modify('-3 year')),
            $this->createSubscriptionPayment($invitee1, $baseDate->modify('-2 year')),
            $this->createSubscriptionPayment($invitee1, $baseDate->modify('+1 day')),
            $this->createSubscriptionPayment($invitee1, $baseDate->modify('+1 year')),
            $this->createSubscriptionScheduledPayment($invitee1, $baseDate->modify('+1 year')),
            $this->createSubscriptionAT201Payment($invitee1, $baseDate->modify('+1 year')),
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 2 * 25),
            $referralManager->getTotalBonusByUser($invitee1->getUserid())
        );

        $invitee2 = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee2->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee2, $baseDate->modify('-2 year')),
            $this->createSubscriptionPayment($invitee2, $baseDate->modify('+1 day')),
            $this->createSubscriptionPayment($invitee2, $baseDate->modify('+1 year')),
            $this->createSubscriptionPayment($invitee2, $baseDate->modify('+2 year')),
            $this->createSubscriptionScheduledPayment($invitee2, $baseDate->modify('+2 year')),
            $this->createSubscriptionAT201Payment($invitee2, $baseDate->modify('+2 year')),
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 25),
            $referralManager->getTotalBonusByUser($invitee2->getUserid())
        );

        $invitee3 = $this->createAwUser();
        $this->aw->inviteUser($this->inviterId, $invitee3->getUserid());
        $this->createPayments(
            $this->createSubscriptionPayment($invitee3, $baseDate->modify('+1 day')),
            $this->createSubscriptionPayment($invitee3, $baseDate->modify('+1 year')),
            $this->createSubscriptionPayment($invitee3, $baseDate->modify('+2 year')),
            $this->createSubscriptionPayment($invitee3, $baseDate->modify('+3 year')),
            $this->createSubscriptionScheduledPayment($invitee3, $baseDate->modify('+3 year')),
            $this->createSubscriptionAT201Payment($invitee3, $baseDate->modify('+3 year')),
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * 15),
            $referralManager->getTotalBonusByUser($invitee3->getUserid())
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 12,
            $referralManager->getTotalReferralIncomeByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * (3 * 25 + 15)),
            $referralManager->getTotalReferralBonusBalanceByUser($this->inviterId)
        );

        assertSame(
            (int) round(AwPlusSubscription::PRICE * (3 * 25 + 15)),
            $referralManager->getTotalReferralBonusEligibleIncomePointsByUser($this->inviterId)
        );

        assertSame(
            ((float) AwPlusSubscription::PRICE) * 4,
            $referralManager->getTotalReferralBonusEligibleIncomeByUser($this->inviterId)
        );
    }

    protected function awBonusManager(array $inactiveUsersWithBonusPoints): AwReferralIncomeManager
    {
        return new AwReferralIncomeManager(
            $this->container->get('database_connection'),
            $this->container->get('doctrine')->getRepository(BonusConversion::class),
            $inactiveUsersWithBonusPoints
        );
    }

    protected function createSubscriptionPayment(Usr $payer, \DateTimeInterface $payDate): TestPayment
    {
        return new TestPayment($payer, [new AwPlusSubscription()], PAYMENTTYPE_CREDITCARD, $payDate);
    }

    protected function createSubscriptionScheduledPayment(Usr $payer, \DateTimeInterface $payDate): TestPayment
    {
        return new TestPayment(
            $payer,
            [
                (new AwPlusSubscription())
                ->setScheduledDate(new \DateTime('+1 months')),
            ],
            PAYMENTTYPE_CREDITCARD,
            $payDate
        );
    }

    protected function createSubscriptionAT201Payment(Usr $payer, \DateTimeInterface $payDate): TestPayment
    {
        return new TestPayment(
            $payer,
            [new AT201Subscription6Months()],
            PAYMENTTYPE_CREDITCARD,
            $payDate
        );
    }

    /**
     * @param array $payments
     */
    protected function createPayments(...$payments)
    {
        /** @var TestPayment $payment */
        foreach ($payments as $payment) {
            if (is_array($payment)) {
                $this->createPayments(...$payment);
            } else {
                $this->cartManager->setUser($payment->getPayer());
                $cart = $this->cartManager->createNewCart();
                $cart->setPaymenttype($payment->getPaymentType());

                foreach ($payment->getCartItems() as $cartItem) {
                    $cart->addItem($cartItem);
                }

                $this->cartManager->markAsPayed($cart, null, DateUtils::toMutable($payment->getPayDate()));
            }
        }
    }

    protected function createAwUser(): Usr
    {
        return $this->userRep->find(
            $this->aw->createAwUser('bns' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD)
        );
    }
}

class TestPayment
{
    /**
     * @var Usr
     */
    protected $payer;
    /**
     * @var string[]
     */
    protected $cartItems;
    /**
     * @var int
     */
    protected $paymentType;
    /**
     * @var \DateTime
     */
    protected $payDate;

    /**
     * TestPayment constructor.
     *
     * @param string[] $cartItems
     */
    public function __construct(Usr $payer, array $cartItems, int $paymentType, \DateTimeInterface $payDate)
    {
        $this->payer = $payer;
        $this->cartItems = $cartItems;
        $this->paymentType = $paymentType;
        $this->payDate = $payDate;
    }

    public function getPayer(): Usr
    {
        return $this->payer;
    }

    /**
     * @param Usr $payer
     * @return TestPayment
     */
    public function setPayer($payer)
    {
        $this->payer = $payer;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getCartItems(): array
    {
        return $this->cartItems;
    }

    /**
     * @param string[] $cartItems
     * @return TestPayment
     */
    public function setCartItems(array $cartItems)
    {
        $this->cartItems = $cartItems;

        return $this;
    }

    public function getPaymentType(): int
    {
        return $this->paymentType;
    }

    /**
     * @return TestPayment
     */
    public function setPaymentType(int $paymentType)
    {
        $this->TestPaymentType = $paymentType;

        return $this;
    }

    public function getPayDate(): \DateTimeInterface
    {
        return $this->payDate;
    }

    /**
     * @param \DateTime $payDate
     * @return TestPayment
     */
    public function setPayDate(\DateTimeInterface $payDate)
    {
        $this->payDate = $payDate;

        return $this;
    }
}
