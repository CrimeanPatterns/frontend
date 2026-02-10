<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

class LoyaltyLocationModel extends AbstractEntityAwareModel
{
    /**
     * @var string
     * @Assert\NotBlank
     * @Assert\Type(type="string")
     * @Assert\Length(max = "250")
     */
    private $name;

    /**
     * @var float
     * @Assert\NotBlank
     * @Assert\Type(type="numeric")
     * @Assert\Range(min = -90, max = 90)
     */
    private $lat;

    /**
     * @var float
     * @Assert\NotBlank
     * @Assert\Type(type="numeric")
     * @Assert\Range(min = -180, max = 180)
     */
    private $lng;

    /**
     * @var int
     * @Assert\Type(type="integer")
     * @Assert\Range(min = 10, max = 1000)
     */
    private $radius = 50;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     */
    private $tracked = false;

    /**
     * @var Usr
     */
    private $user;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return LoyaltyLocationModel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     * @return LoyaltyLocationModel
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     * @return LoyaltyLocationModel
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }

    /**
     * @return int
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     * @return LoyaltyLocationModel
     */
    public function setRadius($radius)
    {
        if ($radius) {
            $this->radius = (int) $radius;
        }

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
     * @param bool $tracked
     * @return LoyaltyLocationModel
     */
    public function setTracked($tracked)
    {
        $this->tracked = (bool) $tracked;

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
     * @return LoyaltyLocationModel
     */
    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }
}
