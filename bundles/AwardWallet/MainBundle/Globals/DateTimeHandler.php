<?php

namespace AwardWallet\MainBundle\Globals;

class DateTimeHandler
{
    public const DATEFORMAT_US = 1;
    public const DATEFORMAT_EU = 2;

    public function getDateFormats($dateFormat = self::DATEFORMAT_US)
    {
        switch ($dateFormat) {
            case self::DATEFORMAT_US:
                return [
                    'datetime' => "F d, Y H:i:s",
                    'date' => "m/d/Y",
                    'dateshort' => "m/d/y",
                    'time' => "h:ia",
                    'timewithoutzero' => "g:i A",
                    'datelong' => "F j, Y",
                    'monthday' => "F j",
                    'timelong' => "g:i A",
                    'datetimelong' => "F j, Y g:i A",
                    'weekdatetime' => "D m/d",
                ];

            case self::DATEFORMAT_EU:
                return [
                    'datetime' => "d F, Y H:i:s",
                    'date' => "d/m/Y",
                    'dateshort' => "d/m/y",
                    'time' => "H:i",
                    'timewithoutzero' => "H:i",
                    'datelong' => "j F, Y",
                    'monthday' => "j F",
                    'timelong' => "G:i",
                    'datetimelong' => "j F, Y G:i",
                    'weekdatetime' => "D d/m",
                ];

            default:
                throw new \LogicException('Unknown date format: ' . $dateFormat);
        }
    }

    public function getDateTime($type, $format = self::DATEFORMAT_US)
    {
        $types = $this->getDateFormats($format);

        if (isset($types[$type])) {
            return $types[$type];
        }

        return false;
    }
}
