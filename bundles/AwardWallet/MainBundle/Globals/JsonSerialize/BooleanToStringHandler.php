<?php

namespace AwardWallet\MainBundle\Globals\JsonSerialize;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;

/**
 * Custom handler for converting boolean values as a string to a regular boolean type.
 */
class BooleanToStringHandler implements SubscribingHandlerInterface
{
    public const HANDLER_TYPE = 'boolean_custom';

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => self::HANDLER_TYPE,
                'method' => 'serializeBooleanToString',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => self::HANDLER_TYPE,
                'method' => 'deserializeBooleanFromString',
            ],
        ];
    }

    public function serializeBooleanToString(JsonSerializationVisitor $visitor, $value, array $type, Context $context): string
    {
        return ($value === true) ? 'true' : 'false';
    }

    public function deserializeBooleanFromString(JsonDeserializationVisitor $visitor, $value, array $type, Context $context): bool
    {
        return $value === 'true' || $value === '1';
    }
}
