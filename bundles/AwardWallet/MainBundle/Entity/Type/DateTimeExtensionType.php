<?php

namespace AwardWallet\MainBundle\Entity\Type;

use AwardWallet\MainBundle\FrameworkExtension\DateTimeExtension;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class DateTimeExtensionType extends DateTimeType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $dateTime = parent::convertToPHPValue($value, $platform);

        if (!$dateTime) {
            return $dateTime;
        }

        return new DateTimeExtension('@' . $dateTime->format('U'));
    }

    public function getName()
    {
        return 'datetime_ext';
    }
}
