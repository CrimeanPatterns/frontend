<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\AbAccountProgram.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="AbAccountProgram",
 *     indexes={
 *         @ORM\Index(name="IDX_199DBEBB18FCD26A", columns={"RequestID"}),
 *         @ORM\Index(name="IDX_199DBEBBDB411183", columns={"AccountID"})
 *     },
 *     uniqueConstraints={@ORM\UniqueConstraint(name="RequestAccount", columns={"RequestID","AccountID"})}
 * )
 */
class AbAccountProgram
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbAccountProgramID;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $Requested = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $Shared;

    /**
     * @var AbRequest
     * @ORM\ManyToOne(targetEntity="AbRequest", inversedBy="Accounts")
     * @ORM\JoinColumn(name="RequestID", referencedColumnName="AbRequestID", nullable=false)
     */
    protected $RequestID;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID", nullable=false)
     */
    protected $AccountID;

    /**
     * Get AbAccountProgramID.
     *
     * @return int
     */
    public function getAbAccountProgramID()
    {
        return $this->AbAccountProgramID;
    }

    /**
     * Set Requested.
     *
     * @param bool $Requested
     * @return AbAccountProgram
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
     * Set request.
     *
     * @return AbAccountProgram
     */
    public function setRequest(AbRequest $request)
    {
        $this->RequestID = $request;

        return $this;
    }

    /**
     * Get request.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequest()
    {
        return $this->RequestID;
    }

    /**
     * Set account.
     *
     * @return AbAccountProgram
     */
    public function setAccount(Account $account)
    {
        $this->AccountID = $account;

        return $this;
    }

    /**
     * Get account.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccount()
    {
        return $this->AccountID;
    }

    /**
     * @return bool
     */
    public function getShared()
    {
        return $this->Shared;
    }

    /**
     * @param bool $Shared
     */
    public function setShared($Shared)
    {
        $this->Shared = $Shared;
    }
}
