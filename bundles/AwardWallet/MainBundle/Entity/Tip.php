<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tip.
 *
 * @ORM\Table(name="Tip")
 * @ORM\Entity
 */
class Tip
{
    /**
     * @var int
     * @ORM\Column(name="TipID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $tipId;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=64, nullable=true)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="Title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     * @ORM\Column(name="ReshowInterval", type="integer", nullable=true)
     */
    protected $reshowInterval;

    /**
     * @var string
     * @ORM\Column(name="Route", type="string", length=64, nullable=true)
     */
    protected $route;

    /**
     * @var string
     * @ORM\Column(name="Element", type="string", length=64, nullable=true)
     */
    protected $element;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $createDate;

    /**
     * @var bool
     * @ORM\Column(name="Enabled", type="boolean", nullable=false)
     */
    protected $enabled = true;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=true)
     */
    protected $sortIndex;

    public function __construct()
    {
        $this->createDate = new \DateTime();
    }

    public function __toString()
    {
        return 'tip_' . $this->tipId;
    }

    /**
     * @deprecated use getId
     */
    public function getTipId(): int
    {
        return $this->tipId;
    }

    public function getId(): int
    {
        return $this->tipId;
    }

    /**
     * @param string $code
     */
    public function setCode($code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param int $countDays
     */
    public function setReshowInterval($countDays): self
    {
        $this->reshowInterval = $countDays;

        return $this;
    }

    public function getReshowInterval(): ?int
    {
        return $this->reshowInterval;
    }

    /**
     * @param string $routeName
     */
    public function setRoute($routeName): self
    {
        $this->route = $routeName;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * @param string $element
     */
    public function setElement($element): self
    {
        $this->element = $element;

        return $this;
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    /**
     * @param bool $isEnabled
     */
    public function setEnabled($isEnabled): self
    {
        $this->enabled = (bool) $isEnabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param \DateTime $dateCreation
     */
    public function setCreateDate($dateCreation): self
    {
        $this->createDate = $dateCreation;

        return $this;
    }

    public function getCreateDate(): \DateTime
    {
        return $this->createDate;
    }

    /**
     * @param int $sortIndex
     */
    public function setSortIndex($sortIndex): self
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }
}
