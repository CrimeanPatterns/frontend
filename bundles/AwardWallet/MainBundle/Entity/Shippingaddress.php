<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Shippingaddress.
 *
 * @ORM\Table(name="ShippingAddress")
 * @ORM\Entity
 */
class Shippingaddress
{
    /**
     * @var int
     * @ORM\Column(name="ShippingAddressID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $shippingaddressid;

    /**
     * @var string
     * @ORM\Column(name="AddressName", type="string", length=128, nullable=false)
     */
    protected $addressname;

    /**
     * @var string
     * @ORM\Column(name="FirstName", type="string", length=40, nullable=false)
     */
    protected $firstname;

    /**
     * @var string
     * @ORM\Column(name="LastName", type="string", length=40, nullable=false)
     */
    protected $lastname;

    /**
     * @var string
     * @ORM\Column(name="Address1", type="string", length=250, nullable=false)
     */
    protected $address1;

    /**
     * @var string
     * @ORM\Column(name="Address2", type="string", length=250, nullable=true)
     */
    protected $address2;

    /**
     * @var string
     * @ORM\Column(name="City", type="string", length=80, nullable=false)
     */
    protected $city;

    /**
     * @var string
     * @ORM\Column(name="Zip", type="string", length=40, nullable=false)
     */
    protected $zip;

    /**
     * @var int
     * @ORM\Column(name="CountryID", type="integer", nullable=false)
     */
    protected $countryid;

    /**
     * @var int
     * @ORM\Column(name="StateID", type="integer", nullable=false)
     */
    protected $stateid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get shippingaddressid.
     *
     * @return int
     */
    public function getShippingaddressid()
    {
        return $this->shippingaddressid;
    }

    /**
     * Set addressname.
     *
     * @param string $addressname
     * @return Shippingaddress
     */
    public function setAddressname($addressname)
    {
        $this->addressname = $addressname;

        return $this;
    }

    /**
     * Get addressname.
     *
     * @return string
     */
    public function getAddressname()
    {
        return $this->addressname;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     * @return Shippingaddress
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     * @return Shippingaddress
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set address1.
     *
     * @param string $address1
     * @return Shippingaddress
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * Get address1.
     *
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set address2.
     *
     * @param string $address2
     * @return Shippingaddress
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get address2.
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set city.
     *
     * @param string $city
     * @return Shippingaddress
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
     * Set zip.
     *
     * @param string $zip
     * @return Shippingaddress
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
     * Set countryid.
     *
     * @param int $countryid
     * @return Shippingaddress
     */
    public function setCountryid($countryid)
    {
        $this->countryid = $countryid;

        return $this;
    }

    /**
     * Get countryid.
     *
     * @return int
     */
    public function getCountryid()
    {
        return $this->countryid;
    }

    /**
     * Set stateid.
     *
     * @param int $stateid
     * @return Shippingaddress
     */
    public function setStateid($stateid)
    {
        $this->stateid = $stateid;

        return $this;
    }

    /**
     * Get stateid.
     *
     * @return int
     */
    public function getStateid()
    {
        return $this->stateid;
    }

    /**
     * Set userid.
     *
     * @return Shippingaddress
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
