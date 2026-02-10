<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Form\Model\Profile\PasswordModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class ChangePasswordTransformer extends AbstractModelTransformer
{
    public function transform($user)
    {
        return (new PasswordModel())
            ->setEntity($user);
    }
}
