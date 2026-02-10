<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema\Form;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @NoDI
 */
class RAFlightSearchQueryModel extends AbstractEntityAwareModel
{
    private ?int $id = null;

    /**
     * @Assert\NotBlank
     */
    private ?string $fromAirports = null;

    /**
     * @Assert\NotBlank
     */
    private ?string $toAirports = null;

    /**
     * range between fromDate and toDate should be less than 30 days.
     *
     * @Assert\NotBlank
     * @Assert\LessThanOrEqual(propertyPath="toDate", message="This value should be less than or equal to 'To Date'")
     * @Assert\Expression(
     *     "this.getToDate() and this.getToDate().diff(this.getFromDate()).days <= 30",
     *     message="The range between fromDate and toDate should be less than 30 days"
     * )
     */
    private ?\DateTime $fromDate = null;

    /**
     * @Assert\NotBlank
     * @Assert\GreaterThanOrEqual(propertyPath="fromDate", message="This value should be greater than or equal to 'From Date'")
     */
    private ?\DateTime $toDate = null;

    /**
     * @Assert\NotBlank
     */
    private ?int $flightClass = null;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min = "1", max = "10")
     */
    private ?int $adults = null;

    /**
     * @Assert\NotBlank
     */
    private ?int $searchInterval = null;

    private ?bool $autoSelectParsers = null;

    /**
     * @Assert\Type(type="array")
     */
    private ?array $excludeParsers = [];

    /**
     * @Assert\Type(type="array")
     */
    private ?array $parsers = [];

    private ?int $economyMilesLimit = null;

    private ?int $premiumEconomyMilesLimit = null;

    private ?int $businessMilesLimit = null;

    private ?int $firstMilesLimit = null;

    /**
     * @Assert\Range(max = "999")
     */
    private ?float $maxTotalDuration = null;

    /**
     * @Assert\Range(max = "999")
     */
    private ?float $maxSingleLayoverDuration = null;

    /**
     * @Assert\Range(max = "999")
     */
    private ?float $maxTotalLayoverDuration = null;

    /**
     * @Assert\Range(max = "9")
     */
    private ?int $maxStops = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFromAirports(): ?string
    {
        return $this->fromAirports;
    }

    public function setFromAirports(?string $fromAirports): self
    {
        $this->fromAirports = $fromAirports;

        return $this;
    }

    public function getToAirports(): ?string
    {
        return $this->toAirports;
    }

    public function setToAirports(?string $toAirports): self
    {
        $this->toAirports = $toAirports;

        return $this;
    }

    public function getFromDate(): ?\DateTime
    {
        return $this->fromDate;
    }

    public function setFromDate(?\DateTime $fromDate): self
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    public function getToDate(): ?\DateTime
    {
        return $this->toDate;
    }

    public function setToDate(?\DateTime $toDate): self
    {
        $this->toDate = $toDate;

        return $this;
    }

    public function getFlightClass(): ?int
    {
        return $this->flightClass;
    }

    public function setFlightClass(?int $flightClass): self
    {
        $this->flightClass = $flightClass;

        return $this;
    }

    public function getAdults(): ?int
    {
        return $this->adults;
    }

    public function setAdults(?int $adults): self
    {
        $this->adults = $adults;

        return $this;
    }

    public function getSearchInterval(): ?int
    {
        return $this->searchInterval;
    }

    public function setSearchInterval(?int $searchInterval): self
    {
        $this->searchInterval = $searchInterval;

        return $this;
    }

    public function getAutoSelectParsers(): ?bool
    {
        return $this->autoSelectParsers;
    }

    public function setAutoSelectParsers(?bool $autoSelectParsers): self
    {
        $this->autoSelectParsers = $autoSelectParsers;

        return $this;
    }

    public function getExcludeParsers(): ?array
    {
        return $this->excludeParsers;
    }

    public function setExcludeParsers(?array $excludeParsers): self
    {
        $this->excludeParsers = $excludeParsers;

        return $this;
    }

    public function getParsers(): ?array
    {
        return $this->parsers;
    }

    public function setParsers(?array $parsers): self
    {
        $this->parsers = $parsers;

        return $this;
    }

    public function getEconomyMilesLimit(): ?int
    {
        return $this->economyMilesLimit;
    }

    public function setEconomyMilesLimit(?int $economyMilesLimit): self
    {
        $this->economyMilesLimit = $economyMilesLimit;

        return $this;
    }

    public function getPremiumEconomyMilesLimit(): ?int
    {
        return $this->premiumEconomyMilesLimit;
    }

    public function setPremiumEconomyMilesLimit(?int $premiumEconomyMilesLimit): self
    {
        $this->premiumEconomyMilesLimit = $premiumEconomyMilesLimit;

        return $this;
    }

    public function getBusinessMilesLimit(): ?int
    {
        return $this->businessMilesLimit;
    }

    public function setBusinessMilesLimit(?int $businessMilesLimit): self
    {
        $this->businessMilesLimit = $businessMilesLimit;

        return $this;
    }

    public function getFirstMilesLimit(): ?int
    {
        return $this->firstMilesLimit;
    }

    public function setFirstMilesLimit(?int $firstMilesLimit): self
    {
        $this->firstMilesLimit = $firstMilesLimit;

        return $this;
    }

    public function getMaxTotalDuration(): ?float
    {
        return $this->maxTotalDuration;
    }

    public function setMaxTotalDuration(?float $maxTotalDuration): self
    {
        $this->maxTotalDuration = $maxTotalDuration;

        return $this;
    }

    public function getMaxSingleLayoverDuration(): ?float
    {
        return $this->maxSingleLayoverDuration;
    }

    public function setMaxSingleLayoverDuration(?float $maxSingleLayoverDuration): self
    {
        $this->maxSingleLayoverDuration = $maxSingleLayoverDuration;

        return $this;
    }

    public function getMaxTotalLayoverDuration(): ?float
    {
        return $this->maxTotalLayoverDuration;
    }

    public function setMaxTotalLayoverDuration(?float $maxTotalLayoverDuration): self
    {
        $this->maxTotalLayoverDuration = $maxTotalLayoverDuration;

        return $this;
    }

    public function getMaxStops(): ?int
    {
        return $this->maxStops;
    }

    public function setMaxStops(?int $maxStops): self
    {
        $this->maxStops = $maxStops;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context): void
    {
        if (is_null($this->autoSelectParsers)) {
            $context->buildViolation(/** @Ignore */ 'This value is required')
                ->atPath('autoSelectParsers')
                ->addViolation();
        } elseif (!$this->autoSelectParsers && empty($this->parsers)) {
            $context->buildViolation(/** @Ignore */ 'This value is required')
                ->atPath('parsers')
                ->addViolation();
        }
    }
}
