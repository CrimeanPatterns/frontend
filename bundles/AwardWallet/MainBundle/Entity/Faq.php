<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Faq.
 *
 * @ORM\Table(name="Faq")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\FaqRepository")
 */
class Faq
{
    /**
     * @var int
     * @ORM\Column(name="FaqID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $faqid;

    /**
     * @var Faqcategory
     * @ORM\ManyToOne(targetEntity="Faqcategory", inversedBy="faqs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="FaqCategoryID", referencedColumnName="FaqCategoryID")
     * })
     */
    protected $faqcategory;

    /**
     * @var string
     * @ORM\Column(name="Question", type="text", nullable=false)
     */
    protected $question;

    /**
     * @var string
     * @ORM\Column(name="Answer", type="text", nullable=false)
     */
    protected $answer;

    /**
     * @var int
     * @ORM\Column(name="Rank", type="integer", nullable=false)
     */
    protected $rank;

    /**
     * @var bool
     * @ORM\Column(name="Visible", type="boolean", nullable=true)
     */
    protected $visible;

    /**
     * @var bool
     * @ORM\Column(name="Mobile", type="boolean", nullable=true)
     */
    protected $mobile;

    /**
     * @var bool
     * @ORM\Column(name="EnglishOnly", type="boolean", nullable=false)
     */
    protected $englishonly;

    /**
     * Get faqid.
     *
     * @return int
     */
    public function getFaqid()
    {
        return $this->faqid;
    }

    /**
     * Set faqcategory.
     *
     * @param Faqcategory $faqcategory
     * @return Faq
     */
    public function setFaqcategory($faqcategory)
    {
        $this->faqcategory = $faqcategory;

        return $this;
    }

    /**
     * Get faqcategory.
     *
     * @return Faqcategory
     */
    public function getFaqcategory()
    {
        return $this->faqcategory;
    }

    /**
     * Set question.
     *
     * @param string $question
     * @return Faq
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
     * @return Faq
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
     * Set rank.
     *
     * @param int $rank
     * @return Faq
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
     * Set visible.
     *
     * @param bool $visible
     * @return Faq
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

    public function isMobile(): bool
    {
        return $this->mobile;
    }

    public function setMobile(bool $mobile): Faq
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Set englishonly.
     *
     * @return Faq
     */
    public function setEnglishonly($englishOnly)
    {
        $this->englishonly = $englishOnly;

        return $this;
    }

    /**
     * Get englishonly.
     *
     * @return bool
     */
    public function getEnglishonly()
    {
        return $this->englishonly;
    }
}
