<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Country.
 *
 * @ORM\Table(name="Country")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\CountryRepository")
 */
class Country
{
    public const UNITED_STATES = 230;
    public const UK = 229;
    public const RUSSIA = 179;

    public const US_CODE = 'US';

    /**
     * @var int
     * @ORM\Column(name="CountryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $countryid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var bool
     * @ORM\Column(name="HaveStates", type="boolean", nullable=false)
     */
    protected $havestates;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=2, nullable=true)
     */
    protected $code;

    /**
     * @var Region[]|Collection
     * @ORM\OneToMany(targetEntity="\AwardWallet\MainBundle\Entity\Region", mappedBy="country")
     */
    protected $regions;

    public function __toString()
    {
        return $this->getName();
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
     * @return Country
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

    /**
     * Set havestates.
     *
     * @param bool $havestates
     * @return Country
     */
    public function setHavestates($havestates)
    {
        $this->havestates = $havestates;

        return $this;
    }

    /**
     * Get havestates.
     *
     * @return bool
     */
    public function getHavestates()
    {
        return $this->havestates;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Country
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Region[]|Collection
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * @param Region[]|Collection $regions
     */
    public function setRegions($regions): self
    {
        $this->regions = $regions;

        return $this;
    }
}
