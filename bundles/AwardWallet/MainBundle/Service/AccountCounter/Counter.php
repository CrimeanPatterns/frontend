<?php

namespace AwardWallet\MainBundle\Service\AccountCounter;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class Counter
{
    private Connection $connection;

    private EntityManagerInterface $em;

    public function __construct(Connection $connection, EntityManagerInterface $em)
    {
        $this->connection = $connection;
        $this->em = $em;
    }

    public function calculate(int $userId, ?string $accountsFilter = null, ?string $couponsFilter = null): Summary
    {
        $user = $this->em->getRepository(Usr::class)->find($userId);

        if (!$user) {
            return new Summary($userId, []);
        }

        $providerFilter = sprintf(
            ' AND ((%s) OR p.State = %d)',
            $user->getProviderFilter(),
            PROVIDER_RETAIL
        );
        $accountStateFilter = ' AND a.State > ' . ACCOUNT_DISABLED;
        $sql = "
            SELECT
                'Account' AS TableName,
                a.AccountID AS ID,
                a.ProviderID,
                NULL AS ParentID,
                0 AS Shared,
                NULL AS SharedUserAgentID,
                a.UserID,
                a.UserAgentID
            FROM
                Account a
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
            WHERE
                a.UserID = $userId
                $providerFilter
                $accountStateFilter
                " . ($accountsFilter ?? '') . "
                
            UNION ALL
            
            SELECT
                'Account' AS TableName,
                a.AccountID AS ID,
                a.ProviderID,
                NULL AS ParentID,
                1 AS Shared,
                ua.UserAgentID AS SharedUserAgentID,
                a.UserID,
                a.UserAgentID
            FROM
                AccountShare ash
                JOIN Account a ON ash.AccountID = a.AccountID
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID
                LEFT JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
            WHERE
                ua.AgentID = $userId
                AND ua.IsApproved = 1
                AND (au.IsApproved = 1 OR au.IsApproved IS NULL)
                $providerFilter
                $accountStateFilter
                " . ($accountsFilter ?? '') . "
                
            UNION ALL
            
            SELECT
                'Coupon' AS TableName,
                a.ProviderCouponID AS ID,
                NULL AS ProviderID,
                a.AccountID AS ParentID,
                0 AS Shared,
                NULL AS SharedUserAgentID,
                a.UserID,
                a.UserAgentID
            FROM
                ProviderCoupon a
            WHERE
                a.UserID = $userId
                " . ($couponsFilter ?? '') . "
                
            UNION ALL
            
            SELECT
                'Coupon' AS TableName,
                a.ProviderCouponID AS ID,
                NULL AS ProviderID,
                a.AccountID AS ParentID,
                1 AS Shared,
                ua.UserAgentID AS SharedUserAgentID,
                a.UserID,
                a.UserAgentID
            FROM
                ProviderCouponShare ash
                JOIN ProviderCoupon a ON ash.ProviderCouponID = a.ProviderCouponID
                JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID
                LEFT JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
            WHERE
                ua.AgentID = $userId
                AND ua.IsApproved = 1
                AND (au.IsApproved = 1 OR au.IsApproved IS NULL)
                " . ($couponsFilter ?? '') . "
        ";

        return new Summary($userId, stmtAssoc($this->connection->executeQuery($sql)));
    }
}
