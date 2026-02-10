<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\InAppPurchase.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="InAppPurchase",
 *     indexes={
 * @ORM\Index(name="idx_IAPUserID", columns={"UserID"})
 *     }
 * )
 */
class InAppPurchase
{
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $InAppPurchaseID;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $StartDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $EndDate;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    protected $UserAgent;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $UserID;

    /**
     * Get InAppPurchaseID.
     *
     * @return int
     */
    public function getInAppPurchaseID()
    {
        return $this->InAppPurchaseID;
    }

    /**
     * Set StartDate.
     *
     * @param \DateTime $StartDate
     * @return InAppPurchase
     */
    public function setStartDate($StartDate)
    {
        $this->StartDate = $StartDate;

        return $this;
    }

    /**
     * Get StartDate.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->StartDate;
    }

    /**
     * Set EndDate.
     *
     * @param \DateTime $EndDate
     * @return InAppPurchase
     */
    public function setEndDate($EndDate)
    {
        $this->EndDate = $EndDate;

        return $this;
    }

    /**
     * Get EndDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->EndDate;
    }

    /**
     * Set UserAgent.
     *
     * @param string $UserAgent
     * @return InAppPurchase
     */
    public function setUserAgent($UserAgent)
    {
        $this->UserAgent = $UserAgent;

        return $this;
    }

    /**
     * Get UserAgent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->UserAgent;
    }

    /**
     * Set User.
     *
     * @return InAppPurchase
     */
    public function setUser(Usr $User)
    {
        $this->UserID = $User;

        return $this;
    }

    /**
     * Get User.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUser()
    {
        return $this->UserID;
    }
}
