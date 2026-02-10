<?php

namespace AwardWallet\MainBundle\Form\Transformer;

abstract class GenericEntityContainerModelTransformer extends AbstractModelTransformer
{
    public function reverseTransform($value)
    {
        return $value->getEntity();
    }
}
