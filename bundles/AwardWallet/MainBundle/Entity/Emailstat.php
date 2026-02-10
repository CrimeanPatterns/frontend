<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Emailstat.
 *
 * @ORM\Table(name="EmailStat")
 * @ORM\Entity
 */
class Emailstat
{
    /**
     * @var int
     * @ORM\Column(name="EmailStatID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $emailstatid;

    /**
     * @var \DateTime
     * @ORM\Column(name="StatDate", type="date", nullable=false)
     */
    protected $statdate;

    /**
     * @var string
     * @ORM\Column(name="Kind", type="string", length=80, nullable=false)
     */
    protected $kind;

    /**
     * @var int
     * @ORM\Column(name="Messages", type="integer", nullable=false)
     */
    protected $messages;

    /**
     * Get emailstatid.
     *
     * @return int
     */
    public function getEmailstatid()
    {
        return $this->emailstatid;
    }

    /**
     * Set statdate.
     *
     * @param \DateTime $statdate
     * @return Emailstat
     */
    public function setStatdate($statdate)
    {
        $this->statdate = $statdate;

        return $this;
    }

    /**
     * Get statdate.
     *
     * @return \DateTime
     */
    public function getStatdate()
    {
        return $this->statdate;
    }

    /**
     * Set kind.
     *
     * @param string $kind
     * @return Emailstat
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set messages.
     *
     * @param int $messages
     * @return Emailstat
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Get messages.
     *
     * @return int
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
