<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contactus.
 *
 * @ORM\Table(name="ContactUs")
 * @ORM\Entity
 */
class Contactus
{
    /**
     * @var int
     * @ORM\Column(name="ContactUsID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $contactusid;

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=true)
     */
    protected $userid;

    /**
     * @var string
     * @Assert\NotBlank(groups={"unauth"})
     * @Assert\Length(min = 4, allowEmptyString="true", groups={"unauth"})
     * @ORM\Column(name="FullName", type="string", length=255, nullable=true)
     */
    protected $fullname;

    /**
     * @var string
     * @Assert\NotBlank(groups={"unauth"})
     * @Assert\Email(groups={"unauth"})
     * @ORM\Column(name="Email", type="string", length=255, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="Phone", type="string", length=255, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     * @Assert\NotBlank(groups={"unauth", "auth"})
     * @ORM\Column(name="RequestType", type="string", length=255, nullable=true)
     */
    protected $requesttype;

    /**
     * @var string
     * @Assert\NotBlank(groups={"unauth", "auth"})
     * @ORM\Column(name="Message", type="text", nullable=true)
     */
    protected $message;

    /**
     * @var \DateTime
     * @ORM\Column(name="DateSubmitted", type="datetime", nullable=false)
     */
    protected $datesubmitted;

    /**
     * @var string
     * @ORM\Column(name="UserIP", type="string", length=255, nullable=true)
     */
    protected $userip;

    /**
     * @var float
     * @ORM\Column(name="LifetimeContribution", type="float", nullable=true)
     */
    protected $lifetimecontribution;

    /**
     * @var bool
     * @ORM\Column(name="Replied", type="boolean", nullable=true)
     */
    protected $replied = false;

    /**
     * Debugging popups, which were shown to the user.
     *
     * @var string
     */
    protected $shownData;

    /**
     * Get contactusid.
     *
     * @return int
     */
    public function getContactusid()
    {
        return $this->contactusid;
    }

    /**
     * Set userid.
     *
     * @param int $userid
     * @return Contactus
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

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
     * Set fullname.
     *
     * @param string $fullname
     * @return Contactus
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Contactus
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
     * @return Contactus
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
     * Set requesttype.
     *
     * @param string $requesttype
     * @return Contactus
     */
    public function setRequesttype($requesttype)
    {
        $this->requesttype = $requesttype;

        return $this;
    }

    /**
     * Get requesttype.
     *
     * @return string
     */
    public function getRequesttype()
    {
        return $this->requesttype;
    }

    /**
     * Set message.
     *
     * @param string $message
     * @return Contactus
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set datesubmitted.
     *
     * @param \DateTime $datesubmitted
     * @return Contactus
     */
    public function setDatesubmitted($datesubmitted)
    {
        $this->datesubmitted = $datesubmitted;

        return $this;
    }

    /**
     * Get datesubmitted.
     *
     * @return \DateTime
     */
    public function getDatesubmitted()
    {
        return $this->datesubmitted;
    }

    /**
     * Set userip.
     *
     * @param string $userip
     * @return Contactus
     */
    public function setUserip($userip)
    {
        $this->userip = $userip;

        return $this;
    }

    /**
     * Get userip.
     *
     * @return string
     */
    public function getUserip()
    {
        return $this->userip;
    }

    /**
     * Set lifetimecontribution.
     *
     * @param float $lifetimecontribution
     * @return Contactus
     */
    public function setLifetimecontribution($lifetimecontribution)
    {
        $this->lifetimecontribution = $lifetimecontribution;

        return $this;
    }

    /**
     * Get lifetimecontribution.
     *
     * @return float
     */
    public function getLifetimecontribution()
    {
        return $this->lifetimecontribution;
    }

    /**
     * Set shown data.
     *
     * @param string $shownData
     * @return Contactus
     */
    public function setShownData($shownData)
    {
        $this->shownData = $shownData;

        return $this;
    }

    /**
     * Get shown data.
     *
     * @return string
     */
    public function getShownData()
    {
        return $this->shownData;
    }

    /**
     * Get replied.
     *
     * @return bool
     */
    public function getReplied()
    {
        return $this->replied;
    }

    /**
     * Set replied.
     *
     * @param bool $replied
     * @return Contactus
     */
    public function setReplied($replied)
    {
        $this->replied = $replied;

        return $this;
    }
}
