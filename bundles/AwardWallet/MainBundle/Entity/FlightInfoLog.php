<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacheStorageInterface;
use AwardWallet\MainBundle\Service\FlightInfo\Engine\HttpResponse;
use Doctrine\ORM\Mapping as ORM;

/**
 * FlightInfoLog.
 *
 * @ORM\Table(name="FlightInfoLog")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\FlightInfoLogRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FlightInfoLog
{
    public const STATE_NEW = 0;
    public const STATE_OK = 1;
    public const STATE_API_ERROR = 2;
    public const STATE_AUTH_ERROR = 3;

    /**
     * @var int
     * @ORM\Column(name="FlightInfoLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $FlightInfoLogID;

    /**
     * @var string
     * @ORM\Column(name="Service", type="string", length=100, nullable=false)
     */
    protected $Service = '';

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $State = self::STATE_NEW;

    /**
     * @var bool
     * @ORM\Column(name="Changed", type="boolean", nullable=false)
     */
    protected $Changed = false;

    /**
     * @var string
     * @ORM\Column(name="Request", type="string", length=1000, nullable=false)
     */
    protected $Request;

    /**
     * @var string
     * @ORM\Column(name="RequestHash", type="string", length=32, nullable=false)
     */
    protected $RequestHash;

    /**
     * @var string
     * @ORM\Column(name="Response", type="text", nullable=false)
     */
    protected $Response;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $CreateDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ExpireDate", type="datetime", nullable=true)
     */
    protected $ExpireDate;

    protected $_response;

    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getFlightInfoLogID()
    {
        return $this->FlightInfoLogID;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->CreateDate;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return urldecode($this->Request);
    }

    /**
     * @param string $Request
     */
    public function setRequest($Request)
    {
        $this->Request = $Request;
        $this->RequestHash = md5($this->Request);
    }

    /**
     * @return []|null
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return HttpResponse
     */
    public function getHttpResponse()
    {
        /** @var FlightInfoLog|CacheStorageInterface $this */
        return HttpResponse::createFromCache($this);
    }

    /**
     * @param HttpResponse|[]|null $Response
     */
    public function setResponse($Response)
    {
        /** @var FlightInfoLog|CacheStorageInterface $this */
        if ($Response instanceof HttpResponse) {
            $Response->saveToCache($this);
        } else {
            $this->_response = $Response;
        }
    }

    public function setHttpResponse(HttpResponse $Response)
    {
        /** @var FlightInfoLog|CacheStorageInterface $this */
        $Response->saveToCache($this);
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
     * @return bool
     */
    public function isChanged()
    {
        return $this->Changed;
    }

    /**
     * @param bool $Changed
     */
    public function setChanged($Changed)
    {
        $this->Changed = $Changed;
    }

    /**
     * @return \DateTime
     */
    public function getExpireDate()
    {
        return $this->ExpireDate;
    }

    /**
     * @param \DateTime $ExpireDate
     */
    public function setExpireDate($ExpireDate)
    {
        if ($this->ExpireDate) {
            return;
        }
        $this->ExpireDate = $ExpireDate;
    }

    /**
     * @return string
     */
    public function getRequestHash()
    {
        return $this->RequestHash;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        if (empty($this->ExpireDate)) {
            return true;
        }

        return $this->getExpireDate()->getTimestamp() <= time();
    }

    /**
     * @ORM\PreFlush
     */
    public function preFlush()
    {
        $this->Response = serialize($this->_response);
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
        $this->_response = $this->Response ? unserialize($this->Response) : $this->Response;
    }

    /**
     * @return mixed|void
     */
    public function getJson()
    {
        if ($this->_response && is_array($this->_response) && array_key_exists('content', $this->_response)) {
            if (is_array($this->_response['content'])) {
                return $this->_response['content'];
            }

            return json_decode($this->_response['content'], true);
        }

        return null;
    }
}
