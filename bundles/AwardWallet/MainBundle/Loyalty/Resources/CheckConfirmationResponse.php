<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use AwardWallet\Schema\Itineraries\Itinerary;
use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'CheckConfirmationResponse'.
 */
class CheckConfirmationResponse
{
    /**
     * @var string
     * @Type("string")
     */
    protected $requestId;

    /**
     * @var string
     * @Type("string")
     */
    protected $userData;

    /**
     * @var string
     * @Type("string")
     */
    protected $provider;

    /**
     * @var string
     * @Type("string")
     */
    protected $debugInfo;

    /**
     * @var int
     * @Type("integer")
     */
    protected $state;

    /**
     * @var string
     * @Type("string")
     */
    protected $message;

    /**
     * @var string
     * @Type("string")
     */
    protected $errorReason;

    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     */
    protected $checkDate;

    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     */
    protected $requestDate;

    /**
     * @var Itinerary
     * @Type("array<AwardWallet\Schema\Itineraries\Itinerary>")
     */
    protected $itineraries;

    /**
     * @param string
     * @return $this
     */
    public function setRequestid($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setUserdata($userData)
    {
        $this->userData = $userData;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setDebuginfo($debugInfo)
    {
        $this->debugInfo = $debugInfo;

        return $this;
    }

    /**
     * @param int
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param \DateTime
     * @return $this
     */
    public function setCheckdate($checkDate)
    {
        $this->checkDate = $checkDate;

        return $this;
    }

    /**
     * @param \DateTime
     * @return $this
     */
    public function setRequestdate($requestDate)
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setItineraries($itineraries)
    {
        $this->itineraries = $itineraries;

        return $this;
    }

    /**
     * @return $this
     */
    public function setErrorreason($errorReason)
    {
        $this->errorReason = $errorReason;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorreason()
    {
        return $this->errorReason;
    }

    /**
     * @return string
     */
    public function getRequestid()
    {
        return $this->requestId;
    }

    /**
     * @return string
     */
    public function getUserdata()
    {
        return $this->userData;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getDebuginfo()
    {
        return $this->debugInfo;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \DateTime
     */
    public function getCheckdate()
    {
        return $this->checkDate;
    }

    /**
     * @return \DateTime
     */
    public function getRequestdate()
    {
        return $this->requestDate;
    }

    /**
     * @return array
     */
    public function getItineraries()
    {
        return $this->itineraries;
    }
}
