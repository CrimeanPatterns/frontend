<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Review.
 *
 * @ORM\Table(name="Review")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ReviewRepository")
 */
class Review
{
    public const SCORES_FIELDS = [
        'AbilityToEarn',
        'EaseOfRedemption',
        'Flexibility',
        'Partners',
        'EliteBenefits',
        'OnlineServices',
        'CustomerService',
    ];

    public const CACHE_PROVIDER_KEY = 'rating_v2_%d';
    public const CACHE_TIME = 3600 * 3;

    /**
     * @var int
     * @ORM\Column(name="ReviewID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $reviewid;

    /**
     * @var bool
     * @ORM\Column(name="AbilityToEarn", type="integer", nullable=false)
     */
    protected $abilitytoearn = 0;

    /**
     * @var bool
     * @ORM\Column(name="EaseOfRedemption", type="integer", nullable=false)
     */
    protected $easeofredemption = 0;

    /**
     * @var bool
     * @ORM\Column(name="Flexibility", type="integer", nullable=false)
     */
    protected $flexibility = 0;

    /**
     * @var int
     * @ORM\Column(name="Partners", type="integer", nullable=false)
     */
    protected $partners = 0;

    /**
     * @var bool
     * @ORM\Column(name="EliteBenefits", type="integer", nullable=false)
     */
    protected $elitebenefits = 0;

    /**
     * @var bool
     * @ORM\Column(name="OnlineServices", type="integer", nullable=false)
     */
    protected $onlineservices = 0;

    /**
     * @var bool
     * @ORM\Column(name="CustomerService", type="integer", nullable=false)
     */
    protected $customerservice = 0;

    /**
     * @var string
     * @ORM\Column(name="Review", type="text", nullable=false)
     */
    protected $review;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $updatedate;

    /**
     * @var int
     * @ORM\Column(name="Votes", type="integer", nullable=false)
     */
    protected $votes = 0;

    /**
     * @var int
     * @ORM\Column(name="YesVotes", type="integer", nullable=false)
     */
    protected $yesvotes = 0;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * @var bool
     * @ORM\Column(name="Approved", type="boolean", nullable=false)
     */
    private $approved;

    /**
     * Get reviewid.
     *
     * @return int
     */
    public function getReviewid()
    {
        return $this->reviewid;
    }

    /**
     * Set abilitytoearn.
     *
     * @param bool $abilitytoearn
     * @return Review
     */
    public function setAbilitytoearn($abilitytoearn)
    {
        $this->abilitytoearn = $abilitytoearn;

        return $this;
    }

    /**
     * Get abilitytoearn.
     *
     * @return int
     */
    public function getAbilitytoearn()
    {
        return $this->abilitytoearn;
    }

    /**
     * Set easeofredemption.
     *
     * @param int $easeofredemption
     * @return Review
     */
    public function setEaseofredemption($easeofredemption)
    {
        $this->easeofredemption = $easeofredemption;

        return $this;
    }

    /**
     * Get easeofredemption.
     *
     * @return int
     */
    public function getEaseofredemption()
    {
        return $this->easeofredemption;
    }

    /**
     * Set flexibility.
     *
     * @param int $flexibility
     * @return Review
     */
    public function setFlexibility($flexibility)
    {
        $this->flexibility = $flexibility;

        return $this;
    }

    /**
     * Get flexibility.
     *
     * @return int
     */
    public function getFlexibility()
    {
        return $this->flexibility;
    }

    /**
     * Set partners.
     *
     * @param int $partners
     * @return Review
     */
    public function setPartners($partners)
    {
        $this->partners = $partners;

        return $this;
    }

    /**
     * Get partners.
     *
     * @return int
     */
    public function getPartners()
    {
        return $this->partners;
    }

    /**
     * Set elitebenefits.
     *
     * @param int $elitebenefits
     * @return Review
     */
    public function setElitebenefits($elitebenefits)
    {
        $this->elitebenefits = $elitebenefits;

        return $this;
    }

    /**
     * Get elitebenefits.
     *
     * @return int
     */
    public function getElitebenefits()
    {
        return $this->elitebenefits;
    }

    /**
     * Set onlineservices.
     *
     * @param int $onlineservices
     * @return Review
     */
    public function setOnlineservices($onlineservices)
    {
        $this->onlineservices = $onlineservices;

        return $this;
    }

    /**
     * Get onlineservices.
     *
     * @return int
     */
    public function getOnlineservices()
    {
        return $this->onlineservices;
    }

    /**
     * Set customerservice.
     *
     * @param int $customerservice
     * @return Review
     */
    public function setCustomerservice($customerservice)
    {
        $this->customerservice = $customerservice;

        return $this;
    }

    /**
     * Get customerservice.
     *
     * @return int
     */
    public function getCustomerservice()
    {
        return $this->customerservice;
    }

    /**
     * Set review.
     *
     * @param string $review
     * @return Review
     */
    public function setReview($review)
    {
        $this->review = $review;

        return $this;
    }

    /**
     * Get review.
     *
     * @return string
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Review
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
     * Set updatedate.
     *
     * @param \DateTime $updatedate
     * @return Review
     */
    public function setUpdatedate($updatedate)
    {
        $this->updatedate = $updatedate;

        return $this;
    }

    /**
     * Get updatedate.
     *
     * @return \DateTime
     */
    public function getUpdatedate()
    {
        return $this->updatedate;
    }

    /**
     * Set votes.
     *
     * @param int $votes
     * @return Review
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
     * Set yesvotes.
     *
     * @param int $yesvotes
     * @return Review
     */
    public function setYesvotes($yesvotes)
    {
        $this->yesvotes = $yesvotes;

        return $this;
    }

    /**
     * Get yesvotes.
     *
     * @return int
     */
    public function getYesvotes()
    {
        return $this->yesvotes;
    }

    /**
     * Set providerid.
     *
     * @return Review
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
     * @return Review
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

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * Get rating fields value.
     */
    public function getRatingValues(): array
    {
        $result = [];

        foreach (self::SCORES_FIELDS as $field) {
            $field = strtolower($field);
            $result[$field] = $this->{$field};
        }

        return $result;
    }

    /**
     * Get total rating of review.
     */
    public function getReviewTotalRating(): float
    {
        $vals = array_filter($this->getRatingValues());

        return (empty($vals)) ? 0 : round(array_sum($vals) / count($vals));
    }
}
