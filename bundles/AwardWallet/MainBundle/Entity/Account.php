<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Account.
 *
 * @ORM\Table(name="Account")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AccountRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\EntityListeners({ "AwardWallet\MainBundle\Entity\Listener\AccountListener" })
 * @AwAssert\Account
 */
class Account implements LoyaltyProgramInterface
{
    use CardImageContainerTrait {
        removeCardImage as protected removeCardImageTrait;
    }
    use LocationContainerTrait;
    use CustomLoyaltyPropertyContainerTrait;
    use OwnableTrait {
        setOwner as protected traitSetOwner;
    }

    public const DISABLE_REASON_USER = 1;
    public const DISABLE_REASON_PREVENT_LOCKOUT = 2;
    public const DISABLE_REASON_PROVIDER_ERROR = 3;
    public const DISABLE_REASON_ENGINE_ERROR = 4;
    public const DISABLE_REASON_LOCKOUT = 5;

    public const SAVE_PASSWORD_OPTIONS = [SAVE_PASSWORD_DATABASE, SAVE_PASSWORD_LOCALLY];

    public const LOGIN2_USA_VALUES = ['US', 'USA', 'United States'];

    public const NOT_ARCHIVED = 0;
    public const ARCHIVED = 1;

    public const CHECKED_BY_BACKGROUND_CHECK = 1;
    public const CHECKED_BY_USER_SERVER = 2;
    public const CHECKED_BY_USER_BROWSER_EXTENSION = 4;
    public const CHECKED_BY_EMAIL = 5;
    public const CHECKED_BY_SUBACCOUNT = 6;

    public const CHECKED_BY_NAMES = [
        self::CHECKED_BY_BACKGROUND_CHECK => "Background Check",
        self::CHECKED_BY_USER_SERVER => "User, server",
        self::CHECKED_BY_USER_BROWSER_EXTENSION => "User, extension",
        self::CHECKED_BY_EMAIL => "Email",
        self::CHECKED_BY_SUBACCOUNT => "Subaccount",
    ];

    public const CHECKED_BY_USER = [self::CHECKED_BY_USER_SERVER, self::CHECKED_BY_USER_BROWSER_EXTENSION];

    /**
     * one of fields password, login, login2, login3 was changed.
     *
     * @var bool
     */
    public $credentialsChanged = false;

    /**
     * one of fields password, login, login2, login3 was changed.
     *
     * @var bool
     */
    public $disabledChanged = false;

    /**
     * used to decrypt password in getPass(). set from AccountListener.postLoad.
     *
     * @var LocalPasswordsManager
     */
    public $localPasswordManager;

    /**
     * @var int
     * @ORM\Column(name="AccountID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $accountid;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state = ACCOUNT_ENABLED;

    /**
     * @var int
     * @ORM\Column(name="ErrorCode", type="integer", nullable=true)
     */
    protected $errorcode = ACCOUNT_UNCHECKED;

    /**
     * @var string
     * @ORM\Column(name="ErrorMessage", type="text", nullable=true)
     */
    protected $errormessage;

    /**
     * @var float
     * @ORM\Column(name="Balance", type="decimal", nullable=true)
     */
    protected $balance;

    /**
     * @var string
     * @ORM\Column(name="Login", type="string", length=80, nullable=false)
     */
    protected $login;

    /**
     * @var string
     * @ORM\Column(name="LoginID", type="string", length=250, nullable=true)
     */
    protected $loginId;

    /**
     * @var string
     * @ORM\Column(name="Pass", type="string", length=250, nullable=false)
     */
    protected $pass = '';

