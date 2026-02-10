<?php

namespace Codeception\Module;

use Codeception\Module;

class CreditCards extends Module
{
    public function createAwMerchant(array $fields = []): int
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');
        $random = bin2hex(random_bytes(6));

        return $db->haveInDatabase('Merchant', array_merge([
            "Name" => $fields['Name'] ?? 'MERCHANT ' . $random,
            "DisplayName" => $fields['DisplayName'] ?? 'Merchant ' . $random,
        ], $fields));
    }

    public function createAwMerchantPattern(array $fields = []): int
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');
        $random = bin2hex(random_bytes(6));

        return $db->haveInDatabase('MerchantPattern', array_merge([
            "Name" => $fields['Name'] ?? 'MERCHANT ' . $random,
            "Patterns" => "#{$random}#",
        ], $fields));
    }

    public function createAwCreditCard(int $providerId, array $fields = []): int
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase(
            'CreditCard',
            array_merge([
                'ProviderID' => $providerId,
                'Name' => $fields['Name'] ?? 'CreditCard ' . bin2hex(random_bytes(6)),
                //                'DisplayNameFormat' => 'Chase',
                //                'IsBusiness' => 0,
                //                'Patterns' => 'Chase',
                'MatchingOrder' => 10,
                //                'ClickURL' => 'https://awardwallet.com',
                //                'CardFullName' => 'Chase',
                //                'VisibleInList' => 1,
                //                'DirectClickUrl' => 'https://awardwallet.com',
                //                'PictureVer' => time(),
                //                'PictureExt' => 'png',
                //                'SortIndex' => 1,
                //                'Text' => 'info',
            ], $fields)
        );
    }
}
