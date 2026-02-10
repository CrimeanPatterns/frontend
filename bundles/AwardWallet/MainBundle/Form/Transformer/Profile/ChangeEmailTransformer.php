<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Form\Model\Profile\EmailModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class ChangeEmailTransformer extends AbstractModelTransformer
{
    public function transform($user)
    {
        return (new EmailModel())
            ->setEmail($user->getEmail())
            ->setEntity($user);
    }
}
