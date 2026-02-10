<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Forum.
 *
 * @ORM\Table(name="Forum")
 * @ORM\Entity
 */
class Forum
{
    /**
     * @var int
     * @ORM\Column(name="ForumID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $forumid;

    /**
     * @var int
     * @ORM\Column(name="ForumNumber", type="integer", nullable=false)
     */
    protected $forumnumber;

    /**
     * @var string
     * @ORM\Column(name="FullName", type="text", nullable=true)
     */
    protected $fullname;

    /**
     * @var string
     * @ORM\Column(name="Email", type="text", nullable=true)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="Title", type="text", nullable=true)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="BodyText", type="text", nullable=true)
     */
    protected $bodytext;

    /**
     * @var string
     * @ORM\Column(name="IP", type="text", nullable=false)
     */
    protected $ip;

    /**
     * @var bool
     * @ORM\Column(name="Visible", type="boolean", nullable=false)
     */
    protected $visible;

    /**
     * @var int
     * @ORM\Column(name="Rank", type="integer", nullable=true)
     */
    protected $rank;

    /**
     * @var \DateTime
     * @ORM\Column(name="PostTime", type="datetime", nullable=true)
     */
    protected $posttime;

    /**
     * Get forumid.
     *
     * @return int
     */
    public function getForumid()
    {
        return $this->forumid;
    }

    /**
     * Set forumnumber.
     *
     * @param int $forumnumber
     * @return Forum
     */
    public function setForumnumber($forumnumber)
    {
        $this->forumnumber = $forumnumber;

        return $this;
    }

    /**
     * Get forumnumber.
     *
     * @return int
     */
    public function getForumnumber()
    {
        return $this->forumnumber;
    }

    /**
     * Set fullname.
     *
     * @param string $fullname
     * @return Forum
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Forum
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set title.
     *
     * @param string $title
     * @return Forum
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set bodytext.
     *
     * @param string $bodytext
     * @return Forum
     */
    public function setBodytext($bodytext)
    {
        $this->bodytext = $bodytext;

        return $this;
    }

    /**
     * Get bodytext.
     *
     * @return string
     */
    public function getBodytext()
    {
        return $this->bodytext;
    }

    /**
     * Set ip.
     *
     * @param string $ip
     * @return Forum
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     * @return Forum
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set rank.
     *
     * @param int $rank
     * @return Forum
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank.
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set posttime.
     *
     * @param \DateTime $posttime
     * @return Forum
     */
    public function setPosttime($posttime)
    {
        $this->posttime = $posttime;

        return $this;
    }

    /**
     * Get posttime.
     *
     * @return \DateTime
     */
    public function getPosttime()
    {
        return $this->posttime;
    }
}
