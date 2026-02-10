<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Providerproperty.
 *
 * @ORM\Table(name="ProviderProperty")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ProviderpropertyRepository")
 */
class Providerproperty
{
    public const DETECTEDCARD_PROPERTY_ID = 3928;

    public const TYPE_NUMBER = 1;
    public const TYPE_DATE = 2;

    public const TYPE_NAMES = [
        self::TYPE_NUMBER => 'Number',
        self::TYPE_DATE => 'Date',
    ];

    /**
     * @var int
     * @ORM\Column(name="ProviderPropertyID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerpropertyid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=40, nullable=false)
     */
    protected $code;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortindex;

    /**
     * @var bool
     * @ORM\Column(name="Required", type="boolean", nullable=false)
     */
    protected $required = true;

    /**
     * @var int
     * @ORM\Column(name="Kind", type="integer", nullable=true)
     */
    protected $kind;

    /**
     * @var int
     * @see self::TYPE_* constants
     * @ORM\Column(name="Type", type="integer", nullable=true)
     */
    protected $type;

    /**
     * @var bool
     * @ORM\Column(name="Visible", type="boolean", nullable=false)
     */
    protected $visible = true;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider", inversedBy="properties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get providerpropertyid.
     *
     * @return int
     */
    public function getProviderpropertyid()
    {
        return $this->providerpropertyid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Providerproperty
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
     * Set code.
     *
     * @param string $code
     * @return Providerproperty
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
     * Set sortindex.
     *
     * @param int $sortindex
     * @return Providerproperty
     */
    public function setSortindex($sortindex)
    {
        $this->sortindex = $sortindex;

        return $this;
    }

    /**
     * Get sortindex.
     *
     * @return int
     */
    public function getSortindex()
    {
        return $this->sortindex;
    }

    /**
     * Set required.
     *
     * @param bool $required
     * @return Providerproperty
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get required.
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Providerproperty
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

    /**
     * @see self::TYPE_* constants
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @see self::TYPE_* constants
     */
    public function setType(?int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     * @return Providerproperty
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set providerid.
     *
     * @return Providerproperty
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }
}
