<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\CartItem\At201Items;
use AwardWallet\MainBundle\Entity\CartItem\Booking;
use AwardWallet\MainBundle\Entity\Repositories\BonusConversionRepository;
use Doctrine\DBAL\Connection;

class AwReferralIncomeManager
{
    /**
     * @see https://redmine.awardwallet.com/issues/15624
     */
    public const REFERRAL_INCOME_TO_BONUS_RATES = [
        ['1970-01-01 00:00:00', 25],
        ['2017-10-20 00:00:00', 15],
    ];
    /**
     * @see https://redmine.awardwallet.com/issues/15624
     */
    public const FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE = '2017-11-03 09:00:00';
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var int[]
     */
    protected $inactiveUsersWithBonusPoints;
    /**
     * @var BonusConversionRepository
     */
    private $bonusConversionRep;

    public function __construct(
        Connection $connection,
        BonusConversionRepository $bonusConversionRep,
        array $inactiveUsersWithBonusPoints
    ) {
        $this->connection = $connection;
        $this->bonusConversionRep = $bonusConversionRep;
        $this->inactiveUsersWithBonusPoints = $inactiveUsersWithBonusPoints;
    }

    /**
     * Returns *bonus* users invited by *inviter* brought.
     *
     * @param $userID inviter
     * @param null $fromDate
     * @param null $toDate
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalReferralBonusEligibleIncomePointsByUser($userID, $fromDate = null, $toDate = null): int
    {
        return (int) round($this->doGetTotalReferralIncomeByUser($userID, $fromDate, $toDate, true));
    }

    /**
     * Returns *money (eligible for bonus)* users invited by *inviter* pay.
     *
     * @param $userID inviter
     * @param null $fromDate
     * @param null $toDate
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalReferralBonusEligibleIncomeByUser($userID, $fromDate = null, $toDate = null): float
    {
        return $this->doGetTotalReferralIncomeByUser($userID, $fromDate, $toDate, false);
    }

    /**
     * Returns *bonus* points available to redeem.
     *
     * @param $userId inviter
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalReferralBonusBalanceByUser($userId): int
    {
        return max(
            round(
                $this->getTotalReferralBonusEligibleIncomePointsByUser($userId, null, null) - $this->bonusConversionRep->getRedeemedBonusByUser($userId)
            ),
            0
        );
    }

    /**
     * Returns *bonus* invitee brought to his inviter.
     *
     * @param $userID invitee
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalBonusByUser($userID): int
    {
        /**
         * @see https://redmine.awardwallet.com/issues/15624
         */
        $isInvitedByInactiveUserWithBonusPoints = (bool) $this->connection->executeQuery('
            select 1
            from Invites
            where
                InviteeID = :inviteeID and
                InviterID in (:inactiveUsersWithBonusPoints)',
            [
                ':inviteeID' => $userID,
                ':inactiveUsersWithBonusPoints' => $this->inactiveUsersWithBonusPoints,
            ],
            [
                ':inviteeID' => \PDO::PARAM_INT,
                ':inactiveUsersWithBonusPoints' => Connection::PARAM_INT_ARRAY,
            ]
        )->fetchColumn();

        $aggregate = '(ci.Price * ci.Cnt * (100 - ci.Discount) / 100)';

        if ($isInvitedByInactiveUserWithBonusPoints) {
            $pointsAggregate = "({$aggregate} * 15)";

            /**
             * @see https://redmine.awardwallet.com/issues/15624
             */
            $sql = "
                select 
                    coalesce(
                        round(sum({$pointsAggregate}), 2),
                        0
                    ) as Total
                from (
                    select
                        firstPaymentDate.UserID,
                        firstPaymentDate.PayDate,
                        min(c.CartID) as FirstPaymentCartID
                    from (
                        select
                            c.UserID,
                            min(c.PayDate) as PayDate
                        from Cart c
                        join CartItem ci on 
                            c.CartID = ci.CartID
                        left join CartItem cib on
                            c.CartID = cib.CartID and
                            cib.TypeID in (:excludedCartItemTypeID)
                        where 
                            c.UserID = :inviteeID and
                            c.PayDate is not null and
                            cib.TypeID is null and
                            ci.TypeID not in (:excludedCartItemTypeID) and
                            ci.ScheduledDate is null and 
                            c.PaymentType <> :excludedCartPaymentType and
                            (ci.Price * ci.Cnt * ((100 - ci.Discount) / 100)) <> 0 
                        group by c.UserID
                    ) firstPaymentDate
                    join Cart c on
                        firstPaymentDate.UserID = c.UserID and
                        firstPaymentDate.PayDate = c.PayDate
                    group by firstPaymentDate.UserID, firstPaymentDate.PayDate
                ) matchingPaymentCart
                join CartItem ci on
                    ci.CartID = matchingPaymentCart.FirstPaymentCartID";
        } else {
            $pointsAggregate = '(case';

            foreach (array_reverse(self::REFERRAL_INCOME_TO_BONUS_RATES) as [$rateStartDate, $rate]) {
                $pointsAggregate .= "
                    when matchingPaymentCart.PayDate >= '{$rateStartDate}' then round({$aggregate} * {$rate}, 2)
                ";
            }

            $pointsAggregate .= "
                    else round({$aggregate} * 25, 2)
                end)";

