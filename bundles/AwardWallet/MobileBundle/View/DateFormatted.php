<?php

namespace AwardWallet\MobileBundle\View;

class DateFormatted
{
    /**
     * @var int
     */
    public $ts;
    /**
     * @var string|array
     */
    public $fmt;

    /**
     * DateFormatted constructor.
     *
     * @param int $timestamp
     * @param string $formatted
     */
    public function __construct($timestamp, $formatted)
    {
        $this->ts = $timestamp;
        $this->fmt = $formatted;
    }

    /**
     * @param string $format
     * @return DateFormatted
     */
    public function fromDateTimeAndFormat(\DateTime $dateTime, $format)
    {
        return new self($dateTime->getTimestamp(), $format);
    }
}
