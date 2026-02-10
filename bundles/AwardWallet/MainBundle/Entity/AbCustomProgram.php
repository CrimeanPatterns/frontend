<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbCustomProgram.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="AbCustomProgram",
 *     indexes={@ORM\Index(name="IDX_E5A4981E18FCD26A", columns={"RequestID"})}
 * )
 */
class AbCustomProgram
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbCustomProgramID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Owner;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(min = "1", max = "255", allowEmptyString="true")
     */
    protected $EliteStatus;

    /**
     * @var float
     * @ORM\Column(type="decimal", length=15, nullable=true, scale=2)
     * @Assert\NotBlank()
     * @Assert\Type(type="float")
     */
    protected $Balance;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $Requested = false;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID", nullable=true)
     */
    protected $ProviderID;

    /**
     * @var AbRequest
     * @ORM\ManyToOne(targetEntity="AbRequest", inversedBy="CustomPrograms")
     * @ORM\JoinColumn(name="RequestID", referencedColumnName="AbRequestID", nullable=false)
     */
    protected $RequestID;

    /**
     * Get AbCustomProgramID.
     *
     * @return int
     */
    public function getAbCustomProgramID()
    {
        return $this->AbCustomProgramID;
    }

    /**
     * Set Name.
     *
     * @param string $name
     * @return AbCustomProgram
     */
    public function setName($name)
    {
        $this->Name = $name;

        return $this;
    }

    /**
     * Get Name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * Set Owner.
     *
     * @param string $Owner
     * @return AbCustomProgram
     */
    public function setOwner($Owner)
    {
        $this->Owner = $Owner;

        return $this;
    }

    /**
     * Get Owner.
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->Owner;
    }

    /**
     * Set EliteStatus.
     *
     * @param string $EliteStatus
     * @return AbCustomProgram
     */
    public function setEliteStatus($EliteStatus)
    {
        $this->EliteStatus = $EliteStatus;

        return $this;
    }

    /**
     * Get EliteStatus.
     *
     * @return string
     */
    public function getEliteStatus()
    {
        return $this->EliteStatus;
    }

    /**
     * Set Balance.
     *
     * @param float $balance
     * @return AbCustomProgram
     */
    public function setBalance($balance)
    {
        $this->Balance = $balance;

        return $this;
    }

    /**
     * Get Balance.
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->Balance;
    }

    /**
     * Set Requested.
     *
     * @param bool $Requested
     * @return AbCustomProgram
     */
    public function setRequested($Requested)
    {
        $this->Requested = $Requested;
    }

    /**
     * Get Requested.
     *
     * @return bool
     */
    public function getRequested()
    {
        return $this->Requested;
    }

    /**
     * Set provider.
     *
     * @param Provider|null $provider
     * @return AbCustomProgram
     * @throws \Exception
     */
    public function setProvider($provider)
    {
        if (is_null($provider) || $provider instanceof Provider) {
            $this->ProviderID = $provider;
        } elseif (!is_null($provider)) {
            throw new \Exception('Variable must be Provider entity');
        }

        return $this;
    }

    /**
     * Get provider.
     *
     * @return Provider
     */
    public function getProvider()
    {
        return $this->ProviderID;
    }

    /**
     * Set request.
     *
     * @return AbCustomProgram
     */
    public function setRequest(AbRequest $request)
    {
        $this->RequestID = $request;

        return $this;
    }

    /**
     * Get requests.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequest()
    {
        return $this->RequestID;
    }
}