            /**
             * @see https://redmine.awardwallet.com/issues/15624
             */
            $sql = "
                select 
                    round(sum(SubTotals.SubTotal), 2) as Total
                from (
                    (
                        select 
                            coalesce(
                                round(sum({$pointsAggregate}), 2),
                                0
                            ) as SubTotal
                        from (
                            select
                                firstPaymentDate.UserID,
                                firstPaymentDate.PayDate,
                                min(c.CartID) as FirstPaymentCartID
                            from (
                                select
                                    c.UserID,
                                    min(c.PayDate) as PayDate
                                from Cart c
                                join CartItem ci on 
                                    c.CartID = ci.CartID
                                left join CartItem cib on
                                    c.CartID = cib.CartID and
                                    cib.TypeID in (:excludedCartItemTypeID)
                                where 
                                    c.UserID = :inviteeID and
                                    c.PayDate is not null and
                                    cib.TypeID is null and
                                    ci.TypeID not in (:excludedCartItemTypeID) and
                                    ci.ScheduledDate is null and 
                                    c.PaymentType <> :excludedCartPaymentType and
                                    (ci.Price * ci.Cnt * ((100 - ci.Discount) / 100)) <> 0 
                                group by c.UserID
                            ) firstPaymentDate
                            join Cart c on
                                firstPaymentDate.UserID = c.UserID and
                                firstPaymentDate.PayDate = c.PayDate
                            group by firstPaymentDate.UserID, firstPaymentDate.PayDate 
                            having 
                                firstPaymentDate.PayDate >= :firstPayOnlyBonusAccountingStartDate
                        ) matchingPaymentCart
                        join CartItem ci on
                            ci.CartID = matchingPaymentCart.FirstPaymentCartID
                    )
                    union 
                    (
                        select
                            coalesce(
                                round(sum({$pointsAggregate}), 2),
                                0
                            ) as SubTotal
                        from Cart matchingPaymentCart
                        join CartItem ci on
                            ci.CartID = matchingPaymentCart.CartID
                        left join CartItem cib on
                            ci.CartID = cib.CartID and
                            cib.TypeID in (:excludedCartItemTypeID)
                        where
                            matchingPaymentCart.UserID = :inviteeID and
                            matchingPaymentCart.PayDate is not null and
                            matchingPaymentCart.PayDate < :firstPayOnlyBonusAccountingStartDate and
                            cib.CartItemID is null and
                            ci.TypeID not in (:excludedCartItemTypeID) and
                            ci.ScheduledDate is null and 
                            matchingPaymentCart.PaymentType <> :excludedCartPaymentType and
                            (ci.Price * ci.Cnt * ((100-ci.Discount)/100)) <> 0
                        group by matchingPaymentCart.UserID  
                    ) 
                ) SubTotals";
        }

        $stmt = $this->connection->executeQuery($sql,
            [
                ':inviteeID' => $userID,
                ':excludedCartPaymentType' => PAYMENTTYPE_BUSINESS_BALANCE,
                ':excludedCartItemTypeID' => self::getExcludedCartItemTypes(),
                ':firstPayOnlyBonusAccountingStartDate' => self::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE,
            ],
            [
                ':inviteeID' => \PDO::PARAM_INT,
                ':excludedCartPaymentType' => \PDO::PARAM_INT,
                ':excludedCartItemTypeID' => Connection::PARAM_INT_ARRAY,
                ':firstPayOnlyBonusAccountingStartDate' => \PDO::PARAM_STR,
            ]
        );
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) round($result['Total']);
    }

    /**
     * Returns all *money* users invited by *inviter* pay.
     *
     * @param $userID inviter
     * @param string|null $fromDate
     * @param string|null $toDate
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalReferralIncomeByUser($userID, $fromDate = null, $toDate = null): float
    {
        return (float) $this->connection->executeQuery("
            select
               coalesce(
                   round(sum((ci.Cnt * ci.Price * (100 - ci.Discount) / 100)), 2),
                   0
               ) as Total
           from Invites i
           join Cart matchingPaymentCart on
               matchingPaymentCart.UserID = i.InviteeID
           join CartItem ci on
               ci.CartID = matchingPaymentCart.CartID
           left join CartItem cib on
               ci.CartID = cib.CartID and
               cib.TypeID in (:excludedCartItemTypeID)
           where
               i.InviterID = :inviterID and
               i.InviteeID is not null and
               i.InviteeID <> :inviterID and
               matchingPaymentCart.PayDate is not null and
               cib.CartItemID is null and
               ci.TypeID not in (:excludedCartItemTypeID) and
               ci.ScheduledDate is null and
               matchingPaymentCart.PaymentType <> :excludedCartPaymentType and
               (ci.Price * ci.Cnt * ((100-ci.Discount)/100)) <> 0 " .
               (isset($fromDate) ? ' and matchingPaymentCart.PayDate >= :fromDate' : '') .
               (isset($toDate) ? ' and matchingPaymentCart.PayDate <= :toDate' : ''),
            array_merge(
                [
                    ':inviterID' => $userID,
                    ':excludedCartPaymentType' => PAYMENTTYPE_BUSINESS_BALANCE,
                    ':excludedCartItemTypeID' => self::getExcludedCartItemTypes(),
                ],
                isset($fromDate) ? [':fromDate' => new \DateTime($fromDate)] : [],
                isset($toDate) ? [':toDate' => new \DateTime($toDate)] : []
            ),
            array_merge(
                [
                    ':inviterID' => \PDO::PARAM_INT,
                    ':excludedCartPaymentType' => \PDO::PARAM_INT,
                    ':excludedCartItemTypeID' => Connection::PARAM_INT_ARRAY,
                ],
                isset($fromDate) ? [':fromDate' => 'datetime'] : [],
                isset($toDate) ? [':toDate' => 'datetime'] : []
            )
        )->fetchColumn();
    }

    public static function getExcludedCartItemTypes(): array
    {
        return \array_merge([Booking::TYPE], At201Items::getTypes());
    }

    /**
     * Returns *money (eligible for bonus)* / *bonus* users invited by *inviter* pay / brought.
     *
     * @param $userID inviter
     * @param null $fromDate
     * @param null $toDate
     * @param bool $inPoints
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doGetTotalReferralIncomeByUser($userID, $fromDate = null, $toDate = null, $inPoints = false): float
    {
        $aggregate = "(ci.Cnt * ci.Price * (100 - ci.Discount) / 100)";

        if (in_array($userID, $this->inactiveUsersWithBonusPoints)) {
            if ($inPoints) {
                $aggregate = "({$aggregate} * 15)";
            }

            /**
             * @see https://redmine.awardwallet.com/issues/15624
             */
            $sql = "
                   select 
                       coalesce(
                           round(sum({$aggregate}), 2),
                           0
                       ) as Total
                   from (
                       select
                           firstPaymentDate.UserID,
                           firstPaymentDate.PayDate,
                           min(c.CartID) as FirstPaymentCartID
                       from (
                           select
                               i.InviteeID as UserID,
                               min(c.PayDate) as PayDate
                           from Invites i
                           join Cart c on 
                               c.UserID = i.InviteeID
                           join CartItem ci on 
                               c.CartID = ci.CartID
                           left join CartItem cib on
                               c.CartID = cib.CartID and
                               cib.TypeID in (:excludedCartItemTypeID)
                           where 
                               i.InviterID = :inviterID and
                               i.InviteeID is not null and
                               i.InviteeID <> :inviterID and
                               c.PayDate is not null and
                               cib.TypeID is null and
                               ci.TypeID not in (:excludedCartItemTypeID) and
                               ci.ScheduledDate is null and 
                               c.PaymentType <> :excludedCartPaymentType and
                               (ci.Price * ci.Cnt * ((100 - ci.Discount) / 100)) <> 0 
                           group by i.InviteeID
                       ) firstPaymentDate
                       join Cart c on
                           firstPaymentDate.UserID = c.UserID and
                           firstPaymentDate.PayDate = c.PayDate
                       group by firstPaymentDate.UserID, firstPaymentDate.PayDate
                       having 
                           1 = 1 " .
                           (isset($fromDate) ? ' and firstPaymentDate.PayDate >= ?' : '') .
                           (isset($toDate) ? '   and firstPaymentDate.PayDate <= ?' : '') .
                   ") matchingPaymentCart
                   join CartItem ci on
                       ci.CartID = matchingPaymentCart.FirstPaymentCartID";
        } else {
            if ($inPoints) {
                $pointsAggregate = '(case';

                foreach (array_reverse(self::REFERRAL_INCOME_TO_BONUS_RATES) as [$rateStartDate, $rate]) {
                    $pointsAggregate .= "
                           when matchingPaymentCart.PayDate >= '{$rateStartDate}' then round({$aggregate} * {$rate}, 2)
                       ";
                }

                $pointsAggregate .= "
                           else round({$aggregate} * 25, 2)
                       end)";
                $aggregate = $pointsAggregate;
            }

            /**
             * @see https://redmine.awardwallet.com/issues/15624
             */
            $sql = "
                   select 
                       round(sum(SubTotals.SubTotal), 2) as Total
                   from (
                       (
                           select 
                               coalesce(
                                   round(sum({$aggregate}), 2),
                                   0
                               ) as SubTotal
                           from (
                               select
                                   firstPaymentDate.UserID,
                                   firstPaymentDate.PayDate,
                                   min(c.CartID) as FirstPaymentCartID
                               from (
                                   select
                                       i.InviteeID as UserID,
                                       min(c.PayDate) as PayDate
                                   from Invites i
                                   join Cart c on 
                                       c.UserID = i.InviteeID
                                   join CartItem ci on 
                                       c.CartID = ci.CartID
                                   left join CartItem cib on
                                       c.CartID = cib.CartID and
                                       cib.TypeID in (:excludedCartItemTypeID)
                                   where 
                                       i.InviterID = :inviterID and
                                       i.InviteeID is not null and
                                       i.InviteeID <> :inviterID and
                                       c.PayDate is not null and
                                       cib.TypeID is null and
                                       ci.TypeID not in (:excludedCartItemTypeID) and
                                       ci.ScheduledDate is null and 
                                       c.PaymentType <> :excludedCartPaymentType and
                                       (ci.Price * ci.Cnt * ((100 - ci.Discount) / 100)) <> 0 
                                   group by i.InviteeID
                               ) firstPaymentDate
                               join Cart c on
                                   firstPaymentDate.UserID = c.UserID and
                                   firstPaymentDate.PayDate = c.PayDate
                               group by firstPaymentDate.UserID, firstPaymentDate.PayDate 
                               having 
                                   firstPaymentDate.PayDate >= :firstPayOnlyBonusAccountingStartDate" .
                                   (isset($fromDate) ? ' and firstPaymentDate.PayDate >= :fromDate' : '') .
                                   (isset($toDate) ? ' and firstPaymentDate.PayDate <= :toDate' : '') .
                           ") matchingPaymentCart
                           join CartItem ci on
                               ci.CartID = matchingPaymentCart.FirstPaymentCartID
                       )
                       union 
                       (
                           select
                               coalesce(
                                   round(sum({$aggregate}), 2),
                                   0
                               ) as SubTotal
                           from Invites i
                           join Cart matchingPaymentCart on
                               matchingPaymentCart.UserID = i.InviteeID
                           join CartItem ci on
                               ci.CartID = matchingPaymentCart.CartID
                           left join CartItem cib on
                               ci.CartID = cib.CartID and
                               cib.TypeID in (:excludedCartItemTypeID)
                           where
                               i.InviterID = :inviterID and
                               i.InviteeID is not null and
                               i.InviteeID <> :inviterID and
                               matchingPaymentCart.PayDate is not null and
                               matchingPaymentCart.PayDate < :firstPayOnlyBonusAccountingStartDate and
                               cib.CartItemID is null and
                               ci.TypeID not in (:excludedCartItemTypeID) and
                               ci.ScheduledDate is null and 
                               matchingPaymentCart.PaymentType <> :excludedCartPaymentType and
                               (ci.Price * ci.Cnt * ((100-ci.Discount)/100)) <> 0 " .
                               (isset($fromDate) ? ' and matchingPaymentCart.PayDate >= :fromDate' : '') .
                               (isset($toDate) ? ' and matchingPaymentCart.PayDate <= :toDate' : '') . "
                            group by i.InviterID  
                       ) 
                   ) SubTotals    
               ";
        }

        $stmt = $this->connection->executeQuery($sql,
            array_merge(
                [
                    ':inviterID' => $userID,
                    ':excludedCartPaymentType' => PAYMENTTYPE_BUSINESS_BALANCE,
                    ':excludedCartItemTypeID' => self::getExcludedCartItemTypes(),
                    ':firstPayOnlyBonusAccountingStartDate' => self::FIRST_PAY_ONLY_BONUS_ACCOUNTING_STRATEGY_START_DATE,
                ],
                isset($fromDate) ? [':fromDate' => new \DateTime($fromDate)] : [],
                isset($toDate) ? [':toDate' => new \DateTime($toDate)] : []
            ),
            array_merge(
                [
                    ':inviterID' => \PDO::PARAM_INT,
                    ':excludedCartPaymentType' => \PDO::PARAM_INT,
                    ':excludedCartItemTypeID' => Connection::PARAM_INT_ARRAY,
                    ':firstPayOnlyBonusAccountingStartDate' => \PDO::PARAM_STR,
                ],
                isset($fromDate) ? [':fromDate' => 'datetime'] : [],
                isset($toDate) ? [':toDate' => 'datetime'] : []
            )
        );
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (float) $result['Total'];
    }
}
