<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Polloption.
 *
 * @ORM\Table(name="PollOption")
 * @ORM\Entity
 */
class Polloption
{
    /**
     * @var int
     * @ORM\Column(name="PollOptionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $polloptionid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortindex = 0;

    /**
     * @var int
     * @ORM\Column(name="Votes", type="integer", nullable=false)
     */
    protected $votes = 0;

    /**
     * @var \Poll
     * @ORM\ManyToOne(targetEntity="Poll")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PollID", referencedColumnName="PollID")
     * })
     */
    protected $pollid;

    /**
     * Get polloptionid.
     *
     * @return int
     */
    public function getPolloptionid()
    {
        return $this->polloptionid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Polloption
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sortindex.
     *
     * @param int $sortindex
     * @return Polloption
     */
    public function setSortindex($sortindex)
    {
        $this->sortindex = $sortindex;

        return $this;
    }

    /**
     * Get sortindex.
     *
     * @return int
     */
    public function getSortindex()
    {
        return $this->sortindex;
    }

    /**
     * Set votes.
     *
     * @param int $votes
     * @return Polloption
     */
    public function setVotes($votes)
    {
        $this->votes = $votes;

        return $this;
    }

    /**
     * Get votes.
     *
     * @return int
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * Set pollid.
     *
     * @return Polloption
     */
    public function setPollid(?Poll $pollid = null)
    {
        $this->pollid = $pollid;

        return $this;
    }

    /**
     * Get pollid.
     *
     * @return \AwardWallet\MainBundle\Entity\Poll
     */
    public function getPollid()
    {
        return $this->pollid;
    }
}
