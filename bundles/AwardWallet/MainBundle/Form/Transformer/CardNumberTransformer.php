<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class CardNumberTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        return preg_replace('/[^\d]/', '', $value);
    }
}
