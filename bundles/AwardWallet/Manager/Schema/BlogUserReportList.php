<?php

namespace AwardWallet\Manager\Schema;

use AwardWallet\MainBundle\Service\Blog\UpgradeReaders;

/**
 * @property BlogUserReport $Schema
 */
class BlogUserReportList extends \TBaseList
{
    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);
        $this->Query->Fields = $this->formatFieldsRow($this->Query->Fields);
    }

    public function formatFieldsRow($row)
    {
        if (isset($_GET['real']) || isset($_GET['fake']) || isset($_GET['group']) || isset($_GET['group2']) || isset($_GET['group3'])) {
            $row['UserID'] = '<a href="/manager/list.php?Schema=UserAdmin&UserID=' . $row['UserID'] . '">' . trim($row['FirstName'] . ' ' . $row['LastName']) . ' (' . $row['UserID'] . ') </a>';
        }

        if (isset($row['_timeVisit'])) {
            $row['_timeVisit'] = (float) $row['_timeVisit'] >= 1 ? (int) $row['_timeVisit'] : round($row['_timeVisit'], 2);
        }

        return $row;
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
        $this->drawExtend();
    }

    public function DrawEmptyList()
    {
        parent::DrawEmptyList();
        $this->drawExtend();
    }

    private function drawExtend()
    {
        $minDate = new \DateTime(UpgradeReaders::CONDITION_DATE);

        $condition = '';

        if (isset($_GET['real'])) {
            $condition = 'AccountLevel = Free;
            Subscription is Empty;
            Account >= ' . UpgradeReaders::CONDITION_MIN_ACCOUNTS . ';
            From Date >= ' . $minDate->format('Y-m-d') . ';
            Earnings > ' . UpgradeReaders::CONDITION_MIN_EARNING_SUM . ';
            Visits > ' . UpgradeReaders::CONDITION_MIN_VISIT . ';
            TimeVisit > ' . UpgradeReaders::CONDITION_MIN_TIME_IN_MINUTE . ' min';
        } elseif (isset($_GET['fake'])) {
            $condition = '10% of "REAL" conditions, starting with largest visits';
        } elseif (isset($_GET['group2'])) {
            $condition = 'Earnings > 0';
        } elseif (isset($_GET['group3'])) {
            $condition = 'Earnings > 0;
            From Date >= ' . $minDate->format('Y-m-d') . ';
            AccountLevel = FREE;';
        }

        if (!empty($condition)) {
            $condition = '<br><br>CONDITION: <b>' . $condition . '</b>';
        }

        $extend = '
<div style="float:left;padding:0 0 10px 10px;">
    <a href="/manager/list.php?Schema=BlogUserReport&real">Show <b>real</b> users for upgrade</a>
    ' . str_repeat('&nbsp;', 3) . '
    <a href="/manager/list.php?Schema=BlogUserReport&fake">Show <b>fake</b> users for upgrade</a>
    ' . str_repeat('&nbsp;', 3) . '
    <a href="/manager/list.php?Schema=BlogUserReport&group2">Group Users (Earnings>0)</a>
    ' . str_repeat('&nbsp;', 3) . '
    <a href="/manager/list.php?Schema=BlogUserReport&group3">Group Users (Earnings>0 - 6 months ago)</a>
    ' . $condition . '
</div>
        ';

        $html = '<script>';
        $html .= '$("#extendFixedMenu ").prepend("' . addslashes(str_replace("\n", '', $extend)) . '");';
        $html .= '</script>';

        echo $html;
    }
}
