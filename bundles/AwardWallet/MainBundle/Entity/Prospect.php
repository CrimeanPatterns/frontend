<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prospect.
 *
 * @ORM\Table(name="Prospect")
 * @ORM\Entity
 */
class Prospect
{
    /**
     * @var int
     * @ORM\Column(name="ProspectID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $prospectid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=true)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=80, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="Phone", type="string", length=80, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     * @ORM\Column(name="Address", type="string", length=250, nullable=true)
     */
    protected $address;

    /**
     * @var string
     * @ORM\Column(name="CityStateZip", type="string", length=250, nullable=true)
     */
    protected $citystatezip;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastUseDate", type="datetime", nullable=false)
     */
    protected $lastusedate;

    /**
     * @var int
     * @ORM\Column(name="Uses", type="integer", nullable=false)
     */
    protected $uses;

    /**
     * Get prospectid.
     *
     * @return int
     */
    public function getProspectid()
    {
        return $this->prospectid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Prospect
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Prospect
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     * @return Prospect
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set address.
     *
     * @param string $address
     * @return Prospect
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
     * Set citystatezip.
     *
     * @param string $citystatezip
     * @return Prospect
     */
    public function setCitystatezip($citystatezip)
    {
        $this->citystatezip = $citystatezip;

        return $this;
    }

    /**
     * Get citystatezip.
     *
     * @return string
     */
    public function getCitystatezip()
    {
        return $this->citystatezip;
    }

    /**
     * Set lastusedate.
     *
     * @param \DateTime $lastusedate
     * @return Prospect
     */
    public function setLastusedate($lastusedate)
    {
        $this->lastusedate = $lastusedate;

        return $this;
    }

    /**
     * Get lastusedate.
     *
     * @return \DateTime
     */
    public function getLastusedate()
    {
        return $this->lastusedate;
    }

    /**
     * Set uses.
     *
     * @param int $uses
     * @return Prospect
     */
    public function setUses($uses)
    {
        $this->uses = $uses;

        return $this;
    }

    /**
     * Get uses.
     *
     * @return int
     */
    public function getUses()
    {
        return $this->uses;
    }
}
