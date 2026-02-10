<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\RegionalModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class RegionalTransformer extends AbstractModelTransformer
{
    /**
     * @param Usr $user
     * @return RegionalModel
     */
    public function transform($user)
    {
        return (new RegionalModel())
            ->setLanguage($user->getLanguage())
            ->setRegion($user->getRegion())
            ->setCurrency($user->getCurrency())
            ->setModelChanged(false)
            ->setEntity($user);
    }
}
