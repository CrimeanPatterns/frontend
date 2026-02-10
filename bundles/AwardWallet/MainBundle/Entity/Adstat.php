<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adstat.
 *
 * @ORM\Table(name="AdStat")
 * @ORM\Entity
 */
class Adstat
{
    /**
     * @var int
     * @ORM\Column(name="AdStatID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $adstatid;

    /**
     * @var \DateTime
     * @ORM\Column(name="StatDate", type="date", nullable=false)
     */
    protected $statdate;

    /**
     * @var int
     * @ORM\Column(name="Messages", type="integer", nullable=false)
     */
    protected $messages = 0;

    /**
     * @var int
     * @ORM\Column(name="Clicks", type="integer", nullable=false)
     */
    protected $clicks = 0;

    /**
     * @var int
     * @ORM\Column(name="Sent", type="integer", nullable=false)
     */
    protected $sent = 0;

    /**
     * @var Socialad
     * @ORM\ManyToOne(targetEntity="Socialad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SocialAdID", referencedColumnName="SocialAdID")
     * })
     */
    protected $socialadid;

    /**
     * Get adstatid.
     *
     * @return int
     */
    public function getAdstatid()
    {
        return $this->adstatid;
    }

    /**
     * Set statdate.
     *
     * @param \DateTime $statdate
     * @return Adstat
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
     * Set messages.
     *
     * @param int $messages
     * @return Adstat
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

    /**
     * Set clicks.
     *
     * @param int $clicks
     * @return Adstat
     */
    public function setClicks($clicks)
    {
        $this->clicks = $clicks;

        return $this;
    }

    /**
     * Get clicks.
     *
     * @return int
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * Set socialadid.
     *
     * @return Adstat
     */
    public function setSocialadid(?Socialad $socialadid = null)
    {
        $this->socialadid = $socialadid;

        return $this;
    }

    /**
     * Get socialadid.
     *
     * @return Socialad
     */
    public function getSocialadid()
    {
        return $this->socialadid;
    }

    /**
     * @return int
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @param int $sent
     * @return Adstat
     */
    public function setSent($sent)
    {
        $this->sent = $sent;

        return $this;
    }
}
