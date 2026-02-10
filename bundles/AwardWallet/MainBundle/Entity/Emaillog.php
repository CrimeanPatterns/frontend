<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Emaillog.
 *
 * @ORM\Table(name="EmailLog")
 * @ORM\Entity
 */
class Emaillog
{
    /**
     * @var int
     * @ORM\Column(name="EmailLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $emaillogid;

    /**
     * @var int
     * @ORM\Column(name="MessageKind", type="integer", nullable=false)
     */
    protected $messagekind;

    /**
     * @var \DateTime
     * @ORM\Column(name="EmailDate", type="datetime", nullable=false)
     */
    protected $emaildate;

    /**
     * @var int
     * @ORM\Column(name="MessageCount", type="integer", nullable=false)
     */
    protected $messagecount = 1;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get emaillogid.
     *
     * @return int
     */
    public function getEmaillogid()
    {
        return $this->emaillogid;
    }

    /**
     * Set messagekind.
     *
     * @param int $messagekind
     * @return Emaillog
     */
    public function setMessagekind($messagekind)
    {
        $this->messagekind = $messagekind;

        return $this;
    }

    /**
     * Get messagekind.
     *
     * @return int
     */
    public function getMessagekind()
    {
        return $this->messagekind;
    }

    /**
     * Set emaildate.
     *
     * @param \DateTime $emaildate
     * @return Emaillog
     */
    public function setEmaildate($emaildate)
    {
        $this->emaildate = $emaildate;

        return $this;
    }

    /**
     * Get emaildate.
     *
     * @return \DateTime
     */
    public function getEmaildate()
    {
        return $this->emaildate;
    }

    /**
     * Set messagecount.
     *
     * @param int $messagecount
     * @return Emaillog
     */
    public function setMessagecount($messagecount)
    {
        $this->messagecount = $messagecount;

        return $this;
    }

    /**
     * Get messagecount.
     *
     * @return int
     */
    public function getMessagecount()
    {
        return $this->messagecount;
    }

    /**
     * Set userid.
     *
     * @return Emaillog
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
