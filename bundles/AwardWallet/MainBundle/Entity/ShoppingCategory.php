<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * ShoppingCategory.
 *
 * @ORM\Entity
 * @ORM\Table(name="ShoppingCategory")
 */
class ShoppingCategory
{
    public const IGNORED_CATEGORIES = [
        45 /* Travel, Shipping, Advertising, Telecom */ ,
        49 /* Eligible mobile wallet */ ,
        52 /* Chase Pay */ ,
        1237 /* earned on all purchases */ ,
        1241 /* on all other purchases */ ,
        805 /* REWARD */ ,
        48 /* Internet/Cble/Phone Srvc */ ,
        172 /* gas stns & restaurants */ ,
        32 /* hotels & gas stations */ ,
        28 /* internt, cable, phone, ofc sply */ ,
        1240 /* internt, cable,phone,ofc sply */ ,
    ];

    public const LINKED_TO_GROUP_BY_MANUALLY = 0;
    public const LINKED_TO_GROUP_BY_PATTERNS = 1;

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
     * @var int
     * @ORM\Column(name="MatchingOrder", type="integer", nullable=false)
     */
    protected $matchingOrder;

    /**
     * @var string
     * @ORM\Column(name="ClickURL", type="text", nullable=true)
     */
    protected $clickURL;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $provider;

    /**
     * @var ShoppingCategoryGroup
     * @ORM\ManyToOne(targetEntity="ShoppingCategoryGroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShoppingCategoryGroupID", referencedColumnName="ShoppingCategoryGroupID")
     * })
     */
    protected $group;

    /**
     * @var int
     * @ORM\Column(name="MatchingPriority", type="integer", nullable=false)
     */
    protected $matchingPriority;

    /**
     * @var MasterSlaveCategoryReport[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="MasterSlaveCategoryReport",
     *     mappedBy="masterCategory",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="kind"
     * )
     */
    protected $slaveCategories;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $linkedToGroupBy = self::LINKED_TO_GROUP_BY_MANUALLY;

    /**
     * @var int
     * @ORM\Column(name="ShoppingCategoryID", type="integer", nullable=false)
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
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * @param string $patterns
     */
    public function setPatterns($patterns)
    {
        $this->patterns = $patterns;
    }

    /**
     * @return int
     */
    public function getMatchingOrder()
    {
        return $this->matchingOrder;
    }

    /**
     * @return $this
     */
    public function setMatchingOrder(int $matchingOrder)
    {
        $this->matchingOrder = $matchingOrder;

        return $this;
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

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
    }

    public function getGroup(): ?ShoppingCategoryGroup
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setGroup(?ShoppingCategoryGroup $group)
    {
        $this->group = $group;

        return $this;
    }

    public function getMatchingPriority(): ?int
    {
        return $this->matchingPriority;
    }

    /**
     * @return $this
     */
    public function setMatchingPriority(int $matchingPriority)
    {
        $this->matchingPriority = $matchingPriority;

        return $this;
    }

    /**
     * @return MasterSlaveCategoryReport[]|PersistentCollection
     */
    public function getSlaveCategories()
    {
        return $this->slaveCategories;
    }

    /* for admin show */
    public function getSlaveCategoriesToString()
    {
        $result = [];

        foreach ($this->slaveCategories as $item) {
            $result[] = $item->getSlaveCategory()->getName() . " [merchants counter: <b>" . $item->getCounter() . "</b>]";
        }

        return implode("<br />", $result);
    }

    /* for admin show */
    public function getSlaveCategoriesCounter()
    {
        return $this->slaveCategories->count();
    }
}
