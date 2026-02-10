<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Accounttraffic.
 *
 * @ORM\Table(name="AccountTraffic")
 * @ORM\Entity
 */
class Accounttraffic
{
    /**
     * @var int
     * @ORM\Column(name="AccountTrafficID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $accounttrafficid;

    /**
     * @var int
     * @ORM\Column(name="Downloaded", type="integer", nullable=false)
     */
    protected $downloaded;

    /**
     * @var float
     * @ORM\Column(name="Duration", type="float", nullable=false)
     */
    protected $duration;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get accounttrafficid.
     *
     * @return int
     */
    public function getAccounttrafficid()
    {
        return $this->accounttrafficid;
    }

    /**
     * Set downloaded.
     *
     * @param int $downloaded
     * @return Accounttraffic
     */
    public function setDownloaded($downloaded)
    {
        $this->downloaded = $downloaded;

        return $this;
    }

    /**
     * Get downloaded.
     *
     * @return int
     */
    public function getDownloaded()
    {
        return $this->downloaded;
    }

    /**
     * Set duration.
     *
     * @param float $duration
     * @return Accounttraffic
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration.
     *
     * @return float
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Accounttraffic
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;

        return $this;
    }

    /**
     * Get creationdate.
     *
     * @return \DateTime
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set providerid.
     *
     * @return Accounttraffic
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }
}
