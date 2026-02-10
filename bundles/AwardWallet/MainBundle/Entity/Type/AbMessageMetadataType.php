<?php

namespace AwardWallet\MainBundle\Entity\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType;

class AbMessageMetadataType extends JsonArrayType
{
    public const AB_MESSAGE_METADATA = 'ab_message_metadata';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TINYTEXT';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $result = new AbMessageMetadata();
        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if ($value === null) {
            return $result;
        }

        $val = @unserialize($value);

        if ($val !== false) {
            $value = $val;
        } else {
            $value = json_decode($value, true);
        }

        if (is_array($value)) {
            foreach ($value as $name => $val) {
                if (method_exists($result, 'set' . $name)) {
                    call_user_func([$result, 'set' . $name], $val);
                }
            }
        }

        return $result;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_object($value) && $value instanceof AbMessageMetadata) {
            $val = $value->toArray();

            if ($val) {
                return json_encode($val);
            }
        }

        return null;
    }

    public function getName()
    {
        return self::AB_MESSAGE_METADATA;
    }
}
