<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class BankTransactionsDateUtils
{
    public const THIS_MONTH = 1;
    public const THIS_QUARTER = 2;
    public const LAST_MONTH = 3;
    public const LAST_QUARTER = 4;
    public const THIS_YEAR = 5;
    public const LAST_YEAR = 6;

    /* using for MySQL queries */
    public static function getCurrentQuarterLimits(?\DateTime $date = null)
    {
        $date = empty($date) ? new \DateTime('now') : $date;

        $limits = [
            1 => ["s" => "-01-01", "e" => "-04-01"],
            2 => ["s" => "-04-01", "e" => "-07-01"],
            3 => ["s" => "-07-01", "e" => "-10-01"],
            4 => ["s" => "-10-01", "e" => "-01-01"],
        ];

        $current = intval((date("n", $date->getTimestamp()) + 2) / 3);
        $startYear = date('Y', $date->getTimestamp());
        $endYear = $current === 4 ? strval(intval($startYear) + 1) : $startYear;

        return [
            "q" => $current,
            "start" => $startYear . $limits[$current]['s'],
            "end" => $endYear . $limits[$current]['e'],
        ];
    }

    public static function findRangeLimits(int $range)
    {
        switch ($range) {
            case self::THIS_MONTH:
                $range = [
                    'start' => (new \DateTime('first day of this month'))->format('Y-m-d'),
                    'end' => (new \DateTime('first day of next month'))->format('Y-m-d'),
                ];

                break;

            case self::THIS_QUARTER:
                $range = self::getCurrentQuarterLimits();

                break;

            case self::LAST_MONTH:
                $range = [
                    'start' => (new \DateTime('first day of previous month'))->format('Y-m-d'),
                    'end' => (new \DateTime('first day of this month'))->format('Y-m-d'),
                ];

                break;

            case self::LAST_QUARTER:
                $range = self::getCurrentQuarterLimits(new \DateTime('-3 months'));

                break;

            case self::THIS_YEAR:
                $range = [
                    'start' => date('Y') . "-01-01",
                    'end' => (date('Y') + 1) . "-01-01",
                ];

                break;

            case self::LAST_YEAR:
                $range = [
                    'start' => (date('Y') - 1) . "-01-01",
                    'end' => date('Y') . "-01-01",
                ];

                break;

            default:
                throw new \LogicException('Unknown range');
        }

        return $range;
    }
}
