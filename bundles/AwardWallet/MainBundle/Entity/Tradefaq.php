<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tradefaq.
 *
 * @ORM\Table(name="TradeFAQ")
 * @ORM\Entity
 */
class Tradefaq
{
    /**
     * @var int
     * @ORM\Column(name="TradeFAQID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $tradefaqid;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortindex;

    /**
     * @var string
     * @ORM\Column(name="Question", type="string", length=400, nullable=false)
     */
    protected $question;

    /**
     * @var string
     * @ORM\Column(name="Answer", type="text", nullable=false)
     */
    protected $answer;

    /**
     * @var bool
     * @ORM\Column(name="Visible", type="boolean", nullable=false)
     */
    protected $visible = true;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get tradefaqid.
     *
     * @return int
     */
    public function getTradefaqid()
    {
        return $this->tradefaqid;
    }

    /**
     * Set sortindex.
     *
     * @param int $sortindex
     * @return Tradefaq
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
     * Set question.
     *
     * @param string $question
     * @return Tradefaq
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
     * @return Tradefaq
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
     * Set visible.
     *
     * @param bool $visible
     * @return Tradefaq
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
     * Set providerid.
     *
     * @return Tradefaq
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
}
