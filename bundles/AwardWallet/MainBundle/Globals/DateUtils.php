<?php

namespace AwardWallet\MainBundle\Globals;

class DateUtils
{
    public static function toMutable(\DateTimeInterface $dateTime): \DateTime
    {
        if ($dateTime instanceof \DateTime) {
            return $dateTime;
        } else {
            return new \DateTime(
                $dateTime->format('Y-m-d H:i:s.u'),
                $dateTime->getTimezone()
            );
        }
    }

    public static function toSQLDateTime(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }
}
