<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class CamelCaseNameConverter implements NameConverterInterface
{
    public function normalize($propertyName)
    {
        return lcfirst($propertyName);
    }

    public function denormalize($propertyName)
    {
        return ucfirst($propertyName);
    }
}
