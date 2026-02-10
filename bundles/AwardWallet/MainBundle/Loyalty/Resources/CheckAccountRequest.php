<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'CheckAccountRequest'.
 */
class CheckAccountRequest
{
    /**
     * @var int
     * @Type("integer")
     */
    protected $timeout;
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
    private $password;

    /**
     * @var string
     * @Type("string")
     */
    private $userId;

    /**
     * @var UserData
     * @Type("string")
     */
    private $userData;

    /**
     * @var string
     * @Type("string")
     */
    private $state;

    /**
     * @var int
     * @Type("integer")
     */
    private $priority;

    /**
     * @var string
     * @Type("string")
     */
    private $callbackUrl;

    /**
     * @var int
     * @Type("integer")
     */
    private $retries;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $parseItineraries;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $parsePastItineraries = false;

    /**
     * @var string
     * @Type("string")
     */
    private $browserState;

    /**
     * @var Answer[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Answer>")
     */
    private $answers;

    /**
     * @var RequestItemHistory
     * @Type("AwardWallet\MainBundle\Loyalty\Resources\RequestItemHistory")
     */
    private $history;

    /**
     * @var array
     * @Type("array")
     */
    private $options;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $browserExtensionAllowed = false;

    /**
     * @Type("string")
     */
    private ?string $loginId;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $mailboxConnected = false;

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
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setUserid($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    public function setUserdata(?UserData $userData): self
    {
        $this->userData = $userData;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param int
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setCallbackurl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    /**
     * @param int
     * @return $this
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setParseitineraries($parseItineraries)
    {
        $this->parseItineraries = $parseItineraries;

        return $this;
    }

    public function isParsePastItineraries(): bool
    {
        return $this->parsePastItineraries;
    }

    public function setParsePastItineraries(bool $parsePastItineraries): self
    {
        $this->parsePastItineraries = $parsePastItineraries;

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
    public function setAnswers($answers)
    {
        $this->answers = $answers;

        return $this;
    }

    /**
     * @param RequestItemHistory
     * @return $this
     */
    public function setHistory($history)
    {
        $this->history = $history;

        return $this;
    }

    /**
     * @param RequestItemFiles
     * @return $this
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @param array
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
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
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getUserid()
    {
        return $this->userId;
    }

    public function getUserdata(): ?UserData
    {
        return $this->userData;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return string
     */
    public function getCallbackurl()
    {
        return $this->callbackUrl;
    }

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @return bool
     */
    public function getParseitineraries()
    {
        return $this->parseItineraries;
    }

    /**
     * @return string
     */
    public function getBrowserstate()
    {
        return $this->browserState;
    }

    /**
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @return RequestItemHistory
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return $this
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setBrowserExtensionAllowed(bool $browserExtensionAllowed): CheckAccountRequest
    {
        $this->browserExtensionAllowed = $browserExtensionAllowed;

        return $this;
    }

    public function isBrowserExtensionAllowed(): bool
    {
        return $this->browserExtensionAllowed;
    }

    public function setLoginId(?string $loginId): void
    {
        $this->loginId = $loginId;
    }

    public function isMailboxConnected(): bool
    {
        return $this->mailboxConnected;
    }

    public function setMailboxConnected(bool $mailboxConnected): void
    {
        $this->mailboxConnected = $mailboxConnected;
    }
}
