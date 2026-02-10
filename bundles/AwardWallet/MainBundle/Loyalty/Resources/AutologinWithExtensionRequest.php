<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'CheckAccountRequest'.
 */
class AutologinWithExtensionRequest
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
     * @var int
     * @Type("integer")
     */
    private $priority;

    /**
     * @var int
     * @Type("integer")
     */
    private $retries;

    /**
     * @var Answer[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\Answer>")
     */
    private $answers;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $browserExtensionAllowed = false;

    /**
     * @Type("string")
     */
    private ?string $loginId;

    private bool $affiliateLinksAllowed = false;

    /**
     * @Type("string")
     */
    private ?string $targetUrl = null;

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
     * @param array
     * @return $this
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;

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
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
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

    public function setBrowserExtensionAllowed(bool $browserExtensionAllowed): AutologinWithExtensionRequest
    {
        $this->browserExtensionAllowed = $browserExtensionAllowed;

        return $this;
    }

    public function setLoginId(?string $loginId): self
    {
        $this->loginId = $loginId;

        return $this;
    }

    public function isAffiliateLinksAllowed(): bool
    {
        return $this->affiliateLinksAllowed;
    }

    public function setAffiliateLinksAllowed(bool $affiliateLinksAllowed): AutologinWithExtensionRequest
    {
        $this->affiliateLinksAllowed = $affiliateLinksAllowed;

        return $this;
    }

    public function setTargetUrl(?string $targetUrl): self
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }
}
