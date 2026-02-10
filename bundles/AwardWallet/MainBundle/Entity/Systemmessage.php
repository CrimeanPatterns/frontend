<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Systemmessage.
 *
 * @ORM\Table(name="SystemMessage")
 * @ORM\Entity
 */
class Systemmessage
{
    /**
     * @var int
     * @ORM\Column(name="SystemMessageID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $systemmessageid;

    /**
     * @var int
     * @ORM\Column(name="Type", type="integer", nullable=false)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(name="Message", type="string", length=50, nullable=false)
     */
    protected $message;

    /**
     * @var \DateTime
     * @ORM\Column(name="TimeStamp", type="datetime", nullable=false)
     */
    protected $timestamp;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    /**
     * Get systemmessageid.
     *
     * @return int
     */
    public function getSystemmessageid()
    {
        return $this->systemmessageid;
    }

    /**
     * Set type.
     *
     * @param int $type
     * @return Systemmessage
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set message.
     *
     * @param string $message
     * @return Systemmessage
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
     * Set timestamp.
     *
     * @param \DateTime $timestamp
     * @return Systemmessage
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp.
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
