<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

class DateTimeExtended extends \DateTime
{
    /**
     * @var string
     */
    protected $timezoneAbbr;

    public function getTimezoneAbbr(): ?string
    {
        return $this->timezoneAbbr;
    }

    public function setTimezoneAbbr(\DateTimeZone $dateTimeZone, ?string $timezoneAbbr = null)
    {
        parent::setTimezone($dateTimeZone);
        $this->timezoneAbbr = $timezoneAbbr;
    }

    public function setTimezone($timezone)
    {
        parent::setTimezone($timezone);

        $this->timezoneAbbr = null;
    }

    public static function create(\DateTime $dateTime, ?string $timezoneAbbr = null): self
    {
        $result = new self();
        $result->setTimestamp($dateTime->getTimestamp());
        $result->setTimezoneAbbr($dateTime->getTimezone(), isset($timezoneAbbr) ? strtoupper($timezoneAbbr) : null);

        return $result;
    }
}
