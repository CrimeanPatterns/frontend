<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Extensioninstall.
 *
 * @ORM\Table(name="ExtensionInstall")
 * @ORM\Entity
 */
class Extensioninstall
{
    /**
     * @var int
     * @ORM\Column(name="ExtensionInstallID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $extensioninstallid;

    /**
     * @var string
     * @ORM\Column(name="Browser", type="string", length=250, nullable=false)
     */
    protected $browser;

    /**
     * @var \DateTime
     * @ORM\Column(name="InstallDate", type="datetime", nullable=false)
     */
    protected $installdate;

    /**
     * @var int
     * @ORM\Column(name="InstallCount", type="integer", nullable=false)
     */
    protected $installcount;

    /**
     * @var string
     * @ORM\Column(name="Version", type="string", length=20, nullable=true)
     */
    protected $version;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get extensioninstallid.
     *
     * @return int
     */
    public function getExtensioninstallid()
    {
        return $this->extensioninstallid;
    }

    /**
     * Set browser.
     *
     * @param string $browser
     * @return Extensioninstall
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get browser.
     *
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * Set installdate.
     *
     * @param \DateTime $installdate
     * @return Extensioninstall
     */
    public function setInstalldate($installdate)
    {
        $this->installdate = $installdate;

        return $this;
    }

    /**
     * Get installdate.
     *
     * @return \DateTime
     */
    public function getInstalldate()
    {
        return $this->installdate;
    }

    /**
     * Set installcount.
     *
     * @param int $installcount
     * @return Extensioninstall
     */
    public function setInstallcount($installcount)
    {
        $this->installcount = $installcount;

        return $this;
    }

    /**
     * Get installcount.
     *
     * @return int
     */
    public function getInstallcount()
    {
        return $this->installcount;
    }

    /**
     * Set version.
     *
     * @param string $version
     * @return Extensioninstall
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set userid.
     *
     * @return Extensioninstall
     */
    public function setUserid(?Usr $userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->userid;
    }
}
