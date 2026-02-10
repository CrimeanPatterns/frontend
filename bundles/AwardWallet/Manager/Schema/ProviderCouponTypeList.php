<?php

namespace AwardWallet\Manager\Schema;

/**
 * @property ProviderCouponType $Schema
 */
class ProviderCouponTypeList extends \TBaseList
{
    public function FormatFields($output = 'html'): void
    {
        parent::FormatFields($output);

        $types = \AwardWallet\MainBundle\Entity\Providercoupon::TYPES + \AwardWallet\MainBundle\Entity\Providercoupon::DOCUMENT_TYPES;
        $this->Query->Fields['TypeID'] = $types[(int) $this->Query->Fields['TypeID']];
    }
}
