<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Elitelevelprogress.
 *
 * @ORM\Table(name="EliteLevelProgress")
 * @ORM\Entity
 */
class Elitelevelprogress
{
    /**
     * @var int
     * @ORM\Column(name="EliteLevelProgressID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $elitelevelprogressid;

    /**
     * @var bool
     * @ORM\Column(name="EndMonth", type="boolean", nullable=true)
     */
    protected $endmonth;

    /**
     * @var bool
     * @ORM\Column(name="EndDay", type="boolean", nullable=true)
     */
    protected $endday;

    /**
     * @var int
     * @ORM\Column(name="Lifetime", type="integer", nullable=false)
     */
    protected $lifetime = 0;

    /**
     * @var int
     * @ORM\Column(name="ToNextLevel", type="integer", nullable=false)
     */
    protected $tonextlevel = 0;

    /**
     * @var int
     * @ORM\Column(name="GroupIndex", type="integer", nullable=true)
     */
    protected $groupindex;

    /**
     * @var \Providerproperty
     * @ORM\ManyToOne(targetEntity="Providerproperty")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderPropertyID", referencedColumnName="ProviderPropertyID")
     * })
     */
    protected $providerpropertyid;

    /**
     * @var \Providerproperty
     * @ORM\ManyToOne(targetEntity="Providerproperty")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="StartDatePropertyID", referencedColumnName="ProviderPropertyID")
     * })
     */
    protected $startdatepropertyid;

    /**
     * @var \Elitelevel
     * @ORM\ManyToOne(targetEntity="Elitelevel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="StartLevelID", referencedColumnName="EliteLevelID")
     * })
     */
    protected $startlevelid;

    /**
     * Get elitelevelprogressid.
     *
     * @return int
     */
    public function getElitelevelprogressid()
    {
        return $this->elitelevelprogressid;
    }

    /**
     * Set endmonth.
     *
     * @param bool $endmonth
     * @return Elitelevelprogress
     */
    public function setEndmonth($endmonth)
    {
        $this->endmonth = $endmonth;

        return $this;
    }

    /**
     * Get endmonth.
     *
     * @return bool
     */
    public function getEndmonth()
    {
        return $this->endmonth;
    }

    /**
     * Set endday.
     *
     * @param bool $endday
     * @return Elitelevelprogress
     */
    public function setEndday($endday)
    {
        $this->endday = $endday;

        return $this;
    }

    /**
     * Get endday.
     *
     * @return bool
     */
    public function getEndday()
    {
        return $this->endday;
    }

    /**
     * Set lifetime.
     *
     * @param int $lifetime
     * @return Elitelevelprogress
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Get lifetime.
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Set tonextlevel.
     *
     * @param int $tonextlevel
     * @return Elitelevelprogress
     */
    public function setTonextlevel($tonextlevel)
    {
        $this->tonextlevel = $tonextlevel;

        return $this;
    }

    /**
     * Get tonextlevel.
     *
     * @return int
     */
    public function getTonextlevel()
    {
        return $this->tonextlevel;
    }

    /**
     * Set groupindex.
     *
     * @param int $groupindex
     * @return Elitelevelprogress
     */
    public function setGroupindex($groupindex)
    {
        $this->groupindex = $groupindex;

        return $this;
    }

    /**
     * Get groupindex.
     *
     * @return int
     */
    public function getGroupindex()
    {
        return $this->groupindex;
    }

    /**
     * Set providerpropertyid.
     *
     * @return Elitelevelprogress
     */
    public function setProviderpropertyid(?Providerproperty $providerpropertyid = null)
    {
        $this->providerpropertyid = $providerpropertyid;

        return $this;
    }

    /**
     * Get providerpropertyid.
     *
     * @return \AwardWallet\MainBundle\Entity\Providerproperty
     */
    public function getProviderpropertyid()
    {
        return $this->providerpropertyid;
    }

    /**
     * Set startdatepropertyid.
     *
     * @return Elitelevelprogress
     */
    public function setStartdatepropertyid(?Providerproperty $startdatepropertyid = null)
    {
        $this->startdatepropertyid = $startdatepropertyid;

        return $this;
    }

    /**
     * Get startdatepropertyid.
     *
     * @return \AwardWallet\MainBundle\Entity\Providerproperty
     */
    public function getStartdatepropertyid()
    {
        return $this->startdatepropertyid;
    }

    /**
     * Set startlevelid.
     *
     * @return Elitelevelprogress
     */
    public function setStartlevelid(?Elitelevel $startlevelid = null)
    {
        $this->startlevelid = $startlevelid;

        return $this;
    }

    /**
     * Get startlevelid.
     *
     * @return \AwardWallet\MainBundle\Entity\Elitelevel
     */
    public function getStartlevelid()
    {
        return $this->startlevelid;
    }
}
