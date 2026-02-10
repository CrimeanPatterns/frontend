<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * MerchantGroup.
 *
 * @ORM\Entity
 * @ORM\Table(name="MerchantGroup")
 */
class MerchantGroup
{
    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="ClickURL", type="text", nullable=true)
     */
    protected $clickURL;

    /**
     * @var CreditCardMerchantGroup[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="CreditCardMerchantGroup",
     *     mappedBy="merchantGroup",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="kind"
     * )
     * @ORM\OrderBy({"sortIndex" = "ASC"})
     */
    protected $multipliers;

    /**
     * @var MerchantPatternGroup[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="MerchantPatternGroup",
     *     mappedBy="merchantgroup",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="kind"
     * )
     */
    protected $merchantpatterns;
    /**
     * @var int
     * @ORM\Column(name="MerchantGroupID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    public function __toString(): ?string
    {
        return empty($this->name) ? "" : $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getClickURL(): ?string
    {
        return $this->clickURL;
    }

    public function setClickURL($clickURL): self
    {
        $this->clickURL = $clickURL;

        return $this;
    }

    /**
     * @return CreditCardMerchantGroup[]|Collection
     */
    public function getMultipliers()
    {
        return $this->multipliers;
    }

    /**
     * @param CreditCardMerchantGroup[]|Collection $multipliers
     * @return $this
     */
    public function setMultipliers($multipliers): self
    {
        $this->multipliers = $multipliers;

        return $this;
    }

    public function addMultiplier(CreditCardMerchantGroup $multiplier): void
    {
        $multiplier->setMerchantGroup($this);
        $this->multipliers->add($multiplier);
    }

    public function removeMultiplier(CreditCardMerchantGroup $multiplier): void
    {
        $this->multipliers->removeElement($multiplier);
    }

    /**
     * @return MerchantPatternGroup[]|Collection
     */
    public function getPatterns()
    {
        return $this->merchantpatterns;
    }

    /**
     * @return $this
     */
    public function setMerchants($merchantpatterns): self
    {
        $this->merchantpatterns = $merchantpatterns;

        return $this;
    }

    public function addPattern(MerchantPatternGroup $patternGroup): void
    {
        $patternGroup->setMerchantGroup($this);
        $this->merchantpatterns->add($patternGroup);
    }

    public function removeMerchant(MerchantPatternGroup $patternGroup): void
    {
        $this->merchantpatterns->removeElement($patternGroup);
    }

    /* for admin list */
    public function getMerchantPatternsToString(): ?string
    {
        $result = [];

        foreach ($this->merchantpatterns as $item) {
            $result[] = $item->getMerchantPattern()->getName();
        }

        return implode("<br />", $result);
    }

    /* for admin list */
    public function getMultipliersToString(): ?string
    {
        $result = [];

        foreach ($this->multipliers as $item) {
            $result[] = $item->getMultiplier() . 'x - ' . $item->getCreditCard();
        }

        return implode("<br />", $result);
    }
}
