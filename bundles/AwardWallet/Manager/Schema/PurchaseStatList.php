<?php

namespace AwardWallet\Manager\Schema;

/**
 * @property PurchaseStat $Schema
 */
class PurchaseStatList extends \TBaseList
{
    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);
        $transferTimes = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\TransferTimes::class);

        $this->Query->Fields['BonusDescription'] = $transferTimes->processBonusText($this->Query->Fields,
            $this->Query->Fields['BonusDescription'] ?? '');

        if (!empty($purchaseText = $transferTimes->getPurchaseText($this->Query->Fields))) {
            $this->Query->Fields['BonusDescription'] .= '<br>' . $purchaseText;
        }
    }
}
