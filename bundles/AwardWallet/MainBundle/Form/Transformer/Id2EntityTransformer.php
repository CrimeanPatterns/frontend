<?php

namespace AwardWallet\MainBundle\Form\Transformer;

class Id2EntityTransformer extends Entity2IdTransformer
{
    public function transform($entity)
    {
        if (is_numeric($entity)) {
            return $entity;
        }

        if (null === $entity) {
            return $entity;
        }

        return parent::transform($entity);
    }
}
