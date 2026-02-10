<?php

namespace AwardWallet\Manager\Schema;

class CreditCardOffer extends \TBaseSchema
{
    public function TuneList(&$list): void
    {
        parent::TuneList($list);

        $list->ShowExport = false;
        $list->ShowImport = false;

        $list->SQL = '
            SELECT co.*, cc.RankIndex, cc.CreditCardID as CardID
            FROM CreditCardOffer co
            LEFT JOIN CreditCard cc ON cc.CreditCardID = co.CreditCardID
            WHERE 1 [Filters]
            ';

        $list->Fields['CreditCardID']['FilterField'] =
        $list->Fields['CreditCardID']['Sort'] = 'co.CreditCardID';

        $list->Fields['CardID'] =
        $list->Fields['RankIndex'] = [
            'Type' => 'integer',
            'Required' => false,
            'Caption' => 'Rank Index',
            'Sort' => 'cc.RankIndex',
            'DisplayFormat' => '',
            'HTML' => '',
        ];
        $list->Fields['CardID']['Sort'] = '';
        $list->Fields['CardID']['Caption'] = 'Card ID';
    }

    public function GetFormFields(): array
    {
        $result = parent::GetFormFields();

        $result['OfferNote']['Caption'] = 'Internal Note';
        $result['OfferNote']['InputType'] = 'textarea';

        $result['OfferQuality']['Options'] = ['' => ''] + \AwardWallet\MainBundle\Entity\CreditCardOffer::OFFER_QUALITY_LIST;

        return $result;
    }
}
