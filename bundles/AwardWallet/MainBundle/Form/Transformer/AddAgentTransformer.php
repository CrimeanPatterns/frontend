<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Model\AddAgentModel;

class AddAgentTransformer extends AbstractModelTransformer
{
    /**
     * @param Useragent $value
     * @return AddAgentModel
     */
    public function transform($value)
    {
        return (new AddAgentModel())
            ->setEntity($value)
            ->setEmail($value->getEmail())
            ->setFirstname($value->getFirstname())
            ->setLastname($value->getLastname());
    }
}
