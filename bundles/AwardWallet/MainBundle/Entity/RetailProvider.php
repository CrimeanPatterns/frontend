<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RetailProvider.
 *
 * @ORM\Table(name="RetailProvider")
 * @ORM\Entity
 */
class RetailProvider
{
    public const STATE_INITIAL = 0;
    public const STATE_IGNORED = 1;
    public const STATE_IMPORTED = 2;
    public const STATE_WIP = 3;
    public const STATE_REFERRAL_DETECTED = 4;
    public const STATE_PROVIDER_FOUND = 5;

    /**
     * @var int
     * @ORM\Column(name="RetailProviderID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $retailProviderId;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=200, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=200, nullable=false)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="InitialCode", type="string", length=200, nullable=false)
     */
    protected $initialCode;

    /**
     * @var string
     * @ORM\Column(name="Homepage", type="string", length=512, nullable=true)
     */
    protected $homepage;

    /**
     * @var string
     * @ORM\Column(name="Keywords", type="string", length=2048, nullable=true)
     */
    protected $keywords;

    /**
     * @var string
     * @ORM\Column(name="Regions", type="string", length=2048, nullable=true)
     */
    protected $regions;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="string", length=1024, nullable=true)
     */
    protected $comment;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ReviewerID", referencedColumnName="UserID")
     * })
     */
    protected $reviwerId;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastReviewDate", type="datetime", nullable=true)
     */
    protected $lastReviewDate;

    /**
     * @var string
     * @ORM\Column(name="AdditionalInfo", type="string", nullable=true)
     */
    protected $additionalInfo;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ImportedProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $importedProviderId;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DetectedProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $detectedProviderId;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state;

    /**
     * @return int
     */
    public function getRetailProviderId()
    {
        return $this->retailProviderId;
    }

    /**
     * @param int $retailProviderId
     * @return RetailProvider
     */
    public function setRetailProviderId($retailProviderId)
    {
        $this->retailProviderId = $retailProviderId;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RetailProvider
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return RetailProvider
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getInitialCode()
    {
        return $this->initialCode;
    }

    /**
     * @param string $initialCode
     * @return RetailProvider
     */
    public function setInitialCode($initialCode)
    {
        $this->initialCode = $initialCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param string $homepage
     * @return RetailProvider
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;

        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     * @return RetailProvider
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * @param string $regions
     * @return RetailProvider
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return RetailProvider
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getReviwerId()
    {
        return $this->reviwerId;
    }

    /**
     * @param Usr $reviwerId
     * @return RetailProvider
     */
    public function setReviwerId($reviwerId)
    {
        $this->reviwerId = $reviwerId;

        return $this;
    }

    /**
     * @return Provider
     */
    public function getImportedProviderId()
    {
        return $this->importedProviderId;
    }

    /**
     * @param Provider $importedProviderId
     * @return RetailProvider
     */
    public function setImportedProviderId($importedProviderId)
    {
        $this->importedProviderId = $importedProviderId;

        return $this;
    }

    /**
     * @return Provider
     */
    public function getDetectedProviderId()
    {
        return $this->detectedProviderId;
    }

    /**
     * @param Provider $detectedProviderId
     * @return RetailProvider
     */
    public function setDetectedProviderId($detectedProviderId)
    {
        $this->detectedProviderId = $detectedProviderId;

        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     * @return RetailProvider
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastReviewDate()
    {
        return $this->lastReviewDate;
    }

    /**
     * @param \DateTime $lastReviewDate
     * @return RetailProvider
     */
    public function setLastReviewDate($lastReviewDate)
    {
        $this->lastReviewDate = $lastReviewDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }

    /**
     * @param string $additionalInfo
     * @return RetailProvider
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }
}
