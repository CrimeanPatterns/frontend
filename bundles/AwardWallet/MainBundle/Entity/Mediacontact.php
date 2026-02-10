<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mediacontact.
 *
 * @ORM\Table(name="MediaContact")
 * @ORM\Entity
 */
class Mediacontact
{
    /**
     * @var int
     * @ORM\Column(name="MediaContactID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $mediacontactid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=4000, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="URL", type="string", length=1000, nullable=true)
     */
    protected $url;

    /**
     * @var string
     * @ORM\Column(name="FirstName", type="string", length=30, nullable=true)
     */
    protected $firstname;

    /**
     * @var string
     * @ORM\Column(name="LastName", type="string", length=50, nullable=true)
     */
    protected $lastname;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=80, nullable=true)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="AltContactMethod", type="string", length=4000, nullable=true)
     */
    protected $altcontactmethod;

    /**
     * @var string
     * @ORM\Column(name="LastContactedBy", type="string", length=250, nullable=true)
     */
    protected $lastcontactedby;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastContactDate", type="datetime", nullable=true)
     */
    protected $lastcontactdate;

    /**
     * @var string
     * @ORM\Column(name="Responses", type="text", nullable=true)
     */
    protected $responses;

    /**
     * @var string
     * @ORM\Column(name="Comments", type="text", nullable=true)
     */
    protected $comments;

    /**
     * @var bool
     * @ORM\Column(name="NDR", type="boolean", nullable=false)
     */
    protected $ndr = false;

    /**
     * @var bool
     * @ORM\Column(name="Unsubscribed", type="boolean", nullable=false)
     */
    protected $unsubscribed = false;

    /**
     * Get mediacontactid.
     *
     * @return int
     */
    public function getMediacontactid()
    {
        return $this->mediacontactid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Mediacontact
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
     * Set url.
     *
     * @param string $url
     * @return Mediacontact
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     * @return Mediacontact
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
     * @return Mediacontact
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
     * Set email.
     *
     * @param string $email
     * @return Mediacontact
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
     * Set altcontactmethod.
     *
     * @param string $altcontactmethod
     * @return Mediacontact
     */
    public function setAltcontactmethod($altcontactmethod)
    {
        $this->altcontactmethod = $altcontactmethod;

        return $this;
    }

    /**
     * Get altcontactmethod.
     *
     * @return string
     */
    public function getAltcontactmethod()
    {
        return $this->altcontactmethod;
    }

    /**
     * Set lastcontactedby.
     *
     * @param string $lastcontactedby
     * @return Mediacontact
     */
    public function setLastcontactedby($lastcontactedby)
    {
        $this->lastcontactedby = $lastcontactedby;

        return $this;
    }

    /**
     * Get lastcontactedby.
     *
     * @return string
     */
    public function getLastcontactedby()
    {
        return $this->lastcontactedby;
    }

    /**
     * Set lastcontactdate.
     *
     * @param \DateTime $lastcontactdate
     * @return Mediacontact
     */
    public function setLastcontactdate($lastcontactdate)
    {
        $this->lastcontactdate = $lastcontactdate;

        return $this;
    }

    /**
     * Get lastcontactdate.
     *
     * @return \DateTime
     */
    public function getLastcontactdate()
    {
        return $this->lastcontactdate;
    }

    /**
     * Set responses.
     *
     * @param string $responses
     * @return Mediacontact
     */
    public function setResponses($responses)
    {
        $this->responses = $responses;

        return $this;
    }

    /**
     * Get responses.
     *
     * @return string
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     * @return Mediacontact
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set ndr.
     *
     * @param bool $ndr
     * @return Mediacontact
     */
    public function setNdr($ndr)
    {
        $this->ndr = $ndr;

        return $this;
    }

    /**
     * Get ndr.
     *
     * @return bool
     */
    public function getNdr()
    {
        return $this->ndr;
    }

    /**
     * Set unsubscribed.
     *
     * @param bool $unsubscribed
     * @return Mediacontact
     */
    public function setUnsubscribed($unsubscribed)
    {
        $this->unsubscribed = $unsubscribed;

        return $this;
    }

    /**
     * Get unsubscribed.
     *
     * @return bool
     */
    public function getUnsubscribed()
    {
        return $this->unsubscribed;
    }
}
