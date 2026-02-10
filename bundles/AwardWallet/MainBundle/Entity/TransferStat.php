<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="TransferStat")
 * @ORM\Entity()
 * @UniqueEntity(fields={"sourceProvider","targetProvider"})
 */
class TransferStat
{
    /**
     * @var int|null
     * @ORM\Column(name="TransferStatID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SourceProviderID", referencedColumnName="ProviderID")
     * })
     */
    private $sourceProvider;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TargetProviderID", referencedColumnName="ProviderID")
     * })
     */
    private $targetProvider;

    /**
     * @var int|null
     * @ORM\Column(name="SourceRate", type="integer", nullable=true)
     */
    private $sourceRate;

    /**
     * @var int|null
     * @ORM\Column(name="TargetRate", type="integer", nullable=true)
     */
    private $targetRate;

    /**
     * @var int|null
     * @ORM\Column(name="TransactionCount", type="integer", nullable=true)
     */
    private $transactionCount;

    /**
     * @var int|null
     * @ORM\Column(name="TimeDeviation", type="integer", nullable=true)
     */
    private $timeDeviation;

    /**
     * @var int|null
     * @ORM\Column(name="MinDuration", type="decimal", nullable=true)
     */
    private $minDuration;

    /**
     * @var int|null
     * @ORM\Column(name="MaxDuration", type="decimal", nullable=true)
     */
    private $maxDuration;

    /**
     * @var int|null
     * @ORM\Column(name="CalcDuration", type="integer", nullable=true)
     */
    private $calcDuration;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceProvider(): Provider
    {
        return $this->sourceProvider;
    }

    /**
     * @return $this
     */
    public function setSourceProvider(Provider $sourceProvider): self
    {
        $this->sourceProvider = $sourceProvider;

        return $this;
    }

    public function getTargetProvider(): Provider
    {
        return $this->targetProvider;
    }

    /**
     * @return $this
     */
    public function setTargetProvider(Provider $targetProvider): self
    {
        $this->targetProvider = $targetProvider;

        return $this;
    }

    public function getSourceRate(): ?int
    {
        return $this->sourceRate;
    }

    /**
     * @return $this
     */
    public function setSourceRate(?int $sourceRate): self
    {
        $this->sourceRate = $sourceRate;

        return $this;
    }

    public function getTargetRate(): ?int
    {
        return $this->targetRate;
    }

    /**
     * @return $this
     */
    public function setTargetRate(?int $targetRate): self
    {
        $this->targetRate = $targetRate;

        return $this;
    }

    public function getTransactionCount(): ?int
    {
        return $this->transactionCount;
    }

    /**
     * @return $this
     */
    public function setTransactionCount(?int $transactionCount): self
    {
        $this->transactionCount = $transactionCount;

        return $this;
    }

    public function getTimeDeviation(): ?int
    {
        return $this->timeDeviation;
    }

    /**
     * @return $this
     */
    public function setTimeDeviation(?int $timeDeviation): self
    {
        $this->timeDeviation = $timeDeviation;

        return $this;
    }

    public function getMinDuration(): ?int
    {
        return $this->minDuration;
    }

    /**
     * @return $this
     */
    public function setMinDuration(?int $minDuration): self
    {
        $this->minDuration = $minDuration;

        return $this;
    }

    public function getMaxDuration(): ?int
    {
        return $this->maxDuration;
    }

    /**
     * @return $this
     */
    public function setMaxDuration(?int $maxDuration): self
    {
        $this->maxDuration = $maxDuration;

        return $this;
    }

    public function getCalcDuration(): ?int
    {
        return $this->calcDuration;
    }

    /**
     * @return $this
     */
    public function setCalcDuration(?int $calcDuration): self
    {
        $this->calcDuration = $calcDuration;

        return $this;
    }
}
