<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Useragent.
 *
 * @ORM\Table(name="UserAgent")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\UseragentRepository")
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\UseragentListener" })
 * @ORM\HasLifecycleCallbacks
 */
class Useragent
{
    public const ACCESS_READ_NUMBER = 0;
    public const ACCESS_READ_BALANCE_AND_STATUS = 1;
    public const ACCESS_READ_ALL = 2;
    public const ACCESS_WRITE = 3;
    public const ACCESS_ADMIN = 4;
    public const ACCESS_NONE = 5;
    public const ACCESS_BOOKING_MANAGER = 6;
    public const ACCESS_BOOKING_REFERRAL = 7;
    public const ACCESS_BOOKING_ADMINISTRATOR = ACCESS_BOOKING_MANAGER;

    public const TRIP_ACCESS_READ_ONLY = 0;
    public const TRIP_ACCESS_FULL_CONTROL = 1;

    /**
     * @var int
     * @ORM\Column(name="UserAgentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $useragentid;

    /**
     * @var string
     * @Assert\NotBlank(groups={"add"})
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true", groups={"add"})
     * @ORM\Column(name="FirstName", type="string", length=30, nullable=true)
     */
    protected $firstname;

    /**
     * @var string
     * @Assert\Length(max = 30, groups={"add"})
     * @ORM\Column(name="MidName", type="string", length=30, nullable=true)
     */
    protected $midname;

    /**
     * @var string
     * @Assert\NotBlank(groups={"add"})
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true", groups={"add"})
     * @ORM\Column(name="LastName", type="string", length=30, nullable=true)
     */
    protected $lastname;

    /**
     * @var \DateTime
     * @ORM\Column(name="Birthday", type="datetime", nullable=true)
     */
    protected $birthday;

    /**
     * @var string
     * @Assert\Email(groups={"add"})
     * @ORM\Column(name="Email", type="string", length=80, nullable=true)
     */
    protected $email;

    /**
     * @var bool
     * @ORM\Column(name="SendEmails", type="boolean", nullable=false)
     */
    protected $sendemails = true;

    /**
     * @var int
     * @ORM\Column(name="AccessLevel", type="integer", nullable=false)
     */
    protected $accesslevel;

    /**
     * @var bool
     * @ORM\Column(name="IsApproved", type="boolean", nullable=false)
     */
    protected $isapproved = false;

    /**
     * @var string
     * @ORM\Column(name="Notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var bool
     * @ORM\Column(name="ShareByDefault", type="boolean", nullable=false)
     */
    protected $sharebydefault = true;

    /**
     * @var string
     * @ORM\Column(name="ShareCode", type="string", length=10, nullable=true)
     */
    protected $sharecode;

    /**
     * @var bool
     * @ORM\Column(name="TripShareByDefault", type="boolean", nullable=false)
     */
    protected $tripsharebydefault = false;

    /**
     * @var int
     * @ORM\Column(name="TripAccessLevel", type="integer", nullable=false)
     */
    protected $tripAccessLevel = 1;

    /**
     * @var string
     * @ORM\Column(name="Comment", type="string", length=4000, nullable=true)
     */
    protected $comment;

    /**
     * @var \DateTime
     * @ORM\Column(name="ShareDate", type="datetime", nullable=true)
     */
    protected $sharedate;

    /**
     * @var int
     * @ORM\Column(name="PictureVer", type="integer", nullable=true)
     */
    protected $picturever;

    /**
     * @var string
     * @ORM\Column(name="PictureExt", type="string", length=5, nullable=true)
     */
    protected $pictureext;

    /**
     * @var string
     * @ORM\Column(name="ItineraryCalendarCode", type="string", length=32, nullable=true)
     */
    protected $itinerarycalendarcode;

