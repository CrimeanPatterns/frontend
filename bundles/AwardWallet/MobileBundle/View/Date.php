<?php

namespace AwardWallet\MobileBundle\View;

class Date
{
    /**
     * @var int
     */
    public $ts;

    /**
     * @var string
     */
    public $tz;

    /**
     * @var int[]
     */
    public $fmt;

    public function __construct(\DateTime $date)
    {
        $this->ts = $date->getTimestamp();
        $this->tz = $date->format('T');
        $this->fmt = $this->formatDate($date);
    }

    /**
     * Formats date to js friendly format.
     *
     * @return \int[] (year, month, day, hour, minute)
     */
    private function formatDate(\DateTime $dateTime)
    {
        return [
            'y' => (int) $dateTime->format('Y'),
            'm' => (int) (($m = $dateTime->format('m')) > 0 ? $m - 1 : $m),
            'd' => (int) $dateTime->format('d'),
            'h' => (int) $dateTime->format('H'),
            'i' => (int) $dateTime->format('i'),
        ];
    }
}
