<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class LocationRepository extends EntityRepository
{
    /**
     * @param string $filter
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function getLocationsByUser(Usr $user, $filter = "")
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                $this->getSQLQuery($user, $filter),
                ["userID" => $user->getUserid()],
                ["userID" => \PDO::PARAM_INT]
            );
    }

    public function getCountTotal(Usr $user)
    {
        return (int) $this->getEntityManager()->getConnection()
            ->executeQuery(
                "SELECT COUNT(*) AS Count FROM (" . $this->getSQLQuery($user) . ") t",
                ["userID" => $user->getUserid()],
                ["userID" => \PDO::PARAM_INT]
            )->fetchColumn();
    }

    public function getCountTracked(Usr $user)
    {
        $filter = " AND COALESCE(ls.Tracked, 0) = 1";

        return (int) $this->getEntityManager()->getConnection()
            ->executeQuery(
                "SELECT COUNT(*) AS Count FROM (" . $this->getSQLQuery($user, $filter) . ") t",
                ["userID" => $user->getUserid()],
                ["userID" => \PDO::PARAM_INT]
            )->fetchColumn();
    }

    public function getSQLQuery(Usr $user, $filter = "")
    {
        $providerFilter = sprintf(
            ' AND ((%s) OR p.State = %d)',
            $user->getProviderFilter(),
            PROVIDER_RETAIL
        );
        $stateFilter = "";
        //        $stateFilter = "AND a.State > " . ACCOUNT_DISABLED;

        $rightsFilter = " AND ua.AccessLevel IN (" . implode(", ", [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]) . ")";

        $locationFields = "
            l.LocationID AS LocationID,
            l.Name AS LocationName,
            l.Lat AS Lat,
            l.Lng AS Lng,
            l.Radius AS Radius,
            l.CreationDate AS CreationDate,
            COALESCE(ls.Tracked, 0) AS Tracked
        ";

        $queries = [];
        $queries['Account'] = "
            SELECT 
                'Account' AS AccountType,
                a.AccountID AS AccountID,
                NULL AS SubAccountID,
                a.AccountID AS ShortAccountID,
                CONCAT('a', a.AccountID) AS ComplexAccountID,
                COALESCE(p.DisplayName, a.ProgramName) AS ProgramName,
                p.ProviderID AS ProviderID,
                p.Code AS ProviderCode,
                p.BackgroundColor,
                p.FontColor,
                $locationFields
            FROM 
                Account a
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN Location l ON a.AccountID = l.AccountID
                LEFT JOIN LocationSetting ls ON l.LocationID = ls.LocationID AND ls.UserID = :userID
            WHERE  
                a.UserID = :userID
                $providerFilter
                $stateFilter
                $filter
        ";
        $queries['AccountShare'] = "
            SELECT 
                'AccountShare' AS AccountType,
                a.AccountID AS AccountID,
                NULL AS SubAccountID,
                a.AccountID AS ShortAccountID,
                CONCAT('a', a.AccountID) AS ComplexAccountID,
                COALESCE(p.DisplayName, a.ProgramName) AS ProgramName,
                p.ProviderID AS ProviderID,
                p.Code AS ProviderCode,
                p.BackgroundColor,
                p.FontColor,
                $locationFields
            FROM
                AccountShare ash
                JOIN Account a ON ash.AccountID = a.AccountID
                JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID AND ua.IsApproved = 1
                JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID AND au.IsApproved = 1
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN Location l ON a.AccountID = l.AccountID
                LEFT JOIN LocationSetting ls ON l.LocationID = ls.LocationID AND ls.UserID = :userID
            WHERE
                ua.AgentID = :userID
                $rightsFilter
                $providerFilter
                $stateFilter
                $filter
        ";
        $queries['SubAccount'] = "
            SELECT 
                'SubAccount' AS AccountType,
                sa.AccountID AS AccountID,
                sa.SubAccountID AS SubAccountID,
                CONCAT_WS('.', sa.AccountID, sa.SubAccountID) AS ShortAccountID,
                CONCAT('a', sa.AccountID) AS ComplexAccountID,
                COALESCE(sa.DisplayName, p.DisplayName, a.ProgramName) AS ProgramName,
                p.ProviderID AS ProviderID,
                p.Code AS ProviderCode,
                p.BackgroundColor,
                p.FontColor,
                $locationFields
            FROM 
                Account a
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN SubAccount sa ON sa.AccountID = a.AccountID
                JOIN Location l ON sa.SubAccountID = l.SubAccountID
                LEFT JOIN LocationSetting ls ON l.LocationID = ls.LocationID AND ls.UserID = :userID
            WHERE  
                a.UserID = :userID
                $providerFilter
                $stateFilter
                $filter
        ";
        $queries['SubAccountShare'] = "
            SELECT 
                'SubAccountShare' AS AccountType,
                sa.AccountID AS AccountID,
                sa.SubAccountID AS SubAccountID,
                CONCAT_WS('.', sa.AccountID, sa.SubAccountID) AS ShortAccountID,
                CONCAT('a', sa.AccountID) AS ComplexAccountID,
                COALESCE(sa.DisplayName, p.DisplayName, a.ProgramName) AS ProgramName,
                p.ProviderID AS ProviderID,
                p.Code AS ProviderCode,
                p.BackgroundColor,
                p.FontColor,
                $locationFields
            FROM
                AccountShare ash
                JOIN Account a ON ash.AccountID = a.AccountID
                JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID AND ua.IsApproved = 1
                JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID AND au.IsApproved = 1
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN SubAccount sa ON sa.AccountID = a.AccountID
                JOIN Location l ON sa.SubAccountID = l.SubAccountID
                LEFT JOIN LocationSetting ls ON l.LocationID = ls.LocationID AND ls.UserID = :userID
            WHERE
                ua.AgentID = :userID
                $rightsFilter
                $providerFilter
                $stateFilter
                $filter
        ";
        $queries['Coupon'] = "
            SELECT 
                'Coupon' AS AccountType,
                a.ProviderCouponID AS AccountID,
                NULL AS SubAccountID,
                a.ProviderCouponID AS ShortAccountID,
                CONCAT('c', a.ProviderCouponID) AS ComplexAccountID,
                a.ProgramName AS ProgramName,
                NULL AS ProviderID,
                NULL AS ProviderCode,
                NULL AS BackgroundColor,
                NULL AS FontColor,
                $locationFields
            FROM 
                ProviderCoupon a
                JOIN Location l ON a.ProviderCouponID = l.ProviderCouponID
                LEFT JOIN LocationSetting ls ON l.LocationID = ls.LocationID AND ls.UserID = :userID
            WHERE  
                a.UserID = :userID
                $filter
        ";
        $queries['CouponShare'] = "
            SELECT 
                'CouponShare' AS AccountType,
                a.ProviderCouponID AS AccountID,
                NULL AS SubAccountID,
                a.ProviderCouponID AS ShortAccountID,
                CONCAT('c', a.ProviderCouponID) AS ComplexAccountID,
                a.ProgramName AS ProgramName,
                NULL AS ProviderID,
                NULL AS ProviderCode,
                NULL AS BackgroundColor,
                NULL AS FontColor,
                $locationFields
            FROM 
                ProviderCouponShare pcsh
                JOIN ProviderCoupon a ON pcsh.ProviderCouponID = a.ProviderCouponID
                JOIN UserAgent ua on pcsh.UserAgentID = ua.UserAgentID AND ua.IsApproved = 1
                JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID AND au.IsApproved = 1
                JOIN Location l ON a.ProviderCouponID = l.ProviderCouponID
                LEFT JOIN LocationSetting ls ON l.LocationID = ls.LocationID AND ls.UserID = :userID
            WHERE  
                ua.AgentID = :userID
                $rightsFilter
                $filter
        ";

        return implode(" UNION ", $queries) . " ORDER BY Tracked DESC, LocationName ASC";
    }
}
