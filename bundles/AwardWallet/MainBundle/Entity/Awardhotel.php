<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Awardhotel.
 *
 * @ORM\Table(name="AwardHotel")
 * @ORM\Entity
 */
class Awardhotel
{
    /**
     * @var int
     * @ORM\Column(name="AwardHotelID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $awardhotelid;

    /**
     * @var int
     * @ORM\Column(name="Category", type="integer", nullable=false)
     */
    protected $category;

    /**
     * @var string
     * @ORM\Column(name="Address", type="string", length=250, nullable=false)
     */
    protected $address;

    /**
     * @var string
     * @ORM\Column(name="City", type="string", length=80, nullable=false)
     */
    protected $city;

    /**
     * @var string
     * @ORM\Column(name="State", type="string", length=60, nullable=false)
     */
    protected $state;

    /**
     * @var string
     * @ORM\Column(name="Zip", type="string", length=20, nullable=false)
     */
    protected $zip;

    /**
     * @var string
     * @ORM\Column(name="Country", type="string", length=40, nullable=false)
     */
    protected $country;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get awardhotelid.
     *
     * @return int
     */
    public function getAwardhotelid()
    {
        return $this->awardhotelid;
    }

    /**
     * Set category.
     *
     * @param int $category
     * @return Awardhotel
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set address.
     *
     * @param string $address
     * @return Awardhotel
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city.
     *
     * @param string $city
     * @return Awardhotel
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state.
     *
     * @param string $state
     * @return Awardhotel
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set zip.
     *
     * @param string $zip
     * @return Awardhotel
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip.
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set country.
     *
     * @param string $country
     * @return Awardhotel
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set providerid.
     *
     * @return Awardhotel
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
