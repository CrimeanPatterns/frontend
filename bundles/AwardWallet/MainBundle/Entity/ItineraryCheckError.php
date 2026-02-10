<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItineraryCheckError.
 *
 * @ORM\Table(name="ItineraryCheckError")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ItineraryCheckErrorRepository")
 */
class ItineraryCheckError
{
    // Error types
    public const NO_UPDATE = 2;
    public const PARSER_NOTICE = 3;
    public const RETRIEVE_ERROR = 4;
    public const NO_FUTURE_ITINERARIES = 5;
    public const SHOULD_BE_NO_ITINERARIES = 6;
    public const OUTDATED = 7;

    // Error operating statuses
    public const STATUS_NEW = 1;
    //	const STATUS_IN_PROGRESS = 2;
    public const STATUS_RESOLVED = 3;

    public static $errorDescription = [
        self::NO_UPDATE => 'No update',
        self::PARSER_NOTICE => 'Parser notice',
        self::RETRIEVE_ERROR => 'Errors from retrieve by conf #',
        self::NO_FUTURE_ITINERARIES => 'No future itineraries',
        self::SHOULD_BE_NO_ITINERARIES => 'Should be noItinerariesArr', // '0 itineraries should be noItinerariesArr',
        self::OUTDATED => 'Outdated itineraries',
    ];

    public static $statusDescription = [
        self::STATUS_NEW => 'New',
        //		self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_RESOLVED => 'Resolved',
    ];

    /**
     * @var int
     * @ORM\Column(name="ItineraryCheckErrorID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $itinerarycheckerrorid;

    /**
     * @var \DateTime
     * @ORM\Column(name="DetectionDate", type="datetime", nullable=false)
     */
    protected $detectiondate;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID", nullable=false)
     * })
     */
    protected $providerid;

    /**
     * @var string
     * @ORM\Column(name="ItineraryType", type="string", length=1, nullable=true)
     */
    protected $itinerarytype;

    /**
     * @var int
     * @ORM\Column(name="ItineraryID", type="integer", nullable=true)
     */
    protected $itineraryid;

    /**
     * @var string
     * @ORM\Column(name="ConfirmationNumber", type="string", length=100, nullable=true)
     */
    protected $confirmationnumber;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID", nullable=true)
     * })
     */
    protected $accountid;

    /**
     * @var string
     * @ORM\Column(name="RequestID", type="string", length=64, nullable=true)
     */
    protected $requestid;

    /**
     * @var string
     * @ORM\Column(name="Partner", type="string", length=40, nullable=true)
     */
    protected $partner;

    /**
     * @var int
     * @ORM\Column(name="ErrorType", type="integer", nullable=false)
     */
    protected $errortype;

    /**
     * @var string
     * @ORM\Column(name="ErrorMessage", type="string", nullable=true)
     */
    protected $errormessage;

    /**
     * @var int
     * @ORM\Column(name="Status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="string", nullable=true)
     */
    protected $comment;

    public function __construct()
    {
    }

    /**
     * Get itinerarycheckerrorid.
     *
     * @return int
     */
    public function getItinerarycheckerrorid()
    {
        return $this->itinerarycheckerrorid;
    }

    /**
     * Set detectiondate.
     *
     * @param \DateTime $detectiondate
     * @return ItineraryCheckError
     */
    public function setDetectiondate($detectiondate)
    {
        $this->detectiondate = $detectiondate;

        return $this;
    }

    /**
     * Get detectiondate.
     *
     * @return \DateTime
     */
    public function getDetectiondate()
    {
        return $this->detectiondate;
    }

    /**
     * Set itinerarytype.
     *
     * @param string $itinerarytype
     * @return ItineraryCheckError
     */
    public function setItinerarytype($itinerarytype)
    {
        $this->itinerarytype = $itinerarytype;

        return $this;
    }

    /**
     * Get itinerarytype.
     *
     * @return string
     */
    public function getItinerarytype()
    {
        return $this->itinerarytype;
    }

    /**
     * Set itineraryid.
     *
     * @param int $itineraryid
     * @return ItineraryCheckError
     */
    public function setItineraryid($itineraryid)
    {
        $this->itineraryid = $itineraryid;

        return $this;
    }

    /**
     * Get itineraryid.
     *
     * @return int
     */
    public function getItineraryid()
    {
        return $this->itineraryid;
    }

    /**
     * Set confirmationnumber.
     *
     * @param string $confirmationnumber
     * @return ItineraryCheckError
     */
    public function setConfirmationnumber($confirmationnumber)
    {
        $this->confirmationnumber = $confirmationnumber;

        return $this;
    }

    /**
     * Get confirmationnumber.
     *
     * @return string
     */
    public function getConfirmationnumber()
    {
        return $this->confirmationnumber;
    }

    /**
     * Set errortype.
     *
     * @param int $errortype
     * @return ItineraryCheckError
     */
    public function setErrortype($errortype)
    {
        $this->errortype = $errortype;

        return $this;
    }

    /**
     * Get errortype.
     *
     * @return int
     */
    public function getErrortype()
    {
        return $this->errortype;
    }

    /**
     * Set errormessage.
     *
     * @param string $errormessage
     * @return ItineraryCheckError
     */
    public function setErrormessage($errormessage)
    {
        $this->errormessage = $errormessage;

        return $this;
    }

    /**
     * Get errormessage.
     *
     * @return string
     */
    public function getErrormessage()
    {
        return $this->errormessage;
    }

    /**
     * Set status.
     *
     * @param int $status
     * @return ItineraryCheckError
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     * @return ItineraryCheckError
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set providerid.
     *
     * @return ItineraryCheckError
     */
    public function setProviderid(Provider $providerid)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }

    /**
     * Set accountid.
     *
     * @return ItineraryCheckError
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }

    public function getRequestid(): string
    {
        return $this->requestid;
    }

    /**
     * @return ItineraryCheckError
     */
    public function setRequestid(string $requestid)
    {
        $this->requestid = $requestid;

        return $this;
    }

    public function getPartner(): string
    {
        return $this->partner;
    }

    /**
     * @return ItineraryCheckError
     */
    public function setPartner(string $partner)
    {
        $this->partner = $partner;

        return $this;
    }
}
