<?php

namespace AwardWallet\MainBundle\Service\BusinessTransaction;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Entity\BusinessTransaction\AbRequestClosed;
use AwardWallet\MainBundle\Entity\BusinessTransaction\MembershipRenewed;
use AwardWallet\MainBundle\Entity\BusinessTransaction\Payment;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BusinessTransactionManager
{
    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;

    protected $transRep;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        /** @var Usr $business */
        $this->em = $em;
        $this->logger = $logger;
        $this->transRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\BusinessTransaction::class);
    }

    /**
     * @return bool
     */
    public function bookingRequestComplete(AbRequest $abRequest)
    {
        if ($this->isBookingRequestPaid($abRequest)) {
            throw new \InvalidArgumentException(sprintf("AbRequest #%d has already been paid", $abRequest->getAbRequestID()));
        }

        return $this->addTransaction($abRequest->getBooker(), new AbRequestClosed($abRequest));
    }

    /**
     * @return bool
     */
    public function isBookingRequestPaid(AbRequest $abRequest)
    {
        $lastTransaction = $this->transRep->getLastTransaction(
            $abRequest->getBooker(),
            $abRequest->getAbRequestID(),
            ['AbRequestClosed']
        );

        return isset($lastTransaction);
    }

    /**
     * @return bool
     */
    public function addPayment(Usr $business, $amount, $billMonth = true)
    {
        $transaction = new Payment($amount);
        $amount = $transaction->getAmount() * -1;
        $business->getBusinessInfo()->setTrialEndDate(null);
        $paid = $this->addTransaction($business, $transaction, $amount);

        if ($paid && $billMonth) {
            $this->billMonth($business, new \DateTime());
        }

        return $paid;
    }

    /**
     * make monthly bill
     * should be run from cron, once per month, or when disabled business paid money.
     *
     * without parameters it will make payment for full month, otherwise payment from specified date till end of month
     *
     * @return int - number of upgraded members
     */
    public function billMonth(Usr $business, ?\DateTime $billDate = null, $allowPartialPayment = false)
    {
        if ($business->getBusinessInfo()->isTrial()) {
            return 0;
        }

        $members = $this->getMembersCount($business);

        if ($members > 0) {
            $transaction = new MembershipRenewed($members);

            if (!empty($billDate)) {
                $lastDay = strtotime("last day of", $billDate->getTimestamp());
                $daysInMonth = date("d", $lastDay);
                $day = intval(date("d", $billDate->getTimestamp())) - 1;
                $remainingDays = $daysInMonth - $day;
                $ratio = $remainingDays / $daysInMonth;
                $amount = round($transaction->getAmount() * $ratio, 2);
                $this->logger->info('corrected transaction amount', ['UserID' => $business->getUserid(), 'amount' => $amount, 'daysInMonth' => $daysInMonth, 'date' => $billDate->format("Y-m-d"), 'remainingDays' => $remainingDays, 'ratio' => $ratio]);
                $transaction->setAmount($amount);
            } else {
                $billDate = new \DateTime();
            }

            if ($business->getBusinessInfo()->isPaid($billDate)) {
                return 0;
            }

            $payUntilDate = new \DateTime('@' . strtotime("last day of", $billDate->getTimestamp()));
            $transaction->setSourceDesc($payUntilDate);

            if ($this->addTransaction($business, $transaction)) {
                $business->getBusinessInfo()->setPaidUntilDate($payUntilDate);
                $this->logger->info("set PaidUntilDate to " . $business->getBusinessInfo()->getPaidUntilDate()->format("Y-m-d"), ['UserID' => $business->getUserid()]);
                $this->em->flush([$business->getBusinessInfo()]);
            } else {
                $members = 0;
            }
        }

        return $members;
    }

    public function getRecommendedPayment(Usr $business)
    {
        $members = $this->getMembersCount($business);
        $transaction = new MembershipRenewed($members);
        $membersCost = $transaction->getAmount() * (1 - $business->getBusinessInfo()->getDiscount() / 100);

        $awPlusCost = $this->getKeepUpgradedMembersCount($business) * AwPlusSubscription::PRICE / 12;

        $bookingCost = 0;

        if ($business->isBooker()) {
            $transaction = new AbRequestClosed(new AbRequest());
            $bookingCost = $this->getClosedRequestsCount($business) * $transaction->getAmount() * (1 - $business->getBookerInfo()->getDiscount() / 100);
        }

        $cost = $membersCost + $awPlusCost + $bookingCost;
        $round = 10;

        if ($cost > 200) {
            $round = 50;
        }

        if ($cost > 500) {
            $round = 100;
        }

        return ceil($cost / $round) * $round;
    }

    public function getMembersCount(Usr $business)
    {
        return $this->em->createQuery("
        select
            count(ua)
        from
            AwardWallet\MainBundle\Entity\Useragent ua
        where
            ua.agentid = :business
            and ua.isapproved = 1")
            ->execute(["business" => $business], AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    public function getKeepUpgradedMembersCount(Usr $business)
    {
        return $this->em->createQuery("
        select
            count(ua)
        from
            AwardWallet\MainBundle\Entity\Useragent ua
        left join
            AwardWallet\MainBundle\Entity\Useragent au with ua.agentid = au.clientid and au.agentid = ua.clientid
        where
            ua.agentid = :business
            and ua.isapproved = 1
            and au.keepUpgraded = 1")
            ->execute(["business" => $business], AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    public function getClosedRequestsCount(Usr $business)
    {
        return $this->transRep->getClosedRequestsCount($business);
    }

    /**
     * @return bool
     */
    public function addTransaction(Usr $business, BusinessTransaction $transaction, $amount = null)
    {
        if (is_null($amount)) {
            $amount = round($transaction->getAmount() * (1 - $this->getDiscount($business, $transaction) / 100), 2);
        }
        $balance = $business->getBusinessInfo()->getBalance();
        $logData = [
            'Success' => false,
            'Type' => get_class($transaction),
            'SourceID' => $transaction->getSourceID(),
            'Amount' => $amount,
            'UserID' => $business->getUserid(),
        ];

        if (($transaction->isFreeWhenTrial() && $business->getBusinessInfo()->isTrial()) || $amount == 0) {
            $transaction->setAmount(0);
            $transaction->setBalance($balance);
        } elseif ($transaction instanceof BusinessTransaction\BalanceWatchRefund) {
            $balance = round($balance + $transaction->getAmount(), 2);
            $transaction->setBalance($balance);
            $business->getBusinessInfo()->setBalance($balance);
        } else {
            if ($amount > 0 && $balance - $amount < 0) {
                $this->logger->info('business transaction, insufficient balance', $logData);

                return false;
            }
            $transaction->setAmount(abs($amount));
            $balance = round($balance - $amount, 2);
            $transaction->setBalance($balance);
            $business->getBusinessInfo()->setBalance($balance);
        }

        $business->addBusinessTransaction($transaction);
        $this->em->flush([$business, $business->getBusinessInfo()]);
        $this->logger->info('business transaction', array_merge($logData, ['Success' => true]));

        return true;
    }

    private function getDiscount(Usr $business, BusinessTransaction $transaction)
    {
        if ($transaction instanceof AbRequestClosed) {
            return $business->getBookerInfo()->getDiscount();
        } elseif ($transaction instanceof MembershipRenewed) {
            return $business->getBusinessInfo()->getDiscount();
        }

        return 0;
    }
}
