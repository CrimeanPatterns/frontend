<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transaction.
 *
 * @ORM\Table(name="Transaction")
 * @ORM\Entity
 */
class Transaction
{
    /**
     * @var int
     * @ORM\Column(name="TransactionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $transactionid;

    /**
     * @var int
     * @ORM\Column(name="AccountID", type="integer", nullable=true)
     */
    protected $accountid;

    /**
     * @var int
     * @ORM\Column(name="Miles", type="integer", nullable=false)
     */
    protected $miles = 0;

    /**
     * @var string
     * @ORM\Column(name="Comments", type="text", nullable=true)
     */
    protected $comments;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var bool
     * @ORM\Column(name="ContactByPhone", type="boolean", nullable=false)
     */
    protected $contactbyphone = false;

    /**
     * @var string
     * @ORM\Column(name="Phone", type="string", length=80, nullable=true)
     */
    protected $phone;

    /**
     * @var bool
     * @ORM\Column(name="ContactByEmail", type="boolean", nullable=false)
     */
    protected $contactbyemail = false;

    /**
     * @var string
     * @ORM\Column(name="Email", type="string", length=80, nullable=true)
     */
    protected $email;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=true)
     */
    protected $updatedate;

    /**
     * @var float
     * @ORM\Column(name="Price", type="float", nullable=true)
     */
    protected $price;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    public function __construct()
    {
        $this->creationdate = new \DateTime();
    }

    /**
     * Get transactionid.
     *
     * @return int
     */
    public function getTransactionid()
    {
        return $this->transactionid;
    }

    /**
     * Set accountid.
     *
     * @param int $accountid
     * @return Transaction
     */
    public function setAccountid($accountid)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return int
     */
    public function getAccountid()
    {
        return $this->accountid;
    }

    /**
     * Set miles.
     *
     * @param int $miles
     * @return Transaction
     */
    public function setMiles($miles)
    {
        $this->miles = $miles;

        return $this;
    }

    /**
     * Get miles.
     *
     * @return int
     */
    public function getMiles()
    {
        return $this->miles;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     * @return Transaction
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
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Transaction
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;

        return $this;
    }

    /**
     * Get creationdate.
     *
     * @return \DateTime
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set contactbyphone.
     *
     * @param bool $contactbyphone
     * @return Transaction
     */
    public function setContactbyphone($contactbyphone)
    {
        $this->contactbyphone = $contactbyphone;

        return $this;
    }

    /**
     * Get contactbyphone.
     *
     * @return bool
     */
    public function getContactbyphone()
    {
        return $this->contactbyphone;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     * @return Transaction
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
     * Set contactbyemail.
     *
     * @param bool $contactbyemail
     * @return Transaction
     */
    public function setContactbyemail($contactbyemail)
    {
        $this->contactbyemail = $contactbyemail;

        return $this;
    }

    /**
     * Get contactbyemail.
     *
     * @return bool
     */
    public function getContactbyemail()
    {
        return $this->contactbyemail;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Transaction
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
     * Set state.
     *
     * @param int $state
     * @return Transaction
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set updatedate.
     *
     * @param \DateTime $updatedate
     * @return Transaction
     */
    public function setUpdatedate($updatedate)
    {
        $this->updatedate = $updatedate;

        return $this;
    }

    /**
     * Get updatedate.
     *
     * @return \DateTime
     */
    public function getUpdatedate()
    {
        return $this->updatedate;
    }

    /**
     * Set price.
     *
     * @param float $price
     * @return Transaction
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set userid.
     *
     * @return Transaction
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
