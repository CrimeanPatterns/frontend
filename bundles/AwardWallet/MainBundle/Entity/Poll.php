<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Poll.
 *
 * @ORM\Table(name="Poll")
 * @ORM\Entity
 */
class Poll
{
    /**
     * @var int
     * @ORM\Column(name="PollID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $pollid;

    /**
     * @var bool
     * @ORM\Column(name="IsTrivia", type="boolean", nullable=false)
     */
    protected $istrivia = false;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Location", type="string", length=250, nullable=true)
     */
    protected $location;

    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="Question", type="string", length=250, nullable=false)
     */
    protected $question;

    /**
     * @var bool
     * @ORM\Column(name="IsOpen", type="boolean", nullable=false)
     */
    protected $isopen = false;

    /**
     * @var bool
     * @ORM\Column(name="OnlyUsersVote", type="boolean", nullable=false)
     */
    protected $onlyusersvote = false;

    /**
     * @var bool
     * @ORM\Column(name="OnlyUsersView", type="boolean", nullable=false)
     */
    protected $onlyusersview = false;

    /**
     * @var bool
     * @ORM\Column(name="OnlyOneVote", type="boolean", nullable=false)
     */
    protected $onlyonevote = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \Polloption
     * @ORM\ManyToOne(targetEntity="Polloption")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CorrectAnswerID", referencedColumnName="PollOptionID")
     * })
     */
    protected $correctanswerid;

    public function __construct()
    {
        $this->creationdate = new \DateTime();
    }

    /**
     * Get pollid.
     *
     * @return int
     */
    public function getPollid()
    {
        return $this->pollid;
    }

    /**
     * Set istrivia.
     *
     * @param bool $istrivia
     * @return Poll
     */
    public function setIstrivia($istrivia)
    {
        $this->istrivia = $istrivia;

        return $this;
    }

    /**
     * Get istrivia.
     *
     * @return bool
     */
    public function getIstrivia()
    {
        return $this->istrivia;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Poll
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
     * Set location.
     *
     * @param string $location
     * @return Poll
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Poll
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set question.
     *
     * @param string $question
     * @return Poll
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set isopen.
     *
     * @param bool $isopen
     * @return Poll
     */
    public function setIsopen($isopen)
    {
        $this->isopen = $isopen;

        return $this;
    }

    /**
     * Get isopen.
     *
     * @return bool
     */
    public function getIsopen()
    {
        return $this->isopen;
    }

    /**
     * Set onlyusersvote.
     *
     * @param bool $onlyusersvote
     * @return Poll
     */
    public function setOnlyusersvote($onlyusersvote)
    {
        $this->onlyusersvote = $onlyusersvote;

        return $this;
    }

    /**
     * Get onlyusersvote.
     *
     * @return bool
     */
    public function getOnlyusersvote()
    {
        return $this->onlyusersvote;
    }

    /**
     * Set onlyusersview.
     *
     * @param bool $onlyusersview
     * @return Poll
     */
    public function setOnlyusersview($onlyusersview)
    {
        $this->onlyusersview = $onlyusersview;

        return $this;
    }

    /**
     * Get onlyusersview.
     *
     * @return bool
     */
    public function getOnlyusersview()
    {
        return $this->onlyusersview;
    }

    /**
     * Set onlyonevote.
     *
     * @param bool $onlyonevote
     * @return Poll
     */
    public function setOnlyonevote($onlyonevote)
    {
        $this->onlyonevote = $onlyonevote;

        return $this;
    }

    /**
     * Get onlyonevote.
     *
     * @return bool
     */
    public function getOnlyonevote()
    {
        return $this->onlyonevote;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Poll
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;

        return $this;
    }

    /**
     * Get creationdate.
     *
     * @return \DateTime
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set correctanswerid.
     *
     * @return Poll
     */
    public function setCorrectanswerid(?Polloption $correctanswerid = null)
    {
        $this->correctanswerid = $correctanswerid;

        return $this;
    }

    /**
     * Get correctanswerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Polloption
     */
    public function getCorrectanswerid()
    {
        return $this->correctanswerid;
    }
}
