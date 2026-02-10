<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Billingaddress.
 *
 * @ORM\Table(name="BillingAddress")
 * @ORM\Entity
 */
class Billingaddress
{
    /**
     * @var int
     * @ORM\Column(name="BillingAddressID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $billingaddressid;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 128)
     * @ORM\Column(name="AddressName", type="string", length=128, nullable=false)
     */
    protected $addressname;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 250)
     * @ORM\Column(name="Address1", type="string", length=250, nullable=false)
     */
    protected $address1;

    /**
     * @var string
     * @Assert\Length(max = 250)
     * @ORM\Column(name="Address2", type="string", length=250, nullable=true)
     */
    protected $address2;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 80)
     * @ORM\Column(name="City", type="string", length=80, nullable=false)
     */
    protected $city;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 40)
     * @ORM\Column(name="Zip", type="string", length=40, nullable=false)
     */
    protected $zip;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 40)
     * @ORM\Column(name="FirstName", type="string", length=40, nullable=false)
     */
    protected $firstname;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(max = 40)
     * @ORM\Column(name="LastName", type="string", length=40, nullable=false)
     */
    protected $lastname;

    /**
     * @var \Country
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CountryID", referencedColumnName="CountryID")
     * })
     */
    protected $countryid;

    /**
     * @var \State
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="StateID", referencedColumnName="StateID")
     * })
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
     * Get billingaddressid.
     *
     * @return int
     */
    public function getBillingaddressid()
    {
        return $this->billingaddressid;
    }

    /**
     * Set addressname.
     *
     * @param string $addressname
     * @return Billingaddress
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
     * Set address1.
     *
     * @param string $address1
     * @return Billingaddress
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
     * @return Billingaddress
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
     * @return Billingaddress
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
     * @return Billingaddress
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
     * Set firstname.
     *
     * @param string $firstname
     * @return Billingaddress
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
     * @return Billingaddress
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
     * Set countryid.
     *
     * @return Billingaddress
     */
    public function setCountryid(?Country $countryid = null)
    {
        $this->countryid = $countryid;

        return $this;
    }

    /**
     * Get countryid.
     *
     * @return \AwardWallet\MainBundle\Entity\Country
     */
    public function getCountryid()
    {
        return $this->countryid;
    }

    /**
     * Set stateid.
     *
     * @return Billingaddress
     */
    public function setStateid(?State $stateid = null)
    {
        $this->stateid = $stateid;

        return $this;
    }

    /**
     * Get stateid.
     *
     * @return \AwardWallet\MainBundle\Entity\State
     */
    public function getStateid()
    {
        return $this->stateid;
    }

    /**
     * Set userid.
     *
     * @return Billingaddress
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

    public function getFullName()
    {
    }
}
