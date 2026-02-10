<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="HotelBrand")
 * @ORM\Entity
 */
class HotelBrand
{
    /**
     * @var int
     * @ORM\Column(name="HotelBrandID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    private $name;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID", nullable=false)
     * })
     */
    private $provider;

    /**
     * @var string
     * @ORM\Column(name="Patterns", type="string", nullable=false)
     */
    private $patterns;

    /**
     * @var int
     * @ORM\Column(name="MatchingPriority", type="integer", nullable=false)
     */
    private $matchingPriority = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): HotelBrand
    {
        $this->name = $name;

        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function setProvider(Provider $provider): HotelBrand
    {
        $this->provider = $provider;

        return $this;
    }

    public function getPatterns(): string
    {
        return $this->patterns;
    }

    public function setPatterns(string $patterns): HotelBrand
    {
        $this->patterns = $patterns;

        return $this;
    }

    public function getMatchingPriority(): int
    {
        return $this->matchingPriority;
    }

    public function setMatchingPriority(int $matchingPriority): HotelBrand
    {
        $this->matchingPriority = $matchingPriority;

        return $this;
    }
}
