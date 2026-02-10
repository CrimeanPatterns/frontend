<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Answer.
 *
 * @ORM\Table(name="Answer")
 * @ORM\Entity
 */
class Answer
{
    /**
     * @var int
     * @ORM\Column(name="AnswerID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $answerid;

    /**
     * @var string
     * @ORM\Column(name="Question", type="string", length=250, nullable=false)
     */
    protected $question;

    /**
     * @var string
     * @ORM\Column(name="Answer", type="string", length=250, nullable=false)
     */
    protected $answer;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="answers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $CreateDate;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $Valid = true;

    /**
     * Get answerid.
     *
     * @return int
     */
    public function getAnswerid()
    {
        return $this->answerid;
    }

    /**
     * Set question.
     *
     * @param string $question
     * @return Answer
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
     * Set answer.
     *
     * @param string $answer
     * @return Answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer.
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set accountid.
     *
     * @return Answer
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }

    /**
     * Set CreateDate.
     *
     * @param \DateTime $createDate
     * @return Answer
     */
    public function setCreateDate($createDate)
    {
        $this->CreateDate = $createDate;

        return $this;
    }

    /**
     * Get CreateDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->CreateDate;
    }

    /**
     * Set Valid.
     *
     * @param bool $Valid
     * @return Answer
     */
    public function setValid($Valid)
    {
        $this->Valid = $Valid;

        return $this;
    }

    /**
     * Get Valid.
     *
     * @return bool
     */
    public function getValid()
    {
        return $this->Valid;
    }
}
