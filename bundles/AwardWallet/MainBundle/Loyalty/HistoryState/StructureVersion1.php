<?php

namespace AwardWallet\MainBundle\Loyalty\HistoryState;

use JMS\Serializer\Annotation\Type;

class StructureVersion1 extends HistoryState
{
    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d', '', '!Y-m-d'>")
     */
    private $lastDate;
    /**
     * @var int
     * @Type("integer")
     */
    private $cacheVersion;
    /**
     * @var array
     * @Type("array<string, DateTime<'Y-m-d', '', '!Y-m-d'>>")
     */
    private $subAccountLastDates = [];

    public function __construct()
    {
        $this->type = 1;
    }

    /**
     * @return \DateTime
     */
    public function getLastDate()
    {
        return $this->lastDate;
    }

    /**
     * @param \DateTime $lastDate
     * @return $this
     */
    public function setLastDate($lastDate)
    {
        $this->lastDate = $lastDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getCacheVersion()
    {
        return $this->cacheVersion;
    }

    /**
     * @param int $cacheVersion
     * @return $this
     */
    public function setCacheVersion($cacheVersion)
    {
        $this->cacheVersion = $cacheVersion;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubAccountLastDates()
    {
        return $this->subAccountLastDates;
    }

    /**
     * @param array $subAccountLastDates
     * @return $this
     */
    public function setSubAccountLastDates($subAccountLastDates)
    {
        $this->subAccountLastDates = $subAccountLastDates;

        return $this;
    }
}
