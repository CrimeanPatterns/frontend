<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Travelplansection.
 *
 * @ORM\Table(name="TravelPlanSection")
 * @ORM\Entity
 */
class Travelplansection
{
    /**
     * @var int
     * @ORM\Column(name="TravelPlanSectionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $travelplansectionid;

    /**
     * @var string
     * @ORM\Column(name="SectionKind", type="string", length=1, nullable=true)
     */
    protected $sectionkind;

    /**
     * @var int
     * @ORM\Column(name="SectionID", type="integer", nullable=true)
     */
    protected $sectionid;

    /**
     * @var \Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelplanid;

    /**
     * Get travelplansectionid.
     *
     * @return int
     */
    public function getTravelplansectionid()
    {
        return $this->travelplansectionid;
    }

    /**
     * Set sectionkind.
     *
     * @param string $sectionkind
     * @return Travelplansection
     */
    public function setSectionkind($sectionkind)
    {
        $this->sectionkind = $sectionkind;

        return $this;
    }

    /**
     * Get sectionkind.
     *
     * @return string
     */
    public function getSectionkind()
    {
        return $this->sectionkind;
    }

    /**
     * Set sectionid.
     *
     * @param int $sectionid
     * @return Travelplansection
     */
    public function setSectionid($sectionid)
    {
        $this->sectionid = $sectionid;

        return $this;
    }

    /**
     * Get sectionid.
     *
     * @return int
     */
    public function getSectionid()
    {
        return $this->sectionid;
    }

    /**
     * Set travelplanid.
     *
     * @return Travelplansection
     */
    public function setTravelplanid(?Travelplan $travelplanid = null)
    {
        $this->travelplanid = $travelplanid;

        return $this;
    }

    /**
     * Get travelplanid.
     *
     * @return \AwardWallet\MainBundle\Entity\Travelplan
     */
    public function getTravelplanid()
    {
        return $this->travelplanid;
    }
}
