<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Doctrine;

use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\AbstractSource;
use Webit\DoctrineJmsJson\Serializer\Type\DefaultSerializerTypeResolver;

class SerializerTypeResolver implements \Webit\DoctrineJmsJson\Serializer\Type\SerializerTypeResolver
{
    /**
     * @var DefaultSerializerTypeResolver
     */
    private $defaultResolver;

    public function __construct()
    {
        $this->defaultResolver = new DefaultSerializerTypeResolver();
    }

    public function resolveType($value): string
    {
        // default type resolver does not understand parent types
        if (is_array($value) && count($value) > 0) {
            $firstValue = $value[array_keys($value)[0]];

            // poor man's parent abstract type detection.
            // ideally it should be done with some reflections / jms metadata
            if ($firstValue instanceof AbstractSource) {
                return "array<string, AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\AbstractSource>";
            }
        }

        return $this->defaultResolver->resolveType($value);
    }
}
