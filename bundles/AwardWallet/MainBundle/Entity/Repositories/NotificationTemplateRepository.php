<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * NotificationTemplateRepository.
 */
class NotificationTemplateRepository extends EntityRepository
{
    public const MIN_MOBILE_VERSION = '3.19.0';

    public const TEMPLATE_OFFSET = 1000000;

    public function getNotificationTemplateCount()
    {
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery("SELECT COUNT(*) AS cnt FROM NotificationTemplate");
        $r = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ($r !== false) ? $r['cnt'] : 0;
    }

    public function getUserGroups()
    {
        $groups = [
            'all' => 'All Registered Users',
            'plus' => 'All AWPlus Users',
            'no_plus' => 'All Regular Users',
            'no_subs' => 'All No Subscription Users',
            'us' => 'US Based Users',
            'us_plus' => 'US Based AWPlus Users',
            'us_no_plus' => 'US Based Regular Users',
            'all_anon' => 'All Anonymous Users',
            'us_anon' => 'US Anonymous Users',
            'business' => 'Business Users',
        ];

        foreach ($groups as $k => $v) {
            $cnt = $this->getUserGroupCount($k);
            $groups[$k] = $v . ' (' . $cnt . ')';
        }

        return $groups;
    }

    public function getStaffUserIds()
    {
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery("
            SELECT distinct md.UserID AS UserID
            FROM MobileDevice md
                join GroupUserLink gul on md.UserID = gul.UserID
            where gul.SiteGroupID = 53
        ");
        $ret = [];

        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ret[] = $r['UserID'];
        }

        return $ret;
    }

    public function getUsersFromGroup($group, $excludeTemplateId = null, $deviceType = null)
    {
        $connection = $this->getEntityManager()->getConnection();

        $excludeJoinSQL = '';
        $excludeWhereSQL = '';

        if (!empty($excludeTemplateId) && is_numeric($excludeTemplateId)) {
            $excludeJoinSQL = 'LEFT JOIN EmailLog el on el.UserID = md.UserID and el.MessageKind = ' . (intval($excludeTemplateId) + self::TEMPLATE_OFFSET);
            $excludeWhereSQL = 'AND el.EmailLogID is null';
        }

        $devicesWhereSQL = '';

        if (!empty($deviceType) && is_array($deviceType)) {
            $presentedDesktopTypes = array_intersect($deviceType, MobileDevice::TYPES_DESKTOP);

            if ($presentedDesktopTypes) {
                $presentedDesktopTypesSql = it($presentedDesktopTypes)->map('\\intval')->joinToString(', ');
                $otherPresentedTypes = array_diff($deviceType, $presentedDesktopTypes);
                $otherPresentedTypesSql = it($otherPresentedTypes)->map('\\intval')->joinToString(', ');

                $devicesWhereSQL = "AND 
                    (
                        (
                            md.DeviceType in ({$presentedDesktopTypesSql}) AND
                            md.AppVersion like 'web%' 
                        ) OR
                        " . ($otherPresentedTypes ? "md.DeviceType in ({$otherPresentedTypesSql})" : "1=0") . '
                    )';
            } else {
                $devicesWhereSQL = 'AND md.DeviceType in (' . it($deviceType)->map('\\intval')->joinToString(', ') . ')';
            }
        } elseif (!empty($deviceType) && is_numeric($deviceType)) {
            $devicesWhereSQL = 'AND md.DeviceType = ' . intval($deviceType);

            if (in_array($deviceType, MobileDevice::TYPES_DESKTOP)) {
                $devicesWhereSQL .= ' AND md.AppVersion like "web%" ';
            }
        }

        $ret = [];

        switch ($group) {
            case 'all':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        {$excludeJoinSQL}
                    where md.UserID is not null
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'plus':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                        {$excludeJoinSQL}
                    where u.AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . "
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'no_plus':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                        {$excludeJoinSQL}
                    where u.AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'no_subs':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                        {$excludeJoinSQL}
                    where u.Subscription is null
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'us_plus':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                        {$excludeJoinSQL}
                    where (u.CountryID = 230 or md.CountryID = 230) and u.AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . "
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'us_no_plus':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                        {$excludeJoinSQL}
                    where (u.CountryID = 230 or md.CountryID = 230) and u.AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'all_anon':
                $stmt = $connection->executeQuery("
                    SELECT md.IP AS UserID
                    FROM MobileDevice md
                    where UserID is null
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'us':
                $stmt = $connection->executeQuery("
                    SELECT distinct md.UserID AS UserID
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                        {$excludeJoinSQL}
                    where u.CountryID = 230 or md.CountryID = 230
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'us_anon':
                $stmt = $connection->executeQuery("
                    SELECT md.IP AS UserID
                    FROM MobileDevice md
                    where md.UserID is null and md.CountryID = 230
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");

                break;

            case 'business':
                $stmt = $connection->executeQuery("
                    SELECT md.UserID as UserID,
                        SUM(IF(ap.Val IS NOT NULL AND LOCATE('business', ap.Val) > 0, 1, 0)) as s
                    FROM MobileDevice md
                        join Account a on md.UserID = a.UserID
                        join AccountProperty ap on a.AccountID = ap.AccountID
                        {$excludeJoinSQL}
                    where ap.ProviderPropertyID = 3928 and a.ProviderID in (84,503,75,364,123,104,87,49,103)
                        {$excludeWhereSQL}
                        {$devicesWhereSQL} and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                    group by md.UserID
                    having SUM(IF(ap.Val IS NOT NULL AND LOCATE('business', ap.Val) > 0, 1, 0)) > 0
                ");

                break;

            default:
                return [];
        }

        while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ret[] = $r['UserID'];
        }

        return $ret;
    }

    public function recordLogNotification(Usr $user, $kind)
    {
        $kind += self::TEMPLATE_OFFSET; // todo i know its ugly

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery("
            SELECT
                *
            FROM
                EmailLog
            WHERE
                UserID = ?
                AND MessageKind = ?
        ", [$user->getUserid(), $kind]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $connection->update('EmailLog',
                ['MessageCount' => $row['MessageCount'] + 1],
                ['EmailLogID' => $row['EmailLogID']]
            );
        } else {
            $connection->insert('EmailLog', [
                'UserID' => $user->getUserid(),
                'MessageKind' => $kind,
                'EmailDate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'MessageCount' => 1,
            ]);
        }
    }

    public function setState($notificationId, $state)
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->update('NotificationTemplate',
            ['State' => $state],
            ['NotificationTemplateID' => $notificationId]
        );
    }

    public function setQueueStat($notificationId, $stat)
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->update('NotificationTemplate',
            ['QueueStat' => $stat],
            ['NotificationTemplateID' => $notificationId]
        );
    }

