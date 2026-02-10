<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AbPhoneNumber.
 *
 * @ORM\Table(name="AbPhoneNumber")
 * @ORM\Entity
 */
class AbPhoneNumber
{
    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $AbPhoneNumberID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Provider;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Phone;

    /**
     * @var AbMessage
     * @ORM\ManyToOne(targetEntity="AbMessage", inversedBy="PhoneNumbers", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="MessageID", referencedColumnName="AbMessageID")
     * })
     */
    protected $MessageID;

    /**
     * Get AbPhoneNumberID.
     *
     * @return int
     */
    public function getAbPhoneNumberID()
    {
        return $this->AbPhoneNumberID;
    }

    /**
     * Set Provider.
     *
     * @param string $Provider
     * @return AbPhoneNumber
     */
    public function setProvider($Provider)
    {
        $this->Provider = $Provider;

        return $this;
    }

    /**
     * Get Provider.
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->Provider;
    }

    /**
     * Set Phone.
     *
     * @param string $Phone
     * @return AbPhoneNumber
     */
    public function setPhone($Phone)
    {
        $this->Phone = $Phone;

        return $this;
    }

    /**
     * Get Phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->Phone;
    }

    /**
     * Set message.
     *
     * @return AbPhoneNumber
     */
    public function setMessage(AbMessage $message)
    {
        $this->MessageID = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return AbMessage
     */
    public function getMessage()
    {
        return $this->MessageID;
    }
}
