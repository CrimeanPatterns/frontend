<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Globals\DeprecationUtils;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * AwardWallet\MainBundle\Entity\AbRequest.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository")
 * @ORM\Table(
 *     name="AbRequest",
 *     indexes={
 * @ORM\Index(name="IDX_D468CD5190316B36", columns={"BookerUserID"}),
 * @ORM\Index(name="IDX_D468CD51DBA0CE34", columns={"AssignedUserID"}),
 * @ORM\Index(name="IDX_D468CD5158746832", columns={"UserID"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Assert\Callback("isValid")
 * @AwAssert\CallbackWithDep(callback={
 *      "AwardWallet\MainBundle\Form\Type\AbRequestType", "validate"
 * }, services={"doctrine.orm.default_entity_manager", "AwardWallet\MainBundle\Manager\AccountListManager", "AwardWallet\MainBundle\Globals\AccountList\OptionsFactory", "security.authorization_checker"})
 */
class AbRequest
{
    public const BOOKING_REASON_NOT_RESPONSE = 1;
    public const BOOKING_REASON_DIDNT_LIKE = 2;
    public const BOOKING_REASON_CANCEL = 3;
    public const BOOKING_REASON_REJECTED = 4;
    public const BOOKING_REASON_AFTER_PITCH = 5;
    public const BOOKING_REASON_REVEALED = 6;
    public const BOOKING_REASON_MISSED_CALL = 7;
    public const BOOKING_REASON_NOBOOK_GOOD_ROUTECOST = 8;
    public const BOOKING_REASON_NOBOOK_BAD_ROUTE = 9;
    public const BOOKING_REASON_NOBOOK_BAD_COST = 10;
    public const BOOKING_REASON_NOBOOK_BAD_ROUTECOST = 11;

    public const BOOKING_STATUS_NOT_VERIFIED = -1;
    public const BOOKING_STATUS_PENDING = 0;
    public const BOOKING_STATUS_PROCESSING = 1;
    public const BOOKING_STATUS_BOOKED = 2;
    public const BOOKING_STATUS_CANCELED = 3;
    public const BOOKING_STATUS_FUTURE = 5;
    public const BOOKING_STATUS_BOOKED_OPENED = 100;

    public const STATUSES = [
        self::BOOKING_STATUS_NOT_VERIFIED,
        self::BOOKING_STATUS_PENDING,
        self::BOOKING_STATUS_PROCESSING,
        self::BOOKING_STATUS_BOOKED,
        self::BOOKING_STATUS_CANCELED,
        self::BOOKING_STATUS_FUTURE,
        self::BOOKING_STATUS_BOOKED_OPENED,
    ];

    public const HASH_SALT = 'v0VB9u5@Ua~zdrY}kRtVH@Bx$cPgZU';

    public const BOUND_AIRLINES_CODES = ["AA", "IB", "CX", "BA", "UA", "AC", "NH", "SQ", "DL", "AF", "KE", "EY", "EK", "AS", "AV", "VS", "QF"];

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbRequestID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "2", max = "255", allowEmptyString="true")
     * @Groups({"partner"})
     */
    protected $ContactName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "2", max = "255", allowEmptyString="true")
     * @Groups({"partner"})
     */
    protected $ContactEmail;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(min = "2", max = "255", allowEmptyString="true")
     * @Groups({"partner"})
     */
    protected $ContactPhone;

    /**
     * @var string
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $Status = self::BOOKING_STATUS_NOT_VERIFIED;

    /**
     * @var AbRequestStatus
     * @ORM\ManyToOne(targetEntity="AbRequestStatus")
     * @ORM\JoinColumn(name="InternalStatus", referencedColumnName="AbRequestStatusID", nullable=true)
     */
    protected $InternalStatus;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(min = "1", max = "4000", allowEmptyString="true")
     * @Groups({"partner"})
     */
    protected $Notes;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $CreateDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $LastUpdateDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @AwAssert\DateRange(min = "+1 day", max = "+90 day")
     */
    protected ?\DateTime $RemindDate = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     * @Groups({"partner"})
     */
    protected $CabinFirst = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     * @Groups({"partner"})
     */
    protected $CabinBusiness = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     * @Groups({"partner"})
     */
    protected $CabinEconomy = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     * @Groups({"partner"})
     */
    protected $CabinPremiumEconomy = false;

    /**
     * @var bool
     * @ORM\Column(name="PaymentCash", type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     */
    protected $paymentCash = false;

    /**
     * @var bool
     * @ORM\Column(name="BusinessTravel", type="boolean", nullable=false)
     */
    protected $businessTravel = false;

    /**
     * @var string
     * @ORM\Column(type="string", length=4000, nullable=true)
     * @Assert\Length(max = "4000")
     * @Groups({"partner"})
     */
    protected $PriorSearchResults;

    /**
     * @var AbMessage
     * @ORM\OneToMany(targetEntity="AbMessage", mappedBy="RequestID", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"CreateDate" = "ASC"})
     */
    protected $Messages;

    /**
     * @var AbRequestMark
     * @ORM\OneToMany(targetEntity="AbRequestMark", mappedBy="RequestID")
     */
    protected $RequestsMark;

    /**
     * @var AbPassenger
     * @ORM\OneToMany(targetEntity="AbPassenger", mappedBy="RequestID", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     * @Assert\Count(min = "1", max = "10")
     * @Groups({"partner"})
     */
    protected $Passengers;

    /**
     * @var AbCustomProgram[]
     * @ORM\OneToMany(targetEntity="AbCustomProgram", mappedBy="RequestID", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Groups({"partner"})
     */
    protected $CustomPrograms;

    /**
     * @var AbAccountProgram[]
     * @ORM\OneToMany(targetEntity="AbAccountProgram", mappedBy="RequestID", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Valid
     */
    protected $Accounts;

    /**
     * @var AbSegment[]
     * @ORM\OneToMany(targetEntity="AbSegment", mappedBy="RequestID", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Assert\Count(min = "1", max = "50")
     * @Assert\Valid
     * @Groups({"partner"})
     */
    protected $Segments;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr", fetch="EAGER")
     * @ORM\JoinColumn(name="BookerUserID", referencedColumnName="UserID", nullable=false)
     */
    protected $BookerUser;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr", fetch="EAGER")
     * @ORM\JoinColumn(name="AssignedUserID", referencedColumnName="UserID", nullable=true)
     */
    protected $AssignedUser;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr", fetch="EAGER")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=true)
     * @Assert\NotBlank()
     */
    protected $User;

    /**
     * @var SiteAd
     * @ORM\ManyToOne(targetEntity="Sitead")
     * @ORM\JoinColumn(name="CameFrom", referencedColumnName="SiteAdID", nullable=true)
     */
    protected $SiteAd;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $CancelReason;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ByBooker = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $SendMailUser = true;

    /**
     * @var Airline
     * @ORM\ManyToOne(targetEntity="Airline", fetch="EAGER")
     * @ORM\JoinColumn(name="InboundAirlineID", referencedColumnName="AirlineID", nullable=true)
     */
    protected $inboundAirline;

    /**
     * @var Airline
     * @ORM\ManyToOne(targetEntity="Airline", fetch="EAGER")
     * @ORM\JoinColumn(name="OutboundAirlineID", referencedColumnName="AirlineID", nullable=true)
     */
    protected $outboundAirline;

    /**
     * @ORM\ManyToOne(targetEntity="Plan")
     * @ORM\JoinColumn(name="PlanID", referencedColumnName="PlanID", nullable=true)
     */
    protected ?Plan $travelPlan = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->Messages = new ArrayCollection();
        $this->RequestsMark = new ArrayCollection();
        $this->Passengers = new ArrayCollection();
        $this->CustomPrograms = new ArrayCollection();
        $this->Accounts = new ArrayCollection();
        $this->Segments = new ArrayCollection();
    }

    public function __get($name)
    {
        if ('filteredCustomPrograms' == $name) {
            return $this->CustomPrograms->matching(Criteria::create()->where(Criteria::expr()->eq('Requested', false)));
        }

        throw new NoSuchPropertyException();
    }

    public function __set($name, $value)
    {
        if ('filteredCustomPrograms' == $name) {
            /** @var AbCustomProgram $entity */
            foreach ($value as $entity) {
                $entity->setRequest($this);

                if (!$this->CustomPrograms->contains($entity)) {
                    $this->CustomPrograms->add($entity);
                }
            }
        }

        return $this;
    }

    /**
     * Get AbRequestID.
     *
     * @return int
     */
    public function getAbRequestID()
    {
        return $this->AbRequestID;
    }

    /**
     * Set ContactName.
     *
     * @param string $contactName
     * @return AbRequest
     */
    public function setContactName($contactName)
    {
        $this->ContactName = $contactName;

        return $this;
    }

    /**
     * Get ContactName.
     *
     * @return string
     */
    public function getContactName()
    {
        return $this->ContactName;
    }

    /**
     * Get ContactFirstName.
     *
     * @return string
     */
    public function ContactFirstName()
    {
        preg_match("/^([^\\s]+)\\s/", $this->ContactName, $firstName);

        return $firstName[1] ?? $this->ContactName;
    }

    /**
     * Set ContactEmail.
     *
     * @param string $contactEmail
     * @return AbRequest
     */
    public function setContactEmail($contactEmail)
    {
        $this->ContactEmail = $contactEmail;

        return $this;
    }

    /**
     * Get ContactEmail.
     *
     * @return string
     */
    public function getContactEmail()
    {
        return $this->ContactEmail;
    }

    public function getContactEmails()
    {
        return array_map('trim', explode(',', $this->ContactEmail));
    }

    public function getMainContactEmail()
    {
        return $this->getContactEmails()[0];
    }

    /**
     * Set ContactPhone.
     *
     * @param string $contactPhone
     * @return AbRequest
     */
    public function setContactPhone($contactPhone)
    {
        $this->ContactPhone = $contactPhone;

        return $this;
    }

    /**
     * Get ContactPhone.
     *
     * @return string
     */
    public function getContactPhone()
    {
        return $this->ContactPhone;
    }

    /**
     * Set Status.
     *
     * @param string $Status
     * @return AbRequest
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;

        return $this;
    }

    /**
     * Get Status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * Set Notes.
     *
     * @param string $notes
     * @return AbRequest
     */
    public function setNotes($notes)
    {
        $this->Notes = $notes;

        return $this;
    }

    /**
     * Get Notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->Notes;
    }

    /**
     * Set CreateDate.
     *
     * @param \DateTime $createDate
     * @return AbRequest
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
     * Set LastUpdateDate.
     *
     * @param \DateTime $LastUpdateDate
     * @return AbRequest
     */
    public function setLastUpdateDate($LastUpdateDate)
    {
        $this->LastUpdateDate = $LastUpdateDate;

        return $this;
    }

    /**
     * Get LastUpdateDate.
     *
     * @return \DateTime
     */
    public function getLastUpdateDate()
    {
        return $this->LastUpdateDate;
    }

    public function setRemindDate(?\DateTime $remindDate): self
    {
        $this->RemindDate = $remindDate;

        return $this;
    }

    public function getRemindDate(): ?\DateTime
    {
        return $this->RemindDate;
    }

    /**
     * Set CabinFirst.
     *
     * @param bool $CabinFirst
     * @return AbRequest
     */
    public function setCabinFirst($CabinFirst)
    {
        $this->CabinFirst = $CabinFirst;

        return $this;
    }

    /**
     * Get CabinFirst.
     *
     * @return bool
     */
    public function getCabinFirst()
    {
        return $this->CabinFirst;
    }

    /**
     * Set CabinBusiness.
     *
     * @param bool $CabinBusiness
     * @return AbRequest
     */
    public function setCabinBusiness($CabinBusiness)
    {
        $this->CabinBusiness = $CabinBusiness;

        return $this;
    }

    /**
     * Get CabinBusiness.
     *
     * @return bool
     */
    public function getCabinBusiness()
    {
        return $this->CabinBusiness;
    }

    /**
     * @param bool $CabinEconomy
     */
    public function setCabinEconomy($CabinEconomy)
    {
        $this->CabinEconomy = $CabinEconomy;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCabinEconomy()
    {
        return $this->CabinEconomy;
    }

    /**
     * @param bool $paymentCash
     */
    public function setPaymentCash($paymentCash)
    {
        $this->paymentCash = $paymentCash;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPaymentCash()
    {
        return $this->paymentCash;
    }

    /**
     * Set PriorSearchResults.
     *
     * @param string $PriorSearchResults
     * @return AbRequest
     */
    public function setPriorSearchResults($PriorSearchResults)
    {
        $this->PriorSearchResults = $PriorSearchResults;

        return $this;
    }

    /**
     * Get PriorSearchResults.
     *
     * @return string
     */
    public function getPriorSearchResults()
    {
        return $this->PriorSearchResults;
    }

    /**
     * Add Message.
     *
     * @return AbRequest
     */
    public function addMessage(AbMessage $message)
    {
        $message->setRequest($this);
        $this->Messages[] = $message;

        return $this;
    }

    /**
     * Remove Messages.
     */
    public function removeMessage(AbMessage $message)
    {
        $this->Messages->removeElement($message);
    }

    public function getMessages()
    {
        $messages = $this->Messages->toArray();

        // auto message, ignore for autoreply invoice, e.g. AwardMagic
        if ($this->hasAutoReplyMessage() && !$this->getBooker()->getBookerInfo()->isAutoreplyInvoiceRequired()) {
            array_unshift($messages, $this->getAutoReplyMessage());
        }

        return new ArrayCollection($messages);
    }

    public function hasAutoReplyMessage()
    {
        return
            ($bookerInfo = $this->getBooker()->getBookerInfo())
            && ('' !== $bookerInfo->getAutoReplyMessage())
            && $this->getUser();
    }

    public function getAutoReplyMessage()
    {
        return (new AbMessage())
            ->setCreateDate($this->getCreateDate())
            ->setPost($this->getBooker()->getBookerInfo()->getAutoReplyMessage())
            ->setRequest($this)
            ->setUser($this->getBooker())
            ->setFromBooker(true)
            ->setType(AbMessage::TYPE_COMMON);
    }

    /**
     * Add RequestsMark.
     *
     * @return AbRequest
     */
    public function addRequestsMark(AbRequestMark $RequestsMark)
    {
        $RequestsMark->setRequest($this);
        $this->RequestsMark[] = $RequestsMark;

        return $this;
    }

    /**
     * Remove RequestsMark.
     */
    public function removeRequestsMark(AbRequestMark $RequestsMark)
    {
        $this->RequestsMark->removeElement($RequestsMark);
    }

    /**
     * Get RequestsMark.
     */
    public function getRequestsMark()
    {
        return $this->RequestsMark;
    }

    /**
     * Is read?
     *
     * @return bool
     * @deprecated use \AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository::isRequestReadByUser
     */
    public function isRead(Usr $user)
    {
        DeprecationUtils::alert('AbRequest_isRead');
        $userMark = null;

        /** @var \AwardWallet\MainBundle\Entity\AbRequestMark $requestMark */
        foreach ($this->getRequestsMark() as $requestMark) {
            if ($requestMark->getUser() == $user) {
                $userMark = $requestMark;

                break;
            }
        }

        if (!$userMark) {
            return true;
        }

        if ($this->CreateDate >= $userMark->getReadDate()) {
            return false;
        }

        $hasUnread = false;

        /** @var AbMessage $message */
        foreach ($this->Messages->toArray() as $message) {
            if (
                ($message->getUser() !== $user)
                && ($message->getCreateDate() > $userMark->getReadDate())
            ) {
                $hasUnread = true;

                break;
            }
        }

        return !$hasUnread;
    }

    /**
     * Add Passenger.
     *
     * @return AbRequest
     */
    public function addPassenger(AbPassenger $Passenger)
    {
        $Passenger->setRequest($this);
        $this->Passengers[] = $Passenger;

        return $this;
    }

    /**
     * Remove Passenger.
     */
    public function removePassenger(AbPassenger $Passenger)
    {
        $this->Passengers->removeElement($Passenger);
    }

    /**
     * Get Passengers.
     *
     * @return Collection
     */
    public function getPassengers()
    {
        return $this->Passengers;
    }

    /**
     * Add CustomProgram.
     *
     * @return AbRequest
     */
    public function addCustomProgram(AbCustomProgram $CustomProgram)
    {
        $CustomProgram->setRequest($this);
        $this->CustomPrograms[] = $CustomProgram;

        return $this;
    }

    /**
     * Remove CustomProgram.
     */
    public function removeCustomProgram(AbCustomProgram $CustomProgram)
    {
        $this->CustomPrograms->removeElement($CustomProgram);
    }

    /**
     * Get CustomPrograms.
     *
     * @return Collection|AbCustomProgram[]
     */
    public function getCustomPrograms()
    {
        return $this->CustomPrograms;
    }

    /**
     * Add Account.
     *
     * @return AbRequest
     */
    public function addAccount(AbAccountProgram $Account)
    {
        $Account->setRequest($this);
        $this->Accounts[] = $Account;

        return $this;
    }

    /**
     * Remove Account.
     */
    public function removeAccount(AbAccountProgram $Account)
    {
        $this->Accounts->removeElement($Account);
    }

    /**
     * Get Accounts.
     *
     * @return Collection|AbAccountProgram[]
     */
    public function getAccounts()
    {
        return $this->Accounts;
    }

    /**
     * Add Segment.
     *
     * @return AbRequest
     */
    public function addSegment(AbSegment $Segment)
    {
        $Segment->setRequest($this);
        $this->Segments[] = $Segment;

        return $this;
    }

    /**
     * Remove Segment.
     */
    public function removeSegment(AbSegment $Segment)
    {
        $this->Segments->removeElement($Segment);
    }

    /**
     * Get Segments.
     *
     * @return Collection
     */
    public function getSegments()
    {
        return $this->Segments;
    }

    /**
     * Set Booker.
     *
     * @return AbRequest
     */
    public function setBooker(Usr $Booker)
    {
        $this->BookerUser = $Booker;

        return $this;
    }

    /**
     * Get Booker.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getBooker()
    {
        return $this->BookerUser;
    }

    /**
     * Set assigned user.
     *
     * @param \AwardWallet\MainBundle\Entity\Usr|null $AssignedUser
     * @return AbRequest
     */
    public function setAssignedUser($AssignedUser)
    {
        $this->AssignedUser = $AssignedUser;

        return $this;
    }

    /**
     * Get assigned user.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getAssignedUser()
    {
        return $this->AssignedUser;
    }

    /**
     * Set author.
     *
     * @return AbRequest
     */
    public function setUser(Usr $Author)
    {
        $this->User = $Author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Get internal status.
     *
     * @return AbRequestStatus
     */
    public function getInternalStatus()
    {
        return $this->InternalStatus;
    }

    /**
     * Set internal status.
     *
     * @param AbRequestStatus $InternalStatus
     */
    public function setInternalStatus($InternalStatus)
    {
        $this->InternalStatus = $InternalStatus;
    }

    public function setRef($ref)
    {
    }

    public function isValid(ExecutionContextInterface $context)
    {
        //        $count = $this->getCustomPrograms()->count() + $this->getAccounts()->count();
        //        if ($count == 0 && !$this->getByBooker())
        //            $context->addViolation('booking.request.add.form.miles.errors.at-least-one');
    }

    /**
     * @return AbInvoice|null
     */
    public function getLastInvoice()
    {
        $result = null;

        foreach ($this->getMessages() as $message) {
            if ($message->isInvoice()) {
                $result = $message->getInvoice();
            }
        }

        return $result;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->CreateDate = new \DateTime();
        $this->LastUpdateDate = new \DateTime();
    }

    /**
     * Remove messages files.
     *
     * @ORM\PostRemove
     */
    public function removeFiles(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $em->getRepository(\AwardWallet\MainBundle\Entity\File::class)->deleteAllFilesByResourceId('abmessage.' . $this->getAbRequestID());
    }

    public function getFinalServiceFee(): float
    {
        $total = 0;

        /** @var \AwardWallet\MainBundle\Entity\AbMessage $message */
        foreach ($this->getMessages() as $message) {
            if ($message->isInvoice() && $message->getInvoice()->isPaid()) {
                $total += $message->getInvoice()->getTotalBookingServiceFees();
            }
        }

        return $total;
    }

    public function getFinalTaxes(): float
    {
        $total = 0;

        /** @var \AwardWallet\MainBundle\Entity\AbMessage $message */
        foreach ($this->getMessages() as $message) {
            if ($message->isInvoice() && $message->getInvoice()->isPaid()) {
                $total += $message->getInvoice()->getTotalTaxes();
            }
        }

        return $total;
    }

    public function getFees(): float
    {
        $total = 0;

        /** @var \AwardWallet\MainBundle\Entity\AbMessage $message */
        foreach ($this->getMessages() as $message) {
            if ($message->isInvoice() && $message->getInvoice()->isPaid()) {
                $total += $message->getInvoice()->getFees();
            }
        }

        return $total;
    }

    /**
     * returns array of program owners, typically only request author, but also can contains any connected
     * user, if request author include other user program in request.
     *
     * @return Usr[]
     */
    public function getAccountOwners()
    {
        $result[$this->getUser()->getId()] = $this->getUser();

        foreach ($this->getAccounts() as $account) {
            $result[$account->getAccount()->getUser()->getId()] = $account->getAccount()->getUser();
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getCancelReason()
    {
        return $this->CancelReason;
    }

    /**
     * @param int $CancelReason
     */
    public function setCancelReason($CancelReason)
    {
        $this->CancelReason = $CancelReason;
    }

    /**
     * @param bool $ByBooker
     */
    public function setByBooker($ByBooker)
    {
        $this->ByBooker = $ByBooker;
    }

    /**
     * @return bool
     */
    public function getByBooker()
    {
        return $this->ByBooker;
    }

    /**
     * @param bool $SendMailUser
     */
    public function setSendMailUser($SendMailUser)
    {
        $this->SendMailUser = $SendMailUser;
    }

    /**
     * @return bool
     */
    public function getSendMailUser()
    {
        return $this->SendMailUser;
    }

    public static function getActiveStatuses($isBooker = false)
    {
        if (!$isBooker) {
            return [
                self::BOOKING_STATUS_BOOKED,
                self::BOOKING_STATUS_PENDING,
                self::BOOKING_STATUS_FUTURE,
                self::BOOKING_STATUS_NOT_VERIFIED,
                self::BOOKING_STATUS_BOOKED_OPENED,
            ];
        }

        return [
            self::BOOKING_STATUS_BOOKED,
            self::BOOKING_STATUS_PENDING,
            self::BOOKING_STATUS_BOOKED_OPENED,
        ];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return in_array($this->Status, self::getActiveStatuses());
    }

    /**
     * @return SiteAd
     */
    public function getSiteAd()
    {
        return $this->SiteAd;
    }

    /**
     * @param SiteAd $SiteAd
     */
    public function setSiteAd($SiteAd)
    {
        $this->SiteAd = $SiteAd;
    }

    /**
     * @param Usr|null $forBooker
     * @param string $size
     * @return string|null
     */
    public function getRefIconForBooker($forBooker = null, $size = 'small')
    {
        $siteAd = $this->getSiteAd();

        if (!$this->getByBooker() && $siteAd) {
            $bookerIcon = $siteAd->getIcon($size);

            if ($bookerIcon && $forBooker && $forBooker != $siteAd->getBooker()) {
                $bookerIcon = null;
            }
        } elseif ($this->getByBooker() && $this->getBooker()->isBooker()) {
            $bookerIcon = $this->getBooker()->getBookerInfo()->getIcon($size);
        } else {
            $bookerIcon = null;
        }

        return $bookerIcon;
    }

    public function getRefIcon($size = 'small')
    {
        return $this->getRefIconForBooker(null, $size);
    }

    public function getConfirmationCode()
    {
        return substr(sha1($this->getAbRequestID() . $this->getMainContactEmail()), 0, 20);
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return sha1(
            AbRequest::HASH_SALT .
            $this->getAbRequestID() .
            $this->getContactName() .
            $this->getContactEmail() .
            $this->getCreateDate()->getTimestamp()
        );
    }

    /**
     * @param bool $forBooker
     */
    public function getRealStatus($forBooker = false)
    {
        if ($forBooker) {
            return $this->getStatus();
        }

        switch ($this->getStatus()) {
            case self::BOOKING_STATUS_FUTURE:
                return self::BOOKING_STATUS_PENDING;

                break;

            default:
                return $this->getStatus();

                break;
        }
    }

    public function getInboundAirline(): ?Airline
    {
        return $this->inboundAirline;
    }

    /**
     * @return $this
     */
    public function setInboundAirline(?Airline $inboundAirline = null)
    {
        $this->inboundAirline = $inboundAirline;

        return $this;
    }

    public function getOutboundAirline(): ?Airline
    {
        return $this->outboundAirline;
    }

    /**
     * @return $this
     */
    public function setOutboundAirline(?Airline $outboundAirline = null)
    {
        $this->outboundAirline = $outboundAirline;

        return $this;
    }

    public function getTravelPlan(): ?Plan
    {
        return $this->travelPlan;
    }

    public function setTravelPlan(?Plan $travelPlan): AbRequest
    {
        $this->travelPlan = $travelPlan;

        return $this;
    }

    public function isCabinPremiumEconomy(): bool
    {
        return $this->CabinPremiumEconomy;
    }

    public function setCabinPremiumEconomy(bool $CabinPremiumEconomy): void
    {
        $this->CabinPremiumEconomy = $CabinPremiumEconomy;
    }

    public function isBusinessTravel(): bool
    {
        return $this->businessTravel;
    }

    public function setBusinessTravel(bool $businessTravel): void
    {
        $this->businessTravel = $businessTravel;
    }
}
