<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

class DateTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        return new \DateTime($value);
    }

    public function reverseTransform($value)
    {
        return $value;
    }
}
