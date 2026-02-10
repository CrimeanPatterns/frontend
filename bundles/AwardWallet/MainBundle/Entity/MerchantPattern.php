<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("MerchantPattern")
 */
class MerchantPattern implements IdentityInterface
{
    /**
     * @var ?int
     * @ORM\Column(name="MerchantPatternID", type=Types::INTEGER, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ?string
     * @ORM\Column(name="`Name`", type=Types::STRING, length=250, nullable=false)
     */
    protected $name;

    /**
     * @var ?string
     * @ORM\Column(name="Patterns", type=Types::STRING, length=512, nullable=false)
     */
    protected $patterns;

    /**
     * @var ?string
     * @ORM\Column(name="ClickURL", type=Types::STRING, length=512, nullable=true)
     */
    protected $clickurl;

    /**
     * @var ?int
     * @ORM\Column(name="Transactions", type=Types::INTEGER)
     */
    protected $transactions;

    /**
     * filled in by AnalyzeMerchantStatCommand.
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected ?array $stat;

    /**
     * @var MerchantPatternGroup[]|Collection<MerchantPatternGroup>
     * @ORM\OneToMany(
     *     targetEntity="AwardWallet\MainBundle\Entity\MerchantPatternGroup",
     *     mappedBy="merchantpattern",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="kind"
     * )
     */
    private $groups;

    public function getStat(): ?array
    {
        return $this->stat;
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

    public function getPatterns(): ?string
    {
        return $this->patterns;
    }

    public function setPatterns(string $patterns): self
    {
        $this->patterns = $patterns;

        return $this;
    }

    public function getClickurl(): ?string
    {
        return $this->clickurl;
    }

    public function setClickurl(string $clickurl): self
    {
        $this->clickurl = $clickurl;

        return $this;
    }

    public function getTransactions(): ?int
    {
        return $this->transactions;
    }

    public function setTransactions(int $transactions): self
    {
        $this->transactions = $transactions;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return MerchantPatternGroup[]|Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param MerchantPatternGroup[]|Collection $groups
     */
    public function setGroups($groups): self
    {
        $this->groups = $groups;

        return $this;
    }
}
