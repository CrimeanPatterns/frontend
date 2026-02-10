<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Track.
 *
 * @ORM\Table(name="Track")
 */
class Track
{
    /**
     * @var int
     * @ORM\Column(name="TrackID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $trackId;

    /**
     * @var int
     * @ORM\Column(name="SiteAdID", type="integer", nullable=true)
     */
    protected $siteAdId;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userId;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $updateDate;

    /**
     * @var string
     * @ORM\Column(name="CtID", type="string", nullable=true)
     */
    protected $ctId;

    public function __construct()
    {
        $this->updatedate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getTrackId();
    }

    /**
     * Get TrackID.
     *
     * @return int
     */
    public function getTrackId()
    {
        return $this->trackId;
    }

    /**
     * Set SiteAdId.
     *
     * @param int|null $siteAdId
     */
    public function setSiteAdId($siteAdId = null): self
    {
        $this->siteAdId = $siteAdId;

        return $this;
    }

    /**
     * Get SiteAdId.
     *
     * @return int|null
     */
    public function getSiteAdId()
    {
        return $this->siteAdId;
    }

    /**
     * Get UserId.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->userId;
    }

    /**
     * Set UpdateDate.
     *
     * @param \DateTime $updateDate
     */
    public function setUpdateDate($updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get UpdateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set CtID.
     *
     * @param string|null $ctId
     */
    public function setCtId($ctId = null): self
    {
        $this->ctId = $ctId;

        return $this;
    }

    /**
     * Get CtID.
     *
     * @return string|null
     */
    public function getCtId()
    {
        return $this->ctId;
    }
}
