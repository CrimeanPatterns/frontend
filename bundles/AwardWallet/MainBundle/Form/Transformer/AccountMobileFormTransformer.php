<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Form\Model\AccountModelMobile;

class AccountMobileFormTransformer extends AccountFormTransformer
{
    protected function createAccountModel(): AccountModel
    {
        return new AccountModelMobile();
    }
}
