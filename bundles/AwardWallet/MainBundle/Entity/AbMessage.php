<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\Type\AbMessageMetadata;
use AwardWallet\MainBundle\Globals\DeprecationUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AwardWallet\MainBundle\Entity\AbMessage.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbMessageRepository")
 * @ORM\Table(
 *     name="AbMessage",
 *     indexes={
 *         @ORM\Index(name="IDX_594272B118FCD26A", columns={"RequestID"}),
 *         @ORM\Index(name="IDX_594272B158746832", columns={"UserID"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class AbMessage
{
    public const TYPE_PAYMENT = -2;
    public const TYPE_REF = -1;
    public const TYPE_COMMON = 0;
    public const TYPE_UPDATE_REQUEST = 1;
    public const TYPE_STATUS_REQUEST = 2;
    public const TYPE_SHARE_ACCOUNTS = 3;
    public const TYPE_INVOICE_PAID = 4;
    public const TYPE_WRITE_CHECK = 5;
    public const TYPE_REQUEST_SHARE_ACCOUNTS = 6;
    public const TYPE_INTERNAL = 8;
    public const TYPE_SEAT_ASSIGNMENTS = 9;
    public const TYPE_SHARE_ACCOUNTS_INTERNAL = 10;
    public const TYPE_YCB_SCHEDULE = 11;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbMessageID;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $CreateDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $LastUpdateDate;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $Post;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $Type;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $FromBooker = false;

    /**
     * @var AbMessageMetadata
     * @ORM\Column(type="ab_message_metadata", nullable=false)
     */
    protected $Metadata;

    /**
     * @var AbInvoice
     * @ORM\OneToMany(targetEntity="AbInvoice", mappedBy="message", cascade={"persist", "remove"})
     * warning: dont change this to one-to-one!
     */
    protected $Invoice;

    /**
     * @var AbPhoneNumber
     * @ORM\OneToMany(targetEntity="AbPhoneNumber", mappedBy="MessageID", cascade={"persist", "remove"})
     */
    protected $PhoneNumbers;

    /**
     * @var AbRequest
     * @ORM\ManyToOne(targetEntity="AbRequest", inversedBy="Messages")
     * @ORM\JoinColumn(name="RequestID", referencedColumnName="AbRequestID", nullable=false)
     */
    protected $RequestID;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=true)
     */
    protected $UserID;

    /**
     * Message-ID header, if this message was imported from email by Imap.
     *
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    protected $ImapMessageID;

    /**
     * @var AbMessageColor
     * @ORM\ManyToOne(targetEntity="AbMessageColor")
     * @ORM\JoinColumn(name="ColorID", referencedColumnName="AbMessageColorID", nullable=true)
     */
    protected $Color;

    /**
     * Constructor.
     */
    public function __construct(
        ?Usr $user = null,
        ?AbRequest $request = null,
        $type = null
    ) {
        if (isset($user)) {
            $this->setUser($user);
        }

        if (isset($request)) {
            $this->setRequest($request);
        }

        if (isset($type)) {
            $this->setType($type);
        }

        $this->Metadata = new AbMessageMetadata();
        $this->PhoneNumbers = new ArrayCollection();
        $this->Invoice = new ArrayCollection();
    }

    /**
     * Get AbMessageID.
     *
     * @return int
     */
    public function getAbMessageID()
    {
        return $this->AbMessageID;
    }

    /**
     * Set CreateDate.
     *
     * @param \DateTime $createDate
     * @return AbMessage
     */
    public function setCreateDate($createDate)
    {
        $this->CreateDate = $createDate;

        return $this;
    }

    /**
     * Get CreateDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->CreateDate;
    }

    /**
     * Set Post.
     *
     * @param string $post
     * @return AbMessage
     */
    public function setPost($post)
    {
        $this->Post = $post;

        return $this;
    }

    /**
     * Get Post.
     *
     * @return string
     */
    public function getPost()
    {
        return $this->Post;
    }

    /**
     * Set Type.
     *
     * @param int $type
     * @return AbMessage
     */
    public function setType($type)
    {
        $this->Type = $type;

        return $this;
    }

    /**
     * Get Type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * Set Metadata.
     *
     * @return AbMessage
     */
    public function setMetadata(AbMessageMetadata $metadata)
    {
        $this->Metadata = $metadata;

        return $this;
    }

    /**
     * Get Metadata.
     *
     * @return AbMessageMetadata
     */
    public function getMetadata()
    {
        return ($this->Metadata && $this->Metadata instanceof AbMessageMetadata) ? $this->Metadata : new AbMessageMetadata();
    }

    /**
     * Set invoice.
     *
     * @return AbMessage
     */
    public function setInvoice(?AbInvoice $invoice = null)
    {
        if ($invoice) {
            $invoice->setMessage($this);
            $this->Invoice = new ArrayCollection([$invoice]);
        } else {
            $this->Invoice = new ArrayCollection();
        }

        return $this;
    }

    /**
     * Get invoice.
     *
     * @return AbInvoice
     */
    public function getInvoice()
    {
        return $this->Invoice->count() ? $this->Invoice->first() : null;
    }

    /**
     * Add PhoneNumber.
     *
     * @return AbMessage
     */
    public function addPhoneNumber(AbPhoneNumber $PhoneNumber)
    {
        $PhoneNumber->setMessage($this);
        $this->PhoneNumbers[] = $PhoneNumber;

        return $this;
    }

    /**
     * Remove PhoneNumber.
     */
    public function removePhoneNumber(AbPhoneNumber $PhoneNumber)
    {
        $this->PhoneNumbers->removeElement($PhoneNumber);
    }

    /**
     * Get PhoneNumbers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPhoneNumbers()
    {
        return $this->PhoneNumbers;
    }

    /**
     * Set request.
     *
     * @return AbMessage
     */
    public function setRequest(AbRequest $request)
    {
        $this->RequestID = $request;

        return $this;
    }

    /**
     * Get request.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequest()
    {
        return $this->RequestID;
    }

    /**
     * Set user.
     *
     * @return AbMessage
     */
    public function setUser(Usr $user)
    {
        $this->UserID = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUser()
    {
        return $this->UserID;
    }

    /**
     * @param \DateTime $LastUpdateDate
     */
    public function setLastUpdateDate($LastUpdateDate)
    {
        $this->LastUpdateDate = $LastUpdateDate;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdateDate()
    {
        return $this->LastUpdateDate;
    }

    /**
     * @return \DateTime
     */
    public function getVersionDate()
    {
        return max($this->CreateDate, $this->LastUpdateDate);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (empty($this->CreateDate)) {
            $this->CreateDate = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->LastUpdateDate = new \DateTime();
    }

    /**
     * Is service type message.
     *
     * @return bool
     */
    public function isService()
    {
        return !in_array($this->getType(), [self::TYPE_INTERNAL, self::TYPE_COMMON]) || $this->isInvoice();
    }

    /**
     * Is internal type message.
     *
     * @return bool
     */
    public function isInternal()
    {
        return in_array($this->getType(), [self::TYPE_INTERNAL, self::TYPE_SHARE_ACCOUNTS_INTERNAL]);
    }

    /**
     * Is invoice type message.
     *
     * @return bool
     */
    public function isInvoice()
    {
        return count($this->Invoice) > 0;
    }

    /**
     * Is share request type message.
     *
     * @return bool
     */
    public function isShareRequest()
    {
        return $this->Type == self::TYPE_REQUEST_SHARE_ACCOUNTS;
    }

    /**
     * Is share type message.
     *
     * @return bool
     */
    public function isShareResponse()
    {
        return in_array($this->Type, [self::TYPE_SHARE_ACCOUNTS, self::TYPE_SHARE_ACCOUNTS_INTERNAL]);
    }

    /**
     * Is seat assignments type message.
     *
     * @return bool
     */
    public function isSeatAssignments()
    {
        return $this->Type == self::TYPE_SEAT_ASSIGNMENTS;
    }

    /**
     * Is user input type message.
     *
     * @return bool
     */
    public function isUserText()
    {
        return in_array($this->getType(), [self::TYPE_INTERNAL, self::TYPE_COMMON]) && !$this->isInvoice();
    }

    /**
     * Is YCB booking message.
     *
     * @return bool
     */
    public function isYcbMessage()
    {
        return in_array($this->getType(), [self::TYPE_YCB_SCHEDULE]) && !$this->isInvoice();
    }

    /**
     * Is not readed for time.
     *
     * @param \DateTime|null $time
     * @return bool
     */
    public function isNotReaded($time = null)
    {
        return $time && !$this->isReaded($time);
    }

    /**
     * Is readed for time.
     *
     * @return bool
     */
    public function isReaded(\DateTime $time)
    {
        return $this->getCreateDate() <= $time;
    }

    /**
     * Is editable by user.
     *
     * @deprecated use $authChecker->isGranted('EDIT', $message) instead
     * @return bool
     */
    public function isEditable(Usr $user)
    {
        DeprecationUtils::alert('AbMessage_isEditable');
        $isEditable = in_array($this->getType(), [self::TYPE_INTERNAL, self::TYPE_COMMON])
            && !$this->isInvoice()
            && $this->AbMessageID;

        if ($this->FromBooker) {
            if ($user->getBooker() != null && $this->getRequest()->getBooker() == $user->getBooker()) {
                $isEditable = $isEditable && true;
            } else {
                $isEditable = false;
            }
        } else {
            $isEditable = $isEditable && $this->getUser() == $user;
        }

        return $isEditable;
    }

    /**
     * Is it possible to remove message by user.
     *
     * @deprecated use $authChecker->isGranted('DELETE', $message) instead
     * @return bool
     */
    public function canDelete(Usr $user)
    {
        DeprecationUtils::alert('AbMessage_canDelete');
        $canDelete = in_array($this->getType(), [self::TYPE_INTERNAL, self::TYPE_COMMON, self::TYPE_SEAT_ASSIGNMENTS])
            && ($this->getInvoice() === null || !$this->getInvoice()->isPaid())
            && $this->AbMessageID;

        if ($this->FromBooker) {
            if ($user->getBooker() != null && $this->getRequest()->getBooker() == $user->getBooker()) {
                $canDelete = $canDelete && true;
            } else {
                $canDelete = false;
            }
        } else {
            $canDelete = $canDelete && $this->getUser() == $user;
        }

        return $canDelete;
    }

    /**
     * @return bool
     */
    public function getFromBooker()
    {
        return $this->FromBooker;
    }

    /**
     * @param bool $FromBooker
     */
    public function setFromBooker($FromBooker)
    {
        $this->FromBooker = $FromBooker;

        return $this;
    }

    /**
     * Set ImapMessageID.
     *
     * @param string $imapMessageID
     * @return AbMessage
     */
    public function setImapMessageID($imapMessageID)
    {
        $this->ImapMessageID = $imapMessageID;

        return $this;
    }

    /**
     * Get ImapMessageID.
     *
     * @return string
     */
    public function getImapMessageID()
    {
        return $this->ImapMessageID;
    }

    /**
     * Set RequestID.
     *
     * @return AbMessage
     */
    public function setRequestID(AbRequest $requestID)
    {
        $this->RequestID = $requestID;

        return $this;
    }

    /**
     * Get RequestID.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequestID()
    {
        return $this->RequestID;
    }

    /**
     * Set UserID.
     *
     * @return AbMessage
     */
    public function setUserID(?Usr $userID = null)
    {
        $this->UserID = $userID;

        return $this;
    }

    /**
     * Get UserID.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserID()
    {
        return $this->UserID;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\AbMessageColor
     */
    public function getColor()
    {
        return $this->Color;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\AbMessageColor $Color
     */
    public function setColor($Color)
    {
        $this->Color = $Color;
    }

    public function isAutoreplyInvoice()
    {
        return
            $this->getFromBooker()
            && $this->isInvoice()
            && $this->getRequest()->getBooker()->getBookerInfo()->isAutoreplyInvoiceRequired()
            && $this->getPost()
        ;
    }
}
