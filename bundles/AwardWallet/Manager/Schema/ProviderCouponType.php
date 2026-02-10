<?php

namespace AwardWallet\Manager\Schema;

class ProviderCouponType extends \TBaseSchema
{
    public function TuneList(&$list): void
    {
        parent::TuneList($list);

        $list->ShowExport = false;
        $list->ShowImport = false;
    }

    public function GetFormFields(): array
    {
        $result = parent::GetFormFields();

        $result['TypeID']['Options'] = \AwardWallet\MainBundle\Entity\Providercoupon::TYPES + \AwardWallet\MainBundle\Entity\Providercoupon::DOCUMENT_TYPES;

        return $result;
    }
}
