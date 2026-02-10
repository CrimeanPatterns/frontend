<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Statearea.
 *
 * @ORM\Table(name="StateArea")
 * @ORM\Entity
 */
class Statearea
{
    /**
     * @var int
     * @ORM\Column(name="AreaID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $areaid;

    /**
     * @var int
     * @ORM\Column(name="CountryID", type="integer", nullable=true)
     */
    protected $countryid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * Get areaid.
     *
     * @return int
     */
    public function getAreaid()
    {
        return $this->areaid;
    }

    /**
     * Set countryid.
     *
     * @param int $countryid
     * @return Statearea
     */
    public function setCountryid($countryid)
    {
        $this->countryid = $countryid;

        return $this;
    }

    /**
     * Get countryid.
     *
     * @return int
     */
    public function getCountryid()
    {
        return $this->countryid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Statearea
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
