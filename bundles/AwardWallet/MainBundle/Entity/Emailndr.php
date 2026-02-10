<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Emailndr.
 *
 * @ORM\Table(name="EmailNDR")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\EmailndrRepository")
 */
class Emailndr
{
    /**
     * @var int
     * @ORM\Column(name="EmailNDRID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $emailndrid;

    /**
     * @var string
     * @ORM\Column(name="Address", type="string", length=255, nullable=false)
     */
    protected $address;

    /**
     * @var int
     * @ORM\Column(name="Cnt", type="integer", nullable=false)
     */
    protected $cnt = 0;

    /**
     * Get emailndrid.
     *
     * @return int
     */
    public function getEmailndrid()
    {
        return $this->emailndrid;
    }

    /**
     * Set address.
     *
     * @param string $address
     * @return Emailndr
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
     * Set cnt.
     *
     * @param int $cnt
     * @return Emailndr
     */
    public function setCnt($cnt)
    {
        $this->cnt = $cnt;

        return $this;
    }

    /**
     * Get cnt.
     *
     * @return int
     */
    public function getCnt()
    {
        return $this->cnt;
    }
}
