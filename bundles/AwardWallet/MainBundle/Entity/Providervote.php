<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Providervote.
 *
 * @ORM\Table(name="ProviderVote")
 * @ORM\Entity
 */
class Providervote
{
    /**
     * @var int
     * @ORM\Column(name="ProviderVoteID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providervoteid;

    /**
     * @var \DateTime
     * @ORM\Column(name="VoteDate", type="datetime", nullable=false)
     */
    protected $votedate;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get providervoteid.
     *
     * @return int
     */
    public function getProvidervoteid()
    {
        return $this->providervoteid;
    }

    /**
     * Set votedate.
     *
     * @param \DateTime $votedate
     * @return Providervote
     */
    public function setVotedate($votedate)
    {
        $this->votedate = $votedate;

        return $this;
    }

    /**
     * Get votedate.
     *
     * @return \DateTime
     */
    public function getVotedate()
    {
        return $this->votedate;
    }

    /**
     * Set providerid.
     *
     * @return Providervote
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }

    /**
     * Set userid.
     *
     * @return Providervote
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
