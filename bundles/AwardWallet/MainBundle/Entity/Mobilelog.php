<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mobilelog.
 *
 * @ORM\Table(name="MobileLog")
 * @ORM\Entity
 */
class Mobilelog
{
    /**
     * @var int
     * @ORM\Column(name="MobileLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $mobilelogid;

    /**
     * @var \DateTime
     * @ORM\Column(name="LogTime", type="datetime", nullable=false)
     */
    protected $logtime;

    /**
     * @var string
     * @ORM\Column(name="LogModule", type="string", length=20, nullable=true)
     */
    protected $logmodule;

    /**
     * @var string
     * @ORM\Column(name="Message", type="text", nullable=true)
     */
    protected $message;

    /**
     * @var string
     * @ORM\Column(name="LogLevel", type="string", length=10, nullable=true)
     */
    protected $loglevel;

    /**
     * @var \DateTime
     * @ORM\Column(name="AddTime", type="datetime", nullable=false)
     */
    protected $addtime;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    public function __construct()
    {
        $this->addtime = new \DateTime();
    }

    /**
     * Get mobilelogid.
     *
     * @return int
     */
    public function getMobilelogid()
    {
        return $this->mobilelogid;
    }

    /**
     * Set logtime.
     *
     * @param \DateTime $logtime
     * @return Mobilelog
     */
    public function setLogtime($logtime)
    {
        $this->logtime = $logtime;

        return $this;
    }

    /**
     * Get logtime.
     *
     * @return \DateTime
     */
    public function getLogtime()
    {
        return $this->logtime;
    }

    /**
     * Set logmodule.
     *
     * @param string $logmodule
     * @return Mobilelog
     */
    public function setLogmodule($logmodule)
    {
        $this->logmodule = $logmodule;

        return $this;
    }

    /**
     * Get logmodule.
     *
     * @return string
     */
    public function getLogmodule()
    {
        return $this->logmodule;
    }

    /**
     * Set message.
     *
     * @param string $message
     * @return Mobilelog
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set loglevel.
     *
     * @param string $loglevel
     * @return Mobilelog
     */
    public function setLoglevel($loglevel)
    {
        $this->loglevel = $loglevel;

        return $this;
    }

    /**
     * Get loglevel.
     *
     * @return string
     */
    public function getLoglevel()
    {
        return $this->loglevel;
    }

    /**
     * Set addtime.
     *
     * @param \DateTime $addtime
     * @return Mobilelog
     */
    public function setAddtime($addtime)
    {
        $this->addtime = $addtime;

        return $this;
    }

    /**
     * Get addtime.
     *
     * @return \DateTime
     */
    public function getAddtime()
    {
        return $this->addtime;
    }

    /**
     * Set userid.
     *
     * @return Mobilelog
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
}
