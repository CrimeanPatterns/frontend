<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Faqcategory.
 *
 * @ORM\Table(name="FaqCategory")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\FaqCategoryRepository")
 */
class Faqcategory
{
    /**
     * @var int
     * @ORM\Column(name="FaqCategoryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $faqcategoryid;

    /**
     * @var string
     * @ORM\Column(name="CategoryTitle", type="string", length=255, nullable=false)
     */
    protected $categorytitle;

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
     * @var Faq[]
     * @ORM\OneToMany(targetEntity="Faq", mappedBy="faqcategory")
     * @ORM\OrderBy({"rank" = "ASC"})
     */
    protected $faqs;

    public function __construct()
    {
        $this->faqs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get faqcategoryid.
     *
     * @return int
     */
    public function getFaqcategoryid()
    {
        return $this->faqcategoryid;
    }

    /**
     * Set categorytitle.
     *
     * @param string $categorytitle
     * @return Faqcategory
     */
    public function setCategorytitle($categorytitle)
    {
        $this->categorytitle = $categorytitle;

        return $this;
    }

    /**
     * Get categorytitle.
     *
     * @return string
     */
    public function getCategorytitle()
    {
        return $this->categorytitle;
    }

    /**
     * Set rank.
     *
     * @param int $rank
     * @return Faqcategory
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
     * @return Faqcategory
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
     * Add faq.
     *
     * @return Faqcategory
     */
    public function addFaq(Faq $faq)
    {
        $faq->setFaqcategory($this);
        $this->faqs[] = $faq;

        return $this;
    }

    /**
     * Remove faq.
     */
    public function removeFaq(Faq $faq)
    {
        $faq->setFaqcategory(null);
        $this->faqs->removeElement($faq);
    }

    /**
     * Get faqs.
     *
     * @return array
     */
    public function getFaqs()
    {
        return $this->faqs;
    }

    public function getVisibleFaqs()
    {
        return $this->faqs->filter(function ($faq) {
            /** @var Faq $faq */
            return $faq->getVisible();
        });
    }
}
