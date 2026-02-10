<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * State.
 *
 * @ORM\Table(name="State")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\StateRepository")
 */
class State
{
    /**
     * @var int
     * @ORM\Column(name="StateID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $stateid;

    /**
     * @var int
     * @ORM\Column(name="CountryID", type="integer", nullable=false)
     */
    protected $countryid;

    /**
     * @var int
     * @ORM\Column(name="AreaID", type="integer", nullable=true)
     */
    protected $areaid;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=10, nullable=false)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Get stateid.
     *
     * @return int
     */
    public function getStateid()
    {
        return $this->stateid;
    }

    /**
     * Set countryid.
     *
     * @param int $countryid
     * @return State
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
     * Set areaid.
     *
     * @param int $areaid
     * @return State
     */
    public function setAreaid($areaid)
    {
        $this->areaid = $areaid;

        return $this;
    }

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
     * Set code.
     *
     * @param string $code
     * @return State
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

    public function getCodeOrName()
    {
        return ($this->code && $this->areaid) ? $this->code : $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return State
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
