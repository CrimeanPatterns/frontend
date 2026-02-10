<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Provider.
 *
 * @ORM\Table(name="Provider")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\ProviderRepository")
 */
class Provider implements TranslationContainerInterface
{
    public const AA_ID = 1;
    public const DELTA_ID = 7;
    public const ALASKA_ID = 18;
    public const HAWAIIAN_ID = 20;
    public const JETBLUE_ID = 13;
    public const WYNDHAM_ID = 15;
    public const SOUTHWEST_ID = 16;
    public const UNITED_ID = 26;
    public const AIRCANADA_ID = 2;
    public const AIRFRANCE_ID = 44;
    public const KLM_ID = 37;
    public const LUFTHANSA_ID = 39;
    public const VIRGIN_ATLANTIC_ID = 40;
    public const AVIANCA_ID = 416;

    public const CHASE_ID = 87;
    public const AMEX_ID = 84;
    public const CAPITAL_ONE_ID = 104;
    public const CITI_ID = 364;
    public const BANKOFAMERICA_ID = 75;
    public const DISCOVER_ID = 98;
    public const USBANK_ID = 103;
    public const WELLSFARGO_ID = 106;
    public const RBCBANK_ID = 112;
    public const BARCLAYCARD_ID = 123;
    public const DELTA_SKYBONUS_ID = 145;
    public const UNITED_PERKSPLUS_ID = 288;
    public const NAVY_ID = 333;
    public const BREX_ID = 4974;
    public const BILT_REWARDS_ID = 5022;

    public const MARRIOTT_ID = 17;
    public const HILTON_ID = 22;
    public const HYATT_ID = 10;
    public const IHG_REWARDS_ID = 12;
    public const AEGEAN_ID = 186;
    public const SAFEWAY_ID = 369;
    public const SINGAPOREAIR_ID = 71;

    public const TEST_PROVIDER_ID = 636;

    public const EARNING_POTENTIAL_LIST = [
        self::CHASE_ID,
        self::AMEX_ID,
        self::CITI_ID,
        self::BANKOFAMERICA_ID,
        self::CAPITAL_ONE_ID,
        self::BREX_ID,
    ];

    public const OAUTH_PROVIDERS = [self::BANKOFAMERICA_ID, self::CAPITAL_ONE_ID];

    public const BIG3_PROVIDERS = [
        self::DELTA_ID,
        self::DELTA_SKYBONUS_ID,

        self::UNITED_ID,
        self::UNITED_PERKSPLUS_ID,

        self::SOUTHWEST_ID,
    ];

    public const BUSINESS_PROVIDER_ID = [84, 503, 75, 364, 123, 104, 87, 49, 103];

    public const AA_CODE = 'aa';

    /**
     * @var int
     * @ORM\Column(name="ProviderID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=200, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var int
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     */
    protected $kind = 0;

    /**
     * @var int
     * @ORM\Column(name="EmailFormatKind", type="integer", nullable=false)
     */
    protected $emailFormatKind = 0;

    /**
     * @var int
     * @ORM\Column(name="Engine", type="integer", nullable=false)
     */
    protected $engine = 0;

    /**
     * @var string
     * @ORM\Column(name="LoginCaption", type="string", length=255, nullable=false)
     */
    protected $logincaption;

    /**
     * @var bool
     * @ORM\Column(name="LoginRequired", type="boolean", nullable=false)
     */
    protected $loginRequired = true;

    /**
     * @var string
     * @ORM\Column(name="DisplayName", type="string", length=100, nullable=true)
     */
    protected $displayname;

    /**
     * @var string
     * @ORM\Column(name="ProgramName", type="string", length=80, nullable=false)
     */
    protected $programname;

    /**
     * @var int
     * @ORM\Column(name="LoginMinSize", type="integer", nullable=false)
     */
    protected $loginminsize = 3;

    /**
     * @var int
     * @ORM\Column(name="LoginMaxSize", type="integer", nullable=false)
     */
    protected $loginmaxsize = 255;

    /**
     * @var string
     * @ORM\Column(name="PasswordCaption", type="string", length=80, nullable=true)
     */
    protected $passwordcaption;

    /**
     * @var int
     * @ORM\Column(name="PasswordMinSize", type="integer", nullable=false)
     */
    protected $passwordminsize = 1;

    /**
     * @var int
     * @ORM\Column(name="PasswordMaxSize", type="integer", nullable=false)
     */
    protected $passwordmaxsize = 80;

    /**
     * @var bool
     * @ORM\Column(name="CanRetrievePassword", type="boolean", nullable=true)
     */
    protected $canretrievepassword = false;

    /**
     * @var string
     * @ORM\Column(name="Site", type="string", length=80, nullable=false)
     */
    protected $site;

    /**
     * @var string
     * @ORM\Column(name="LoginURL", type="string", length=512, nullable=false)
     */
    protected $loginurl;

    /**
     * @var Providercountry[]|Collection
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\Providercountry", mappedBy="providerId", cascade={"persist", "remove"})
     */
    protected $countries;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EnableDate", type="datetime", nullable=false)
     */
    protected $enabledate;

    /**
     * @var string
     * @ORM\Column(name="Login2Caption", type="string", length=80, nullable=true)
     */
    protected $login2caption;

    /**
     * @var bool
     * @ORM\Column(name="Login2Required", type="boolean", nullable=false)
     */
    protected $login2Required = true;

    /**
     * @var bool
     * @ORM\Column(name="Login2AsCountry", type="boolean", nullable=false)
     */
    protected $login2AsCountry = false;

    /**
     * @var int
     * @ORM\Column(name="Login2MinSize", type="integer", nullable=false)
     */
    protected $login2minsize = 1;

    /**
     * @var int
     * @ORM\Column(name="Login2MaxSize", type="integer", nullable=false)
     */
    protected $login2maxsize = 80;

    /**
     * Max size for input.
     *
     * @var int
     */
    protected $login3MaxSize = 40;

    /**
     * @var int
     * @ORM\Column(name="AutoLogin", type="integer", nullable=false)
     */
    protected $autologin = AUTOLOGIN_DISABLED;

    /**
     * @var string
     * @ORM\Column(name="OneTravelCode", type="string", length=2, nullable=true)
     */
    protected $onetravelcode = '0';

    /**
     * @var string
     * @ORM\Column(name="OneTravelName", type="string", length=80, nullable=true)
     */
    protected $onetravelname;

    /**
     * @var int
     * @ORM\Column(name="OneTravelID", type="integer", nullable=true)
     */
    protected $onetravelid;

    /**
     * @var bool
     * @ORM\Column(name="CanCheck", type="boolean", nullable=false)
     */
    protected $cancheck = true;

    /**
     * @var bool
     * @ORM\Column(name="CanCheckBalance", type="boolean", nullable=false)
     */
    protected $cancheckbalance = true;

    /**
     * @var int
     * @ORM\Column(name="CanCheckConfirmation", type="integer", nullable=false)
     */
    protected $cancheckconfirmation = 0;

    /**
     * @var bool
     * @ORM\Column(name="CanCheckItinerary", type="boolean", nullable=false)
     */
    protected $cancheckitinerary = false;

    /**
     * @var string
     * @ORM\Column(name="ExpirationDateNote", type="text", nullable=true)
     */
    protected $expirationdatenote;

    /**
     * @var string
     * @ORM\Column(name="TradeText", type="text", nullable=true)
     */
    protected $tradetext;

    /**
     * @var int
     * @ORM\Column(name="TradeMin", type="integer", nullable=false)
     */
    protected $trademin = 0;

    /**
     * @var bool
     * @ORM\Column(name="RedirectByHTTPS", type="boolean", nullable=false)
     */
    protected $redirectbyhttps = true;

    /**
     * @var string
     * @ORM\Column(name="DefaultRegion", type="string", length=80, nullable=true)
     */
    protected $defaultregion;

    /**
     * @var string
     * @ORM\Column(name="BalanceFormat", type="string", length=60, nullable=true)
     */
    protected $balanceformat;

