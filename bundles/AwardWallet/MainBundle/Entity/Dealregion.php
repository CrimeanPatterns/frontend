<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dealregion.
 *
 * @ORM\Table(name="DealRegion")
 * @ORM\Entity
 */
class Dealregion
{
    /**
     * @var int
     * @ORM\Column(name="DealRegionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $dealregionid;

    /**
     * @var \Deal
     * @ORM\ManyToOne(targetEntity="Deal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="DealID", referencedColumnName="DealID")
     * })
     */
    protected $dealid;

    /**
     * @var \Region
     * @ORM\ManyToOne(targetEntity="Region")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="RegionID", referencedColumnName="RegionID")
     * })
     */
    protected $regionid;

    /**
     * Get dealregionid.
     *
     * @return int
     */
    public function getDealregionid()
    {
        return $this->dealregionid;
    }

    /**
     * Set dealid.
     *
     * @return Dealregion
     */
    public function setDealid(?Deal $dealid = null)
    {
        $this->dealid = $dealid;

        return $this;
    }

    /**
     * Get dealid.
     *
     * @return \AwardWallet\MainBundle\Entity\Deal
     */
    public function getDealid()
    {
        return $this->dealid;
    }

    /**
     * Set regionid.
     *
     * @return Dealregion
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
