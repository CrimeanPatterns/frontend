<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

abstract class AbstractModelTransformer implements DataTransformerInterface
{
    abstract public function transform($value);

    public function reverseTransform($value)
    {
        return $value;
    }
}
