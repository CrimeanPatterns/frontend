<?php

namespace AwardWallet\MainBundle\Service\FlightInfo;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\FlightInfoConfig;

/**
 * @NoDI()
 */
class ConfigRule
{
    /** @var string */
    private $name;

    /** @var int */
    private $type;
    /** @var string */
    private $service;

    /** @var bool */
    private $enable;
    /** @var bool */
    private $schedule;

    private $scheduleRules;

    private $awplus;
    private $region;

    private $ignoreFields;

    /** @var bool */
    private $debug;

    public function __construct($data)
    {
        $this->name = $data['Name'];
        $this->type = $data['Type'];
        $this->service = $data['Service'];
        $this->enable = $data['Enable'];
        $this->schedule = $data['Schedule'];
        $this->scheduleRules = $data['ScheduleRules'];
        $this->awplus = $data['AWPlusFlag'];
        $this->region = $data['RegionFlag'];
        $this->ignoreFields = $data['IgnoreFields'];
        $this->debug = $data['Debug'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        return $this->enable;
    }

    /**
     * @return bool
     */
    public function isSchedule()
    {
        return $this->schedule;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isCheckRule()
    {
        return $this->type == FlightInfoConfig::TYPE_CHECK;
    }

    /**
     * @return bool
     */
    public function isUpdateRule()
    {
        return $this->type == FlightInfoConfig::TYPE_UPDATE;
    }

    /**
     * @return bool
     */
    public function isSubscribeRule()
    {
        return $this->type == FlightInfoConfig::TYPE_SUBSCRIBE;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param \DateTime|null $flightDate
     * @param \DateTime|null $depDate
     * @param \DateTime|null $arrDate
     * @return \DateTime|false
     */
    public function getScheduleTime($flightDate, $depDate, $arrDate)
    {
        if (empty($this->scheduleRules)) {
            return new \DateTime();
        }

        if (empty($flightDate) && empty($arrDate) && empty($depDate)) {
            return false;
        }

        /** @var \DateTime|false $retDate */
        $retDate = false;
        $timeRules = explode("\n", $this->scheduleRules);

        foreach ($timeRules as $timeRule) {
            $timeRule = trim($timeRule);

            if (empty($timeRule)) {
                continue;
            }
            [$dateVariable, $dateInterval] = explode(' ', $timeRule . ' ', 2);
            $date = false;

            if (in_array($dateVariable, ['FlightDate', 'DepDate', 'ArrDate'])) {
                if ($dateVariable == 'FlightDate') {
                    $date = clone $flightDate;
                } elseif ($dateVariable == 'DepDate') {
                    if (empty($depDate)) {
                        continue;
                    }
                    $date = clone $depDate;
                } elseif ($dateVariable == 'ArrDate') {
                    if (empty($arrDate)) {
                        continue;
                    }
                    $date = clone $arrDate;
                }
            } else {
                $date = clone $flightDate;
                $dateInterval = $timeRule;
            }

            if (!empty($dateInterval)) {
                try {
                    $interval = \DateInterval::createFromDateString($dateInterval);
                } catch (\Exception $e) {
                    continue;
                }
                $date->add($interval);
            }

            if ($retDate === false || $date->getTimestamp() > $retDate->getTimestamp()) {
                $retDate = $date;
            }
        }

        return $retDate;
    }

    /**
     * @return int
     */
    public function getAwPlus()
    {
        return $this->awplus;
    }

    /**
     * @return int
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return array
     */
    public function getIgnoreFields()
    {
        if (empty($this->ignoreFields)) {
            return [];
        }

        $ret = [];
        $ignoreFields = explode("\n", $this->ignoreFields);

        foreach ($ignoreFields as $field) {
            $field = trim($field);

            if ($field) {
                $ret[] = $field;
            }
        }

        return $ret;
    }
}
