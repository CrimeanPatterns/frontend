<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tempelitephonescontacts.
 *
 * @ORM\Table(name="TempElitePhonesContacts")
 * @ORM\Entity
 */
class Tempelitephonescontacts
{
    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $userid;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=80, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="Program", type="string", length=80, nullable=false)
     */
    protected $program;

    /**
     * @var string
     * @ORM\Column(name="EliteLevel", type="string", length=250, nullable=false)
     */
    protected $elitelevel;

    /**
     * @var \DateTime
     * @ORM\Column(name="ContactedOn", type="datetime", nullable=false)
     */
    protected $contactedon;

    /**
     * @var string
     * @ORM\Column(name="Responce", type="text", nullable=false)
     */
    protected $responce;

    /**
     * Get userid.
     *
     * @return int
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Tempelitephonescontacts
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
     * Set program.
     *
     * @param string $program
     * @return Tempelitephonescontacts
     */
    public function setProgram($program)
    {
        $this->program = $program;

        return $this;
    }

    /**
     * Get program.
     *
     * @return string
     */
    public function getProgram()
    {
        return $this->program;
    }

    /**
     * Set elitelevel.
     *
     * @param string $elitelevel
     * @return Tempelitephonescontacts
     */
    public function setElitelevel($elitelevel)
    {
        $this->elitelevel = $elitelevel;

        return $this;
    }

    /**
     * Get elitelevel.
     *
     * @return string
     */
    public function getElitelevel()
    {
        return $this->elitelevel;
    }

    /**
     * Set contactedon.
     *
     * @param \DateTime $contactedon
     * @return Tempelitephonescontacts
     */
    public function setContactedon($contactedon)
    {
        $this->contactedon = $contactedon;

        return $this;
    }

    /**
     * Get contactedon.
     *
     * @return \DateTime
     */
    public function getContactedon()
    {
        return $this->contactedon;
    }

    /**
     * Set responce.
     *
     * @param string $responce
     * @return Tempelitephonescontacts
     */
    public function setResponce($responce)
    {
        $this->responce = $responce;

        return $this;
    }

    /**
     * Get responce.
     *
     * @return string
     */
    public function getResponce()
    {
        return $this->responce;
    }
}