    public function setSendStat($notificationId, $stat)
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->update('NotificationTemplate',
            ['SendStat' => $stat],
            ['NotificationTemplateID' => $notificationId]
        );
    }

    private function minMobileVersionSql($minMobileVersion, $versionFieldAlias, $typeFieldAlias, $trackedFieldAlias)
    {
        $mobileTypesSql = implode(', ', MobileDevice::TYPES_MOBILE);
        $desktopTypesSql = implode(', ', MobileDevice::TYPES_DESKTOP);
        $minMobileVersionParts = explode('.', $minMobileVersion);
        $minMobileVersionInteger =
            $minMobileVersionParts[0] * (10 ** 6) +
            $minMobileVersionParts[1] * (10 ** 3) +
            $minMobileVersionParts[2];

        $versionBaseSql = /** @lang MySQL */ "substring({$versionFieldAlias}, 1, locate('+', {$versionFieldAlias}) - 1)";
        $majorSql = /** @lang MySQL */ "substring_index({$versionBaseSql}, '.', 1)";
        $minorSql = /** @lang MySQL */ "substring_index(substring_index({$versionBaseSql}, '.', -2), '.', 1)";
        $patchSql = /** @lang MySQL */ "substring_index({$versionBaseSql}, '.', -1)";

        return /** @lang MySQL */ "
            (
                (
                    {$typeFieldAlias} IN ({$desktopTypesSql}) OR
                    {$typeFieldAlias} IN ({$mobileTypesSql})
                ) AND
                {$trackedFieldAlias} = 1
            )";
    }

    private function getUserGroupCount($group)
    {
        $connection = $this->getEntityManager()->getConnection();

        switch ($group) {
            case 'all':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct UserID) AS cnt FROM MobileDevice
                    where UserID is not null and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'AppVersion', 'DeviceType', 'Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'plus':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct md.UserID) AS cnt
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                    where u.AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . " and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'no_plus':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct md.UserID) AS cnt
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                    where u.AccountLevel = " . ACCOUNT_LEVEL_FREE . " and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'no_subs':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct md.UserID) AS cnt
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                    where u.Subscription is null and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'us_plus':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct md.UserID) AS cnt
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                    where (u.CountryID = 230 or md.CountryID = 230) and u.AccountLevel = " . ACCOUNT_LEVEL_AWPLUS . " and
                   " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . " 
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'us_no_plus':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct md.UserID) AS cnt
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                    where (u.CountryID = 230 or md.CountryID = 230) and u.AccountLevel = " . ACCOUNT_LEVEL_FREE . " and 
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'all_anon':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(*) AS cnt FROM MobileDevice
                    where UserID is null and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'AppVersion', 'DeviceType', 'Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'us':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(distinct md.UserID) AS cnt
                    FROM MobileDevice md
                        join Usr u on md.UserID = u.UserID
                    where (u.CountryID = 230 or md.CountryID = 230) and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'us_anon':
                $stmt = $connection->executeQuery("
                    SELECT COUNT(*) AS cnt
                    FROM MobileDevice md
                    where md.UserID is null and md.CountryID = 230 and
                    " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            case 'business':
                $stmt = $connection->executeQuery("
                    select count(*) as cnt from (
                        SELECT SUM(IF(ap.Val IS NOT NULL AND LOCATE('business', ap.Val) > 0, 1, 0)) as s
                        FROM MobileDevice md
                            join Account a on md.UserID = a.UserID
                            join AccountProperty ap on a.AccountID = ap.AccountID
                        where ap.ProviderPropertyID = 3928 and a.ProviderID in (84,503,75,364,123,104,87,49,103) and
                        " . $this->minMobileVersionSql(self::MIN_MOBILE_VERSION, 'md.AppVersion', 'md.DeviceType', 'md.Tracked') . "
                        group by md.UserID
                        having SUM(IF(ap.Val IS NOT NULL AND LOCATE('business', ap.Val) > 0, 1, 0)) > 0
                    ) as s
                ");
                $r = $stmt->fetch(\PDO::FETCH_ASSOC);

                return ($r !== false) ? $r['cnt'] : 0;

            default:
                return 0;
        }
    }
}
