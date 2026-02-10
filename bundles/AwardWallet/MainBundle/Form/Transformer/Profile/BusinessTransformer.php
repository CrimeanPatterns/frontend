<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\BusinessModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class BusinessTransformer extends AbstractModelTransformer
{
    /**
     * Model to norm.
     *
     * @param Usr $user
     * @return BusinessModel
     */
    public function transform($user)
    {
        return (new BusinessModel())
            ->setLogin($user->getLogin())
            ->setCompany($user->getCompany())
            ->setEntity($user);
    }
}
