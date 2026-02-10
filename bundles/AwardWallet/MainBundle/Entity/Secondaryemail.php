<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Secondaryemail.
 *
 * @ORM\Table(name="SecondaryEmail")
 * @ORM\Entity
 */
class Secondaryemail
{
    /**
     * @var int
     * @ORM\Column(name="SecondaryEmailID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $secondaryemailid;

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
     * @var \DateTime
     * @ORM\Column(name="RequestDate", type="datetime", nullable=false)
     */
    protected $requestdate;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get secondaryemailid.
     *
     * @return int
     */
    public function getSecondaryemailid()
    {
        return $this->secondaryemailid;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Secondaryemail
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
     * @return Secondaryemail
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
     * Set requestdate.
     *
     * @param \DateTime $requestdate
     * @return Secondaryemail
     */
    public function setRequestdate($requestdate)
    {
        $this->requestdate = $requestdate;

        return $this;
    }

    /**
     * Get requestdate.
     *
     * @return \DateTime
     */
    public function getRequestdate()
    {
        return $this->requestdate;
    }

    /**
     * Set userid.
     *
     * @return Secondaryemail
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
