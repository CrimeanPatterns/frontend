<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PointEssentialProgram.
 *
 * @ORM\Table(name="PointEssentialProgram")
 * @ORM\Entity
 */
class PointEssentialProgram
{
    public const ALLIANCE = [
        1 => 'Star Alliance',
        2 => 'Independent',
        3 => 'Sky Team',
        4 => 'One World',
    ];

    /*
    public const PROVIDER_ASSIGN_ID = [
        'WithAmex'       => 1199,
        'WithChase'      => 87,
        'WithCiti'       => 364,
        'WithCapitalOne' => 76,
        'WithMarriott'   => 17,
    ];
    */
    public const PROVIDER_ASSIGN_NAME = [
        'WithAmex' => 'Amex',
        'WithChase' => 'Chase',
        'WithCiti' => 'Citi',
        'WithCapitalOne' => 'Capital One',
        'WithMarriott' => 'Marriott',
    ];

    /**
     * @var int
     * @ORM\Column(name="PointEssentialProgramID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="Program", type="string", nullable=true)
     */
    private $program;

    /**
     * @var int
     * @ORM\Column(name="Alliance", type="integer", nullable=true)
     */
    private $alliance;

    /**
     * @ORM\Column(name="WithAmex", type="boolean", nullable=true)
     */
    private $withAmex;

    /**
     * @ORM\Column(name="WithChase", type="boolean", nullable=true)
     */
    private $withChase;

    /**
     * @ORM\Column(name="WithCiti", type="boolean", nullable=true)
     */
    private $withCiti;

    /**
     * @ORM\Column(name="WithCapitalOne", type="boolean", nullable=true)
     */
    private $withCapitalOne;

    /**
     * @ORM\Column(name="WithMarriott", type="boolean", nullable=true)
     */
    private $withMarriott;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    private $provider;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setProvider(Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return $this
     */
    public function setProgram(?string $program): self
    {
        $this->program = $program;

        return $this;
    }

    public function getProgram(): ?string
    {
        return $this->program;
    }

    /**
     * @return $this
     */
    public function setAlliance(?int $alliance): self
    {
        $this->alliance = $alliance;

        return $this;
    }

    public function getAlliance(): ?int
    {
        return $this->alliance;
    }

    /**
     * @return $this
     */
    public function setWithAmex(?bool $withAmex): self
    {
        $this->withAmex = $withAmex;

        return $this;
    }

    public function getWithAmex(): ?bool
    {
        return $this->withAmex;
    }

    /**
     * @return $this
     */
    public function setWithChase(?bool $withChase): self
    {
        $this->withChase = $withChase;

        return $this;
    }

    public function getWithChase(): ?bool
    {
        return $this->withChase;
    }

    /**
     * @return $this
     */
    public function setWithCiti(?bool $withCiti): self
    {
        $this->withCiti = $withCiti;

        return $this;
    }

    public function getWithCiti(): ?bool
    {
        return $this->withCiti;
    }

    /**
     * @return $this
     */
    public function setWithCapitalOne(?bool $withCapitalOne): self
    {
        $this->withCapitalOne = $withCapitalOne;

        return $this;
    }

    public function getWithCapitalOne(): ?bool
    {
        return $this->withCapitalOne;
    }

    /**
     * @return $this
     */
    public function setWithMarriott(?bool $withMarriott): self
    {
        $this->withMarriott = $withMarriott;

        return $this;
    }

    public function getWithMarriott(): ?bool
    {
        return $this->withMarriott;
    }
}