    /**
     * used in prePersist / postPersist handlers.
     *
     * @var string
     */
    protected $localPassword;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=true)
     */
    protected $updatedate;

    /**
     * @var string
     * @ORM\Column(name="Login2", type="string", length=80, nullable=true)
     */
    protected $login2;

    /**
     * @var string
     * @Assert\Length(max = 10000)
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(name="SavePassword", type="integer", nullable=false)
     */
    protected int $savepassword = SAVE_PASSWORD_DATABASE;

    /**
     * @var \DateTime
     * @ORM\Column(name="ExpirationDate", type="datetime", nullable=true)
     */
    protected $expirationdate;

    /**
     * @var int
     * @Assert\Type("integer")
     * @ORM\Column(name="Kind", type="integer", nullable=true)
     */
    protected $kind;

    /**
     * @var string
     * @ORM\Column(name="ProgramName", type="string", length=80, nullable=true)
     */
    protected $programname;

    /**
     * @var string
     * @ORM\Column(name="LoginURL", type="string", length=250, nullable=true)
     */
    protected $loginurl;

    /**
     * @var int
     * @ORM\Column(name="ExpirationAutoSet", type="integer", nullable=false)
     */
    protected $expirationautoset = EXPIRATION_UNKNOWN;

    /**
     * @var int
     * @Assert\Length(min = 1, allowEmptyString="true")
     * @ORM\Column(name="Goal", type="integer", nullable=true)
     */
    protected $goal;

    /**
     * @var string
     * @ORM\Column(name="Region", type="string", length=80, nullable=true)
     */
    protected $region;

    /**
     * @var int
     * @ORM\Column(name="GoalAutoSet", type="integer", nullable=false)
     */
    protected $goalautoset = 1;

    /**
     * @var \DateTime
     * @ORM\Column(name="PassChangeDate", type="datetime", nullable=true)
     */
    protected $passchangedate;

    /**
     * @var string
     * @ORM\Column(name="ExpirationWarning", type="string", length=2000, nullable=true)
     */
    protected $expirationwarning;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastChangeDate", type="datetime", nullable=true)
     */
    protected $lastchangedate;

    /**
     * @var int
     * @ORM\Column(name="ChangeCount", type="integer", nullable=false)
     */
    protected $changecount = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastActivity", type="datetime", nullable=true)
     */
    protected $lastactivity;

    /**
     * @var int
     * @ORM\Column(name="SubAccounts", type="integer", nullable=false)
     */
    protected $subaccounts = 0;

    /**
     * @var float
     * @ORM\Column(name="LastBalance", type="float", nullable=true)
     */
    protected $lastbalance;

    /**
     * @var bool
     * @ORM\Column(name="ChangesConfirmed", type="boolean", nullable=false)
     */
    protected $changesConfirmed = true;

    /**
     * @var \DateTime
     * @ORM\Column(name="SuccessCheckDate", type="datetime", nullable=true)
     */
    protected $successcheckdate;

    /**
     * @var string
     * @ORM\Column(name="Login3", type="string", length=40, nullable=true)
     */
    protected $login3;

    /**
     * @var string
     * @ORM\Column(name="Question", type="string", length=250, nullable=true)
     */
    protected $question;

    /**
     * @var float
     * @ORM\Column(name="TotalBalance", type="float", nullable=true)
     */
    protected $totalbalance;

    /**
     * @var int
     * @ORM\Column(name="DontTrackExpiration", type="boolean", nullable=false)
     */
    protected $donttrackexpiration = false;

    /**
     * @var bool
     * @ORM\Column(name="NotRelated", type="boolean", nullable=false)
     */
    protected $notrelated = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="QueueDate", type="datetime", nullable=true)
     */
    protected $queuedate;

    /**
     * @var int
     * @ORM\Column(name="NextCheckPriority", type="integer", nullable=false)
     */
    protected $nextcheckpriority = 5;

    /**
     * @var int
     * @ORM\Column(name="IsActiveTab", type="integer", nullable=false)
     */
    protected $isactivetab = 0;

    /**
     * @var bool
     * @ORM\Column(name="IsArchived", type="boolean", nullable=false)
     */
    protected $isarchived = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="ModifyDate", type="datetime", nullable=true)
     */
    protected $modifydate;

    /**
     * @var int
     * @ORM\Column(name="CheckedBy", type="integer", nullable=true)
     */
    protected $checkedby;

    /**
     * @var bool
     * @ORM\Column(name="Disabled", type="boolean", nullable=false)
     */
    protected $disabled = false;

    /**
     * @var int
     * @ORM\Column(name="DisableReason", type="integer")
     */
    protected $disableReason;

    /**
     * @var \DateTime
     * @ORM\Column(name="DisableDate", type="datetime", nullable=true)
     */
    protected $disableDate;

    /**
     * @var bool
     * @ORM\Column(name="BackgroundCheck", type="boolean", nullable=false)
     */
    protected $BackgroundCheck = true;

    /**
     * @var int
     * @ORM\Column(name="Itineraries", type="integer", nullable=false)
     */
    protected $itineraries = 0;

    /**
     * @var float
     * @ORM\Column(name="LastDurationWithoutPlans", type="float", nullable=true)
     */
    protected $lastdurationwithoutplans;

    /**
     * @var float
     * @ORM\Column(name="LastDurationWithPlans", type="float", nullable=true)
     */
    protected $lastdurationwithplans;

    /**
     * @var int
     * @ORM\Column(name="ActivityScore", type="integer", nullable=false)
     */
    protected $activityscore = 50;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastCheckItDate", type="datetime", nullable=true)
     */
    protected $lastcheckitdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastCheckPastItsDate", type="datetime", nullable=true)
     */
    protected $lastCheckPastItsDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastCheckHistoryDate", type="datetime", nullable=true)
     */
    protected $lastcheckhistorydate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EmailParseDate", type="datetime", nullable=true)
     */
    protected $emailparsedate;

    /**
     * @var int
     * @ORM\Column(name="ErrorCount", type="integer", nullable=false)
     */
    protected $errorcount = 0;

    /**
     * @var string
     * @ORM\Column(name="TripsHash", type="string", length=64, nullable=true)
     */
    protected $tripshash;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var AccountProperty[]|Collection
     * @ORM\OneToMany(targetEntity="Accountproperty", mappedBy="accountid", cascade={"persist", "remove", "refresh"}, orphanRemoval=true)
     */
    protected $Properties;

    /**
     * @var Useragent[]|Collection
     * @ORM\ManyToMany(targetEntity="Useragent")
     * @ORM\JoinTable(name="AccountShare",
     *      joinColumns={@ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID", unique=true)}
     * )
     */
    protected $useragents;

    /**
     * @var Answer[]|Collection
     * @ORM\OneToMany(targetEntity="Answer", mappedBy="accountid", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $answers;

    /**
     * @var Accountbalance[]|Collection
     * @ORM\OneToMany(targetEntity="Accountbalance", mappedBy="accountid", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $balanceHistory;

    /**
     * @var CardImage[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="CardImage",
     *     mappedBy="accountid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=false,
     *     indexBy="kind"
     * )
     */
    protected $cardImages;

    /**
     * @var Location[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="Location",
     *     mappedBy="account",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="name"
     * )
     */
    protected $locations;

    /**
     * @var CustomLoyaltyProperty[]|PersistentCollection
     * @ORM\OneToMany(
     *     targetEntity="CustomLoyaltyProperty",
     *     mappedBy="accountid",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="name"
     * )
     */
    protected $customLoyaltyProperties;

    /**
     * @var Trip[]|Collection
     * @ORM\OneToMany(targetEntity="Trip", mappedBy="account", cascade={"remove"}, orphanRemoval=true)
     */
    protected $trips;

    /**
     * @var Reservation[]|Collection
     * @ORM\OneToMany(targetEntity="Reservation", mappedBy="account", cascade={"remove"}, orphanRemoval=true)
     */
    protected $reservations;

    /**
     * @var Rental[]|Collection
     * @ORM\OneToMany(targetEntity="Rental", mappedBy="account", cascade={"remove"}, orphanRemoval=true)
     */
    protected $rentals;

    /**
     * @var Restaurant[]|Collection
     * @ORM\OneToMany(targetEntity="Restaurant", mappedBy="account", cascade={"remove"}, orphanRemoval=true)
     */
    protected $restaurants;

    /**
     * @var Subaccount[]|Collection
     * @ORM\OneToMany(targetEntity="Subaccount", mappedBy="accountid", cascade={"persist", "remove", "refresh"}, orphanRemoval=true)
     */
    protected $subAccountsEntities;

    /**
     * @var int
     * @ORM\Column(name="HistoryVersion", type="integer", nullable=true)
     */
    protected $historyVersion;

    /**
     * @var string
     * @ORM\Column(name="HistoryState", type="text", nullable=true)
     */
    protected $historyState;

    /**
     * json-serialized oauth2 token, on supported providers.
     *
     * @var string
     * @ORM\Column(name="AuthInfo", type="string", length=2000, nullable=true)
     */
    protected $authInfo;

    /**
     * @var bool
     * @ORM\Column(name="DisableExtension", type="boolean", nullable=false)
     */
    protected $disableExtension = false;

    /**
     * @var bool
     * @ORM\Column(name="DisableClientPasswordAccess", type="boolean", nullable=false)
     */
    protected $disableClientPasswordAccess = false;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="AccountHistory", mappedBy="account", cascade={"persist"})
     */
    protected $history;

    /**
     * @var \DateTime
     * @ORM\Column(name="BalanceWatchStartDate", type="datetime", nullable=true)
     */
    protected $balanceWatchStartDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateLimitDisabledUntil", type="datetime", nullable=true)
     */
    protected $updateLimitDisabledUntil;

    /**
     * @var int
     * @ORM\Column(name="PwnedTimes", type="integer")
     */
    protected $pwnedTimes;

    /**
     * @var string
     * @ORM\Column(name="SourceEmail", type="string", length=100, nullable=true)
     */
    protected $sourceEmail;

    /**
     * @var Currency
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Currency")
     * @ORM\JoinColumn(name="CurrencyID", referencedColumnName="CurrencyID", nullable=true)
     */
    protected $currency;

    /**
     * @var string
     * @ORM\Column(name="CustomEliteLevel", type="string", length=128, nullable=true)
     */
    protected $customEliteLevel;

    /**
     * @var bool
     * @ORM\Column(name="DisableBackgroundUpdating", type="boolean", nullable=false)
     */
    private $disableBackgroundUpdating = false;
    /**
     * @internal
     */
    private PasswordEncryptor $passwordEncryptor;
    /**
     * @internal
     */
    private PasswordDecryptor $passwordDecryptor;

    /**
     * @var \DateTime
     * @ORM\Column(name="SentToUpdateDate", type="datetime", nullable=true)
     */
    private $sentToUpdateDate;

    /**
     * @var float
     * @ORM\Column(name="PointValue", type="decimal", nullable=true)
     */
    private $pointValue;

    public function __construct()
    {
        $this->creationdate = new \DateTime();
        $this->updatedate = clone $this->creationdate;
        $this->modifydate = clone $this->creationdate;
        $this->passchangedate = clone $this->creationdate;
        $this->Properties = new ArrayCollection();
        $this->useragents = new ArrayCollection();
        $this->balanceHistory = new ArrayCollection();
        $this->cardImages = new ArrayCollection();
        $this->locations = new ArrayCollection();
        $this->customLoyaltyProperties = new ArrayCollection();
        $this->subAccountsEntities = new ArrayCollection();
    }

    public function __toString()
    {
        return 'Account.' . $this->accountid;
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
     * @return int
     */
    public function getId()
    {
        return $this->getAccountid();
    }

    /**
     * Set state.
     *
     * @param int $state
     * @return Account
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
     * Set errorcode.
     *
     * @param int $errorcode
     * @return Account
     */
    public function setErrorcode($errorcode)
    {
        $this->errorcode = $errorcode;

        return $this;
    }

    /**
     * Get errorcode.
     *
     * @return int
     */
    public function getErrorcode()
    {
        return $this->errorcode;
    }

    /**
     * Set errormessage.
     *
     * @param string $errormessage
     * @return Account
     */
    public function setErrormessage($errormessage)
    {
        $this->errormessage = $errormessage;

        return $this;
    }

    /**
     * Get errormessage.
     *
     * @return string
     */
    public function getErrormessage()
    {
        return $this->errormessage;
    }

    /**
     * Set balance.
     *
     * @param float $balance
     * @return Account
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set login.
     *
     * @param string $login
     * @return Account
     */
    public function setLogin($login)
    {
        if ($this->login != $login && !$this->isOauthTokenValid()) {
            $this->resetCredentialsState();
        }
        $this->login = $login;

        return $this;
    }

    /**
     * Get login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set pass.
     *
     * @param string $pass
     * @return Account
     */
    public function setPass($pass)
    {
        if ($this->getPass() != $pass && !$this->isOauthTokenValid()) {
            $this->resetCredentialsState(false);
            $this->pwnedTimes = null;
        }

        if ($pass !== null && $pass !== '') {
            $pass = $this->passwordEncryptor->encrypt($pass);
        }

        $this->pass = $pass;
        $this->localPassword = null;

        return $this;
    }

    /**
     * Get pass.
     *
     * @return string
     */
    public function getPass()
    {
        if (!empty($this->localPassword)) {
            return $this->passwordDecryptor->decrypt($this->localPassword);
        }

        if ($this->savepassword == SAVE_PASSWORD_LOCALLY && !empty($this->accountid) && !$this->credentialsChanged) {
            return $this->localPasswordManager->getPassword($this->accountid);
        }

        if ($this->pass === '') {
            return $this->pass;
        }

        return $this->passwordDecryptor->decrypt($this->pass ?? '');
    }

    public function getDatabasePass()
    {
        if (!empty($this->pass)) {
            return $this->passwordDecryptor->decrypt($this->pass);
        }

        return $this->pass;
    }

    /**
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Account
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
     * Set updatedate.
     *
     * @param ?\DateTime $updatedate
     * @return Account
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
     * Set login2.
     *
     * @param string $login2
     * @return Account
     */
    public function setLogin2($login2)
    {
        if ($this->login2 != $login2 && !$this->isOauthTokenValid()) {
            $this->resetCredentialsState();
        }
        $this->login2 = $login2;

        return $this;
    }

    /**
     * Get login2.
     *
     * @return string
     */
    public function getLogin2()
    {
        return $this->login2;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     * @return Account
     */
    public function setComment($comment)
    {
        $this->comment = null === $comment ? null : htmlspecialchars($comment);

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return null === $this->comment ? null : htmlspecialchars_decode($this->comment);
    }

    /**
     * Set savepassword.
     *
     * @return Account
     */
    public function setSavepassword(?int $savepassword)
    {
        if ($savepassword !== null && !in_array($savepassword, self::SAVE_PASSWORD_OPTIONS)) {
            throw new \Exception("SavePassword should be from SAVE_PASSWORD_OPTIONS");
        }

        $this->savepassword = $savepassword;

        return $this;
    }

    /**
     * Get savepassword.
     *
     * @return int
     */
    public function getSavepassword()
    {
        return $this->savepassword;
    }

    /**
     * Set expirationdate.
     *
     * @param \DateTime $expirationdate
     * @return Account
     */
    public function setExpirationdate($expirationdate)
    {
        $this->expirationdate = $expirationdate;

        return $this;
    }

    /**
     * Get expirationdate.
     *
     * @return \DateTime
     */
    public function getExpirationdate()
    {
        return $this->expirationdate;
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Account
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return int
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set programname.
     *
     * @param string $programname
     * @return Account
     */
    public function setProgramname($programname)
    {
        $this->programname = null === $programname ? null : htmlspecialchars($programname);

        return $this;
    }

    /**
     * Get programname.
     *
     * @return string
     */
    public function getProgramname()
    {
        return null === $this->programname ? null : htmlspecialchars_decode($this->programname);
    }

    /**
     * Set loginurl.
     *
     * @param string $loginurl
     * @return Account
     */
    public function setLoginurl($loginurl)
    {
        $this->loginurl = null === $loginurl ? null : htmlspecialchars($loginurl);

        return $this;
    }

    /**
     * Get loginurl.
     *
     * @return string
     */
    public function getLoginurl()
    {
        return null === $this->loginurl ? null : htmlspecialchars_decode($this->loginurl);
    }

    /**
     * Set expirationautoset.
     *
     * @param int $expirationautoset
     * @return Account
     */
    public function setExpirationautoset($expirationautoset)
    {
        $this->expirationautoset = $expirationautoset;

        return $this;
    }

    /**
     * Get expirationautoset.
     *
     * @return int
     */
    public function getExpirationautoset()
    {
        return $this->expirationautoset;
    }

    /**
     * Set goal.
     *
     * @param int $goal
     * @return Account
     */
    public function setGoal($goal)
    {
        if (empty($goal)) {
            $this->setGoalautoset(1);
        } elseif ($this->goal != $goal) {
            $this->setGoalautoset(0);
        }
        $this->goal = $goal;

        return $this;
    }

    /**
     * Get goal.
     *
     * @return int
     */
    public function getGoal()
    {
        return $this->goal;
    }

    /**
     * Set region.
     *
     * @param string $region
     * @return Account
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set goalautoset.
     *
     * @param int $goalautoset
     * @return Account
     */
    public function setGoalautoset($goalautoset)
    {
        $this->goalautoset = $goalautoset;

        return $this;
    }

    /**
     * Get goalautoset.
     *
     * @return int
     */
    public function getGoalautoset()
    {
        return $this->goalautoset;
    }

    /**
     * Set passchangedate.
     *
     * @param \DateTime $passchangedate
     * @return Account
     */
    public function setPasschangedate($passchangedate)
    {
        $this->passchangedate = $passchangedate;

        return $this;
    }

    /**
     * Get passchangedate.
     *
     * @return \DateTime
     */
    public function getPasschangedate()
    {
        return $this->passchangedate;
    }

    /**
     * @return string
     */
    public function getHistoryState()
    {
        return $this->historyState;
    }

    /**
     * @param string $historyState
     * @return $this
     */
    public function setHistoryState($historyState)
    {
        $this->historyState = $historyState;

        return $this;
    }

    /**
     * Set expirationwarning.
     *
     * @param string $expirationwarning
     * @return Account
     */
    public function setExpirationwarning($expirationwarning)
    {
        $this->expirationwarning = $expirationwarning;

        return $this;
    }

    /**
     * Get expirationwarning.
     *
     * @return string
     */
    public function getExpirationwarning()
    {
        return $this->expirationwarning;
    }

    /**
     * Set lastchangedate.
     *
     * @param \DateTime $lastchangedate
     * @return Account
     */
    public function setLastchangedate($lastchangedate)
    {
        $this->lastchangedate = $lastchangedate;

        return $this;
    }

    /**
     * Get lastchangedate.
     *
     * @return \DateTime
     */
    public function getLastchangedate()
    {
        return $this->lastchangedate;
    }

    /**
     * Set changecount.
     *
     * @param int $changecount
     * @return Account
     */
    public function setChangecount($changecount)
    {
        $this->changecount = $changecount;

        return $this;
    }

    /**
     * Get changecount.
     *
     * @return int
     */
    public function getChangecount()
    {
        return $this->changecount;
    }

    /**
     * Set lastactivity.
     *
     * @param \DateTime $lastactivity
     * @return Account
     */
    public function setLastactivity($lastactivity)
    {
        $this->lastactivity = $lastactivity;

        return $this;
    }

    /**
     * Get lastactivity.
     *
     * @return \DateTime
     */
    public function getLastactivity()
    {
        return $this->lastactivity;
    }

    /**
     * Set subaccounts.
     *
     * @param int $subaccounts
     * @return Account
     */
    public function setSubaccounts($subaccounts)
    {
        $this->subaccounts = $subaccounts;

        return $this;
    }

    /**
     * Get subaccounts.
     *
     * @return int
     */
    public function getSubaccounts()
    {
        return $this->subaccounts;
    }

    /**
     * Set lastbalance.
     *
     * @param float $lastbalance
     * @return Account
     */
    public function setLastbalance($lastbalance)
    {
        $this->lastbalance = $lastbalance;

        return $this;
    }

    /**
     * Get lastbalance.
     *
     * @return float
     */
    public function getLastbalance()
    {
        return $this->lastbalance;
    }

    /**
     * Set successcheckdate.
     *
     * @param \DateTime $successcheckdate
     * @return Account
     */
    public function setSuccesscheckdate($successcheckdate)
    {
        $this->successcheckdate = $successcheckdate;

        return $this;
    }

    /**
     * Get successcheckdate.
     *
     * @return \DateTime
     */
    public function getSuccesscheckdate()
    {
        return $this->successcheckdate;
    }

    /**
     * Set login3.
     *
     * @param string $login3
     * @return Account
     */
    public function setLogin3($login3)
    {
        if ($this->login3 != $login3 && !$this->isOauthTokenValid()) {
            $this->resetCredentialsState();
        }
        $this->login3 = $login3;

        return $this;
    }

    /**
     * Get login3.
     *
     * @return string
     */
    public function getLogin3()
    {
        return $this->login3;
    }

    /**
     * Set question.
     *
     * @param string $question
     * @return Account
     */
    public function setQuestion($question)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @return Answer[]|Collection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * Set totalbalance.
     *
     * @param float $totalbalance
     * @return Account
     */
    public function setTotalbalance($totalbalance)
    {
        $this->totalbalance = $totalbalance;

        return $this;
    }

    /**
     * Get totalbalance.
     *
     * @return float
     */
    public function getTotalbalance()
    {
        return $this->totalbalance;
    }

    /**
     * Set donttrackexpiration.
     *
     * @param bool $donttrackexpiration
     * @return Account
     */
    public function setDonttrackexpiration($donttrackexpiration)
    {
        $this->donttrackexpiration = $donttrackexpiration;

        return $this;
    }

    /**
     * Get donttrackexpiration.
     *
     * @return bool
     */
    public function getDonttrackexpiration()
    {
        return $this->donttrackexpiration;
    }

    /**
     * Set notrelated.
     *
     * @param bool $notrelated
     * @return Account
     */
    public function setNotrelated($notrelated)
    {
        $this->notrelated = $notrelated;

        return $this;
    }

    /**
     * Get notrelated.
     *
     * @return bool
     */
    public function getNotrelated()
    {
        return $this->notrelated;
    }

    /**
     * Set queuedate.
     *
     * @param ?\DateTime $queuedate
     * @return Account
     */
    public function setQueuedate($queuedate)
    {
        $this->queuedate = $queuedate;

        return $this;
    }

    /**
     * Get queuedate.
     *
     * @return \DateTime
     */
    public function getQueuedate()
    {
        return $this->queuedate;
    }

    /**
     * Set nextcheckpriority.
     *
     * @param int $nextcheckpriority
     * @return Account
     */
    public function setNextcheckpriority($nextcheckpriority)
    {
        $this->nextcheckpriority = $nextcheckpriority;

        return $this;
    }

    /**
     * Get nextcheckpriority.
     *
     * @return int
     */
    public function getNextcheckpriority()
    {
        return $this->nextcheckpriority;
    }

    /**
     * Set isactivetab.
     *
     * @param int $isactivetab
     * @return Account
     */
    public function setIsactivetab($isactivetab)
    {
        $this->isactivetab = $isactivetab;

        return $this;
    }

    /**
     * Get isactivetab.
     *
     * @return int
     */
    public function getIsactivetab()
    {
        return $this->isactivetab;
    }

    public function setIsArchived(bool $isarchived): self
    {
        $this->isarchived = $isarchived;

        return $this;
    }

    public function getIsArchived(): bool
    {
        return $this->isarchived;
    }

    /**
     * Set modifydate.
     *
     * @param \DateTime $modifydate
     * @return Account
     */
    public function setModifydate($modifydate)
    {
        $this->modifydate = $modifydate;

        return $this;
    }

    /**
     * Get modifydate.
     *
     * @return \DateTime
     */
    public function getModifydate()
    {
        return $this->modifydate;
    }

    /**
     * Set checkedby.
     *
     * @param int? $checkedby
     * @return Account
     */
    public function setCheckedby($checkedby)
    {
        $this->checkedby = $checkedby;

        return $this;
    }

    /**
     * Get checkedby.
     *
     * @return int?
     */
    public function getCheckedby()
    {
        return $this->checkedby;
    }

    /**
     * Set itineraries.
     *
     * @param int $itineraries
     * @return Account
     */
    public function setItineraries($itineraries)
    {
        $this->itineraries = $itineraries;

        return $this;
    }

    /**
     * Get itineraries.
     *
     * @return int
     */
    public function getItineraries()
    {
        return $this->itineraries;
    }

    /**
     * Set lastdurationwithoutplans.
     *
     * @param float $lastdurationwithoutplans
     * @return Account
     */
    public function setLastdurationwithoutplans($lastdurationwithoutplans)
    {
        $this->lastdurationwithoutplans = $lastdurationwithoutplans;

        return $this;
    }

    /**
     * Get lastdurationwithoutplans.
     *
     * @return float
     */
    public function getLastdurationwithoutplans()
    {
        return $this->lastdurationwithoutplans;
    }

    /**
     * Set lastdurationwithplans.
     *
     * @param float $lastdurationwithplans
     * @return Account
     */
    public function setLastdurationwithplans($lastdurationwithplans)
    {
        $this->lastdurationwithplans = $lastdurationwithplans;

        return $this;
    }

    /**
     * Get lastdurationwithplans.
     *
     * @return float
     */
    public function getLastdurationwithplans()
    {
        return $this->lastdurationwithplans;
    }

    /**
     * Set activityscore.
     *
     * @param int $activityscore
     * @return Account
     */
    public function setActivityscore($activityscore)
    {
        $this->activityscore = $activityscore;

        return $this;
    }

    /**
     * Get activityscore.
     *
     * @return int
     */
    public function getActivityscore()
    {
        return $this->activityscore;
    }

    /**
     * Set lastcheckitdate.
     *
     * @param \DateTime $lastcheckitdate
     * @return Account
     */
    public function setLastcheckitdate($lastcheckitdate)
    {
        $this->lastcheckitdate = $lastcheckitdate;

        return $this;
    }

    /**
     * Get lastcheckitdate.
     *
     * @return \DateTime
     */
    public function getLastcheckitdate()
    {
        return $this->lastcheckitdate;
    }

    public function getLastCheckPastItsDate(): ?\DateTime
    {
        return $this->lastCheckPastItsDate;
    }

    public function setLastCheckPastItsDate(?\DateTime $lastCheckPastItsDate): self
    {
        $this->lastCheckPastItsDate = $lastCheckPastItsDate;

        return $this;
    }

    /**
     * Set lastcheckhistorydate.
     *
     * @param \DateTime $lastcheckhistorydate
     * @return Account
     */
    public function setLastcheckhistorydate($lastcheckhistorydate)
    {
        $this->lastcheckhistorydate = $lastcheckhistorydate;

        return $this;
    }

    /**
     * Get lastcheckhistorydate.
     *
     * @return \DateTime
     */
    public function getLastcheckhistorydate()
    {
        return $this->lastcheckhistorydate;
    }

    /**
     * Set emailparsedate.
     *
     * @param \DateTime $emailparsedate
     * @return Account
     */
    public function setEmailparsedate($emailparsedate)
    {
        $this->emailparsedate = $emailparsedate;

        return $this;
    }

    /**
     * Get emailparsedate.
     *
     * @return \DateTime
     */
    public function getEmailparsedate()
    {
        return $this->emailparsedate;
    }

    /**
     * Set errorcount.
     *
     * @param int $errorcount
     * @return Account
     */
    public function setErrorcount($errorcount)
    {
        $this->errorcount = $errorcount;

        return $this;
    }

    /**
     * Get errorcount.
     *
     * @return int
     */
    public function getErrorcount()
    {
        return $this->errorcount;
    }

    /**
     * Set tripshash.
     *
     * @param string $tripshash
     * @return Account
     */
    public function setTripshash($tripshash)
    {
        $this->tripshash = $tripshash;

        return $this;
    }

    /**
     * Get tripshash.
     *
     * @return string
     */
    public function getTripshash()
    {
        return $this->tripshash;
    }

    /**
     * Set providerid.
     *
     * @return Account
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }

    /**
     * Set useragentid.
     *
     * @return Account
     * @deprecated use setUserAgent instead
     */
    public function setUseragentid(?Useragent $useragentid = null)
    {
        $this->setUserAgent($useragentid);
    }

    /**
     * @return $this
     */
    public function setUserAgent(?Useragent $useragentid): self
    {
        $this->userAgent = $useragentid;

        return $this;
    }

    /**
     * Get useragentid.
     *
     * @return Useragent
     * @deprecated use getUserAgent() instead
     */
    public function getUseragentid()
    {
        return $this->getUserAgent();
    }

    public function getUserAgent(): ?Useragent
    {
        return $this->userAgent;
    }

    /**
     * Set userid.
     *
     * @return Account
     * @deprecated use setUser() instead
     */
    public function setUserid(?Usr $userid = null)
    {
        return $this->setUser($userid);
    }

    /**
     * @return $this
     */
    public function setUser(?Usr $user)
    {
        if (!empty($this->user) && (empty($user) || $this->user->getUserid() != $user->getUserid())) {
            $this->useragents = new ArrayCollection();
        }
        $this->user = $user;

        return $this;
    }

    public function setOwner(?Owner $owner)
    {
        if (null !== $this->user && $this->user !== $owner->getUser()) {
            $this->useragents = new ArrayCollection();
        }
        $this->traitSetOwner($owner);
    }

    /**
     * Get userid.
     *
     * @return Usr
     * @deprecated use getUser() instead
     */
    public function getUserid()
    {
        return $this->getUser();
    }

    public function getUser(): ?Usr
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getOwnerFullName()
    {
        if ($this->getUseragentid()) {
            return htmlspecialchars($this->getUseragentid()->getFullName());
        } else {
            return htmlspecialchars($this->getUserid()->getFullName());
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function prePersist()
    {
        // set default goal
        if (
            null === $this->getId()
            && isset($this->providerid)
        ) {
            $this->goal = $this->providerid->getGoal();
        }

        if ($this->savepassword == SAVE_PASSWORD_DATABASE) {
            if ($this->pass !== null && $this->pass !== '') {
                $this->pass = $this->passwordEncryptor->encrypt($this->getDatabasePass());
            }
            $this->localPassword = null;
        } else {
            // will be saved in
            $this->localPassword = $this->passwordEncryptor->encrypt($this->getDatabasePass());
            $this->pass = '';
        }

        if ($this->providerid !== null && $this->providerid->getId() === Provider::AA_ID && $this->savepassword === SAVE_PASSWORD_DATABASE && $this->useragents->count() === 0) {
            //            throw new \Exception("not shared AA account password should not be saved to database. We allow saving password to db only for sharing with bookers");
        }
    }

    /**
     * get account elite level.
     *
     * @return string
     */
    public function getEliteLevel()
    {
        return $this->getAccountPropertyByKind(PROPERTY_KIND_STATUS);
    }

    /**
     * get account number.
     *
     * @return string
     */
    public function getAccountNumber()
    {
        $number = $this->getAccountPropertyByKind(PROPERTY_KIND_NUMBER);

        return $number ?? $this->getLogin();
    }

    /**
     * @param int $kind
     * @return string|null
     */
    public function getAccountPropertyByKind($kind)
    {
        $properties = $this->getProperties();

        if (!$properties || !$properties->count()) {
            return null;
        }
        $filteredProperties = $properties->filter(function (Accountproperty $p) use ($kind) {
            if ($p->getSubaccountid()) {
                return false;
            }

            return $p->getProviderpropertyid() ? $p->getProviderpropertyid()->getKind() == $kind : false;
        });

        if ($filteredProperties->count() > 0) {
            $filteredProperties = $filteredProperties->first();

            /** @var Accountproperty $filteredProperties */
            return $filteredProperties->getVal();
        }

        return null;
    }

    public function getAccountPropertyByCode(string $code): ?string
    {
        $properties = $this->getProperties();

        if (!$properties || !$properties->count()) {
            return null;
        }

        $filteredProperties = $properties->filter(function (Accountproperty $p) use ($code) {
            if ($p->getSubaccountid()) {
                return false;
            }

            return $p->getProviderpropertyid() ? $p->getProviderpropertyid()->getCode() === $code : false;
        });

        if ($filteredProperties->count() > 0) {
            $filteredProperties = $filteredProperties->first();

            /** @var Accountproperty $filteredProperties */
            return $filteredProperties->getVal();
        }

        return null;
    }

    public function hasBalanceInTotalSumProperty(): bool
    {
        foreach ($this->getSubAccountsEntities() as $subAccount) {
            $val = $subAccount->getPropertyByCode('BalanceInTotalSum');

            if (
                !is_null($val)
                && (
                    (int) $val === 1 || $val === 'true'
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return AccountProperty[]|Collection
     */
    public function getProperties()
    {
        return $this->Properties;
    }

    /**
     * @param AccountProperty[]|Collection $properties
     */
    public function setProperties($properties): self
    {
        $this->Properties = $properties;

        return $this;
    }

    /**
     * @return bool
     */
    public function getBackgroundCheck()
    {
        return $this->BackgroundCheck;
    }

    /**
     * @param bool $BackgroundCheck
     */
    public function setBackgroundCheck($BackgroundCheck)
    {
        $this->BackgroundCheck = $BackgroundCheck;
    }

    /**
     * users with whom you shared this account.
     *
     * @return Useragent[]|ArrayCollection|Collection
     */
    public function getUseragents()
    {
        $ret = $this->useragents;

        if (is_array($ret)) {
            $ret = new ArrayCollection($ret);
        }

        return $ret;
    }

    public function getUseragentByUser(Usr $user)
    {
        $users = $this->getUseragents();

        return $users->filter(function (Useragent $ua) use ($user) {
            return $ua->getAgentid()->getUserid() == $user->getUserid() && $ua->getIsapproved();
        });
    }

    public function isSharedWith(Usr $user)
    {
        return (bool) count($this->getUseragentByUser($user));
    }

    public function setUseragents($useragents)
    {
        if (is_array($useragents)) {
            $useragents = new ArrayCollection($useragents);
        }
        $this->useragents = $useragents;

        return $this;
    }

    /**
     * @return Account
     */
    public function addUserAgent(Useragent $useragent)
    {
        if (!$this->useragents->contains($useragent)) {
            $this->useragents->add($useragent);
        }

        return $this;
    }

    public function getDisplayName()
    {
        return !is_null($this->getProviderid())
            ? $this->getProviderid()->getDisplayname()
            : $this->getProgramname();
    }

    public function isCustom()
    {
        return is_null($this->getProviderid());
    }

    /**
     * this account should be checked in browser extension.
     *
     * @return bool
     */
    public function isCheckInBrowser()
    {
        return !empty($this->getProviderid()) && in_array($this->getProviderid()->getCheckinbrowser(), [CHECK_IN_CLIENT, CHECK_IN_MIXED]);
    }

    /**
     * can we check this account ?
     *
     * @return bool
     */
    public function canCheck(?Usr $user = null)
    {
        return !empty($this->getProviderid()) && $this->getProviderid()->canCheck($user);
    }

    /**
     * ExpirationDate was gathered from provider site.
     *
     * @return bool
     */
    public function gotExpirationFromSite()
    {
        return in_array($this->expirationautoset, [EXPIRATION_AUTO, EXPIRATION_FROM_SUBACCOUNT]);
    }

    public function getAccountInfo()
    {
        /** @var \AwardWallet\MainBundle\Entity\Account $account */
        $account = $this;

        // Unfortunately it is not possible to get columns from doctrine cause AccountInfo should have keys like
        // 'AccountID', but doctrine $entityManager->getClassMetadata(...)->getFieldNames() returns accountid
        $keys = [
            'AccountID',
            'ProviderID',
            'UserID',
            'State',
            'ErrorCode',
            'ErrorMessage',
            'Balance',
            'Login',
            'Pass',
            'CreationDate',
            'UpdateDate',
            'Login2',
            'comment',
            'SavePassword',
            'ExpirationDate',
            'Kind',
            'ProgramName',
            'LoginURL',
            'ExpirationAutoSet',
            'UserAgentID',
            'Goal',
            'Region',
            'GoalAutoSet',
            'PassChangeDate',
            'ExpirationWarning',
            'LastChangeDate',
            'ChangeCount',
            'LastActivity',
            'SubAccounts',
            'LastBalance',
            'SuccessCheckDate',
            'Login3',
            'Question',
            'TotalBalance',
            'DontTrackExpiration',
            'NotRelated',
            'QueueDate',
            'NextCheckPriority',
            'IsActiveTab',
            'ModifyDate',
            'CheckedBy',
            'Itineraries',
            'LastDurationWithoutPlans',
            'LastDurationWithPlans',
            'ActivityScore',
            'LastCheckItDate',
            'LastCheckHistoryDate',
            'EmailParseDate',
            'ErrorCount',
            'TripsHash',
            'BackgroundCheck',
        ];
        $accountInfo = [];

        foreach ($keys as $k) {
            $methodName = 'get' . ucfirst(strtolower($k));

            if (is_object($obj = $account->$methodName())) {
                $class = get_class($obj);

                //				echo "Calling \$account->$methodName()->$methodName() where \$account->$methodName() is of class $class\n";
                if ($class == 'DateTime') {
                    $value = date('Y-m-d H:i:s', $obj->getTimestamp());
                } else {
                    $value = $obj->$methodName();
                }
                $accountInfo[$k] = $value;
            } else {
                //				echo "Calling \$account->$methodName()\n";
                $accountInfo[$k] = $account->$methodName();
            }
        }

        $keys = [
            // Key - the field name in Provider table
            // Value - label ot this field name in AccountInfo structure
            'Engine' => 'ProviderEngine',
            'Code' => 'ProviderCode',
            'State' => 'ProviderState',
            'CanCheck' => 'CanCheck',
            'Login2Caption' => 'Login2Caption',
            'AllowFloat' => 'AllowFloat',
            'Kind' => 'ProviderKind',
            'Questions' => 'ProviderQuestions',
            'DisplayName' => 'DisplayName',
            'Name' => 'Name',
            'BalanceFormat' => 'BalanceFormat',
            'ExpirationAlwaysKnown' => 'ExpirationAlwaysKnown',
            'CacheVersion' => 'ProviderCacheVersion',
            'ProviderGroup' => 'ProviderGroup',
        ];

        foreach ($keys as $key => $value) {
            $methodName = 'get' . ucfirst(strtolower($key));
            $accountInfo[$value] = $account->getProviderid()->$methodName();
        }

        return $accountInfo;
    }

    /**
     * account was created/modified, and should be checked.
     *
     * @return bool
     */
    public function isDirty()
    {
        return $this->getErrorcode() == ACCOUNT_UNCHECKED
        || $this->getUpdatedate() === null
        || ($this->getPasschangedate() !== null && $this->getPasschangedate()->getTimestamp() > $this->getUpdatedate()->getTimestamp());
    }

    /**
     * this is AA account, it was checked successfully, no need to ask password.
     *
     * @return bool
     */
    public function isAaPasswordValid()
    {
        return
            $this->providerid->getCode() == 'aa'
            && (
                $this->errorcode == ACCOUNT_CHECKED
                && !empty($this->successcheckdate)
                && !empty($this->passchangedate)
                && $this->successcheckdate->getTimestamp() > $this->passchangedate->getTimestamp()
            )
            && !empty($this->updatedate) && $this->updatedate->getTimestamp() >= strtotime('2014-04-16');
    }

    /**
     * this is OpenAuth accessed account, no need to ask password.
     *
     * @return bool
     */
    public function isOauthTokenValid()
    {
        return
            $this->providerid
            && $this->providerid->isOauthProvider()
            && $this->getAuthInfo();
    }

    public function getLastDuration(bool $checkItineraries): float
    {
        $provider = $this->getProviderid();

        if ($checkItineraries) {
            return (float) ($this->lastdurationwithplans ?: $provider->getAvgDuration($checkItineraries));
        } else {
            return (float) ($this->lastdurationwithoutplans ?: $provider->getAvgDuration($checkItineraries));
        }
    }

    /**
     * Get last check duration according to user experience.
     */
    public function getUXLastDuration(bool $checkItineraries): float
    {
        return in_array($this->checkedby, self::CHECKED_BY_USER, true) ?
            $this->getLastDuration($checkItineraries) :
            $this->providerid->getAvgDuration($checkItineraries);
    }

    /**
     * @return Accountbalance[]|ArrayCollection|Collection
     */
    public function getBalanceHistory()
    {
        return $this->balanceHistory;
    }

    /**
     * @return int
     */
    public function getHistoryVersion()
    {
        return $this->historyVersion;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     * @return Account
     */
    public function setDisabled($disabled)
    {
        if ($this->disabled && !$disabled) {
            // we want to update account after unchecking Disabled checkbox
            $this->disabledChanged = true;
        }
        $this->disabled = $disabled;
    }

    public function setDisableReason($reason)
    {
        $this->disableReason = $reason;

        return $this;
    }

    public function getDisableReason(): ?int
    {
        return $this->disableReason;
    }

    public function setDisableDate(?\DateTime $dateTime)
    {
        $this->disableDate = $dateTime;

        return $this;
    }

    public function getDisableDate(): ?\DateTime
    {
        return $this->disableDate;
    }

    public function wantAutoCheckItineraries()
    {
        return
            $this->user->getAutogatherplans()
            && !empty($this->providerid)
            && $this->providerid->getCancheckitinerary()
            && (empty($this->lastcheckitdate) || $this->lastcheckitdate->getTimestamp() < (time() - SECONDS_PER_DAY));
    }

    public function wantAutoCheckHistory()
    {
        return true === $this->getProviderid()->getCancheckhistory() && !empty($this->getLastcheckhistorydate());
    }

    public function setAuthInfo($authInfo)
    {
        if ($this->authInfo != $authInfo && $authInfo != '') {
            $this->resetCredentialsState();
        }
        $this->authInfo = $authInfo;

        return $this;
    }

    public function getAuthInfo()
    {
        return $this->authInfo;
    }

    /**
     * @return bool
     */
    public function isDisableExtension()
    {
        return $this->disableExtension;
    }

    /**
     * @param bool $option
     * @return Account
     */
    public function setDisableExtension($option)
    {
        $this->disableExtension = $option;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisableClientPasswordAccess()
    {
        return $this->disableClientPasswordAccess;
    }

    /**
     * @return Account
     */
    public function setDisableClientPasswordAccess($disable)
    {
        $this->disableClientPasswordAccess = $disable;

        return $this;
    }

    public function removeCardImage(CardImage $cardImage): self
    {
        $this->removeCardImageTrait($cardImage);
        $cardImage->setProviderId(null);

        return $this;
    }

    public function isChangesConfirmed(): bool
    {
        return $this->changesConfirmed;
    }

    public function setChangesConfirmed(bool $changesConfirmed): self
    {
        $this->changesConfirmed = $changesConfirmed;

        return $this;
    }

    public function setBalanceWatchStartDate(?\DateTime $dateTime): self
    {
        $this->balanceWatchStartDate = $dateTime;

        return $this;
    }

    public function getBalanceWatchStartDate(): ?\DateTime
    {
        return $this->balanceWatchStartDate;
    }

    public function isAllowBalanceWatch(): bool
    {
        if (null === $this->getProviderid()
            || !$this->getId()) {
            return false;
        }

        return true;
    }

    public function isBalanceWatchDisabled(): bool
    {
        if (!$this->getId()
            || in_array($this->getState(), [ACCOUNT_DISABLED, ACCOUNT_PENDING, ACCOUNT_IGNORED])
            || null !== $this->getBalanceWatchStartDate()) {
            return true;
        }

        return false;
    }

    public function sentToUpdate()
    {
        $this->sentToUpdateDate = new \DateTime();
    }

    public function setUpdateLimitDisabledUntil(?\DateTime $dateTime): self
    {
        $this->updateLimitDisabledUntil = $dateTime;

        return $this;
    }

    public function getUpdateLimitDisabledUntil(): ?\DateTime
    {
        return $this->updateLimitDisabledUntil;
    }

    public function getDailyUpdateLimit(?Usr $usrCall = null): ?int
    {
        if (($this->updateLimitDisabledUntil instanceof \DateTime && $this->updateLimitDisabledUntil->getTimestamp() > time())
            || (null !== $usrCall && $usrCall->isAwPlus())
            || (null === $usrCall && null !== $this->user && $this->user->isAwPlus())) {
            return null;
        }

        if ($this->disabled) {
            return null;
        }

        return 2;
    }

    public function setPwnedTimes(int $times): self
    {
        $this->pwnedTimes = $times;

        return $this;
    }

    public function getPwnedTimes(): ?int
    {
        return $this->pwnedTimes;
    }

    public function setHistoryVersion(int $historyVersion): self
    {
        $this->historyVersion = $historyVersion;

        return $this;
    }

    public function getCountry()
    {
        $provider = $this->getProviderid();

        if ($provider && in_array($provider->getLogin2caption(), ['Country', 'Region'])) {
            return $this->getLogin2();
        } else {
            return '';
        }
    }

    /**
     * @return iterable<Subaccount>
     */
    public function getSubAccountsEntities(): iterable
    {
        return $this->subAccountsEntities;
    }

    public function setSubAccountsEntities(iterable $subAccountsEntities): self
    {
        $this->subAccountsEntities = $subAccountsEntities;

        return $this;
    }

    public function getSourceEmail()
    {
        return $this->sourceEmail;
    }

    public function setSourceEmail(string $sourceEmail): self
    {
        $this->sourceEmail = $sourceEmail;

        return $this;
    }

    /**
     * @internal
     */
    public function setPasswordEncryptor(PasswordEncryptor $passwordEncryptor): self
    {
        $this->passwordEncryptor = $passwordEncryptor;

        return $this;
    }

    /**
     * @internal
     */
    public function setPasswordDecryptor(PasswordDecryptor $passwordDecryptor): self
    {
        $this->passwordDecryptor = $passwordDecryptor;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getPointValue(): ?float
    {
        return $this->pointValue;
    }

    public function setPointValue(?float $value): self
    {
        $this->pointValue = $value;

        return $this;
    }

    public function getCustomEliteLevel(): ?string
    {
        return $this->customEliteLevel;
    }

    public function setCustomEliteLevel(?string $eliteLevel): self
    {
        $this->customEliteLevel = $eliteLevel;

        return $this;
    }

    public function getLoginId(): ?string
    {
        return $this->loginId;
    }

    public function setLoginId(?string $loginId): void
    {
        $this->loginId = $loginId;
    }

    public function isDisableBackgroundUpdating(): bool
    {
        return $this->disableBackgroundUpdating;
    }

    public function setDisableBackgroundUpdating(bool $disableBackgroundUpdating): void
    {
        $this->disableBackgroundUpdating = $disableBackgroundUpdating;
    }

    private function decryptPass(?string $pass): ?string
    {
        return $this->passwordDecryptor->decrypt($pass ?? '');
    }

    private function resetCredentialsState($resetAnswers = true)
    {
        $this->credentialsChanged = true;

        if ($resetAnswers && !empty($this->answers)) {
            $this->answers->clear();
        }
        $this->passchangedate = new \DateTime();
        $this->errorcount = 0;
    }
}
