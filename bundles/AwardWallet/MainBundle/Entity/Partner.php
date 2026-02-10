<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Partner.
 *
 * @ORM\Table(name="Partner")
 * @ORM\Entity
 */
class Partner
{
    /**
     * @var int
     * @ORM\Column(name="PartnerID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $partnerid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=80, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=false)
     */
    protected $code;

    /**
     * @var bool
     * @ORM\Column(name="ReturnHiddenProperties", type="boolean", nullable=false)
     */
    protected $returnhiddenproperties = false;

    /**
     * Get partnerid.
     *
     * @return int
     */
    public function getPartnerid()
    {
        return $this->partnerid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Partner
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
     * @return Partner
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
     * Set code.
     *
     * @param string $code
     * @return Partner
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set returnhiddenproperties.
     *
     * @param bool $returnhiddenproperties
     * @return Partner
     */
    public function setReturnhiddenproperties($returnhiddenproperties)
    {
        $this->returnhiddenproperties = $returnhiddenproperties;

        return $this;
    }

    /**
     * Get returnhiddenproperties.
     *
     * @return bool
     */
    public function getReturnhiddenproperties()
    {
        return $this->returnhiddenproperties;
    }
}
