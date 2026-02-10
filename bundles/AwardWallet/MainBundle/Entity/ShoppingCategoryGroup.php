<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ShoppingCategory.
 *
 * @ORM\Entity
 * @ORM\Table(name="ShoppingCategoryGroup")
 */
class ShoppingCategoryGroup
{
    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Patterns", type="text", nullable=true)
     */
    protected $patterns;

    /**
     * @var string
     * @ORM\Column(name="ClickURL", type="text", nullable=true)
     */
    protected $clickURL;

    /**
     * @var int
     * @ORM\Column(name="Priority", type="integer", nullable=false)
     */
    protected $priority;

    /**
     * @var ShoppingCategory[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="ShoppingCategory",
     *     mappedBy="group",
     *     cascade={"persist"},
     *     orphanRemoval=false,
     *     indexBy="kind"
     * )
     */
    protected $categories;

    /**
     * @var CreditCardShoppingCategoryGroup[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="CreditCardShoppingCategoryGroup",
     *     mappedBy="shoppingCategoryGroup",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="kind"
     * )
     * @ORM\OrderBy({"sortIndex" = "ASC"})
     */
    protected $multipliers;

    /**
     * @var bool
     * @ORM\Column(name="IsTravelFamily", type="boolean", nullable=false)
     */
    protected $isTravelFamily;
    /**
     * @var int
     * @ORM\Column(name="ShoppingCategoryGroupID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    public function __toString()
    {
        return empty($this->name) ? "" : $this->name;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getClickURL()
    {
        return $this->clickURL;
    }

    /**
     * @param string $clickURL
     * @return $this
     */
    public function setClickURL($clickURL)
    {
        $this->clickURL = $clickURL;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @return $this
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    public function isTravelFamily(): ?bool
    {
        return $this->isTravelFamily;
    }

    public function setIsTravelFamily(bool $isTravelFamily): self
    {
        $this->isTravelFamily = $isTravelFamily;

        return $this;
    }

    /**
     * @return ShoppingCategory[]|Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param ShoppingCategory[]|Collection $categories
     * @return $this
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return CreditCardShoppingCategoryGroup[]|Collection
     */
    public function getMultipliers()
    {
        return $this->multipliers;
    }

    /**
     * @param CreditCardShoppingCategoryGroup[]|Collection $multipliers
     * @return $this
     */
    public function setMultipliers($multipliers)
    {
        $this->multipliers = $multipliers;

        return $this;
    }

    public function addMultiplier(CreditCardShoppingCategoryGroup $multiplier)
    {
        $multiplier->setShoppingCategoryGroup($this);
        $this->multipliers->add($multiplier);
    }

    public function removeMultiplier(CreditCardShoppingCategoryGroup $multiplier)
    {
        $this->multipliers->removeElement($multiplier);
    }

    public function addCategory(ShoppingCategory $category)
    {
        $category->setGroup($this);
        $this->categories->add($category);
    }

    public function removeCategory(ShoppingCategory $category)
    {
        $category->setGroup(null);
        $this->categories->removeElement($category);
    }

    /* for admin list */
    public function getMultipliersToString()
    {
        $result = [];

        foreach ($this->multipliers as $item) {
            $result[] = $item->getMultiplier() . 'x - ' . $item->getCreditCard();
        }

        return implode("<br />", $result);
    }

    /* for admin list */
    public function getCategoriesToString()
    {
        $result = [];

        foreach ($this->categories as $item) {
            $result[] = $item->getName();
        }

        return implode("<br />", $result);
    }

    /* for admin list */
    public function getCategoriesToLinksList()
    {
        $result = [];

        foreach ($this->categories as $item) {
            $result[] = "<a href='/manager/reports/categoriesLookup.php?ShoppingCategoryID=" . $item->getId() . "' target='_blank'>" . $item->getName() . "</a>";
        }

        return implode("<br />", $result);
    }

    /**
     * @return string|null
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    public function setPatterns(string $patterns)
    {
        $this->patterns = $patterns;
    }
}
