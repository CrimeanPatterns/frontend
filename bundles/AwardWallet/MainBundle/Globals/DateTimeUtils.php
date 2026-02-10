<?php

namespace AwardWallet\MainBundle\Globals;

class DateTimeUtils
{
    public static function areEqualByTimestamp(?\DateTimeInterface $dateTimeA = null, ?\DateTimeInterface $dateTimeB = null): bool
    {
        if (isset($dateTimeA, $dateTimeB)) {
            return $dateTimeA->getTimestamp() === $dateTimeB->getTimestamp();
        } elseif (
            !isset($dateTimeA)
            && !isset($dateTimeB)
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function fromSerializedArray(array $serialized): \DateTime
    {
        if (StringUtils::isEmpty($serialized['date'])) {
            throw new \InvalidArgumentException('Invalid serialized \DateTime data');
        }

        if (StringUtils::isNotEmpty($serialized['timezone'])) {
            $timezone = new \DateTimeZone($serialized['timezone']);
        } else {
            $timezone = null;
        }

        return new \DateTime($serialized['date'], $timezone);
    }

    public static function timezoneFromString(string $timezone, ?\DateTimeZone $default = null): ?\DateTimeZone
    {
        try {
            $obj = new \DateTimeZone($timezone);
        } catch (\Throwable $e) {
            return $default;
        }

        return $obj;
    }
}
