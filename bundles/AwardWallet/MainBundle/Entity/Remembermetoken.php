<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Remembermetoken.
 *
 * @ORM\Table(name="RememberMeToken")
 * @ORM\Entity
 */
class Remembermetoken
{
    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="RememberMeTokenID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $remembermetokenid;

    /**
     * @var string
     * @ORM\Column(name="Series", type="string", nullable=false, length=88)
     */
    protected $series;

    /**
     * @var string
     * @ORM\Column(name="Token", type="string", nullable=false, length=88)
     */
    protected $token;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastUsed", type="datetime", nullable=false)
     */
    protected $lastused;

    /**
     * @var string
     * @ORM\Column(name="IP", type="string", nullable=false, length=15)
     */
    protected $ip;

    /**
     * @var string
     * @ORM\Column(name="UserAgent", type="string", nullable=true, length=250)
     */
    protected $useragent;

    /**
     * Set series.
     *
     * @param string $series
     * @return Remembermetoken
     */
    public function setSeries($series)
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Get series.
     *
     * @return string
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * Set token.
     *
     * @param string $token
     * @return Remembermetoken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set lastused.
     *
     * @param \DateTime $lastused
     * @return Remembermetoken
     */
    public function setLastused($lastused)
    {
        $this->lastused = $lastused;

        return $this;
    }

    /**
     * Get lastused.
     *
     * @return \DateTime
     */
    public function getLastused()
    {
        return $this->lastused;
    }

    /**
     * Set userid.
     *
     * @return Remembermetoken
     */
    public function setUserid(?Usr $userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * @return Remembermetoken
     */
    public function setIp(string $ip)
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string|null $userAgent
     * @return Remembermetoken
     */
    public function setUseragent($userAgent)
    {
        $this->useragent = $userAgent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUseragent()
    {
        return $this->useragent;
    }
}
