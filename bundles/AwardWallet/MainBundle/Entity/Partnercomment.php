<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Partnercomment.
 *
 * @ORM\Table(name="PartnerComment")
 * @ORM\Entity
 */
class Partnercomment
{
    /**
     * @var int
     * @ORM\Column(name="PartnerCommentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $partnercommentid;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="text", nullable=false)
     */
    protected $comment;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \Transaction
     * @ORM\ManyToOne(targetEntity="Transaction")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="TransactionID", referencedColumnName="TransactionID")
     * })
     */
    protected $transactionid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get partnercommentid.
     *
     * @return int
     */
    public function getPartnercommentid()
    {
        return $this->partnercommentid;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     * @return Partnercomment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Partnercomment
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
     * Set transactionid.
     *
     * @return Partnercomment
     */
    public function setTransactionid(?Transaction $transactionid = null)
    {
        $this->transactionid = $transactionid;

        return $this;
    }

    /**
     * Get transactionid.
     *
     * @return \AwardWallet\MainBundle\Entity\Transaction
     */
    public function getTransactionid()
    {
        return $this->transactionid;
    }

    /**
     * Set userid.
     *
     * @return Partnercomment
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
