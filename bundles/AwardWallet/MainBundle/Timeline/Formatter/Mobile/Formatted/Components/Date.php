<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components;

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

    /**
     * @var Date
     */
    public $old;

    public function __construct(\DateTime $new, ?\DateTime $old = null)
    {
        $this->ts = $new->getTimestamp();
        $this->tz = $new->format('T');
        $this->fmt = $this->formatDate($new);

        if (null !== $old) {
            $this->setOldDateTime($old);
        }
    }

    public function setOldDateTime(\DateTime $old)
    {
        $this->old = new Date($old);
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