    /**
     * @var bool
     * @ORM\Column(name="AllowFloat", type="boolean", nullable=false)
     */
    protected $allowfloat = false;

    /**
     * @var string
     * @ORM\Column(name="ShortName", type="string", length=255, nullable=false)
     */
    protected $shortname;

    /**
     * @var int
     * @ORM\Column(name="Difficulty", type="integer", nullable=false)
     */
    protected $difficulty = 1;

    /**
     * @var string
     * @ORM\Column(name="ImageURL", type="string", length=512, nullable=true)
     */
    protected $imageurl;

    /**
     * @var string
     * @ORM\Column(name="ClickURL", type="string", length=512, nullable=true)
     */
    protected $clickurl;

    /**
     * @var bool
     * @ORM\Column(name="WSDL", type="boolean", nullable=false)
     */
    protected $wsdl = false;

    /**
     * @var int
     * @ORM\Column(name="CanCheckExpiration", type="integer", nullable=false)
     */
    protected $cancheckexpiration = false;

    /**
     * @var bool
     * @ORM\Column(name="DontSendEmailsSubaccExpDate", type="integer", nullable=false)
     */
    protected $dontSendEmailsSubaccExpDate = false;

    /**
     * @var int
     * @ORM\Column(name="FAQ", type="integer", nullable=true)
     */
    protected $faq;

    /**
     * @var string
     * @ORM\Column(name="ProviderGroup", type="string", length=20, nullable=true)
     */
    protected $providergroup;

    /**
     * @var string
     * @ORM\Column(name="ExpirationUnknownNote", type="string", length=2000, nullable=true)
     */
    protected $expirationunknownnote;

    /**
     * @var bool
     * @ORM\Column(name="CustomDisplayName", type="boolean", nullable=false)
     */
    protected $customdisplayname = false;

    /**
     * @var string
     * @ORM\Column(name="BarCode", type="string", length=20, nullable=true)
     */
    protected $barcode;

    /**
     * @var bool
     * @ORM\Column(name="MobileAutoLogin", type="boolean", nullable=false)
     */
    protected $mobileautologin = true;

    /**
     * @var bool
     * @ORM\Column(name="Corporate", type="boolean", nullable=false)
     */
    protected $corporate = false;

    /**
     * @var Currency
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Currency", referencedColumnName="CurrencyID")
     * })
     */
    protected $currency;

    /**
     * @var Popularity
     * @ORM\OneToMany(targetEntity="Popularity", mappedBy="provider", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $popularity;

    /**
     * @var int
     * @ORM\Column(name="DeepLinking", type="integer", nullable=false)
     */
    protected $deeplinking = 2;

    /**
     * @var bool
     * @ORM\Column(name="Questions", type="boolean", nullable=false)
     */
    protected $questions = false;

    /**
     * @var string
     * @ORM\Column(name="Note", type="text", nullable=true)
     */
    protected $note;

    /**
     * @var int
     * @ORM\Column(name="TimesRequested", type="integer", nullable=true)
     */
    protected $timesrequested;

    /**
     * @var bool
     * @ORM\Column(name="PasswordRequired", type="boolean", nullable=false)
     */
    protected $passwordrequired = true;

    /**
     * @var int
     * @ORM\Column(name="Tier", type="integer", nullable=true)
     */
    protected $tier;

    /**
     * @var int
     * @ORM\Column(name="Severity", type="integer", nullable=true)
     */
    protected $severity;

    /**
     * @var int
     * @ORM\Column(name="ResponseTime", type="integer", nullable=true)
     */
    protected $responsetime;

    /**
     * @var int
     * @ORM\Column(name="EliteLevelsCount", type="integer", nullable=true)
     */
    protected $elitelevelscount;

    /**
     * @var int
     * @ORM\Column(name="RSlaEventID", type="integer", nullable=true)
     */
    protected $rslaeventid;

    /**
     * @var bool
     * @ORM\Column(name="CanCheckCancelled", type="boolean", nullable=true)
     */
    protected $cancheckcancelled = false;

    /**
     * @var float
     * @ORM\Column(name="AAADiscount", type="float", nullable=true)
     */
    protected $aaadiscount;

    /**
     * @var string
     * @ORM\Column(name="Login3Caption", type="string", length=80, nullable=true)
     */
    protected $login3caption;

    /**
     * @var bool
     * @ORM\Column(name="Login3Required", type="boolean", nullable=false)
     */
    protected $login3Required = true;

    /**
     * @var int
     * @ORM\Column(name="CheckInBrowser", type="integer", nullable=false)
     */
    protected $checkinbrowser = 0;

    /**
     * @var bool
     * @ORM\Column(name="CheckInMobileBrowser", type="boolean", nullable=false)
     */
    protected $checkinmobilebrowser = 0;

    /**
     * @var int
     * @ORM\Column(name="Accounts", type="integer", nullable=false)
     */
    protected $accounts = 0;

    /**
     * @var int
     * @ORM\Column(name="AbAccounts", type="integer", nullable=false)
     */
    protected $abaccounts = 0;

    /**
     * @var string
     * @ORM\Column(name="KeyWords", type="string", length=2000, nullable=true)
     */
    protected $keywords;

    /**
     * @var string
     * @ORM\Column(name="StopKeyWords", type="string", length=2000, nullable=true)
     */
    protected $stopKeywords;

    /**
     * @var float
     * @ORM\Column(name="AvgDurationWithoutPlans", type="float", nullable=true)
     */
    protected $avgdurationwithoutplans;

    /**
     * @var float
     * @ORM\Column(name="AvgDurationWithPlans", type="float", nullable=true)
     */
    protected $avgdurationwithplans;

    /**
     * @var bool
     * @ORM\Column(name="CanMarkCoupons", type="boolean", nullable=false)
     */
    protected $canmarkcoupons = false;

    /**
     * @var bool
     * @ORM\Column(name="CanParseCardImages", type="boolean", nullable=false)
     */
    protected $canParseCardImages = false;

    /**
     * @var bool
     * @ORM\Column(name="CanDetectCreditCards", type="boolean", nullable=false)
     */
    protected $canDetectCreditCards = false;

    /**
     * @var string
     * @ORM\Column(name="Warning", type="string", length=250, nullable=true)
     */
    protected $warning;

    /**
     * @var bool
     * @ORM\Column(name="CanCheckHistory", type="boolean", nullable=false)
     */
    protected $cancheckhistory = false;

    /**
     * @var bool
     * @ORM\Column(name="ExpirationAlwaysKnown", type="boolean", nullable=false)
     */
    protected $expirationalwaysknown = true;

    /**
     * @var int
     * @ORM\Column(name="RequestsPerMinute", type="integer", nullable=true)
     */
    protected $requestsperminute;

    /**
     * @var int
     * @ORM\Column(name="CacheVersion", type="integer", nullable=false)
     */
    protected $cacheversion = 1;

    /**
     * @var bool
     * @ORM\Column(name="CanCheckNoItineraries", type="boolean", nullable=false)
     */
    protected $canchecknoitineraries = false;

    /**
     * @var string
     * @ORM\Column(name="PlanEmail", type="string", length=120, nullable=true)
     */
    protected $planemail;

    /**
     * @var string
     * @ORM\Column(name="InternalNote", type="text", nullable=true)
     */
    protected $internalnote;

    /**
     * @var bool
     * @ORM\Column(name="CalcEliteLevelExpDate", type="boolean", nullable=false)
     */
    protected $calcelitelevelexpdate = false;

    /**
     * @var int
     * @ORM\Column(name="ItineraryAutologin", type="integer", nullable=true)
     */
    protected $itineraryautologin = ITINERARY_AUTOLOGIN_DISABLED;

    /**
     * @var string
     * @ORM\Column(name="EliteProgramComment", type="string", length=2000, nullable=true)
     */
    protected $eliteprogramcomment;

    /**
     * @var bool
     * @ORM\Column(name="CanScanEmail", type="boolean", nullable=false)
     */
    protected $canscanemail = false;

    /**
     * @var int
     * @ORM\Column(name="Category", type="integer", nullable=true)
     */
    protected $category = 3;

