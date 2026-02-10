<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use AwardWallet\Schema\Itineraries\Itinerary;
use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'CheckAccountResponse'.
 */
class CheckAccountResponse
{
    /**
     * @var string
     * @Type("string")
     */
    private $requestId;

    /**
     * @var UserData
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\UserData")
     */
    private $userData;

    /**
     * @var string
     * @Type("string")
     */
    private $provider;

    /**
     * @var string
     * @Type("string")
     */
    private $login;

    /**
     * @var string
     * @Type("string")
     */
    private $login2;

    /**
     * @var string
     * @Type("string")
     */
    private $login3;

    /**
     * @var string
     * @Type("string")
     */
    private $debugInfo;

    /**
     * @var int
     * @Type("integer")
     */
    private $state;

    /**
     * @var string
     * @Type("string")
     */
    private $message;

    /**
     * @var string
     * @Type("string")
     */
    private $errorReason;

    /**
     * @var string
     * @Type("string")
     */
    private $question;

    /**
     * @var string
     * @Type("string")
     */
    private $balance;

    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d'>")
     */
    private $expirationDate;

    /**
     * @var Property[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Property>")
     */
    private $properties;

    /**
     * @var int
     * @Type("integer")
     */
    private $eliteLevel;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $noItineraries;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $neverExpires;

    /**
     * @var SubAccount[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\SubAccount>")
     */
    private $subAccounts;

    /**
     * @var DetectedCard[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\DetectedCard>")
     */
    private $detectedCards;

    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     */
    private $checkDate;

    /**
     * @var \DateTime
     * @Type("DateTime<'Y-m-d\TH:i:sP'>")
     */
    private $requestDate;

    /**
     * @var string
     * @Type("string")
     */
    private $mode;

    /**
     * @var string
     * @Type("string")
     */
    private $browserState;

    /**
     * @var Itinerary
     * @Type("array<AwardWallet\Schema\Itineraries\Itinerary>")
     */
    private $itineraries = [];

    /**
     * @var History
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\History")
     */
    private $history;

    /**
     * @var int
     * @Type("integer")
     */
    private $filesVersion;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $filesCacheValid;

    /**
     * @var Answer[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Answer>")
     */
    private $invalidAnswers;

    /**
     * @var string
     * @Type("string")
     */
    private $options;

    /**
     * @Type("boolean")
     */
    private ?bool $checkedByClientBrowser = null;

    /**
     * CheckAccountResponse constructor.
     */
    public function __construct()
    {
        $this->checkDate = new \DateTime();
    }

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
     * @param UserData
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
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setLogin2($login2)
    {
        $this->login2 = $login2;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setLogin3($login3)
    {
        $this->login3 = $login3;

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
     * @param string
     * @return $this
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param int
     * @return $this
     */
    public function setElitelevel($eliteLevel)
    {
        $this->eliteLevel = $eliteLevel;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setNoitineraries($noItineraries)
    {
        $this->noItineraries = $noItineraries;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setNeverexpires($neverExpires)
    {
        $this->neverExpires = $neverExpires;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setSubaccounts($subAccounts)
    {
        $this->subAccounts = $subAccounts;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setDetectedcards($detectedCards)
    {
        $this->detectedCards = $detectedCards;

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
     * @param string
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setBrowserstate($browserState)
    {
        $this->browserState = $browserState;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setItineraries(array $itineraries)
    {
        $this->itineraries = $itineraries;

        return $this;
    }

    /**
     * @param History $history
     * @return $this
     */
    public function setHistory($history)
    {
        $this->history = $history;

        return $this;
    }

    /**
     * @param int
     * @return $this
     */
    public function setFilesversion($filesVersion)
    {
        $this->filesVersion = $filesVersion;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setFilescachevalid($filesCacheValid)
    {
        $this->filesCacheValid = $filesCacheValid;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setInvalidanswers($invalidAnswers)
    {
        $this->invalidAnswers = $invalidAnswers;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

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
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param \DateTime
     * @return $this
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

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

    public function getUserdata(): ?UserData
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
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getLogin2()
    {
        return $this->login2;
    }

    /**
     * @return string
     */
    public function getLogin3()
    {
        return $this->login3;
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
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return int
     */
    public function getElitelevel()
    {
        return $this->eliteLevel;
    }

    /**
     * @return bool
     */
    public function getNoitineraries()
    {
        return $this->noItineraries;
    }

    /**
     * @return bool
     */
    public function getNeverexpires()
    {
        return $this->neverExpires;
    }

    /**
     * @return array
     */
    public function getSubaccounts()
    {
        return $this->subAccounts;
    }

    /**
     * @return array
     */
    public function getDetectedcards()
    {
        return $this->detectedCards;
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
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function getBrowserstate()
    {
        return $this->browserState;
    }

    public function getItineraries(): array
    {
        return $this->itineraries;
    }

    /**
     * @return History
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @return int
     */
    public function getFilesversion()
    {
        return $this->filesVersion;
    }

    /**
     * @return bool
     */
    public function getFilescachevalid()
    {
        return $this->filesCacheValid;
    }

    /**
     * @return Answer[]
     */
    public function getInvalidanswers()
    {
        return $this->invalidAnswers;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function haveCheckedItineraries(): bool
    {
        // Have we checked itineraries at all?
        if (null === $this->getUserdata()) {
            return false;
        }
        /** @var UserData $userData */
        $userData = $this->getUserdata();

        if (false === $userData->isCheckIts()) {
            return false;
        }

        return true;
    }

    public function setCheckedByClientBrowser(?bool $checkedByClientBrowser): CheckAccountResponse
    {
        $this->checkedByClientBrowser = $checkedByClientBrowser;

        return $this;
    }

    public function getCheckedByClientBrowser(): ?bool
    {
        return $this->checkedByClientBrowser;
    }
}
