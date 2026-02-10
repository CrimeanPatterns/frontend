<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Form\Model\ProviderCouponModel;
use AwardWallet\MainBundle\Form\Model\ProviderCouponModelMobile;

class ProviderCouponMobileFormTransformer extends ProviderCouponFormTransformer
{
    protected function createModel(): ProviderCouponModel
    {
        return new ProviderCouponModelMobile();
    }
}
