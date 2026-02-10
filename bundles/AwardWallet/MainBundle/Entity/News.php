<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * News.
 *
 * @ORM\Table(name="News")
 * @ORM\Entity
 */
class News
{
    /**
     * @var int
     * @ORM\Column(name="NewsID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $newsid;

    /**
     * @var int
     * @ORM\Column(name="NewsNumber", type="integer", nullable=false)
     */
    protected $newsnumber;

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
     * @ORM\Column(name="NewsTime", type="datetime", nullable=true)
     */
    protected $newstime;

    /**
     * @var int
     * @ORM\Column(name="NewsPhotoVer", type="integer", nullable=true)
     */
    protected $newsphotover;

    /**
     * @var string
     * @ORM\Column(name="NewsPhotoExt", type="string", length=5, nullable=true)
     */
    protected $newsphotoext;

    /**
     * Get newsid.
     *
     * @return int
     */
    public function getNewsid()
    {
        return $this->newsid;
    }

    /**
     * Set newsnumber.
     *
     * @param int $newsnumber
     * @return News
     */
    public function setNewsnumber($newsnumber)
    {
        $this->newsnumber = $newsnumber;

        return $this;
    }

    /**
     * Get newsnumber.
     *
     * @return int
     */
    public function getNewsnumber()
    {
        return $this->newsnumber;
    }

    /**
     * Set fullname.
     *
     * @param string $fullname
     * @return News
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
     * @return News
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
     * @return News
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
     * @return News
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
     * Set visible.
     *
     * @param bool $visible
     * @return News
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
     * @return News
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
     * Set newstime.
     *
     * @param \DateTime $newstime
     * @return News
     */
    public function setNewstime($newstime)
    {
        $this->newstime = $newstime;

        return $this;
    }

    /**
     * Get newstime.
     *
     * @return \DateTime
     */
    public function getNewstime()
    {
        return $this->newstime;
    }

    /**
     * Set newsphotover.
     *
     * @param int $newsphotover
     * @return News
     */
    public function setNewsphotover($newsphotover)
    {
        $this->newsphotover = $newsphotover;

        return $this;
    }

    /**
     * Get newsphotover.
     *
     * @return int
     */
    public function getNewsphotover()
    {
        return $this->newsphotover;
    }

    /**
     * Set newsphotoext.
     *
     * @param string $newsphotoext
     * @return News
     */
    public function setNewsphotoext($newsphotoext)
    {
        $this->newsphotoext = $newsphotoext;

        return $this;
    }

    /**
     * Get newsphotoext.
     *
     * @return string
     */
    public function getNewsphotoext()
    {
        return $this->newsphotoext;
    }
}
