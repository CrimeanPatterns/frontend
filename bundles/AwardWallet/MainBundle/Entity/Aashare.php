<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Aashare.
 *
 * @ORM\Table(name="AAShare")
 * @ORM\Entity
 */
class Aashare
{
    /**
     * @var int
     * @ORM\Column(name="AAShareID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $aashareid;

    /**
     * @var \DateTime
     * @ORM\Column(name="CountDate", type="date", nullable=false)
     */
    protected $countdate;

    /**
     * @var float
     * @ORM\Column(name="Share", type="decimal", nullable=false)
     */
    protected $share;

    /**
     * @var int
     * @ORM\Column(name="AAAccounts", type="integer", nullable=true)
     */
    protected $aaaccounts;

    /**
     * @var int
     * @ORM\Column(name="TotalWeight", type="integer", nullable=true)
     */
    protected $totalweight;

    /**
     * @var int
     * @ORM\Column(name="USAccounts", type="integer", nullable=true)
     */
    protected $usaccounts;

    /**
     * Get aashareid.
     *
     * @return int
     */
    public function getAashareid()
    {
        return $this->aashareid;
    }

    /**
     * Set countdate.
     *
     * @param \DateTime $countdate
     * @return Aashare
     */
    public function setCountdate($countdate)
    {
        $this->countdate = $countdate;

        return $this;
    }

    /**
     * Get countdate.
     *
     * @return \DateTime
     */
    public function getCountdate()
    {
        return $this->countdate;
    }

    /**
     * Set share.
     *
     * @param float $share
     * @return Aashare
     */
    public function setShare($share)
    {
        $this->share = $share;

        return $this;
    }

    /**
     * Get share.
     *
     * @return float
     */
    public function getShare()
    {
        return $this->share;
    }

    /**
     * Set aaaccounts.
     *
     * @param int $aaaccounts
     * @return Aashare
     */
    public function setAaaccounts($aaaccounts)
    {
        $this->aaaccounts = $aaaccounts;

        return $this;
    }

    /**
     * Get aaaccounts.
     *
     * @return int
     */
    public function getAaaccounts()
    {
        return $this->aaaccounts;
    }

    /**
     * Set totalweight.
     *
     * @param int $totalweight
     * @return Aashare
     */
    public function setTotalweight($totalweight)
    {
        $this->totalweight = $totalweight;

        return $this;
    }

    /**
     * Get totalweight.
     *
     * @return int
     */
    public function getTotalweight()
    {
        return $this->totalweight;
    }

    /**
     * @param int $usaccounts
     */
    public function setUsaccounts($usaccounts)
    {
        $this->usaccounts = $usaccounts;
    }

    /**
     * @return int
     */
    public function getUsaccounts()
    {
        return $this->usaccounts;
    }
}
