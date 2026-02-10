<?php

namespace AwardWallet\MainBundle\Security\RememberMe;

use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;

class AwPersistentToken implements PersistentTokenInterface
{
    private $class;
    private $username;
    private $series;
    private $tokenValue;
    private $lastUsed;
    private $ip;
    private $userAgent;
    private $tokenId;

    /**
     * Constructor.
     *
     * @param string $class
     * @param string $username
     * @param string $series
     * @param string $tokenValue
     * @param string|null $ip
     * @param string|null $userAgent
     * @throws \InvalidArgumentException
     */
    public function __construct($class, $username, $series, $tokenValue, \DateTime $lastUsed, $ip, $userAgent, $tokenId)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class must not be empty.');
        }

        if (empty($username)) {
            throw new \InvalidArgumentException('$username must not be empty.');
        }

        if (empty($series)) {
            throw new \InvalidArgumentException('$series must not be empty.');
        }

        if (empty($tokenValue)) {
            throw new \InvalidArgumentException('$tokenValue must not be empty.');
        }

        $this->class = $class;
        $this->username = $username;
        $this->series = $series;
        $this->tokenValue = $tokenValue;
        $this->lastUsed = $lastUsed;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->tokenId = $tokenId;
    }

    /**
     * Set user class.
     *
     * @param string $class
     * @return AwPersistentToken $this
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSeries()
    {
        return $this->series;
    }

    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    public function getLastUsed()
    {
        return $this->lastUsed;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getUseragent()
    {
        return $this->userAgent;
    }

    public function getTokenId()
    {
        return $this->tokenId;
    }
}
