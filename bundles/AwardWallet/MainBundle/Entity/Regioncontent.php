<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Regioncontent.
 *
 * @ORM\Table(name="RegionContent")
 * @ORM\Entity
 */
class Regioncontent
{
    /**
     * @var int
     * @ORM\Column(name="RegionContentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $regioncontentid;

    /**
     * @var bool
     * @ORM\Column(name="Exclude", type="boolean", nullable=false)
     */
    protected $exclude = false;

    /**
     * @var \Region
     * @ORM\ManyToOne(targetEntity="Region")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SubRegionID", referencedColumnName="RegionID")
     * })
     */
    protected $subregionid;

    /**
     * @var \Region
     * @ORM\ManyToOne(targetEntity="Region")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="RegionID", referencedColumnName="RegionID")
     * })
     */
    protected $regionid;

    /**
     * Get regioncontentid.
     *
     * @return int
     */
    public function getRegioncontentid()
    {
        return $this->regioncontentid;
    }

    /**
     * Set exclude.
     *
     * @param bool $exclude
     * @return Regioncontent
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Get exclude.
     *
     * @return bool
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * Set subregionid.
     *
     * @return Regioncontent
     */
    public function setSubregionid(?Region $subregionid = null)
    {
        $this->subregionid = $subregionid;

        return $this;
    }

    /**
     * Get subregionid.
     *
     * @return \AwardWallet\MainBundle\Entity\Region
     */
    public function getSubregionid()
    {
        return $this->subregionid;
    }

    /**
     * Set regionid.
     *
     * @return Regioncontent
     */
    public function setRegionid(?Region $regionid = null)
    {
        $this->regionid = $regionid;

        return $this;
    }

    /**
     * Get regionid.
     *
     * @return \AwardWallet\MainBundle\Entity\Region
     */
    public function getRegionid()
    {
        return $this->regionid;
    }
}