    /**
     * @var string
     * @ORM\Column(name="AccExpireCalendarCode", type="string", length=32, nullable=true)
     */
    protected $accexpirecalendarcode;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr", inversedBy="connections")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AgentID", referencedColumnName="UserID")
     * })
     */
    protected $agentid;

    /**
     * @var Usr|null
     * @ORM\ManyToOne(targetEntity="Usr", cascade={"detach"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ClientID", referencedColumnName="UserID")
     * })
     */
    protected $clientid;

    /**
     * @var AbPassenger
     * @ORM\OneToMany(targetEntity="AbPassenger", mappedBy="UserAgentID", cascade={"persist"})
     */
    protected $Passengers;

    /**
     * @var string
     * @ORM\Column(name="Alias", type="string", length=80, nullable=true)
     */
    protected $alias;

    /**
     * @var bool
     * @ORM\Column(name="KeepUpgraded", type="boolean", nullable=false)
     */
    protected $keepUpgraded = false;

    /**
     * @var bool
     * @ORM\Column(name="AccessPopupShown", type="boolean", nullable=false)
     */
    protected $popupShown = false;

    /**
     * @var TimelineShare[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="TimelineShare", mappedBy="userAgent")
     */
    protected $sharedTimelines;

    /**
     * @var TimelineShare[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="TimelineShare", mappedBy="familyMember")
     */
    protected $sharedFMTimelines;

    /**
     * @var array
     * @ORM\Column(name="TravelerProfile", type="json_array")
     */
    private $travelerProfile = [
        'travelerNumber' => '',
        'dateOfBirth' => '',
        'seatPreference' => '',
        'mealPreference' => '',
        'homeAirport' => '',
        'passport' => [
            'name' => '',
            'number' => '',
            'issueDate' => '',
            'country' => '',
            'expirationDate' => '',
        ],
    ];

    public function __construct()
    {
        $this->sharedTimelines = new ArrayCollection();
        $this->sharedFMTimelines = new ArrayCollection();
        $this->Passengers = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    /**
     * Get useragentid.
     *
     * @deprecated use getId()
     * @return int
     */
    public function getUseragentid()
    {
        return $this->useragentid;
    }

    public function getId(): ?int
    {
        return $this->useragentid;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     * @return Useragent
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname ? htmlspecialchars($firstname) : null; // compatibility with old code, value should be escaped in database

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string
     */
    public function getFirstname()
    {
        return htmlspecialchars_decode($this->firstname); // compatibility with old code, value should be escaped in database
    }

    /**
     * @return string
     */
    public function getMidname()
    {
        return htmlspecialchars_decode($this->midname); // compatibility with old code, value should be escaped in database
    }

    /**
     * @param string $midname
     * @return $this
     */
    public function setMidname($midname)
    {
        $this->midname = htmlspecialchars($midname); // compatibility with old code, value should be escaped in database

        return $this;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     * @return Useragent
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname ? htmlspecialchars($lastname) : null; // compatibility with old code, value should be escaped in database

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastname()
    {
        return htmlspecialchars_decode($this->lastname); // compatibility with old code, value should be escaped in database
    }

    /**
     * @param \DateTime $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Useragent
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
     * Set sendemails.
     *
     * @param bool $sendemails
     * @return Useragent
     */
    public function setSendemails($sendemails)
    {
        $this->sendemails = $sendemails;

        return $this;
    }

    /**
     * Get sendemails.
     *
     * @return bool
     */
    public function getSendemails()
    {
        return $this->sendemails;
    }

    /**
     * Set accesslevel.
     *
     * @param int $accesslevel
     * @return Useragent
     */
    public function setAccesslevel($accesslevel)
    {
        $this->accesslevel = $accesslevel;

        return $this;
    }

    /**
     * Get accesslevel.
     *
     * @return int
     */
    public function getAccesslevel()
    {
        return $this->accesslevel;
    }

    /**
     * Set isapproved.
     *
     * @param bool $isapproved
     * @return Useragent
     */
    public function setIsapproved($isapproved)
    {
        $this->isapproved = $isapproved;

        return $this;
    }

    /**
     * Get isapproved.
     *
     * @deprecated use Useragent::isApproved() instead
     * @return bool
     */
    public function getIsapproved()
    {
        return $this->isapproved;
    }

    /**
     * @return bool
     */
    public function isApproved()
    {
        return $this->isapproved;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     * @return Useragent
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set sharebydefault.
     *
     * @param bool $sharebydefault
     * @return Useragent
     */
    public function setSharebydefault($sharebydefault)
    {
        $this->sharebydefault = $sharebydefault;

        return $this;
    }

    /**
     * Get sharebydefault.
     *
     * @return bool
     */
    public function getSharebydefault()
    {
        return $this->sharebydefault;
    }

    /**
     * Set sharecode.
     *
     * @param string $sharecode
     * @return Useragent
     */
    public function setSharecode($sharecode)
    {
        $this->sharecode = $sharecode;

        return $this;
    }

    /**
     * Get sharecode.
     *
     * @return string
     */
    public function getSharecode()
    {
        return $this->sharecode;
    }

    /**
     * Set tripsharebydefault.
     *
     * @param bool $tripsharebydefault
     * @return Useragent
     */
    public function setTripsharebydefault($tripsharebydefault)
    {
        $this->tripsharebydefault = $tripsharebydefault;

        return $this;
    }

    /**
     * Get tripsharebydefault.
     *
     * @return bool
     */
    public function getTripsharebydefault()
    {
        return $this->tripsharebydefault;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     * @return Useragent
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
     * Set sharedate.
     *
     * @param \DateTime $sharedate
     * @return Useragent
     */
    public function setSharedate($sharedate)
    {
        $this->sharedate = $sharedate;

        return $this;
    }

    /**
     * Get sharedate.
     *
     * @return \DateTime
     */
    public function getSharedate()
    {
        return $this->sharedate;
    }

    /**
     * Set picturever.
     *
     * @param int $picturever
     * @return Useragent
     */
    public function setPicturever($picturever)
    {
        $this->picturever = $picturever;

        return $this;
    }

    /**
     * Get picturever.
     *
     * @return int
     */
    public function getPicturever()
    {
        return $this->picturever;
    }

    /**
     * Set pictureext.
     *
     * @param string $pictureext
     * @return Useragent
     */
    public function setPictureext($pictureext)
    {
        $this->pictureext = $pictureext;

        return $this;
    }

    /**
     * Get pictureext.
     *
     * @return string
     */
    public function getPictureext()
    {
        return $this->pictureext;
    }

    /**
     * Set itinerarycalendarcode.
     *
     * @param string $itinerarycalendarcode
     * @return Useragent
     */
    public function setItinerarycalendarcode($itinerarycalendarcode)
    {
        $this->itinerarycalendarcode = $itinerarycalendarcode;

        return $this;
    }

    /**
     * Get itinerarycalendarcode.
     *
     * @return string
     */
    public function getItinerarycalendarcode()
    {
        return $this->itinerarycalendarcode;
    }

    /**
     * Set agentid.
     *
     * @return Useragent
     */
    public function setAgentid(?Usr $agentid = null)
    {
        $this->agentid = $agentid;

        return $this;
    }

    /**
     * Get agentid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getAgentid()
    {
        return $this->agentid;
    }

    /**
     * Set clientid.
     *
     * @return Useragent
     */
    public function setClientid(?Usr $clientid = null)
    {
        $this->clientid = $clientid;

        return $this;
    }

    /**
     * Get clientid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getClientid()
    {
        return $this->clientid;
    }

    /**
     * Add Passenger.
     *
     * @return Useragent
     */
    public function addPassenger(AbPassenger $Passenger)
    {
        if (!$Passenger->getUseragent()) {
            $Passenger->setUseragent($this);
        }
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
     * Set alias.
     *
     * @param string $alias
     * @return Useragent
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function iskeepUpgraded()
    {
        return $this->keepUpgraded;
    }

    /**
     * @param bool $keepUpgraded
     */
    public function setKeepUpgraded($keepUpgraded)
    {
        $this->keepUpgraded = $keepUpgraded;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if ($this->getClientid()) {
            return $this->getClientid()->getFullName();
        } else {
            return self::getFullNameForNameParts(
                $this->getFirstname(),
                $this->getMidname(),
                $this->getLastname()
            );
        }
    }

    public static function getFullNameForNameParts(?string $firstName, ?string $midName, ?string $lastName)
    {
        return \trim($firstName . (StringUtils::isNotEmpty($midName) ? ' ' . $midName : '') . ' ' . $lastName);
    }

    public function isFamilyMember()
    {
        return null === $this->clientid;
    }

    public static function possibleOwnerAccessLevels()
    {
        return [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY];
    }

    public function isPossibleOwner()
    {
        return
            $this->isapproved
            && (
                $this->isFamilyMember()
                    || in_array($this->getAccesslevel(), self::possibleOwnerAccessLevels())
                //                    &&
                //                    $this->getClientid()->getAccountlevel() != ACCOUNT_LEVEL_BUSINESS
            );
    }

    public function isItinerariesShared()
    {
        return $this->isapproved && count($this->sharedTimelines);
    }

    public function isItinerariesSharedWith(Usr $user)
    {
        if ($this->isFamilyMember()) {
            if ($this->agentid->getId() == $user->getId()) {
                return true;
            }

            foreach ($this->sharedFMTimelines as $sharedTimeline) {
                if ($sharedTimeline->getRecipientUser()->getId() == $user->getId()) {
                    return true;
                }
            }
        } else {
            foreach ($this->sharedTimelines as $sharedTimeline) {
                if ($sharedTimeline->getRecipientUser()->getId() == $user->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getItineraryForwardingEmail($host = 'email.AwardWallet.com')
    {
        if ($this->agentid) {
            if (!StringHandler::isEmpty($this->alias) && !$this->clientid) {
                return sprintf('%s.%s@%s', $this->agentid->getLogin(), $this->alias, $host);
            } elseif ($this->clientid) {
                return sprintf('%s@%s', $this->clientid->getLogin(), $host);
            } else {
                return sprintf('%s@%s', $this->agentid->getLogin(), $host);
            }
        }

        return null;
    }

    /**
     * @return TimelineShare[]|PersistentCollection
     */
    public function getSharedTimelines()
    {
        return $this->sharedTimelines;
    }

    public function setSharedTimelines($sharedTimelines)
    {
        $this->sharedTimelines = $sharedTimelines;

        return $this;
    }

    /**
     * @return int
     */
    public function getTripAccessLevel()
    {
        return $this->tripAccessLevel;
    }

    /**
     * @param int $tripAccessLevel
     */
    public function setTripAccessLevel($tripAccessLevel)
    {
        $this->tripAccessLevel = $tripAccessLevel;
    }

    /**
     * @return bool
     */
    public function isPopupShown()
    {
        return $this->popupShown;
    }

    /**
     * @param bool $popupShown
     */
    public function setPopupShown($popupShown)
    {
        $this->popupShown = $popupShown;
    }

    /**
     * @param string|null $name
     * @return string|null
     */
    public static function cleanName($name)
    {
        if (empty($name)) {
            return $name;
        }

        $name = strip_tags($name);
        $name = CleanXMLValue($name);
        $name = preg_replace(['/\[{2,}/', '/\]{2,}/'], ['[', ']'], $name);

        return $name;
    }

    public function getAvatarSrc(): ?string
    {
        return self::generateAvatarSrc($this->getUseragentid(), $this->getPicturever(), $this->getPictureext());
    }

    public static function generateAvatarSrc($userAgentId, $pictureVer, $pictureExt): ?string
    {
        return !empty($pictureVer) ? PicturePath('/images/uploaded/userAgent', 'small', $userAgentId, $pictureVer, $pictureExt, 'file') : null;
    }

    public function getTravelerProfile(): array
    {
        return $this->travelerProfile;
    }

    public function setTravelerProfile(array $travelerProfile): void
    {
        $this->travelerProfile = $travelerProfile;
    }
}
