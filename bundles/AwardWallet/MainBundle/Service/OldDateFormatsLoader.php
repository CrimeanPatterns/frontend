<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\DateTimeHandler;

class OldDateFormatsLoader
{
    public function __construct()
    {
        self::initDateConstants(DateTimeHandler::DATEFORMAT_US);
    }

    public static function initDateConstants(int $usOrEu)
    {
        if (defined('DATE_TIME_FORMAT')) {
            return;
        }

        $dateTime = new DateTimeHandler();
        $dateFormats = $dateTime->getDateFormats($usOrEu);

        define("DATE_TIME_FORMAT", $dateFormats['datetime']);
        define("DATE_FORMAT", $dateFormats['date']);
        define("TIME_FORMAT", $dateFormats['time']);
        define("MONTH_DAY_FORMAT", $dateFormats['monthday']);
        define("DATE_LONG_FORMAT", $dateFormats['datelong']);
        define("DATE_SHORT_FORMAT", $dateFormats['dateshort']);
        define("TIME_LONG_FORMAT", $dateFormats['timelong']);
        define("WEEK_DATE_FORMAT", $dateFormats['weekdatetime']);
        define("TIME_WOZ_FORMAT", $dateFormats['timewithoutzero']);
    }
}
