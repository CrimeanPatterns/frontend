<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Barcodestat.
 *
 * @ORM\Table(name="BarCodeStat")
 * @ORM\Entity
 */
class Barcodestat
{
    /**
     * @var int
     * @ORM\Column(name="BarCodeStatID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $barcodestatid;

    /**
     * @var \DateTime
     * @ORM\Column(name="StatDate", type="datetime", nullable=false)
     */
    protected $statdate;

    /**
     * @var bool
     * @ORM\Column(name="Works", type="boolean", nullable=false)
     */
    protected $works;

    /**
     * @var string
     * @ORM\Column(name="Device", type="string", length=120, nullable=true)
     */
    protected $device;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * Get barcodestatid.
     *
     * @return int
     */
    public function getBarcodestatid()
    {
        return $this->barcodestatid;
    }

    /**
     * Set statdate.
     *
     * @param \DateTime $statdate
     * @return Barcodestat
     */
    public function setStatdate($statdate)
    {
        $this->statdate = $statdate;

        return $this;
    }

    /**
     * Get statdate.
     *
     * @return \DateTime
     */
    public function getStatdate()
    {
        return $this->statdate;
    }

    /**
     * Set works.
     *
     * @param bool $works
     * @return Barcodestat
     */
    public function setWorks($works)
    {
        $this->works = $works;

        return $this;
    }

    /**
     * Get works.
     *
     * @return bool
     */
    public function getWorks()
    {
        return $this->works;
    }

    /**
     * Set device.
     *
     * @param string $device
     * @return Barcodestat
     */
    public function setDevice($device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device.
     *
     * @return string
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set accountid.
     *
     * @return Barcodestat
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }
}
