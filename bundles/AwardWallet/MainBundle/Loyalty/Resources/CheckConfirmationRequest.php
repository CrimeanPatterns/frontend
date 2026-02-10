<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'CheckConfirmationRequest'.
 */
class CheckConfirmationRequest
{
    /**
     * @var string
     * @Type("string")
     */
    protected $provider;

    /**
     * @var string
     * @Type("string")
     */
    protected $userId;

    /**
     * @var string
     * @Type("string")
     */
    protected $userData;

    /**
     * @var int
     * @Type("integer")
     */
    protected $priority;

    /**
     * @var string
     * @Type("string")
     */
    protected $callbackUrl;

    /**
     * @var int
     * @Type("integer")
     */
    protected $retries;

    /**
     * @var int
     * @Type("integer")
     */
    protected $timeout;
    /**
     * @var InputField[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\InputField>")
     */
    private $fields;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $browserExtensionAllowed = false;

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

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
     * @param string $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param string $userData
     * @return $this
     */
    public function setUserData($userData)
    {
        $this->userData = $userData;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
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
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     * @return $this
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->retries;
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

    public function setBrowserExtensionAllowed(bool $browserExtensionAllowed): self
    {
        $this->browserExtensionAllowed = $browserExtensionAllowed;

        return $this;
    }
}
