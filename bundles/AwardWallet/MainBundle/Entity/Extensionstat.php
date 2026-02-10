<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Extensionstat.
 *
 * @ORM\Table(name="ExtensionStat")
 * @ORM\Entity
 */
class Extensionstat
{
    public const STATUS_FAIL = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_TOTAL = 2;

    public const PLATFORM_DESKTOP = 'desktop';
    public const PLATFORM_MOBILE_UPDATE = 'mobile-update';
    public const PLATFORM_MOBILE_AUTOLOGIN = 'mobile-autologin';

    /**
     * @var int
     * @ORM\Column(name="ExtensionStatID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $extensionstatid;

    /**
     * @var int
     * @ORM\Column(name="Status", type="integer", nullable=false)
     */
    protected $status = 0;

    /**
     * @var int
     * @ORM\Column(name="Count", type="integer", nullable=false)
     */
    protected $count = 1;

    /**
     * @var string
     * @ORM\Column(name="ErrorText", type="string", length=200, nullable=true)
     */
    protected $errortext;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var string
     * @ORM\Column(name="Platform", type="string", length=20, nullable=true)
     */
    protected $platform;

    /**
     * @var \DateTime
     * @ORM\Column(name="ErrorDate", type="datetime", nullable=false)
     */
    protected $errorDate;

    /**
     * Get extensionstatid.
     *
     * @return int
     */
    public function getExtensionstatid()
    {
        return $this->extensionstatid;
    }

    /**
     * Set count.
     *
     * @param int $count
     * @return Extensionstat
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set errortext.
     *
     * @param string $errortext
     * @return Extensionstat
     */
    public function setErrortext($errortext)
    {
        $this->errortext = $errortext;

        return $this;
    }

    /**
     * Get errortext.
     *
     * @return string
     */
    public function getErrortext()
    {
        return $this->errortext;
    }

    /**
     * Set providerid.
     *
     * @return Extensionstat
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

    /**
     * @param string $platform
     * @return Extensionstat
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    public function getErrorDate(): \DateTime
    {
        return $this->errorDate;
    }

    public function setErrorDate(\DateTime $errorDate): self
    {
        $this->errorDate = $errorDate;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
