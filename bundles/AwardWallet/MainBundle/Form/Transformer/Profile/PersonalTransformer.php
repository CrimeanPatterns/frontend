<?php

namespace AwardWallet\MainBundle\Form\Transformer\Profile;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\PersonalModel;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class PersonalTransformer extends AbstractModelTransformer
{
    /**
     * Model to norm.
     *
     * @param Usr $user
     * @return PersonalModel
     */
    public function transform($user)
    {
        return (new PersonalModel())
            ->setLogin($user->getLogin())
            ->setFirstname($user->getFirstname())
            ->setMidname($user->getMidname())
            ->setLastname($user->getLastname())
            ->setEntity($user);
    }
}
