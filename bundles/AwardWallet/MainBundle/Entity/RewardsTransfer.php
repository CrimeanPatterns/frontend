<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * RewardsTransfer.
 *
 * @ORM\Table(name="RewardsTransfer")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\RewardsTransferRepository")
 * @UniqueEntity(fields={"sourceProvider","targetProvider","sourceRate"})
 */
class RewardsTransfer
{
    /**
     * @var int
     * @ORM\Column(name="RewardsTransferID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SourceProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $sourceProvider;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TargetProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $targetProvider;

    /**
     * @var int
     * @ORM\Column(name="SourceRate", type="integer", nullable=false)
     */
    protected $sourceRate;

    /**
     * @var int
     * @ORM\Column(name="TargetRate", type="integer", nullable=false)
     */
    protected $targetRate;

    /**
     * @var int
     * @ORM\Column(name="Enabled", type="integer", nullable=false)
     */
    protected $enabled;

    public function getId()
    {
        return $this->id;
    }

    public function getSourceProvider()
    {
        return $this->sourceProvider;
    }

    public function setSourceProvider(Provider $provider)
    {
        $this->sourceProvider = $provider;
    }

    public function getTargetProvider()
    {
        return $this->targetProvider;
    }

    public function setTargetProvider(Provider $provider)
    {
        $this->targetProvider = $provider;
    }

    public function getSourceRate()
    {
        return $this->sourceRate;
    }

    public function setSourceRate($rate)
    {
        $this->sourceRate = $rate;
    }

    public function getTargetRate()
    {
        return $this->targetRate;
    }

    public function setTargetRate($rate)
    {
        $this->targetRate = $rate;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }
}
