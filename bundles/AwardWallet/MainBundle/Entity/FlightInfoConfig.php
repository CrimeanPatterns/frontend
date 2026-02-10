<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FlightInfoConfig.
 *
 * @ORM\Table(name="FlightInfoConfig")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\FlightInfoConfigRepository")
 */
class FlightInfoConfig
{
    public const TYPE_NONE = 0;
    public const TYPE_CHECK = 1;
    public const TYPE_SUBSCRIBE = 2;
    public const TYPE_UPDATE = 3;

    public const AWPLUS_ALL = 0;
    public const AWPLUS_REGULAR = 1;
    public const AWPLUS_PLUS = 2;

    public const REGION_ALL = 0;
    public const REGION_DOMESTIC = 1;
    public const REGION_INTERNATIONAL = 2;

    /**
     * @var int
     * @ORM\Column(name="FlightInfoConfigID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $FlightInfoConfigID;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=100, nullable=false)
     */
    protected $Name;

    /**
     * @var int
     * @ORM\Column(name="Type", type="integer", nullable=false)
     */
    protected $Type;

    /**
     * @var string
     * @ORM\Column(name="Service", type="string", length=100, nullable=false)
     */
    protected $Service;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="string", length=1000, nullable=false)
     */
    protected $Comment = '';

    /**
     * @var string
     * @ORM\Column(name="ScheduleRules", type="string", length=1000, nullable=false)
     */
    protected $ScheduleRules;

    /**
     * @var string
     * @ORM\Column(name="IgnoreFields", type="string", length=1000, nullable=false)
     */
    protected $IgnoreFields;

    /**
     * @var bool
     * @ORM\Column(name="Enable", type="boolean", nullable=false)
     */
    protected $Enable = false;

    /**
     * @var bool
     * @ORM\Column(name="Schedule", type="boolean", nullable=false)
     */
    protected $Schedule = false;

    /**
     * @var bool
     * @ORM\Column(name="Debug", type="boolean", nullable=false)
     */
    protected $Debug = false;

    /**
     * @var int
     * @ORM\Column(name="AWPlusFlag", type="integer", nullable=false)
     */
    protected $AWPlusFlag = self::AWPLUS_ALL;

    /**
     * @var int
     * @ORM\Column(name="RegionFlag", type="integer", nullable=false)
     */
    protected $RegionFlag = self::REGION_ALL;

    /**
     * @return int
     */
    public function getFlightInfoConfigID()
    {
        return $this->FlightInfoConfigID;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * @param string $Name
     */
    public function setName($Name)
    {
        $this->Name = $Name;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param int $Type
     */
    public function setType($Type)
    {
        $this->Type = $Type;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->Service;
    }

    /**
     * @param string $Service
     */
    public function setService($Service)
    {
        $this->Service = $Service;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->Comment;
    }

    /**
     * @param string $Comment
     */
    public function setComment($Comment)
    {
        $this->Comment = $Comment;
    }

    /**
     * @return string
     */
    public function getScheduleRules()
    {
        return $this->ScheduleRules;
    }

    /**
     * @param string $ScheduleRules
     */
    public function setScheduleRules($ScheduleRules)
    {
        $this->ScheduleRules = $ScheduleRules;
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        return $this->Enable;
    }

    /**
     * @param bool $Enable
     */
    public function setEnable($Enable)
    {
        $this->Enable = $Enable;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->Debug;
    }

    /**
     * @param bool $Debug
     */
    public function setDebug($Debug)
    {
        $this->Debug = $Debug;
    }

    /**
     * @return int
     */
    public function getAWPlusFlag()
    {
        return $this->AWPlusFlag;
    }

    /**
     * @param int $AWPlusFlag
     */
    public function setAWPlusFlag($AWPlusFlag)
    {
        $this->AWPlusFlag = $AWPlusFlag;
    }

    /**
     * @return int
     */
    public function getRegionFlag()
    {
        return $this->RegionFlag;
    }

    /**
     * @param int $RegionFlag
     */
    public function setRegionFlag($RegionFlag)
    {
        $this->RegionFlag = $RegionFlag;
    }

    /**
     * @return string
     */
    public function getIgnoreFields()
    {
        return $this->IgnoreFields;
    }

    /**
     * @param string $IgnoreFields
     */
    public function setIgnoreFields($IgnoreFields)
    {
        $this->IgnoreFields = $IgnoreFields;
    }

    /**
     * @return bool
     */
    public function isSchedule()
    {
        return $this->Schedule;
    }

    /**
     * @param bool $Schedule
     */
    public function setSchedule($Schedule)
    {
        $this->Schedule = $Schedule;
    }
}
