<?php
require_once __DIR__ . "/../schema/BalanceWatch.php";

class BalanceWatchAdminList extends TBaseList
{
    protected function getRowColor() : string
    {
        $rowColor = parent::getRowColor();
        switch ($this->OriginalFields["Status"]) {
            case \AwardWallet\MainBundle\Entity\BalanceWatch::STATUS_GOOD:
                $rowColor = '#CFFAFF';
                break;
            case \AwardWallet\MainBundle\Entity\BalanceWatch::STATUS_REVIEW:
                $rowColor = '#FFFD98';
                break;
            case \AwardWallet\MainBundle\Entity\BalanceWatch::STATUS_ERROR:
                $rowColor = '#FFCDCA';
                break;
        }
        return $rowColor;
    }

    function GetEditLinks()
    {
        $links = [];
        $arFields = $this->Query->Fields;
        if (empty($arFields['StopReason'])) {
            $backTo = '/manager/list.php?Schema=BalanceWatch';
            $links[] = ' <a href="/manager/balance-watch/stop/' . $arFields['AccountID'] . '?backTo=' . urlencode($backTo) . '" onclick="return confirm(\'Are you sure?\')">stop</a>';
        }
        $links[] = "<a href=edit.php?ID={$arFields['BalanceWatchID']}{$this->URLParamsString}>Edit</a>";

        return implode(' | ', $links);
    }
}
