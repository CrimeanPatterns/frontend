<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * FlightInfo.
 *
 * @ORM\Table(name="FlightInfo", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="segment", columns={"Airline", "FlightNumber", "FlightDate", "DepCode", "ArrCode"})
 * })
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\FlightInfoRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FlightInfo
{
    public const STATE_NEW = 0;
    public const STATE_CHECKED = 1; // FLIGHTSTATE_SCHEDULE
    public const STATE_DONE = 2; // FLIGHTSTATE_CANCEL or FLIGHTSTATE_ARRIVE
    public const STATE_ERROR = 3; // see FLIGHTSTATE_ERROR_ constants

    public const FLIGHTSTATE_UNKNOWN = 0;
    public const FLIGHTSTATE_SCHEDULE = 1;
    public const FLIGHTSTATE_DEPART = 2;
    public const FLIGHTSTATE_ARRIVE = 3;
    public const FLIGHTSTATE_CANCEL = 4;
    public const FLIGHTSTATE_ERROR_AIRLINE = 11;
    public const FLIGHTSTATE_ERROR_NUMBER = 12;
    public const FLIGHTSTATE_ERROR_DEPARTURE = 13;
    public const FLIGHTSTATE_ERROR_ARRIVAL = 14;
    public const FLIGHTSTATE_ERROR_NOT_FOUND = 15; // api empty response
    public const FLIGHTSTATE_ERROR_NOT_EXISTS = 16; // api non-empty response, but flightInfo record not exists in segments
    public const FLIGHTSTATE_ERROR_OTHER = 21;

    /**
     * @var int
     * @ORM\Column(name="FlightInfoID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $FlightInfoID;

    /**
     * @var string
     * @ORM\Column(name="Airline", type="string", length=4, nullable=false)
     */
    protected $Airline;

    /**
     * @var string
     * @ORM\Column(name="FlightNumber", type="string", length=20, nullable=false)
     */
    protected $FlightNumber;

    /**
     * @var \DateTime
     * @ORM\Column(name="FlightDate", type="datetime", nullable=false)
     */
    protected $FlightDate;

    /**
     * @var string
     * @ORM\Column(name="DepCode", type="string", length=10, nullable=false)
     */
    protected $DepCode = '';

    /**
     * @var string
     * @ORM\Column(name="ArrCode", type="string", length=10, nullable=false)
     */
    protected $ArrCode = '';

    /**
     * @var \DateTime
     * @ORM\Column(name="DepDate", type="datetime", nullable=true)
     */
    protected $DepDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ArrDate", type="datetime", nullable=true)
     */
    protected $ArrDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $CreateDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $UpdateDate;

    /**
     * @var int
     * @ORM\Column(name="ChecksCount", type="integer", nullable=false)
     */
    protected $ChecksCount = 0;

    /**
     * @var int
     * @ORM\Column(name="SubscribesCount", type="integer", nullable=false)
     */
    protected $SubscribesCount = 0;

    /**
     * @var int
     * @ORM\Column(name="UpdatesCount", type="integer", nullable=false)
     */
    protected $UpdatesCount = 0;

    /**
     * @var int
     * @ORM\Column(name="ErrorsCount", type="integer", nullable=false)
     */
    protected $ErrorsCount = 0;

    /**
     * @var string
     * @ORM\Column(name="Properties", type="text", nullable=false)
     */
    protected $Properties;

    /**
     * @var string
     * @ORM\Column(name="Schedule", type="text", nullable=false)
     */
    protected $Schedule;

    /**
     * @var Tripsegment[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="Tripsegment", mappedBy="flightinfoid", cascade={"persist"})
     */
    protected $Segments;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $State = self::STATE_NEW;

    /**
     * @var int
     * @ORM\Column(name="FlightState", type="integer", nullable=false)
     */
    protected $FlightState = self::FLIGHTSTATE_UNKNOWN;

    /**
     * @var string
     * @ORM\Column(name="ErrorMessage", type="string", length=100, nullable=false)
     */
    protected $ErrorMessage = '';

    protected $_properties = [];
    protected $_schedule = [];

    public function __construct()
    {
        $this->Segments = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getFlightInfoID()
    {
        return $this->FlightInfoID;
    }

    public function addSegment(Tripsegment $segment)
    {
        $this->Segments->add($segment);
    }

    /**
     * @return Tripsegment[]|PersistentCollection
     */
    public function getSegments()
    {
        return $this->Segments;
    }

    /**
     * @return string
     */
    public function getAirline()
    {
        return $this->Airline;
    }

    /**
     * @param string $Airline
     */
    public function setAirline($Airline)
    {
        $this->Airline = $Airline;
    }

    /**
     * @return string
     */
    public function getFlightNumber()
    {
        return $this->FlightNumber;
    }

    /**
     * @param string $FlightNumber
     */
    public function setFlightNumber($FlightNumber)
    {
        $this->FlightNumber = $FlightNumber;
    }

    /**
     * @return \DateTime
     */
    public function getFlightDate()
    {
        return $this->FlightDate;
    }

    /**
     * @param \DateTime $FlightDate
     */
    public function setFlightDate($FlightDate)
    {
        $this->FlightDate = clone $FlightDate;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->CreateDate;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->UpdateDate;
    }

    /**
     * @return int
     */
    public function getUpdatesCount()
    {
        return $this->UpdatesCount;
    }

    /**
     * @return array|null
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * @param []|null $Properties
     * @deprecated
     */
    public function setProperties($Properties)
    {
        if (empty($Properties)) {
            return;
        } // keep last non-empty response

        $this->_properties = $Properties;
        $this->UpdateDate = new \DateTime();
    }

    /**
     * @param string $service
     * @param array $Properties
     * @param array $ignoreFields
     */
    public function setPropertiesFromService($service, $Properties, $ignoreFields = [])
    {
        if (empty($Properties)) {
            return;
        } // keep last non-empty response

        if (!is_array($this->_properties)) {
            $this->_properties = ['info' => []];
        }

        if (!array_key_exists('info', $this->_properties)) {
            $this->_properties['info'] = [];
        }

        if (!array_key_exists($service, $this->_properties)) {
            $this->_properties[$service] = $Properties;
            $this->_properties[$service . ':log'] = [];
        } else {
            $this->_properties[$service] = array_merge($this->_properties[$service], $Properties);
        }
        $this->_properties[$service . ':log'][(new \DateTime())->format('c')] = $Properties;

        foreach ($ignoreFields as $field) {
            unset($Properties[$field]);
        }
        $this->_properties['info'] = array_merge($this->_properties['info'], $Properties);

        $this->UpdateDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->State;
    }

    /**
     * @param int $State
     */
    public function setState($State)
    {
        $this->State = $State;

        if ($State != self::STATE_ERROR && $this->getFlightState() >= self::FLIGHTSTATE_ERROR_AIRLINE) {
            $this->setFlightState(self::FLIGHTSTATE_UNKNOWN);
        }
        $this->UpdateDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getErrorsCount()
    {
        return $this->ErrorsCount;
    }

    /**
     * @param int $ErrorsCount
     */
    public function setErrorsCount($ErrorsCount)
    {
        $this->ErrorsCount = $ErrorsCount;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->ErrorMessage;
    }

    /**
     * @param string $ErrorMessage
     */
    public function setErrorMessage($ErrorMessage)
    {
        $this->ErrorMessage = $ErrorMessage;
    }

    /**
     * @return string
     */
    public function getDepCode()
    {
        return $this->DepCode;
    }

    /**
     * @param string $DepCode
     */
    public function setDepCode($DepCode)
    {
        $this->DepCode = $DepCode;
    }

    /**
     * @return string
     */
    public function getArrCode()
    {
        return $this->ArrCode;
    }

    /**
     * @param string $ArrCode
     */
    public function setArrCode($ArrCode)
    {
        $this->ArrCode = $ArrCode;
    }

    /**
     * @return \DateTime
     */
    public function getDepDate()
    {
        return $this->DepDate;
    }

    /**
     * @param \DateTime $DepDate
     */
    public function setDepDate($DepDate)
    {
        $this->DepDate = $DepDate;
    }

    /**
     * @return \DateTime
     */
    public function getArrDate()
    {
        return $this->ArrDate;
    }

    /**
     * @param \DateTime $ArrDate
     */
    public function setArrDate($ArrDate)
    {
        $this->ArrDate = $ArrDate;
    }

    /**
     * @return int
     */
    public function getChecksCount()
    {
        return $this->ChecksCount;
    }

    /**
     * @param int $ChecksCount
     */
    public function setChecksCount($ChecksCount)
    {
        $this->ChecksCount = $ChecksCount;
    }

    /**
     * @return int
     */
    public function getSubscribesCount()
    {
        return $this->SubscribesCount;
    }

    /**
     * @param int $SubscribesCount
     */
    public function setSubscribesCount($SubscribesCount)
    {
        $this->SubscribesCount = $SubscribesCount;
    }

    /**
     * @return array
     */
    public function getSchedule()
    {
        return $this->_schedule;
    }

    /**
     * @param array $Schedule
     * @deprecated
     */
    public function setSchedule($Schedule)
    {
        $this->_schedule = $Schedule;
    }

    /**
     * @param string $task
     * @param bool $onlyOneTask
     * @return bool
     */
    public function scheduleTask($task, $onlyOneTask = true)
    {
        foreach ($this->_schedule as $i => $item) {
            if (!$onlyOneTask && $item[0] == $task && !isset($item[1])) {
                return false;
            }

            if ($onlyOneTask && $item[0] == $task) {
                return false;
            }
        }
        $this->_schedule[] = [$task];

        return true;
    }

    /**
     * @param string $task
     * @param \DateTime|null $date
     * @return bool
     */
    public function publishTask($task, $date = null)
    {
        $idx = false;

        foreach ($this->_schedule as $i => $item) {
            if ($item[0] == $task && !isset($item[1])) {
                $idx = $i;

                break;
            }
        }

        if ($idx !== false) {
            if (empty($date)) {
                $date = new \DateTime();
            }
            $this->_schedule[$idx][1] = $date->format('c');

            return true;
        }

        return false;
    }

    /**
     * @param string $task
     * @param string $result
     * @param \DateTime|null $date
     * @return bool
     */
    public function finalizeTask($task, $result, $date = null)
    {
        $idx = false;

        foreach ($this->_schedule as $i => $item) {
            if ($item[0] == $task && isset($item[1]) && !isset($item[2])) {
                $idx = $i;

                break;
            }
        }

        if ($idx !== false) {
            if (empty($date)) {
                $date = new \DateTime();
            }
            $this->_schedule[$idx][2] = $date->format('c');
            $this->_schedule[$idx][3] = $result;

            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getScheduledTasks()
    {
        $ret = [];

        foreach ($this->_schedule as $i => $item) {
            if (!isset($item[1])) {
                $ret[] = $item[0];
            }
        }

        return $ret;
    }

    /**
     * @return string[]
     */
    public function getPublishedTasks()
    {
        $ret = [];

        foreach ($this->_schedule as $i => $item) {
            if (isset($item[1]) && !isset($item[2])) {
                $ret[] = $item[0];
            }
        }

        return $ret;
    }

    /**
     * @return string[]
     */
    public function getFinalizedTasks()
    {
        $ret = [];

        foreach ($this->_schedule as $i => $item) {
            if (isset($item[1]) && isset($item[2])) {
                $ret[] = $item[0];
            }
        }

        return $ret;
    }

    /**
     * @return int
     */
    public function getFlightState()
    {
        return $this->FlightState;
    }

    /**
     * @param int $FlightState
     */
    public function setFlightState($FlightState)
    {
        $this->FlightState = $FlightState;
    }

    /**
     * @ORM\PreFlush
     */
    public function preFlush()
    {
        $this->Properties = serialize($this->_properties);
        $this->Schedule = serialize($this->_schedule);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (empty($this->CreateDate)) {
            $this->CreateDate = new \DateTime();
        }
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->_properties = $this->Properties ? unserialize($this->Properties) : [];
        $this->_schedule = $this->Schedule ? unserialize($this->Schedule) : [];
    }

    public function incErrors()
    {
        $this->ErrorsCount++;
    }

    public function incUpdates()
    {
        $this->UpdatesCount++;
    }

    public function incChecks()
    {
        $this->ChecksCount++;
    }

    public function incSubscribes()
    {
        $this->SubscribesCount++;
    }

    /**
     * @return array
     */
    public function convertToTripsegment()
    {
        if ($this->isLoaded()) {
            $data = $this->_properties['info'];
            $data['DepDate'] = new \DateTime($data['DepDate']);
            $data['ArrDate'] = new \DateTime($data['ArrDate']);
            unset($data['DepDateUtc']);
            unset($data['ArrDateUtc']);
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * @return \DateTime|null
     */
    public function getDepartureLocalDate()
    {
        if ($this->isLoaded() && !empty($this->_properties['info']['DepDate'])) {
            return new \DateTime($this->_properties['info']['DepDate']);
        }

        return null;
    }

    /**
     * @return \DateTime|null
     */
    public function getArrivalLocalDate()
    {
        if ($this->isLoaded() && !empty($this->_properties['info']['ArrDate'])) {
            return new \DateTime($this->_properties['info']['ArrDate']);
        }

        return null;
    }

    /**
     * @return \DateTime|null
     */
    public function getDepartureUTCDate()
    {
        if ($this->isLoaded() && !empty($this->_properties['info']['DepDateUtc'])) {
            return new \DateTime($this->_properties['info']['DepDateUtc']);
        }

        return null;
    }

    /**
     * @return \DateTime|null
     */
    public function getArrivalUTCDate()
    {
        if ($this->isLoaded() && !empty($this->_properties['info']['ArrDateUtc'])) {
            return new \DateTime($this->_properties['info']['ArrDateUtc']);
        }

        return null;
    }

    /**
     * loaded info.
     *
     * @return bool
     */
    public function isLoaded()
    {
        return is_array($this->_properties) && array_key_exists('info', $this->_properties);
    }

    /**
     * can check flight on exists.
     *
     * @return bool
     */
    public function canCheck()
    {
        if (!$this->isFilled()) {
            return false;
        }

        if (!$this->isRequestable()) {
            return false;
        }

        if (!in_array($this->getState(), [self::STATE_NEW, self::STATE_ERROR])) {
            return false;
        }

        return true;
    }

    /**
     * can subscribe to flight alerts.
     *
     * @return bool
     */
    public function canSubscribe()
    {
        if (!$this->isFilled()) {
            return false;
        }

        if (!$this->isRequestable()) {
            return false;
        }

        if (!in_array($this->getState(), [self::STATE_CHECKED])) {
            return false;
        }

        // too late for subscribes
        if ($this->getArrivalUTCDate() && $this->getArrivalUTCDate()->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    /**
     * can update flight info.
     *
     * @return bool
     */
    public function canUpdate()
    {
        if (!$this->isFilled()) {
            return false;
        }

        if (!$this->isRequestable()) {
            return false;
        }

        if (!in_array($this->getState(), [self::STATE_CHECKED, self::STATE_DONE])) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isFilled()
    {
        return !(
            empty($this->getFlightDate())
            || empty($this->getAirline())
            || empty($this->getFlightNumber())
            || empty($this->getDepCode())
            || empty($this->getArrCode())
        ) && !in_array($this->getFlightState(), [self::FLIGHTSTATE_ERROR_AIRLINE, self::FLIGHTSTATE_ERROR_NUMBER]);
    }

    /**
     * @return bool
     */
    public function isRequestable()
    {
        return
            $this->getFlightDate()->getTimestamp() > (new \DateTime('-2 day'))->getTimestamp()
            && $this->getFlightDate()->getTimestamp() < (new \DateTime('+2 day'))->getTimestamp()
        ;
    }
}
