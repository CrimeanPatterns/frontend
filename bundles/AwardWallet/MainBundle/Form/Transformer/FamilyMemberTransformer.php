<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Form\Model\FamilyMemberModel;

class FamilyMemberTransformer extends AbstractModelTransformer
{
    /**
     * Model to norm.
     *
     * @param FamilyMemberModel $userAgent
     * @return FamilyMemberModel
     */
    public function transform($userAgent)
    {
        return (new FamilyMemberModel())
            ->setFirstname($userAgent->getFirstname())
            ->setMidname($userAgent->getMidname())
            ->setLastname($userAgent->getLastname())
            ->setAlias($userAgent->getAlias())
            ->setEmail($userAgent->getEmail())
            ->setSendemails($userAgent->getSendemails())
            ->setNotes($userAgent->getNotes())
            ->setEntity($userAgent);
    }
}
