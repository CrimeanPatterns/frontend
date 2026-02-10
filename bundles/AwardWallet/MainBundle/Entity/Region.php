<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Region.
 *
 * @ORM\Table(name="Region")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\RegionRepository")
 */
class Region
{
    /**
     * @var int
     * @ORM\Column(name="RegionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $regionid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=120, nullable=false)
     */
    protected $name;

    /**
     * @var int
     * @ORM\Column(name="Kind", type="integer", nullable=true)
     */
    protected $kind;

    /**
     * @var Country
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CountryID", referencedColumnName="CountryID")
     * })
     */
    protected $country;

    public function getId(): ?int
    {
        return $this->regionid;
    }

    /**
     * Get regionid.
     *
     * @deprecated use getId
     * @return int
     */
    public function getRegionid()
    {
        return $this->regionid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Region
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
     * Set kind.
     *
     * @param int $kind
     * @return Region
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return int
     */
    public function getKind()
    {
        return $this->kind;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }
}
