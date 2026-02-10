<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class DateTimeObject
{
    /**
     * @var string дата в формате 'Y-m-d'
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $date;
    /**
     * @var string время в формате 'H:i:s'
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $time;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Type("string")
     */
    private $timezone;
    /**
     * @var string
     * @Type("string")
     */
    private $utc_offset;

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(string $date)
    {
        $this->date = $date;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setTime(string $time)
    {
        $this->time = $time;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getUtcOffset()
    {
        return $this->utc_offset;
    }
}
