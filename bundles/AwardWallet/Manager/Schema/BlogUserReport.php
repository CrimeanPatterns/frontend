<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\Blog\UpgradeReaders;

class BlogUserReport extends \TBaseSchema
{
    public function TuneList(&$list)
    {
        parent::TuneList($list);

        if (isset($_GET['real']) || isset($_GET['fake'])) {
            $list->Fields = [
                'UserID' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'User',
                    'DisplayFormat' => 'string',
                    'Sort' => 'u.UserID DESC',
                ],
                '_sumEarnings' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Sum Earning',
                    'DisplayFormat' => 'string',
                    'Sort' => '_sumEarnings DESC',
                ],
                '_countReport' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'PageViews',
                    'DisplayFormat' => 'string',
                    'Sort' => '_countReport DESC',
                ],
                '_countPost' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Unique Posts Read',
                    'DisplayFormat' => 'string',
                    'Sort' => '_countPost DESC',
                ],
                '_timeVisit' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Time Visit in Minute',
                    'DisplayFormat' => 'string',
                    'Sort' => '_timeVisit DESC',
                ],
            ];
        } elseif (isset($_GET['group']) || isset($_GET['group2']) || isset($_GET['group3'])) {
            $list->DefaultSort = '_sumEarnings';
            $list->Fields = [
                'UserID' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'User',
                    'DisplayFormat' => 'string',
                    'Sort' => 'u.UserID DESC',
                ],
                '_sumEarnings' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Sum Earning',
                    'DisplayFormat' => 'string',
                    'Sort' => '_sumEarnings DESC',
                ],
                '_countReport' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'PageViews',
                    'DisplayFormat' => 'string',
                    'Sort' => '_countReport DESC',
                ],
                '_countPost' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Unique Posts Read',
                    'DisplayFormat' => 'string',
                    'Sort' => '_countPost DESC',
                ],
                '_timeVisit' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Time Visit in Minute',
                    'DisplayFormat' => 'string',
                    'Sort' => '_timeVisit DESC',
                ],
                'AccountLevel' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'AccountLevel',
                    'DisplayFormat' => 'string',
                    'Sort' => 'AccountLevel ASC',
                    'Options' => ['' => '', ACCOUNT_LEVEL_FREE => 'Free', ACCOUNT_LEVEL_AWPLUS => 'AW Plus', ACCOUNT_LEVEL_BUSINESS => 'Business'],
                ],
                'Accounts' => [
                    'Type' => 'integer',
                    'Required' => false,
                    'Caption' => 'Accounts',
                    'DisplayFormat' => 'string',
                    'Sort' => 'Accounts ASC',
                ],
            ];
        } else {
            $list->Fields['InTime']['Type'] =
            $list->Fields['OutTime']['Type'] = 'string';
        }
        $list->Fields['UserID']['FilterField'] = 'bup.UserID';

        $list->MultiEdit =
        $list->InplaceEdit =
        $list->CanAdd =
        $list->ShowExport =
        $list->ShowImport = false;

        $list->SQL = $this->getSqlFields($list);
        $list->DefaultSort = 'UserID';
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        return parent::guessFieldOptions($field, $fieldInfo);
    }

    private function getSqlFields($list)
    {
        $minDate = new \DateTime(UpgradeReaders::CONDITION_DATE);
        $sqlReal = "
            SELECT
                    u.UserID, u.FirstName, u.LastName, null as BlogUserReportID,
                    SUM(qt.Earnings) AS _sumEarnings,
                    (SELECT COUNT(*) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countReport,
                    (SELECT COUNT(DISTINCT BlogPostID) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countPost,
                    (SELECT SUM(UNIX_TIMESTAMP(bup_tv.OutTime) - UNIX_TIMESTAMP(bup_tv.InTime)) / 60 FROM BlogUserReport bup_tv WHERE u.UserID = bup_tv.UserID AND bup_tv.OutTime IS NOT NULL) AS _timeVisit
            FROM Usr u
            JOIN QsTransaction qt ON (qt.UserID = u.UserID)
            WHERE
                    u.AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                AND u.Subscription IS NULL
                AND u.Accounts >= " . UpgradeReaders::CONDITION_MIN_ACCOUNTS . "
                AND qt.ClickDate >= '" . $minDate->format('Y-m-d 00:00') . "'
            GROUP BY u.UserID
            HAVING (
                    _sumEarnings >= " . UpgradeReaders::CONDITION_MIN_EARNING_SUM . "
                AND _countReport >= " . UpgradeReaders::CONDITION_MIN_VISIT . "
                AND _countPost >= " . UpgradeReaders::CONDITION_MIN_POST_READ . "
                AND _timeVisit >= " . UpgradeReaders::CONDITION_MIN_TIME_IN_MINUTE . "
            )
        ";

        if (isset($_GET['group'])) {
            return '
                SELECT
                       null as BlogUserReportID, 
                       u.UserID, u.FirstName, u.LastName, u.AccountLevel, u.Accounts,
                       ROUND(bup._timeVisit, 2) AS _timeVisit,
                       qt._sumEarnings
                FROM Usr u
                LEFT JOIN (
                    SELECT bupIn.UserID, (SUM(UNIX_TIMESTAMP(bupIn.OutTime) - UNIX_TIMESTAMP(bupIn.InTime)) / 60) AS _timeVisit FROM BlogUserReport bupIn GROUP BY bupIn.UserID
                ) AS bup ON (u.UserID = bup.UserID)
                LEFT JOIN (
                    SELECT qtIn.UserID, SUM(qtIn.Earnings) AS _sumEarnings FROM QsTransaction qtIn GROUP BY qtIn.UserID
                ) AS qt ON (u.UserID = qt.UserID)
                WHERE _timeVisit > 0 [Filters]
                GROUP BY u.UserID, bup._timeVisit, qt._sumEarnings
            ';
        } elseif (isset($_GET['group2'])) {
            return '
                SELECT
                       null as BlogUserReportID, 
                       u.UserID, u.FirstName, u.LastName, u.AccountLevel, u.Accounts,
                       ROUND(bup._timeVisit, 2) AS _timeVisit,
                       qt._sumEarnings,
                       (SELECT COUNT(*) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countReport,
                       (SELECT COUNT(DISTINCT BlogPostID) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countPost
                FROM Usr u
                LEFT JOIN (
                    SELECT bupIn.UserID, (SUM(UNIX_TIMESTAMP(bupIn.OutTime) - UNIX_TIMESTAMP(bupIn.InTime)) / 60) AS _timeVisit FROM BlogUserReport bupIn GROUP BY bupIn.UserID
                ) AS bup ON (u.UserID = bup.UserID)
                LEFT JOIN (
                    SELECT qtIn.UserID, SUM(qtIn.Earnings) AS _sumEarnings FROM QsTransaction qtIn GROUP BY qtIn.UserID
                ) AS qt ON (u.UserID = qt.UserID)
                WHERE _timeVisit > 0
                GROUP BY u.UserID, bup._timeVisit, qt._sumEarnings
                HAVING (
                    _sumEarnings > 0
                    [Filters]
                )
            ';
        } elseif (isset($_GET['group3'])) {
            return "
                SELECT
                       null as BlogUserReportID, 
                       u.UserID, u.FirstName, u.LastName, u.AccountLevel, u.Accounts,
                       ROUND(bup._timeVisit, 2) AS _timeVisit,
                       qt._sumEarnings,
                       (SELECT COUNT(*) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countReport,
                       (SELECT COUNT(DISTINCT BlogPostID) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countPost
                FROM Usr u
                LEFT JOIN (
                    SELECT bupIn.UserID, (SUM(UNIX_TIMESTAMP(bupIn.OutTime) - UNIX_TIMESTAMP(bupIn.InTime)) / 60) AS _timeVisit FROM BlogUserReport bupIn GROUP BY bupIn.UserID
                ) AS bup ON (u.UserID = bup.UserID)
                LEFT JOIN (
                    SELECT qtIn.UserID, SUM(qtIn.Earnings) AS _sumEarnings FROM QsTransaction qtIn WHERE qtIn.ClickDate >= '" . $minDate->format('Y-m-d 00:00') . "' GROUP BY qtIn.UserID
                ) AS qt ON (u.UserID = qt.UserID)
                WHERE
                        _timeVisit > 0
                    AND u.AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                GROUP BY u.UserID, bup._timeVisit, qt._sumEarnings
                HAVING (
                    _sumEarnings > 0
                    [Filters]
                )
            ";
        } elseif (isset($_GET['real'])) {
            return $sqlReal;
        } elseif (isset($_GET['fake'])) {
            $realCount = count(SQLToSimpleArray($sqlReal, 'UserID'));
            $limitFakeUpgrade = (int) ($realCount / 100 * UpgradeReaders::CONDITION_PERCENT_FROM_REAL);

            $sqlFake = "
                SELECT
                        u.UserID, u.FirstName, u.LastName, null as BlogUserReportID,
                        ROUND(SUM(qt.Earnings), 0) AS _sumEarnings,
                        (SELECT COUNT(*) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countReport,
                        (SELECT COUNT(DISTINCT BlogPostID) FROM BlogUserReport bup_uc WHERE u.UserID = bup_uc.UserID) AS _countPost,
                        (SELECT SUM(UNIX_TIMESTAMP(bup_tv.OutTime) - UNIX_TIMESTAMP(bup_tv.InTime)) / 60 FROM BlogUserReport bup_tv WHERE u.UserID = bup_tv.UserID AND bup_tv.OutTime IS NOT NULL) AS _timeVisit
                FROM Usr u
                JOIN QsTransaction qt ON (qt.UserID = u.UserID)
                WHERE
                        u.AccountLevel = " . ACCOUNT_LEVEL_FREE . "
                    AND u.Subscription IS NULL
                    AND u.Accounts >= " . UpgradeReaders::CONDITION_MIN_ACCOUNTS . "
                    AND qt.ClickDate >= '" . $minDate->format('Y-m-d 00:00') . "'
                GROUP BY u.UserID
                HAVING (
                        _sumEarnings = " . UpgradeReaders::CONDITION_MAX_EARNING_SUM . "
                )
                ORDER BY _timeVisit DESC, _countReport DESC            
                LIMIT " . $limitFakeUpgrade . "
            -- ";

            return $sqlFake;
        }

        return '
            SELECT bup.*, u.Firstname, u.Lastname
            FROM BlogUserReport bup
            LEFT JOIN Usr u ON (u.UserID = bup.UserID)
            WHERE 1 [Filters]
            GROUP BY bup.UserID, BlogUserReportID, BlogPostID, InTime, OutTime, TimeZoneOffset
        ';
    }
}