    /**
     * @var Alliance
     * @ORM\ManyToOne(targetEntity="Alliance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AllianceID", referencedColumnName="AllianceID")
     * })
     */
    protected $allianceid;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Assignee", referencedColumnName="UserID")
     * })
     */
    protected $assignee;

    /**
     * @var ProviderProperty[]|Collection
     * @ORM\OneToMany(targetEntity="Providerproperty", mappedBy="providerid", cascade={"persist", "remove"})
     */
    protected $properties;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginIE = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginSafari = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginChrome = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginFirefox = false;

    /**
     * @var int
     * @ORM\Column(name="Goal", type="integer", nullable=true)
     */
    protected $Goal;

    /**
     * @var int
     * @ORM\Column(name="CanSavePassword", type="boolean", nullable=true)
     */
    protected $CanSavePassword;

    /**
     * @var int
     * @ORM\Column(name="CanReceiveEmail", type="boolean", nullable=false)
     */
    protected $CanReceiveEmail;

    /**
     * @var bool
     * @ORM\Column(name="CanCheckFiles", type="boolean", nullable=false)
     */
    protected $CanCheckFiles = false;

    /**
     * @var string
     * @ORM\Column(name="IATACode", type="string", nullable=true)
     */
    protected $IATACode;

    /**
     * @var bool
     * @ORM\Column(name="CanTransferRewards", type="boolean", nullable=false)
     */
    protected $canTransferRewards;

    /**
     * @var int
     * @ORM\Column(name="CanRegisterAccount", type="integer", nullable=true)
     */
    protected $canRegisterAccount;

    /**
     * @var int
     * @ORM\Column(name="CanBuyMiles", type="integer", nullable=true)
     */
    protected $canBuyMiles;

    /**
     * @var string
     * @ORM\Column(name="CheckInReminderOffsets", type="string", length=200, nullable=false)
     */
    protected $checkInReminderOffsets = '{"mail":[24],"push":[1,4,24]}';

    /**
     * @var bool
     * @ORM\Column(name="isRetail", type="boolean", nullable=false)
     */
    protected $isRetail;

    /**
     * @var string
     * @ORM\Column(name="additionalInfo", type="string", nullable=true)
     */
    protected $additionalInfo;

    /**
     * @var string
     * @ORM\Column(name="Description", type="string", length=2000, nullable=true)
     */
    protected $description;

    /**
     * @var Providerphone[]|Collection
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\Providerphone", mappedBy="providerid")
     */
    protected $phones;

    /**
     * @var string
     * @ORM\Column(name="BlogTagsID", type="string", nullable=true, length=255)
     */
    protected $blogTagsId;

    /**
     * @var string
     * @ORM\Column(name="BlogPostID", type="string", nullable=true, length=255)
     */
    protected $blogPostId;

    /**
     * @var string
     * @ORM\Column(name="BlogIdsMilesPurchase", type="string", nullable=true, length=250)
     */
    protected $blogIdsMilesPurchase;

    /**
     * @var string
     * @ORM\Column(name="BlogIdsMilesTransfers", type="string", nullable=true, length=250)
     */
    protected $blogIdsMilesTransfers;

    /**
     * @var string
     * @ORM\Column(name="BlogIdsPromos", type="string", nullable=true, length=250)
     */
    protected $blogIdsPromos;

    /**
     * @var string
     * @ORM\Column(name="BlogIdsMileExpiration", type="string", nullable=true, length=250)
     */
    protected $blogIdsMileExpiration;

    /**
     * @var bool
     * @ORM\Column(name="IsEarningPotential", type="boolean", nullable=false)
     */
    protected $isEarningPotential = false;

    /**
     * @var bool
     * @ORM\Column(name="IsExtensionV3ParserEnabled", type="boolean", nullable=false)
     */
    protected $isExtensionV3ParserEnabled = false;

    /**
     * @var bool
     * @ORM\Column(name="ExtensionV3ParserReady", type="boolean", nullable=false)
     */
    protected $extensionV3ParserReady = false;

    /**
     * @var bool
     * @ORM\Column(name="AutologinV3", type="boolean", nullable=false)
     */
    protected $autologinV3 = false;

    /**
     * @var bool
     * @ORM\Column(name="ConfNoV3", type="boolean", nullable=false)
     */
    protected $confNoV3 = false;

    /**
     * @var string|null
     * @ORM\Column(name="AwardChangePolicy", type="string", nullable=true)
     */
    protected $awardChangePolicy;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="ClientSideLastFixDate", type="datetime", nullable=true)
     */
    private $clientSideLastFixDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="ServerSideLastFixDate", type="datetime", nullable=true)
     */
    private $serverSideLastFixDate;

    /**
     * @var bool
     * @ORM\Column(name="ManualUpdate", type="boolean", nullable=false)
     */
    private $manualUpdate = false;

    public function __construct(?int $id = null)
    {
        $this->creationdate = new \DateTime();
        $this->properties = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->providerid = $id;
    }

    public function __toString()
    {
        return $this->getProviderid() . '-' . $this->getCode();
    }

    public function isExtensionV3ParserEnabled(): bool
    {
        return $this->isExtensionV3ParserEnabled;
    }

    public function setIsExtensionV3ParserEnabled(bool $isExtensionV3ParserEnabled): Provider
    {
        $this->isExtensionV3ParserEnabled = $isExtensionV3ParserEnabled;

        return $this;
    }

    public function isAutologinV3(): bool
    {
        return $this->autologinV3;
    }

    public function setAutologinV3(bool $autologinV3): Provider
    {
        $this->autologinV3 = $autologinV3;

        return $this;
    }

    public function getAwardChangePolicy(): ?string
    {
        return $this->awardChangePolicy;
    }

    public function setAwardChangePolicy(?string $awardChangePolicy): self
    {
        $this->awardChangePolicy = $awardChangePolicy;

        return $this;
    }

    /**
     * you can check account of this provider only one time, when adding new account. for southwest.
     *
     * @return bool
     */
    public function getCanCheckFiles()
    {
        return $this->CanCheckFiles;
    }

    /**
     * @param bool $CanCheckFiles
     */
    public function setCanCheckFiles($CanCheckFiles)
    {
        $this->CanCheckFiles = $CanCheckFiles;
    }

    public function getId(): ?int
    {
        return $this->providerid;
    }

    /**
     * Get providerid.
     *
     * @deprecated use getId
     * @return int
     */
    public function getProviderid()
    {
        return $this->providerid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Provider
     */
    public function setName($name)
    {
        $this->name = null === $name ? null : htmlspecialchars($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return null === $this->name ? null : htmlspecialchars_decode($this->name);
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Provider
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Provider
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Set EmailFormatKind.
     *
     * @return Provider
     */
    public function setEmailFormatKind(int $emailFormatKind)
    {
        $this->emailFormatKind = $emailFormatKind;

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
     * Get EmailFOrmatKind.
     *
     * @return int
     */
    public function getEmailFormatKind()
    {
        return $this->emailFormatKind;
    }

    /**
     * Set engine.
     *
     * @param int $engine
     * @return Provider
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * Get engine.
     *
     * @return int
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Set logincaption.
     *
     * @param string $logincaption
     * @return Provider
     */
    public function setLogincaption($logincaption)
    {
        $this->logincaption = $logincaption;

        return $this;
    }

    /**
     * Get logincaption.
     *
     * @return string
     */
    public function getLogincaption()
    {
        return $this->logincaption;
    }

    public function isLoginRequired(): bool
    {
        return $this->loginRequired;
    }

    public function setLoginRequired(bool $loginRequired): Provider
    {
        $this->loginRequired = $loginRequired;

        return $this;
    }

    /**
     * Set displayname.
     *
     * @param string $displayname
     * @return Provider
     */
    public function setDisplayname($displayname)
    {
        $this->displayname = null === $displayname ? null : htmlspecialchars($displayname);

        return $this;
    }

    /**
     * Get displayname.
     *
     * @return string
     */
    public function getDisplayname()
    {
        return null === $this->displayname ? null : htmlspecialchars_decode($this->displayname);
    }

    /**
     * Set programname.
     *
     * @param string $programname
     * @return Provider
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
     * Set loginminsize.
     *
     * @param int $loginminsize
     * @return Provider
     */
    public function setLoginminsize($loginminsize)
    {
        $this->loginminsize = $loginminsize;

        return $this;
    }

    /**
     * Get loginminsize.
     *
     * @return int
     */
    public function getLoginminsize()
    {
        return $this->loginminsize;
    }

    /**
     * Set loginmaxsize.
     *
     * @param int $loginmaxsize
     * @return Provider
     */
    public function setLoginmaxsize($loginmaxsize)
    {
        $this->loginmaxsize = $loginmaxsize;

        return $this;
    }

    /**
     * Get loginmaxsize.
     *
     * @return int
     */
    public function getLoginmaxsize()
    {
        return $this->loginmaxsize;
    }

    /**
     * Set passwordcaption.
     *
     * @param string $passwordcaption
     * @return Provider
     */
    public function setPasswordcaption($passwordcaption)
    {
        $this->passwordcaption = $passwordcaption;

        return $this;
    }

    /**
     * Get passwordcaption.
     *
     * @return string
     */
    public function getPasswordcaption()
    {
        return $this->passwordcaption;
    }

    /**
     * Set passwordminsize.
     *
     * @param int $passwordminsize
     * @return Provider
     */
    public function setPasswordminsize($passwordminsize)
    {
        $this->passwordminsize = $passwordminsize;

        return $this;
    }

    /**
     * Get passwordminsize.
     *
     * @return int
     */
    public function getPasswordminsize()
    {
        return $this->passwordminsize;
    }

    /**
     * Set passwordmaxsize.
     *
     * @param int $passwordmaxsize
     * @return Provider
     */
    public function setPasswordmaxsize($passwordmaxsize)
    {
        $this->passwordmaxsize = $passwordmaxsize;

        return $this;
    }

    /**
     * Get passwordmaxsize.
     *
     * @return int
     */
    public function getPasswordmaxsize()
    {
        return $this->passwordmaxsize;
    }

    /**
     * @return bool
     */
    public function isCanretrievepassword()
    {
        return $this->canretrievepassword;
    }

    /**
     * @param bool $canretrievepassword
     * @return Provider
     */
    public function setCanretrievepassword($canretrievepassword)
    {
        $this->canretrievepassword = $canretrievepassword;

        return $this;
    }

    /**
     * Set site.
     *
     * @param string $site
     * @return Provider
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Get site.
     *
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set loginurl.
     *
     * @param string $loginurl
     * @return Provider
     */
    public function setLoginurl($loginurl)
    {
        $this->loginurl = $loginurl;

        return $this;
    }

    /**
     * Get loginurl.
     *
     * @return string
     */
    public function getLoginurl()
    {
        return $this->loginurl;
    }

    /**
     * @return Providercountry[]|Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param Providercountry[]|Collection $countries
     * @return Provider
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * Set state.
     *
     * @param int $state
     * @return Provider
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
     * Set creationdate.
     *
     * @param \DateTime $creationdate
     * @return Provider
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

    public function isLogin2Regions(): bool
    {
        if ($this->login2Required
            // && !$this->login2AsCountry
            && (false !== strpos($this->login2caption, 'Country') || false !== strpos($this->login2caption, 'Region'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set login2caption.
     *
     * @param string $login2caption
     * @return Provider
     */
    public function setLogin2caption($login2caption)
    {
        $this->login2caption = $login2caption;

        return $this;
    }

    /**
     * Get login2caption.
     *
     * @return string
     */
    public function getLogin2caption()
    {
        return $this->login2caption;
    }

    public function isLogin2Required(): bool
    {
        return $this->login2Required;
    }

    public function setLogin2Required(bool $login2Required): Provider
    {
        $this->login2Required = $login2Required;

        return $this;
    }

    public function isLogin2AsCountry(): bool
    {
        return $this->login2AsCountry;
    }

    public function setLogin2AsCountry(bool $login2AsCountry): Provider
    {
        $this->login2AsCountry = $login2AsCountry;

        return $this;
    }

    /**
     * Set login2minsize.
     *
     * @param int $login2minsize
     * @return Provider
     */
    public function setLogin2minsize($login2minsize)
    {
        $this->login2minsize = $login2minsize;

        return $this;
    }

    /**
     * Get login2minsize.
     *
     * @return int
     */
    public function getLogin2minsize()
    {
        return $this->login2minsize;
    }

    /**
     * Set login2maxsize.
     *
     * @param int $login2maxsize
     * @return Provider
     */
    public function setLogin2maxsize($login2maxsize)
    {
        $this->login2maxsize = $login2maxsize;

        return $this;
    }

    /**
     * Get login2maxsize.
     *
     * @return int
     */
    public function getLogin2maxsize()
    {
        return $this->login2maxsize;
    }

    public function getLogin3Maxsize()
    {
        return $this->login3MaxSize;
    }

    /**
     * Set autologin.
     *
     * @param int $autologin
     * @return Provider
     */
    public function setAutologin($autologin)
    {
        $this->autologin = $autologin;

        return $this;
    }

    /**
     * Get autologin.
     *
     * @return int
     */
    public function getAutologin()
    {
        return $this->autologin;
    }

    /**
     * Set onetravelcode.
     *
     * @param string $onetravelcode
     * @return Provider
     */
    public function setOnetravelcode($onetravelcode)
    {
        $this->onetravelcode = $onetravelcode;

        return $this;
    }

    /**
     * Get onetravelcode.
     *
     * @return string
     */
    public function getOnetravelcode()
    {
        return $this->onetravelcode;
    }

    /**
     * Set onetravelname.
     *
     * @param string $onetravelname
     * @return Provider
     */
    public function setOnetravelname($onetravelname)
    {
        $this->onetravelname = $onetravelname;

        return $this;
    }

    /**
     * Get onetravelname.
     *
     * @return string
     */
    public function getOnetravelname()
    {
        return $this->onetravelname;
    }

    /**
     * Set onetravelid.
     *
     * @param int $onetravelid
     * @return Provider
     */
    public function setOnetravelid($onetravelid)
    {
        $this->onetravelid = $onetravelid;

        return $this;
    }

    /**
     * Get onetravelid.
     *
     * @return int
     */
    public function getOnetravelid()
    {
        return $this->onetravelid;
    }

    /**
     * Set cancheck.
     *
     * @param bool $cancheck
     * @return Provider
     */
    public function setCancheck($cancheck)
    {
        $this->cancheck = $cancheck;

        return $this;
    }

    /**
     * Get cancheck.
     *
     * @return bool
     */
    public function getCancheck()
    {
        return $this->cancheck;
    }

    /**
     * Set cancheckbalance.
     *
     * @param bool $cancheckbalance
     * @return Provider
     */
    public function setCancheckbalance($cancheckbalance)
    {
        $this->cancheckbalance = $cancheckbalance;

        return $this;
    }

    /**
     * Get cancheckbalance.
     *
     * @return bool
     */
    public function getCancheckbalance()
    {
        return $this->cancheckbalance;
    }

    /**
     * Set cancheckconfirmation.
     *
     * @param int $cancheckconfirmation
     * @return Provider
     */
    public function setCancheckconfirmation($cancheckconfirmation)
    {
        $this->cancheckconfirmation = $cancheckconfirmation;

        return $this;
    }

    /**
     * Get cancheckconfirmation.
     *
     * @return int
     */
    public function getCancheckconfirmation()
    {
        return $this->cancheckconfirmation;
    }

    /**
     * Set cancheckitinerary.
     *
     * @param bool $cancheckitinerary
     * @return Provider
     */
    public function setCancheckitinerary($cancheckitinerary)
    {
        $this->cancheckitinerary = $cancheckitinerary;

        return $this;
    }

    /**
     * Get cancheckitinerary.
     *
     * @return bool
     */
    public function getCancheckitinerary()
    {
        return $this->cancheckitinerary;
    }

    /**
     * Set expirationdatenote.
     *
     * @param string $expirationdatenote
     * @return Provider
     */
    public function setExpirationdatenote($expirationdatenote)
    {
        $this->expirationdatenote = $expirationdatenote;

        return $this;
    }

    /**
     * Get expirationdatenote.
     *
     * @return string
     */
    public function getExpirationdatenote()
    {
        return $this->expirationdatenote;
    }

    /**
     * Set tradetext.
     *
     * @param string $tradetext
     * @return Provider
     */
    public function setTradetext($tradetext)
    {
        $this->tradetext = $tradetext;

        return $this;
    }

    /**
     * Get tradetext.
     *
     * @return string
     */
    public function getTradetext()
    {
        return $this->tradetext;
    }

    /**
     * Set trademin.
     *
     * @param int $trademin
     * @return Provider
     */
    public function setTrademin($trademin)
    {
        $this->trademin = $trademin;

        return $this;
    }

    /**
     * Get trademin.
     *
     * @return int
     */
    public function getTrademin()
    {
        return $this->trademin;
    }

    /**
     * Set redirectbyhttps.
     *
     * @param bool $redirectbyhttps
     * @return Provider
     */
    public function setRedirectbyhttps($redirectbyhttps)
    {
        $this->redirectbyhttps = $redirectbyhttps;

        return $this;
    }

    /**
     * Get redirectbyhttps.
     *
     * @return bool
     */
    public function getRedirectbyhttps()
    {
        return $this->redirectbyhttps;
    }

    /**
     * Set defaultregion.
     *
     * @param string $defaultregion
     * @return Provider
     */
    public function setDefaultregion($defaultregion)
    {
        $this->defaultregion = $defaultregion;

        return $this;
    }

    /**
     * Get defaultregion.
     *
     * @return string
     */
    public function getDefaultregion()
    {
        return $this->defaultregion;
    }

    /**
     * Set balanceformat.
     *
     * @param string $balanceformat
     * @return Provider
     */
    public function setBalanceformat($balanceformat)
    {
        $this->balanceformat = $balanceformat;

        return $this;
    }

    /**
     * Get balanceformat.
     *
     * @return string
     */
    public function getBalanceformat()
    {
        return $this->balanceformat;
    }

    /**
     * Set allowfloat.
     *
     * @param bool $allowfloat
     * @return Provider
     */
    public function setAllowfloat($allowfloat)
    {
        $this->allowfloat = $allowfloat;

        return $this;
    }

    /**
     * Get allowfloat.
     *
     * @return bool
     */
    public function getAllowfloat()
    {
        return $this->allowfloat;
    }

    /**
     * Set shortname.
     *
     * @param string $shortname
     * @return Provider
     */
    public function setShortname($shortname)
    {
        $this->shortname = null === $shortname ? null : htmlspecialchars($shortname);

        return $this;
    }

    /**
     * Get shortname.
     *
     * @return string
     */
    public function getShortname()
    {
        return null === $this->shortname ? null : htmlspecialchars_decode($this->shortname);
    }

    /**
     * Set difficulty.
     *
     * @param int $difficulty
     * @return Provider
     */
    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * Get difficulty.
     *
     * @return int
     */
    public function getDifficulty()
    {
        return $this->difficulty;
    }

    /**
     * Set imageurl.
     *
     * @param string $imageurl
     * @return Provider
     */
    public function setImageurl($imageurl)
    {
        $this->imageurl = $imageurl;

        return $this;
    }

    /**
     * Get imageurl.
     *
     * @return string
     */
    public function getImageurl()
    {
        return $this->imageurl;
    }

    /**
     * Set clickurl.
     *
     * @param string $clickurl
     * @return Provider
     */
    public function setClickurl($clickurl)
    {
        $this->clickurl = $clickurl;

        return $this;
    }

    /**
     * Get clickurl.
     *
     * @return string
     */
    public function getClickurl()
    {
        return $this->clickurl;
    }

    /**
     * Set wsdl.
     *
     * @param bool $wsdl
     * @return Provider
     */
    public function setWsdl($wsdl)
    {
        $this->wsdl = $wsdl;

        return $this;
    }

    /**
     * Get wsdl.
     *
     * @return bool
     */
    public function getWsdl()
    {
        return $this->wsdl;
    }

    /**
     * Set cancheckexpiration.
     *
     * @param int $cancheckexpiration
     * @return Provider
     */
    public function setCancheckexpiration($cancheckexpiration)
    {
        $this->cancheckexpiration = $cancheckexpiration;

        return $this;
    }

    /**
     * Get cancheckexpiration.
     *
     * @return int
     */
    public function getCancheckexpiration()
    {
        return $this->cancheckexpiration;
    }

    /**
     * @return bool
     */
    public function isDontSendEmailsSubaccExpDate()
    {
        return $this->dontSendEmailsSubaccExpDate;
    }

    /**
     * @param bool $dontSendEmailsSubaccExpDate
     * @return Provider
     */
    public function setDontSendEmailsSubaccExpDate($dontSendEmailsSubaccExpDate)
    {
        $this->dontSendEmailsSubaccExpDate = $dontSendEmailsSubaccExpDate;

        return $this;
    }

    /**
     * Set faq.
     *
     * @param int $faq
     * @return Provider
     */
    public function setFaq($faq)
    {
        $this->faq = $faq;

        return $this;
    }

    /**
     * Get faq.
     *
     * @return int
     */
    public function getFaq()
    {
        return $this->faq;
    }

    /**
     * Set providergroup.
     *
     * @param string $providergroup
     * @return Provider
     */
    public function setProvidergroup($providergroup)
    {
        $this->providergroup = $providergroup;

        return $this;
    }

    /**
     * Get providergroup.
     *
     * @return string
     */
    public function getProvidergroup()
    {
        return $this->providergroup;
    }

    /**
     * Set expirationunknownnote.
     *
     * @param string $expirationunknownnote
     * @return Provider
     */
    public function setExpirationunknownnote($expirationunknownnote)
    {
        $this->expirationunknownnote = $expirationunknownnote;

        return $this;
    }

    /**
     * Get expirationunknownnote.
     *
     * @return string
     */
    public function getExpirationunknownnote()
    {
        return $this->expirationunknownnote;
    }

    /**
     * Set customdisplayname.
     *
     * @param bool $customdisplayname
     * @return Provider
     */
    public function setCustomdisplayname($customdisplayname)
    {
        $this->customdisplayname = $customdisplayname;

        return $this;
    }

    /**
     * Get customdisplayname.
     *
     * @return bool
     */
    public function getCustomdisplayname()
    {
        return $this->customdisplayname;
    }

    /**
     * Set barcode.
     *
     * @param string $barcode
     * @return Provider
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode.
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set mobileautologin.
     *
     * @param bool $mobileautologin
     * @return Provider
     */
    public function setMobileautologin($mobileautologin)
    {
        $this->mobileautologin = $mobileautologin;

        return $this;
    }

    /**
     * Get mobileautologin.
     *
     * @return bool
     */
    public function getMobileautologin()
    {
        return $this->mobileautologin;
    }

    /**
     * Set corporate.
     *
     * @param bool $corporate
     * @return Provider
     */
    public function setCorporate($corporate)
    {
        $this->corporate = $corporate;

        return $this;
    }

    /**
     * Get corporate.
     *
     * @return bool
     */
    public function getCorporate()
    {
        return $this->corporate;
    }

    /**
     * Set currency.
     *
     * @param Currency $currency
     * @return Provider
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set deeplinking.
     *
     * @param int $deeplinking
     * @return Provider
     */
    public function setDeeplinking($deeplinking)
    {
        $this->deeplinking = $deeplinking;

        return $this;
    }

    /**
     * Get deeplinking.
     *
     * @return int
     */
    public function getDeeplinking()
    {
        return $this->deeplinking;
    }

    /**
     * Set questions.
     *
     * @param bool $questions
     * @return Provider
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;

        return $this;
    }

    /**
     * Get questions.
     *
     * @return bool
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Set note.
     *
     * @param string $note
     * @return Provider
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set timesrequested.
     *
     * @param int $timesrequested
     * @return Provider
     */
    public function setTimesrequested($timesrequested)
    {
        $this->timesrequested = $timesrequested;

        return $this;
    }

    /**
     * Get timesrequested.
     *
     * @return int
     */
    public function getTimesrequested()
    {
        return $this->timesrequested;
    }

    /**
     * Set passwordrequired.
     *
     * @param bool $passwordrequired
     * @return Provider
     */
    public function setPasswordrequired($passwordrequired)
    {
        $this->passwordrequired = $passwordrequired;

        return $this;
    }

    /**
     * Get passwordrequired.
     *
     * @return bool
     */
    public function getPasswordrequired()
    {
        return $this->passwordrequired;
    }

    /**
     * Set tier.
     *
     * @param int $tier
     * @return Provider
     */
    public function setTier($tier)
    {
        $this->tier = $tier;

        return $this;
    }

    /**
     * Get tier.
     *
     * @return int
     */
    public function getTier()
    {
        return $this->tier;
    }

    /**
     * Set severity.
     *
     * @param int $severity
     * @return Provider
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;

        return $this;
    }

    /**
     * Get severity.
     *
     * @return int
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * Set responsetime.
     *
     * @param int $responsetime
     * @return Provider
     */
    public function setResponsetime($responsetime)
    {
        $this->responsetime = $responsetime;

        return $this;
    }

    /**
     * Get responsetime.
     *
     * @return int
     */
    public function getResponsetime()
    {
        return $this->responsetime;
    }

    /**
     * Set elitelevelscount.
     *
     * @param int $elitelevelscount
     * @return Provider
     */
    public function setElitelevelscount($elitelevelscount)
    {
        $this->elitelevelscount = $elitelevelscount;

        return $this;
    }

    /**
     * Get elitelevelscount.
     *
     * @return int
     */
    public function getElitelevelscount()
    {
        return $this->elitelevelscount;
    }

    /**
     * Set rslaeventid.
     *
     * @param int $rslaeventid
     * @return Provider
     */
    public function setRslaeventid($rslaeventid)
    {
        $this->rslaeventid = $rslaeventid;

        return $this;
    }

    /**
     * Get rslaeventid.
     *
     * @return int
     */
    public function getRslaeventid()
    {
        return $this->rslaeventid;
    }

    /**
     * Set cancheckcancelled.
     *
     * @param bool $cancheckcancelled
     * @return Provider
     */
    public function setCancheckcancelled($cancheckcancelled)
    {
        $this->cancheckcancelled = $cancheckcancelled;

        return $this;
    }

    /**
     * Get cancheckcancelled.
     *
     * @return bool
     */
    public function getCancheckcancelled()
    {
        return $this->cancheckcancelled;
    }

    /**
     * Set aaadiscount.
     *
     * @param float $aaadiscount
     * @return Provider
     */
    public function setAaadiscount($aaadiscount)
    {
        $this->aaadiscount = $aaadiscount;

        return $this;
    }

    /**
     * Get aaadiscount.
     *
     * @return float
     */
    public function getAaadiscount()
    {
        return $this->aaadiscount;
    }

    /**
     * Set login3caption.
     *
     * @param string $login3caption
     * @return Provider
     */
    public function setLogin3caption($login3caption)
    {
        $this->login3caption = $login3caption;

        return $this;
    }

    /**
     * Get login3caption.
     *
     * @return string
     */
    public function getLogin3caption()
    {
        return $this->login3caption;
    }

    public function isLogin3Required(): bool
    {
        return $this->login3Required;
    }

    public function setLogin3Required(bool $login3Required): Provider
    {
        $this->login3Required = $login3Required;

        return $this;
    }

    /**
     * Set checkinbrowser.
     *
     * @param int $checkinbrowser
     * @return Provider
     */
    public function setCheckinbrowser($checkinbrowser)
    {
        $this->checkinbrowser = $checkinbrowser;

        return $this;
    }

    /**
     * Get checkinbrowser.
     *
     * @return int
     */
    public function getCheckinbrowser()
    {
        return $this->checkinbrowser;
    }

    /**
     * @return bool
     */
    public function isCheckinmobilebrowser()
    {
        return $this->checkinmobilebrowser;
    }

    /**
     * @param bool $checkInMobileBrowser
     * @return Provider
     */
    public function setCheckinmobilebrowser($checkInMobileBrowser)
    {
        $this->checkinmobilebrowser = $checkInMobileBrowser;

        return $this;
    }

    /**
     * Set accounts.
     *
     * @param int $accounts
     * @return Provider
     */
    public function setAccounts($accounts)
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Get accounts.
     *
     * @return int
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Set abaccounts.
     *
     * @param int $abaccounts
     * @return Provider
     */
    public function setAbaccounts($abaccounts)
    {
        $this->abaccounts = $abaccounts;

        return $this;
    }

    /**
     * Get abaccounts.
     *
     * @return int
     */
    public function getAbaccounts()
    {
        return $this->abaccounts;
    }

    /**
     * Set keywords.
     *
     * @param string $keywords
     * @return Provider
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords.
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return string
     */
    public function getStopKeywords()
    {
        return $this->stopKeywords;
    }

    /**
     * @param string $stopKeywords
     */
    public function setStopKeywords($stopKeywords): self
    {
        $this->stopKeywords = $stopKeywords;

        return $this;
    }

    /**
     * Set avgdurationwithoutplans.
     *
     * @param float $avgdurationwithoutplans
     * @return Provider
     */
    public function setAvgdurationwithoutplans($avgdurationwithoutplans)
    {
        $this->avgdurationwithoutplans = $avgdurationwithoutplans;

        return $this;
    }

    /**
     * Get avgdurationwithoutplans.
     *
     * @return float
     */
    public function getAvgdurationwithoutplans()
    {
        return $this->avgdurationwithoutplans;
    }

    /**
     * Set avgdurationwithplans.
     *
     * @param float $avgdurationwithplans
     * @return Provider
     */
    public function setAvgdurationwithplans($avgdurationwithplans)
    {
        $this->avgdurationwithplans = $avgdurationwithplans;

        return $this;
    }

    /**
     * Get avgdurationwithplans.
     *
     * @return float
     */
    public function getAvgdurationwithplans()
    {
        return $this->avgdurationwithplans;
    }

    public function getAvgDuration(bool $checkItineraries): float
    {
        if ($checkItineraries) {
            $avgDuration = $this->avgdurationwithplans ?: $this->avgdurationwithoutplans;
        } else {
            $avgDuration = $this->avgdurationwithoutplans ?: $this->avgdurationwithplans;
        }

        return (float) ($avgDuration ?: 30);
    }

    /**
     * Set canmarkcoupons.
     *
     * @param bool $canmarkcoupons
     * @return Provider
     */
    public function setCanmarkcoupons($canmarkcoupons)
    {
        $this->canmarkcoupons = $canmarkcoupons;

        return $this;
    }

    /**
     * Get canmarkcoupons.
     *
     * @return bool
     */
    public function getCanmarkcoupons()
    {
        return $this->canmarkcoupons;
    }

    public function getCanParseCardImages(): bool
    {
        return $this->canParseCardImages;
    }

    public function setCanParseCardImages(bool $canParseCardImages): self
    {
        $this->canParseCardImages = $canParseCardImages;

        return $this;
    }

    public function getCanDetectCreditCards(): bool
    {
        return $this->canDetectCreditCards;
    }

    public function setCanDetectCreditCards(bool $canDetectCreditCards): self
    {
        $this->canDetectCreditCards = $canDetectCreditCards;

        return $this;
    }

    /**
     * Set warning.
     *
     * @param string $warning
     * @return Provider
     */
    public function setWarning($warning)
    {
        $this->warning = $warning;

        return $this;
    }

    /**
     * Get warning.
     *
     * @return string
     */
    public function getWarning()
    {
        return $this->warning;
    }

    /**
     * Set cancheckhistory.
     *
     * @param bool $cancheckhistory
     * @return Provider
     */
    public function setCancheckhistory($cancheckhistory)
    {
        $this->cancheckhistory = $cancheckhistory;

        return $this;
    }

    /**
     * Get cancheckhistory.
     *
     * @return bool
     */
    public function getCancheckhistory()
    {
        return $this->cancheckhistory;
    }

    /**
     * Set expirationalwaysknown.
     *
     * @param bool $expirationalwaysknown
     * @return Provider
     */
    public function setExpirationalwaysknown($expirationalwaysknown)
    {
        $this->expirationalwaysknown = $expirationalwaysknown;

        return $this;
    }

    /**
     * Get expirationalwaysknown.
     *
     * @return bool
     */
    public function getExpirationalwaysknown()
    {
        return $this->expirationalwaysknown;
    }

    /**
     * Set requestsperminute.
     *
     * @param int $requestsperminute
     * @return Provider
     */
    public function setRequestsperminute($requestsperminute)
    {
        $this->requestsperminute = $requestsperminute;

        return $this;
    }

    /**
     * Get requestsperminute.
     *
     * @return int
     */
    public function getRequestsperminute()
    {
        return $this->requestsperminute;
    }

    /**
     * Set cacheversion.
     *
     * @param int $cacheversion
     * @return Provider
     */
    public function setCacheversion($cacheversion)
    {
        $this->cacheversion = $cacheversion;

        return $this;
    }

    /**
     * Get cacheversion.
     *
     * @return int
     */
    public function getCacheversion()
    {
        return $this->cacheversion;
    }

    /**
     * Set canchecknoitineraries.
     *
     * @param bool $canchecknoitineraries
     * @return Provider
     */
    public function setCanchecknoitineraries($canchecknoitineraries)
    {
        $this->canchecknoitineraries = $canchecknoitineraries;

        return $this;
    }

    /**
     * Get canchecknoitineraries.
     *
     * @return bool
     */
    public function getCanchecknoitineraries()
    {
        return $this->canchecknoitineraries;
    }

    /**
     * Set planemail.
     *
     * @param string $planemail
     * @return Provider
     */
    public function setPlanemail($planemail)
    {
        $this->planemail = $planemail;

        return $this;
    }

    /**
     * Get planemail.
     *
     * @return string
     */
    public function getPlanemail()
    {
        return $this->planemail;
    }

    /**
     * Set internalnote.
     *
     * @param string $internalnote
     * @return Provider
     */
    public function setInternalnote($internalnote)
    {
        $this->internalnote = $internalnote;

        return $this;
    }

    /**
     * Get internalnote.
     *
     * @return string
     */
    public function getInternalnote()
    {
        return $this->internalnote;
    }

    /**
     * Set calcelitelevelexpdate.
     *
     * @param bool $calcelitelevelexpdate
     * @return Provider
     */
    public function setCalcelitelevelexpdate($calcelitelevelexpdate)
    {
        $this->calcelitelevelexpdate = $calcelitelevelexpdate;

        return $this;
    }

    /**
     * Get calcelitelevelexpdate.
     *
     * @return bool
     */
    public function getCalcelitelevelexpdate()
    {
        return $this->calcelitelevelexpdate;
    }

    /**
     * Set itineraryautologin.
     *
     * @param int $itineraryautologin
     * @return Provider
     */
    public function setItineraryautologin($itineraryautologin)
    {
        $this->itineraryautologin = $itineraryautologin;

        return $this;
    }

    /**
     * Get itineraryautologin.
     *
     * @return int
     */
    public function getItineraryautologin()
    {
        return $this->itineraryautologin;
    }

    /**
     * Set eliteprogramcomment.
     *
     * @param string $eliteprogramcomment
     * @return Provider
     */
    public function setEliteprogramcomment($eliteprogramcomment)
    {
        $this->eliteprogramcomment = $eliteprogramcomment;

        return $this;
    }

    /**
     * Get eliteprogramcomment.
     *
     * @return string
     */
    public function getEliteprogramcomment()
    {
        return $this->eliteprogramcomment;
    }

    /**
     * Set canscanemail.
     *
     * @param bool $canscanemail
     * @return Provider
     */
    public function setCanscanemail($canscanemail)
    {
        $this->canscanemail = $canscanemail;

        return $this;
    }

    /**
     * Get canscanemail.
     *
     * @return bool
     */
    public function getCanscanemail()
    {
        return $this->canscanemail;
    }

    /**
     * Set category.
     *
     * @param int $category
     * @return Provider
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set allianceid.
     *
     * @return Provider
     */
    public function setAllianceid(?Alliance $allianceid = null)
    {
        $this->allianceid = $allianceid;

        return $this;
    }

    /**
     * Get allianceid.
     *
     * @return Alliance
     */
    public function getAllianceid()
    {
        return $this->allianceid;
    }

    /**
     * Set assignee.
     *
     * @return Provider
     */
    public function setAssignee(?Usr $assignee = null)
    {
        $this->assignee = $assignee;

        return $this;
    }

    /**
     * Get assignee.
     *
     * @return Usr
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @return ProviderProperty[]|Collection
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param ProviderProperty[]|Collection $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * @param bool $AutoLoginIE
     */
    public function setAutoLoginIE($AutoLoginIE)
    {
        $this->AutoLoginIE = $AutoLoginIE;
    }

    /**
     * @return bool
     */
    public function getAutoLoginIE()
    {
        return $this->AutoLoginIE;
    }

    /**
     * @param bool $AutoLoginSafari
     */
    public function setAutoLoginSafari($AutoLoginSafari)
    {
        $this->AutoLoginSafari = $AutoLoginSafari;
    }

    /**
     * @return bool
     */
    public function getAutoLoginSafari()
    {
        return $this->AutoLoginSafari;
    }

    /**
     * @param bool $AutoLoginChrome
     */
    public function setAutoLoginChrome($AutoLoginChrome)
    {
        $this->AutoLoginChrome = $AutoLoginChrome;
    }

    /**
     * @return bool
     */
    public function getAutoLoginChrome()
    {
        return $this->AutoLoginChrome;
    }

    /**
     * @param bool $AutoLoginFirefox
     */
    public function setAutoLoginFirefox($AutoLoginFirefox)
    {
        $this->AutoLoginFirefox = $AutoLoginFirefox;
    }

    /**
     * @return bool
     */
    public function getAutoLoginFirefox()
    {
        return $this->AutoLoginFirefox;
    }

    /**
     * @return int
     */
    public function getGoal()
    {
        return $this->Goal;
    }

    /**
     * @param int $Goal
     */
    public function setGoal($Goal)
    {
        $this->Goal = $Goal;
    }

    /**
     * Set CanReceiveEmail.
     *
     * @param bool $canReceiveEmail
     * @return Provider
     */
    public function setCanReceiveEmail($canReceiveEmail)
    {
        $this->CanReceiveEmail = $canReceiveEmail;

        return $this;
    }

    /**
     * Get CanReceiveEmail.
     *
     * @return bool
     */
    public function getCanReceiveEmail()
    {
        return $this->CanReceiveEmail;
    }

    public static function getKinds()
    {
        return [
            PROVIDER_KIND_CREDITCARD => 'track.group.card',
            PROVIDER_KIND_AIRLINE => 'track.group.airline',
            PROVIDER_KIND_HOTEL => 'track.group.hotel',
            PROVIDER_KIND_CAR_RENTAL => 'track.group.rent',
            PROVIDER_KIND_TRAIN => 'track.group.train',
            PROVIDER_KIND_CRUISES => 'track.group.cruise',
            PROVIDER_KIND_SHOPPING => 'track.group.shop',
            PROVIDER_KIND_DINING => 'track.group.dining',
            PROVIDER_KIND_SURVEY => 'track.group.survey',
            PROVIDER_KIND_PARKING => 'track.group.parking',
            PROVIDER_KIND_OTHER => 'track.group.other',
            PROVIDER_KIND_DOCUMENT => 'track.group.document',
        ];
    }

    /**
     * @return string
     */
    public function getIATACode()
    {
        return $this->IATACode;
    }

    /**
     * @param string $IATACode
     */
    public function setIATACode($IATACode)
    {
        $this->IATACode = $IATACode;
    }

    /**
     * @return bool
     */
    public function getCanTransferRewards()
    {
        return $this->canTransferRewards;
    }

    /**
     * @param bool $flag
     */
    public function setCanTransferRewards($flag)
    {
        $this->canTransferRewards = $flag;
    }

    /**
     * @return string
     */
    public function getCheckInReminderOffsets()
    {
        return $this->checkInReminderOffsets;
    }

    /**
     * @param string $checkInReminderOffsets
     * @return Provider
     */
    public function setCheckInReminderOffsets($checkInReminderOffsets)
    {
        $this->checkInReminderOffsets = $checkInReminderOffsets;

        return $this;
    }

    /**
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('track.group.all'))->setDesc('All'),
            (new Message('track.group.airline'))->setDesc('Airlines'),
            (new Message('track.group.hotel'))->setDesc('Hotels'),
            (new Message('track.group.card'))->setDesc('Credit Cards'),
            (new Message('track.group.shop'))->setDesc('Shopping'),
            (new Message('track.group.rent'))->setDesc('Rentals'),
            (new Message('track.group.dining'))->setDesc('Dining'),
            (new Message('track.group.train'))->setDesc('Trains'),
            (new Message('track.group.cruise'))->setDesc('Cruises'),
            (new Message('track.group.survey'))->setDesc('Surveys'),
            (new Message('track.group.parking'))->setDesc('Parking'),
            (new Message('track.group.other'))->setDesc('Other'),
            (new Message('track.group.document'))->setDesc('Documents'),

            (new Message('track.group.bus'))->setDesc('Buses'),
            (new Message('track.group.transfer'))->setDesc('Transfers'),
            (new Message('track.group.event'))->setDesc('Events'),
            (new Message('track.group.ferry'))->setDesc('Ferries'),
            (new Message('track.group.agency'))->setDesc('Agencies'),

            (new Message('track.group.airline.items'))->setDesc('{0}miles|{1}mile|[2,Inf]miles'),
            (new Message('track.group.hotel.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.card.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.shop.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.rent.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.dining.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.train.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.cruise.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.survey.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.parking.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.other.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),

            (new Message('track.group.bus.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.transfer.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.event.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.ferry.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.agency.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
        ];
    }

    /**
     * @return int
     */
    public function getCanSavePassword()
    {
        return $this->CanSavePassword;
    }

    /**
     * @param int $CanSavePassword
     */
    public function setCanSavePassword($CanSavePassword)
    {
        $this->CanSavePassword = $CanSavePassword;
    }

    /**
     * @return \DateTime
     */
    public function getEnabledate()
    {
        return $this->enabledate;
    }

    /**
     * @param \DateTime $enabledate
     */
    public function setEnabledate($enabledate)
    {
        $this->enabledate = $enabledate;
    }

    /**
     * @return bool
     */
    public function isOauthProvider()
    {
        return in_array($this->providerid, self::OAUTH_PROVIDERS);
    }

    public function isBig3()
    {
        return in_array($this->providerid, self::BIG3_PROVIDERS);
    }

    /**
     * @return Popularity
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
     * @param Popularity $popularity
     */
    public function setPopularity($popularity)
    {
        $this->popularity = $popularity;
    }

    /**
     * @return bool
     */
    public function isRetail()
    {
        return $this->isRetail;
    }

    /**
     * @param bool $isRetail
     * @return Provider
     */
    public function setIsRetail($isRetail)
    {
        $this->isRetail = $isRetail;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }

    /**
     * @param string $additionalInfo
     * @return Provider
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    public function userHasAccess(?Usr $user = null): bool
    {
        return
            ($this->getState() > 0)
            || ($this->getState() === PROVIDER_RETAIL)
            || ($this->getState() === null)
            || (
                $user
                && (
                    ($user->hasRole('ROLE_STAFF') ? $this->getState() == PROVIDER_TEST : false)
                    || ($user->getBetaapproved() ? $this->getState() == PROVIDER_IN_BETA : false)
                )
            );
    }

    public function canCheck(?Usr $user = null)
    {
        if (empty($user)) {
            return $this->getCancheck();
        }

        return $this->getCancheck() && $this->userHasAccess($user);
    }

    public function canCheckConfirmation(?Usr $user = null)
    {
        if (empty($user)) {
            return $this->getCancheckconfirmation();
        }

        return $this->getCancheckconfirmation() && $this->userHasAccess($user);
    }

    public function getUserItineraryAutoLogin(?Usr $user = null)
    {
        if (empty($user)) {
            return $this->getItineraryautologin();
        }

        return $this->userHasAccess($user) ? $this->getItineraryautologin() : false;
    }

    public function canAutologin(?Usr $user = null)
    {
        if (empty($user)) {
            return $this->getAutologin() != AUTOLOGIN_DISABLED;
        }

        return (
            $this->userHasAccess($user)
            || ($this->getState() === PROVIDER_RETAIL)
        ) ? $this->getAutologin() != AUTOLOGIN_DISABLED : false;
    }

    public function canAutologinWithExtension(?Usr $user = null): bool
    {
        return $this->canAutologin($user) && in_array($this->autologin, [AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED]);
    }

    /**
     * @return Providerphone[]
     */
    public function getPhones(): array
    {
        return $this->phones->toArray();
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Provider
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setBlogTagsId(?string $tagIds): self
    {
        $this->blogTagsId = $tagIds;

        return $this;
    }

    public function getBlogTagsId(): ?string
    {
        return $this->blogTagsId;
    }

    public function setBlogPostId(?string $postIds): self
    {
        $this->blogPostId = $postIds;

        return $this;
    }

    public function getBlogPostId(): ?string
    {
        return $this->blogPostId;
    }

    public function getBlogIdsMilesPurchase(): ?string
    {
        return $this->blogIdsMilesPurchase;
    }

    public function setBlogIdsMilesPurchase(?string $blogIdsMilesPurchase): self
    {
        $this->blogIdsMilesPurchase = $blogIdsMilesPurchase;

        return $this;
    }

    public function getBlogIdsMilesTransfers(): ?string
    {
        return $this->blogIdsMilesTransfers;
    }

    public function setBlogIdsMilesTransfers(?string $blogIdsMilesTransfers): self
    {
        $this->blogIdsMilesTransfers = $blogIdsMilesTransfers;

        return $this;
    }

    public function getBlogIdsPromos(): ?string
    {
        return $this->blogIdsPromos;
    }

    public function setBlogIdsPromos(?string $blogIdsPromos): self
    {
        $this->blogIdsPromos = $blogIdsPromos;

        return $this;
    }

    public function getBlogIdsMileExpiration(): ?string
    {
        return $this->blogIdsMileExpiration;
    }

    public function setBlogIdsMileExpiration(?string $blogIdsMileExpiration): self
    {
        $this->blogIdsMileExpiration = $blogIdsMileExpiration;

        return $this;
    }

    public function isEarningPotential(): bool
    {
        return $this->isEarningPotential;
    }

    public function setIsEarningPotential(bool $isEarningPotential): void
    {
        $this->isEarningPotential = $isEarningPotential;
    }

    public function isConfNoV3(): bool
    {
        return $this->confNoV3;
    }

    public function isExtensionV3ParserReady(): bool
    {
        return $this->extensionV3ParserReady;
    }

    public function getClientSideLastFixDate(): ?\DateTime
    {
        return $this->clientSideLastFixDate;
    }

    public function setClientSideLastFixDate(?\DateTime $fixDate): self
    {
        $this->clientSideLastFixDate = $fixDate;

        return $this;
    }

    public function getServerSideLastFixDate(): ?\DateTime
    {
        return $this->serverSideLastFixDate;
    }

    public function setServerSideLastFixDate(?\DateTime $fixDate): self
    {
        $this->serverSideLastFixDate = $fixDate;

        return $this;
    }

    public function isManualUpdate(): bool
    {
        return $this->manualUpdate;
    }

    public function setManualUpdate(bool $manualUpdate): self
    {
        $this->manualUpdate = $manualUpdate;

        return $this;
    }
}
