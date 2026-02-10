<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Travelplanshare.
 *
 * @ORM\Table(name="TravelPlanShare")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\TravelplanshareRepository")
 */
class Travelplanshare
{
    /**
     * @var int
     * @ORM\Column(name="TravelPlanShareID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $travelplanshareid;

    /**
     * @var \Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $useragentid;

    /**
     * @var \Travelplan
     * @ORM\ManyToOne(targetEntity="Travelplan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TravelPlanID", referencedColumnName="TravelPlanID")
     * })
     */
    protected $travelplanid;

    /**
     * Get travelplanshareid.
     *
     * @return int
     */
    public function getTravelplanshareid()
    {
        return $this->travelplanshareid;
    }

    /**
     * Set useragentid.
     *
     * @return Travelplanshare
     */
    public function setUseragentid(?Useragent $useragentid = null)
    {
        $this->useragentid = $useragentid;

        return $this;
    }

    /**
     * Get useragentid.
     *
     * @return \AwardWallet\MainBundle\Entity\Useragent
     */
    public function getUseragentid()
    {
        return $this->useragentid;
    }

    /**
     * Set travelplanid.
     *
     * @return Travelplanshare
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
