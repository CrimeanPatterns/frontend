<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationSetting.
 *
 * @ORM\Table(name="LocationSetting")
 * @ORM\Entity()
 */
class LocationSetting
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="LocationSettingID", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="LocationID", referencedColumnName="LocationID", nullable=false)
     * })
     */
    protected $location;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     * })
     */
    protected $user;

    /**
     * @var bool
     * @ORM\Column(name="Tracked", type="boolean", nullable=false)
     */
    protected $tracked = false;

    /**
     * LocationSetting constructor.
     */
    public function __construct(Location $location, Usr $user, bool $tracked)
    {
        $this->location = $location;
        $this->user = $user;
        $this->tracked = $tracked;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return LocationSetting
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return LocationSetting
     */
    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTracked()
    {
        return $this->tracked;
    }

    /**
     * @return LocationSetting
     */
    public function setTracked(bool $tracked)
    {
        $this->tracked = $tracked;

        return $this;
    }
}
