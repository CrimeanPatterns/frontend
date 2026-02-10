<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Form\Model\Profile\NotificationModel;
use AwardWallet\MainBundle\Globals\DeprecationUtils;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Security\OAuth\OAuthType;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Usr.
 *
 * @ORM\Table(name="Usr")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\UsrRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"email"}, errorPath="email", message="user.email_taken", groups={"unique", "change_email"})
 * @UniqueEntity(fields={"login"}, errorPath="login", message="user.login_taken", groups={"unique"})
 * @UniqueEntity(fields={"company"}, errorPath="company", message="company.already.taken", groups={"business_register"})
 * @AwAssert\Service(
 *     name = "AwardWallet\MainBundle\Validator\UserEmailValidator",
 *     method = "validate",
 *     groups={"register", "change_email"}
 * )
 */
class Usr implements UserInterface, EquatableInterface, \Serializable
{
    public const SUBSCRIPTION_SAVED_CARD = 1;
    public const SUBSCRIPTION_PAYPAL = 2;
    public const SUBSCRIPTION_MOBILE = 3;
    public const SUBSCRIPTION_BITCOIN = 4;
    public const SUBSCRIPTION_STRIPE = 5;

    public const SUBSCRIPTION_NAMES = [
        self::SUBSCRIPTION_SAVED_CARD => 'Credit Card',
        self::SUBSCRIPTION_PAYPAL => 'PayPal',
        self::SUBSCRIPTION_MOBILE => 'Mobile',
        self::SUBSCRIPTION_BITCOIN => 'Bitcoin',
        self::SUBSCRIPTION_STRIPE => 'Stripe',
    ];

    public const SUBSCRIPTION_TYPE_AWPLUS = 1;
    public const SUBSCRIPTION_TYPE_AT201 = 2;

    public const CHANGE_PASSWORD_METHOD_LINK = 1;
    public const CHANGE_PASSWORD_METHOD_PROFILE = 2;

    public const TURN_OFF_IOS_SUBSCRIPTION_WARNING = 'TURN_OFF_IOS_SUBSCRIPTION_WARNING';

    public const EMAIL_UNVERIFIED = 0;
    public const EMAIL_VERIFIED = 1;
    public const EMAIL_NDR = 2;

    public const REGISTRATION_PLATFORM_MOBILE_APP = 1;
    public const REGISTRATION_PLATFORM_MOBILE_BROWSER = 2;
    public const REGISTRATION_PLATFORM_DESKTOP_BROWSER = 3;

    public const REGISTRATION_METHOD_FORM = 1;
    public const REGISTRATION_METHOD_OAUTH_GOOGLE = 2;
    public const REGISTRATION_METHOD_OAUTH_MICROSOFT = 3;
    public const REGISTRATION_METHOD_OAUTH_YAHOO = 4;
    public const REGISTRATION_METHOD_OAUTH_AOL = 5;
    public const REGISTRATION_METHOD_OAUTH_APPLE = 6;

    public const REGISTRATION_METHODS = [
        OAuthType::GOOGLE => self::REGISTRATION_METHOD_OAUTH_GOOGLE,
        OAuthType::MICROSOFT => self::REGISTRATION_METHOD_OAUTH_MICROSOFT,
        OAuthType::YAHOO => self::REGISTRATION_METHOD_OAUTH_YAHOO,
        OAuthType::AOL => self::REGISTRATION_METHOD_OAUTH_AOL,
        OAuthType::APPLE => self::REGISTRATION_METHOD_OAUTH_APPLE,
    ];

    public const EMAIL_EXPIRATION_NEVER = 0;
    public const EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7 = 1;
    public const EMAIL_EXPIRATION_90_60_30_7 = 2;

    public static $decimalPoints = [
        ',' => ".",
        '.' => ",",
        ' ' => ",",
    ];

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $userid;

    /**
     * @var string
     * @Assert\Length(min = 4, max = 30, allowEmptyString="true", groups={"register"})
     * @Assert\Regex(pattern="/^[a-z_0-9A-Z\-]+$/i", groups={"register"}, message="user.login.pattern")
     * @ORM\Column(name="Login", type="string", length=30, nullable=false, unique=true)
     */
    protected $login;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register", "change_pass"})
     * @AwAssert\ByteLength(min = 8, max = 72, groups={"register", "change_pass"})
     * @ORM\Column(name="Pass", type="string", length=60, nullable=false)
     */
    protected $pass;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true", groups={"register"})
     * @ORM\Column(name="FirstName", type="string", length=30, nullable=false)
     */
    protected $firstname;

    /**
     * @var string
     * @Assert\Length(max = 30, groups={"register"})
     * @ORM\Column(name="MidName", type="string", length=30, nullable=true)
     */
    protected $midname;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(min = 1, max = 30, allowEmptyString="true", groups={"register"})
     * @ORM\Column(name="LastName", type="string", length=30, nullable=false)
     */
    protected $lastname;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register", "change_email"})
     * @Assert\Regex("/^[_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+)$/i", message="user.email.invalid", groups={"register", "change_email"})
     * @Assert\Regex("/^.+@lista.cc$/i", match=false, message="invalid_captcha", groups={"register"})
     * @Assert\Regex("/^.+bbbnekj@.+\.cc$/i", match=false, message="user.email_taken", groups={"register"})
     * @ORM\Column(name="Email", type="string", length=80, nullable=false, unique=true)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="FacebookUserId", type="string", length=40, nullable=true)
     */
    protected $facebookUserId;

    /**
     * @var int
     * @ORM\Column(name="Age", type="integer", nullable=true)
     */
    protected $age;

    /**
     * @var string
     * @ORM\Column(name="Address1", type="string", length=128, nullable=true)
     */
    protected $address1;

    /**
     * @var string
     * @ORM\Column(name="Address2", type="string", length=128, nullable=true)
     */
    protected $address2;

    /**
     * @var string
     * @ORM\Column(name="City", type="string", length=80, nullable=true)
     */
    protected $city;

    /**
     * @var string
     * @ORM\Column(name="Zip", type="string", length=40, nullable=true)
     */
    protected $zip;

    /**
     * @var string
     * @ORM\Column(name="Country", type="string", length=80, nullable=true)
     */
    protected $country;

    /**
     * @var string
     * @ORM\Column(name="Title", type="string", length=80, nullable=true)
     */
    protected $title;

    /**
     * @var string
     * @Assert\NotBlank(groups={"business_register"})
     * @ORM\Column(name="Company", type="string", length=128, nullable=true, unique=true)
     */
    protected $company;

    /**
     * @var string
     * @ORM\Column(name="Phone1", type="string", length=40, nullable=true)
     */
    protected $phone1;

    /**
     * @var bool
     * @ORM\Column(name="IsNewsSubscriber", type="boolean", nullable=true)
     */
    protected $isnewssubscriber;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDateTime", type="datetime", nullable=true)
     */
    protected $creationdatetime;

    /**
     * @var bool
     * @ORM\Column(name="IsEnabled", type="boolean", nullable=false)
     */
    protected $isenabled = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastLogonDateTime", type="datetime", nullable=true)
     */
    protected $lastlogondatetime;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=true)
     */
    protected $updatedate;

    /**
     * @var int
     * @ORM\Column(name="EmailVerified", type="integer", nullable=false)
     */
    protected $emailverified = 0;

    /**
     * @var int
     * @ORM\Column(name="LogonCount", type="integer", nullable=true)
     */
    protected $logoncount = 0;

    /**
     * @var int
     * @ORM\Column(name="CountryID", type="integer", nullable=true)
     */
    protected $countryid;

    /**
     * @var int
     * @ORM\Column(name="StateID", type="integer", nullable=true)
     */
    protected $stateid;

    /**
     * @var string
     * @ORM\Column(name="RegistrationIP", type="string", length=60, nullable=true)
     */
    protected $registrationip;

    /**
     * @var string
     * @ORM\Column(name="LastLogonIP", type="string", length=60, nullable=true)
     */
    protected $lastlogonip;

    /**
     * @var int
     * @ORM\Column(name="LastScreenWidth", type="integer", nullable=true)
     */
    protected $lastscreenwidth;

    /**
     * @var int
     * @ORM\Column(name="LastScreenHeight", type="integer", nullable=true)
     */
    protected $lastscreenheight;

    /**
     * @var string
     * @ORM\Column(name="LastUserAgent", type="string", length=250, nullable=true)
     */
    protected $lastuseragent;

    /**
     * @var int
     * @ORM\Column(name="CameFrom", type="integer", nullable=true)
     */
    protected $camefrom;

    /**
     * @var SiteAd
     * @ORM\ManyToOne(targetEntity="Sitead")
     * @ORM\JoinColumn(name="CameFrom", referencedColumnName="SiteAdID", nullable=true)
     */
    protected $SiteAd;

    /**
     * @var int
     * @ORM\Column(name="AccountLevel", type="integer", nullable=false)
     */
    protected $accountlevel = ACCOUNT_LEVEL_FREE;

    /**
     * @var int
     * @ORM\Column(name="PrimaryFunctionality", type="integer", nullable=false)
     */
    protected $primaryfunctionality = 1;

    /**
     * @var int
     * @ORM\Column(name="EmailTCSubscribe", type="smallint", nullable=false)
     */
    protected $emailtcsubscribe = 1;

    /**
     * @var string
     * @ORM\Column(name="DefaultTab", type="string", length=40, nullable=false)
     */
    protected $defaulttab = 'All';

    /**
     * @var int
     * @ORM\Column(name="GoalClass", type="integer", nullable=true)
     */
    protected $goalclass;

    /**
     * @var string
     * @ORM\Column(name="Referer", type="string", length=250, nullable=true)
     */
    protected $referer;

    /**
     * @var bool
     * @ORM\Column(name="AutoGatherPlans", type="boolean", nullable=false)
     */
    protected $autogatherplans = true;

    /**
     * @var bool
     * @ORM\Column(name="PublicPlans", type="boolean", nullable=false)
     */
    protected $publicplans = true;

    /**
     * @var int
     * @ORM\Column(name="DateFormat", type="integer", nullable=false)
     */
    protected $dateformat = 1;

    /**
     * @var string
     * @ORM\Column(name="ThousandsSeparator", type="string", length=1, nullable=true)
     */
    protected $thousandsseparator = ',';

    /**
     * @var \DateTime
     * @ORM\Column(name="LockoutStart", type="datetime", nullable=true)
     */
    protected $lockoutstart;

    /**
     * @var int
     * @ORM\Column(name="LoginAttempts", type="integer", nullable=false)
     */
    protected $loginattempts = 0;

    /**
     * @var string
     * @ORM\Column(name="HomeAirport", type="string", length=3, nullable=true)
     */
    protected $homeairport;

    /**
     * @var int
     * @Assert\Image(groups={"business_register"})
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
     * @ORM\Column(name="RefCode", type="string", length=10, nullable=true)
     */
    protected $refcode;

    /**
     * @var int
     * @ORM\Column(name="Accounts", type="integer", nullable=true)
     */
    protected $accounts;

    /**
     * @var int
     * @ORM\Column(name="Providers", type="integer", nullable=true)
     */
    protected $providers;

    /**
     * @var int
     * @ORM\Column(name="UserAgents", type="integer", nullable=true)
     */
    protected $useragents;

    /**
     * @var string
     * @ORM\Column(name="Skin", type="string", length=20, nullable=true)
     */
    protected $skin;

    /**
     * @var string
     * @ORM\Column(name="BrowserKey", type="string", length=64, nullable=true)
     */
    protected $browserkey;

    /**
     * @var bool
     * @ORM\Column(name="InBeta", type="boolean", nullable=false)
     */
    protected $inbeta = false;

    /**
     * @var int
     * @ORM\Column(name="SavePassword", type="integer", nullable=false)
     */
    protected $savepassword = 1;

    /**
     * @var bool
     * @ORM\Column(name="BetaApproved", type="boolean", nullable=false)
     */
    protected $betaapproved = false;

    /**
     * @var bool
     * @ORM\Column(name="OldEmailVerified", type="boolean", nullable=true)
     */
    protected $oldemailverified;

    /**
     * @var string
     * @ORM\Column(name="ExtensionVersion", type="string", length=20, nullable=true)
     */
    protected $extensionversion;

    /**
     * @var string
     * @ORM\Column(name="ExtensionBrowser", type="string", length=250, nullable=true)
     */
    protected $extensionbrowser;

    /**
     * @var \DateTime
     * @ORM\Column(name="ExtensionLastUseDate", type="datetime", nullable=true)
     */
    protected $extensionlastusedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastEmailReadDate", type="datetime", nullable=true)
     */
    protected $lastemailreaddate;

    /**
     * @var bool
     * @ORM\Column(name="Mismanagement", type="boolean", nullable=false)
     */
    protected $mismanagement = false;

    /**
     * @var bool
     * @ORM\Column(name="EnableMobileLog", type="boolean", nullable=false)
     */
    protected $enablemobilelog = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="OfferShowDate", type="datetime", nullable=true)
     */
    protected $offershowdate;

    /**
     * @var string
     * @ORM\Column(name="ResetPasswordCode", type="string", length=64, nullable=true)
     */
    protected $resetpasswordcode;

    /**
     * @var \DateTime
     * @ORM\Column(name="ResetPasswordDate", type="datetime", nullable=true)
     */
    protected $resetpassworddate;

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
     * @var \DateTime
     * @ORM\Column(name="PlansBuildDate", type="datetime", nullable=true)
     */
    protected $plansbuilddate;

    /**
     * @var \DateTime
     * @ORM\Column(name="PlansUpdateDate", type="datetime", nullable=true)
     */
    protected $plansupdatedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ItineraryAddDate", type="datetime", nullable=true)
     */
    protected $itineraryadddate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ItineraryUpdateDate", type="datetime", nullable=true)
     */
    protected $itineraryupdatedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastDesktopLogon", type="datetime", nullable=true)
     */
    protected $lastdesktoplogon;

    /**
     * @var \DateTime
     * @ORM\Column(name="PlansChangeDate", type="datetime", nullable=true)
     */
    protected $planschangedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="PlansMailDate", type="datetime", nullable=true)
     */
    protected $plansmaildate;

    /**
     * @var string
     * @ORM\Column(name="PayPalRecurringProfileID", type="string", length=128, nullable=true)
     */
    protected $paypalrecurringprofileid;

    /**
     * @var int
     * @ORM\Column(name="Subscription", type="integer", nullable=true)
     */
    protected $subscription;

    /**
     * @var int
     * @ORM\Column(name="SubscriptionType", type="integer", nullable=true)
     */
    protected $subscriptionType;

    /**
     * @var string
     * @ORM\Column(name="ReferralID", type="string", length=40, nullable=true)
     */
    protected $referralid;

    /**
     * @var int
     * @ORM\Column(name="RecurringPaymentAmount", type="integer", nullable=true)
     */
    protected $recurringpaymentamount;

    /**
     * @var \DateTime
     * @ORM\Column(name="PlusExpirationDate", type="datetime", nullable=true)
     */
    protected $plusExpirationDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="AT201ExpirationDate", type="datetime", nullable=true)
     */
    protected $at201ExpirationDate;

    /**
     * @var Socialad
     * @ORM\ManyToOne(targetEntity="Socialad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SocialAdID", referencedColumnName="SocialAdID")
     * })
     */
    protected $socialadid;

    /**
     * @var Goal
     * @ORM\ManyToOne(targetEntity="Goal")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GoalID", referencedColumnName="GoalID")
     * })
     */
    protected $goalid;

    /**
     * @var AbBookerInfo
     * @ORM\OneToMany(targetEntity="AbBookerInfo", mappedBy="UserID", cascade={"persist", "remove"})
     * warning: dont change this to one-to-one!
     */
    protected $BookerInfo;

    /**
     * @var BusinessInfo
     * @ORM\OneToOne(targetEntity="BusinessInfo", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $businessInfo;

    /**
     * @var self
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="DefaultBookerID", referencedColumnName="UserID", nullable=true)
     */
    protected $DefaultBooker;

    /**
     * @var self
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="OwnedByBusinessID", referencedColumnName="UserID", nullable=true)
     */
    protected $OwnedByBusiness;

    /**
     * @var self
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="OwnedByManagerID", referencedColumnName="UserID", nullable=true)
     */
    protected $OwnedByManager;

    /**
     * secret for 2-factor auth.
     *
     * @var string
     * @ORM\Column(name="GoogleAuthSecret", type="string", length=250, nullable=true)
     */
    protected $googleAuthSecret;

    /**
     * @var string
     * @ORM\Column(name="GoogleAuthRecoveryCode", type="string", length=250, nullable=true)
     */
    protected $googleAuthRecoveryCode;

    /**
     * @var \DateTime
     * @ORM\Column(name="DiscountedUpgradeBefore", type="datetime", nullable=true)
     */
    protected $discountedUpgradeBefore;

    /**
     * @var Sitegroup[]|Collection
     * @ORM\ManyToMany(targetEntity="Sitegroup")
     * @ORM\JoinTable(name="GroupUserLink",
     *      joinColumns={@ORM\JoinColumn(name="UserID", referencedColumnName="UserID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SiteGroupID", referencedColumnName="SiteGroupID", unique=true)}
     * )
     **/
    protected $groups;

    /**
     * @var Useragent[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="Useragent", mappedBy="agentid", cascade={"persist", "remove"})
     **/
    protected $connections;

    /**
     * @var Cart[]
     * @ORM\OneToMany(targetEntity="Cart", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $carts;

    /**
     * @var BusinessTransaction[]
     * @ORM\OneToMany(targetEntity="BusinessTransaction", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $transactions;

    /**
     * @var int
     * @ORM\Column(name="FailedRecurringPayments", type="integer", nullable=false)
     */
    protected $failedRecurringPayments = 0;

    protected $_ConnectedBusiness = false;
    protected $_BusinessByLevel = [];

    /**
     * @var self
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="BetaInviterID", referencedColumnName="UserID", nullable=true)
     */
    protected $betaInviter;

    /**
     * @var int
     * @ORM\Column(name="BetaInvitesCount", type="integer", nullable=false)
     */
    protected $betaInvitesCount = 5;

    /**
     * @var string
     * @ORM\Column(name="Language", type="string", nullable=false)
     */
    protected $language = 'en';

    /**
     * @var string
     * @ORM\Column(name="Region", type="string", nullable=true)
     */
    protected $region;

    /**
     * @var Currency
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Currency")
     * @ORM\JoinColumn(name="CurrencyID", referencedColumnName="CurrencyID", nullable=false)
     */
    protected $currency;

    /**
     * @var UserQuestion[]|Collection
     * @ORM\OneToMany(targetEntity="\AwardWallet\MainBundle\Entity\UserQuestion", mappedBy="user", indexBy="order")
     */
    protected $securityQuestions;

    /**
     * @var TimelineShare[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="TimelineShare", mappedBy="recipientUser", cascade={"persist", "remove"})
     */
    protected $sharedTimelines;

    /**
     * @var int
     * @ORM\Column(name="InviteCouponsCorrection", type="integer", nullable=false)
     */
    protected $inviteCouponsCorrection = 0;

    /**
     * @var bool
     * @ORM\Column(name="EmailExpiration", type="integer", nullable=false)
     */
    protected $emailexpiration = self::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7;

    /**
     * @var int
     * @ORM\Column(name="EmailRewards", type="integer", nullable=false)
     */
    protected $emailrewards = REWARDS_NOTIFICATION_DAY;

    /**
     * @var bool
     * @ORM\Column(name="EmailNewPlans", type="boolean", nullable=false)
     */
    protected $emailnewplans = true;

    /**
     * @var bool
     * @ORM\Column(name="EmailPlansChanges", type="boolean", nullable=false)
     */
    protected $emailplanschanges = true;

    /**
     * @var bool
     * @ORM\Column(name="CheckinReminder", type="boolean", nullable=false)
     */
    protected $checkinreminder = true;

    /**
     * @var bool
     * @ORM\Column(name="EmailBookingMessages", type="boolean", nullable=false)
     */
    protected $emailBookingMessages = true;

    /**
     * @var bool
     * @ORM\Column(name="EmailProductUpdates", type="boolean", nullable=false)
     */
    protected $emailproductupdates = true;

    /**
     * @var bool
     * @ORM\Column(name="EmailOffers", type="boolean", nullable=false)
     */
    protected $emailoffers = true;

    /**
     * @var int
     * @ORM\Column(name="EmailNewBlogPosts", type="integer", nullable=false)
     */
    protected $emailNewBlogPosts = NotificationModel::BLOGPOST_NEW_NOTIFICATION_NEVER;

    /**
     * @var bool
     * @ORM\Column(name="EmailInviteeReg", type="boolean", nullable=false)
     */
    protected $emailInviteeReg = true;

    /**
     * @var bool
     * @ORM\Column(name="EmailConnectedAlert", type="boolean", nullable=false)
     */
    protected $emailConnectedAlert = false;

    /**
     * @var bool
     * @ORM\Column(name="EmailFamilyMemberAlert", type="boolean", nullable=false)
     */
    protected $emailFamilyMemberAlert = true;

    /**
     * @var bool
     * @ORM\Column(name="WpDisableAll", type="boolean")
     */
    protected $wpDisableAll = false;

    /**
     * @var bool
     * @ORM\Column(name="WpNewPlans", type="boolean")
     */
    protected $wpNewPlans = true;

    /**
     * @var bool
     * @ORM\Column(name="WpPlanChanges", type="boolean")
     */
    protected $wpPlanChanges = true;

    /**
     * @var bool
     * @ORM\Column(name="WpProductUpdates", type="boolean")
     */
    protected $wpProductUpdates = true;

    /**
     * @var bool
     * @ORM\Column(name="WpOffers", type="boolean")
     */
    protected $wpOffers = true;

    /**
     * @var bool
     * @ORM\Column(name="WpExpire", type="boolean")
     */
    protected $wpExpire = true;

    /**
     * @var bool
     * @ORM\Column(name="WpBookingMessages", type="boolean")
     */
    protected $wpBookingMessages = true;

    /**
     * @var bool
     * @ORM\Column(name="WpNewBlogPosts", type="boolean")
     */
    protected $wpNewBlogPosts = false;

    /**
     * @var bool
     * @ORM\Column(name="WpInviteeReg", type="boolean")
     */
    protected $wpInviteeReg = false;

    /**
     * @var bool
     * @ORM\Column(name="WpConnectedAlert", type="boolean")
     */
    protected $wpConnectedAlert = false;

    /**
     * @var bool
     * @ORM\Column(name="WpFamilyMemberAlert", type="boolean")
     */
    protected $wpFamilyMemberAlert = true;

    /**
     * @var bool
     * @ORM\Column(name="WpRewardsActivity", type="boolean")
     */
    protected $wpRewardsActivity = true;

    /**
     * @var bool
     * @ORM\Column(name="WpCheckins", type="boolean")
     */
    protected $wpCheckins = true;

    /**
     * @var bool
     * @ORM\Column(name="MpDisableAll", type="boolean")
     */
    protected $mpDisableAll = false;

    /**
     * @var bool
     * @ORM\Column(name="MpExpire", type="boolean")
     */
    protected $mpExpire = true;

    /**
     * @var bool
     * @ORM\Column(name="MpRewardsActivity", type="boolean")
     */
    protected $mpRewardsActivity = true;

    /**
     * @var bool
     * @ORM\Column(name="MpNewPlans", type="boolean")
     */
    protected $mpNewPlans = true;

    /**
     * @var bool
     * @ORM\Column(name="MpPlanChanges", type="boolean")
     */
    protected $mpPlanChanges = true;

    /**
     * @var bool
     * @ORM\Column(name="MpCheckins", type="boolean")
     */
    protected $mpCheckins = true;

    /**
     * @var bool
     * @ORM\Column(name="MpBookingMessages", type="boolean")
     */
    protected $mpBookingMessages = true;

    /**
     * @var bool
     * @ORM\Column(name="MpProductUpdates", type="boolean")
     */
    protected $mpProductUpdates = true;

    /**
     * @var bool
     * @ORM\Column(name="MpOffers", type="boolean")
     */
    protected $mpOffers = true;

    /**
     * @var bool
     * @ORM\Column(name="MpNewBlogPosts", type="boolean")
     */
    protected $mpNewBlogPosts = false;

    /**
     * @var bool
     * @ORM\Column(name="MpInviteeReg", type="boolean")
     */
    protected $mpInviteeReg = false;

    /**
     * @var bool
     * @ORM\Column(name="MpConnectedAlert", type="boolean")
     */
    protected $mpConnectedAlert = false;

    /**
     * @var bool
     * @ORM\Column(name="MpFamilyMemberAlert", type="boolean")
     */
    protected $mpFamilyMemberAlert = true;

    /**
     * @var bool
     * @ORM\Column(name="MpRetailCards", type="boolean")
     */
    protected $mpRetailCards = true;

    /**
     * @var string
     * @ORM\Column(name="IosReceipt", type="string", nullable=true)
     */
    protected $iosReceipt;

    /**
     * @var bool
     * @ORM\Column(name="IosRestoredReceipt", type="boolean")
     */
    protected $iosRestoredReceipt = true;

    /**
     * @var bool
     * @ORM\Column(name="SplashAdsDisabled", type="boolean")
     */
    protected $splashAdsDisabled = false;

    /**
     * @var bool
     * @ORM\Column(name="LinkAdsDisabled", type="boolean")
     */
    protected $linkAdsDisabled = false;

    /**
     * @var bool
     * @ORM\Column(name="ListAdsDisabled", type="boolean")
     */
    protected $listAdsDisabled = false;

    /**
     * @var bool
     * @ORM\Column(name="IsBlogPostAds", type="boolean")
     */
    protected $isBlogPostAds = true;

    /**
     * @var bool
     * @ORM\Column(name="Promo500k", type="boolean")
     */
    protected $promo500k = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="ZipCodeUpdateDate", type="datetime", nullable=true)
     */
    protected $zipCodeUpdateDate;

    /**
     * @ORM\Column(name="SubscriptionPrice", type="float", nullable=true)
     */
    private ?float $subscriptionPrice;

    /**
     * see SubscriptionPeriod::DAYS_ constants.
     *
     * @ORM\Column(name="SubscriptionPeriod", type="integer", nullable=true)
     */
    private ?int $subscriptionPeriod;

    /**
     * @ORM\OneToOne(targetEntity="CartItem")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="FirstSubscriptionCartItemID", referencedColumnName="CartItemID")
     *  })
     */
    private ?CartItem $firstSubscriptionCartItem;

    /**
     * @ORM\OneToOne(targetEntity="CartItem")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="LastSubscriptionCartItemID", referencedColumnName="CartItemID")
     *   })
     */
    private ?CartItem $lastSubscriptionCartItem;

    /**
     * @var array
     * @ORM\Column(name="TripitOauthToken", type="json", nullable="true")
     */
    private $tripitOauthToken;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $tripitLastSync;

    /**
     * @var string
     * @ORM\Column(name="OldPaypalRecurringProfileID", type="string", length=128, nullable=true)
     */
    private $oldPaypalRecurringProfileId;

    /**
     * @var array
     * @ORM\Column(name="SearchHints", type="json", nullable="true")
     */
    private $searchHints;

    /**
     * @var \DateTimeZone
     */
    private $dateTimeZone;

    /**
     * @var bool
     * @ORM\Column(name="Fraud", type="boolean")
     */
    private $fraud = false;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $changePasswordDate;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $changePasswordMethod;

    /**
     * @var bool
     */
    private $forcePlus = false;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $tripAlertsStartDate;

    /**
     * @var string
     * @ORM\Column(type="string", length=80)
     */
    private $tripAlertsHash;

    /**
     * @var MobileDevice[]
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\MobileDevice", mappedBy="userId", cascade={"persist"})
     */
    private $devices;

    /**
     * @var int
     * @ORM\Column(name="BalanceWatchCredits", type="integer")
     */
    private $balanceWatchCredits;

    /**
     * @var array
     * @ORM\Column(name="TravelerProfile", type="json_array")
     */
    private $travelerProfile;

    /**
     * @var \DateTime
     * @ORM\Column(name="LastSpendAnalysisEmailDate", type="datetime", nullable=true)
     */
    private $lastSpendAnalysisEmailDate;

    /**
     * @var int
     * @ORM\Column(name="UpgradeSkippedCount", type="integer", nullable=false)
     */
    private $upgradeSkippedCount = 0;

    /**
     * @var UserOAuth[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\UserOAuth", mappedBy="user", cascade={"persist", "remove"})
     * @ORM\OrderBy({"lastLoginDate" = "DESC"})
     */
    private $oauth;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $validMailboxesCount = 0;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    private $secret;

    /**
     * @var \DateTime
     * @ORM\Column(name="AvailableCardsUpdateDate", type="datetime")
     */
    private $availableCardsUpdateDate;

    /**
     * @var bool
     * @ORM\Column(name="AutoDetectLoungeCards", type="boolean")
     */
    private $autoDetectLoungeCards = false;

    /**
     * @var bool
     * @ORM\Column(name="HavePriorityPassCard", type="boolean")
     */
    private $havePriorityPassCard = false;

    /**
     * @var bool
     * @ORM\Column(name="HaveDragonPassCard", type="boolean")
     */
    private $haveDragonPassCard = false;

    /**
     * @var bool
     * @ORM\Column(name="HaveLoungeKeyCard", type="boolean")
     */
    private $haveLoungeKeyCard = false;

    /**
     * @var bool
     * @ORM\Column(name="UsGreeting", type="boolean")
     */
    private $usGreeting = false;

    /**
     * @var bool
     * @ORM\Column(name="IsUs", type="boolean")
     */
    private $isUs = false;

    /**
     * @ORM\Column(name="RegistrationPlatform", type="smallint", nullable=true)
     */
    private ?int $registrationPlatform;

    /**
     * @ORM\Column(name="RegistrationMethod", type="smallint", nullable=true)
     */
    private ?int $registrationMethod;

    /**
     *  @ORM\Column(name="FieldsBeforeDowngrade", type="json", nullable="true")
     */
    private ?array $fieldsBeforeDowngrade = null;

    /**
     * @var \DateTime - date when user will be charged by paypal recurring profile
     * @ORM\Column(name="NextBillingDate", type="date", nullable="true")
     */
    private ?\DateTime $nextBillingDate = null;

    /**
     * @var \DateTime - User prepaid some years in advance, and we need to suspend paypal billing until that date
     * @ORM\Column(name="PaypalSuspendedUntilDate", type="date", nullable="true")
     */
    private ?\DateTime $paypalSuspendedUntilDate = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->connections = new ArrayCollection();
        $this->carts = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->sharedTimelines = new ArrayCollection();
        $this->BookerInfo = new ArrayCollection();
        $this->refcode = StringHandler::getPseudoRandomString(5);
        $this->devices = new ArrayCollection();
        $this->oauth = new ArrayCollection();
        $this->secret = StringHandler::getRandomCode(32, true);
    }

    public function __toString()
    {
        return $this->userid . '.' . $this->login;
    }

    /**
     * Get userid.
     *
     * @deprecated use getId
     * @return int
     */
    public function getUserid()
    {
        return $this->userid;
    }

    public function getId(): ?int
    {
        return $this->userid;
    }

    /**
     * Set login.
     *
     * @param string $login
     * @return Usr
     */
    public function setLogin($login)
    {
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
     * @return Usr
     */
    public function setPass($pass)
    {
        $this->pass = $pass;

        return $this;
    }

    /**
     * Get pass.
     *
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     * @return Usr
     */
    public function setFirstname($firstname)
    {
        $this->firstname = htmlspecialchars($firstname); // compatibility with old code, value should be escaped in database

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
     * Set midname.
     *
     * @param string $midname
     * @return Usr
     */
    public function setMidname($midname)
    {
        $this->midname = htmlspecialchars($midname); // compatibility with old code, value should be escaped in database

        return $this;
    }

    /**
     * Get midname.
     *
     * @return string
     */
    public function getMidname()
    {
        return htmlspecialchars_decode($this->midname); // compatibility with old code, value should be escaped in database
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     * @return Usr
     */
    public function setLastname($lastname)
    {
        $this->lastname = htmlspecialchars($lastname); // compatibility with old code, value should be escaped in database

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
     * Set email.
     *
     * @param string $email
     * @return Usr
     */
    public function setEmail($email)
    {
        $this->email = htmlspecialchars($email); // compatibility with old code, value should be escaped in database

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return htmlspecialchars_decode($this->email); // compatibility with old code, value should be escaped in database
    }

    public function getFacebookUserId(): ?string
    {
        return $this->facebookUserId;
    }

    public function setFacebookUserId(?string $facebookUserId): self
    {
        $this->facebookUserId = $facebookUserId;

        return $this;
    }

    /**
     * Set age.
     *
     * @param int $age
     * @return Usr
     */
    public function setAge($age)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Get age.
     *
     * @return int
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * Set address1.
     *
     * @param string $address1
     * @return Usr
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * Get address1.
     *
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set address2.
     *
     * @param string $address2
     * @return Usr
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get address2.
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set city.
     *
     * @param string $city
     * @return Usr
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set zip.
     *
     * @param string $zip
     * @return Usr
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip.
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set country.
     *
     * @param string $country
     * @return Usr
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set title.
     *
     * @param string $title
     * @return Usr
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set company.
     *
     * @param string $company
     * @return Usr
     */
    public function setCompany($company)
    {
        $this->company = htmlspecialchars($company); // compatibility with old code, value should be escaped in database

        return $this;
    }

    /**
     * Get company.
     *
     * @return string
     */
    public function getCompany()
    {
        return htmlspecialchars_decode($this->company); // compatibility with old code, value should be escaped in database
    }

    /**
     * Set phone1.
     *
     * @param string $phone1
     * @return Usr
     */
    public function setPhone1($phone1)
    {
        $this->phone1 = $phone1;

        return $this;
    }

    /**
     * Get phone1.
     *
     * @return string
     */
    public function getPhone1()
    {
        return $this->phone1;
    }

    /**
     * Set isnewssubscriber.
     *
     * @param bool $isnewssubscriber
     * @return Usr
     */
    public function setIsnewssubscriber($isnewssubscriber)
    {
        $this->isnewssubscriber = $isnewssubscriber;

        return $this;
    }

    /**
     * Get isnewssubscriber.
     *
     * @return bool
     */
    public function getIsnewssubscriber()
    {
        return $this->isnewssubscriber;
    }

    /**
     * Set creationdatetime.
     *
     * @param \DateTime $creationdatetime
     * @return Usr
     */
    public function setCreationdatetime($creationdatetime)
    {
        $this->creationdatetime = $creationdatetime;

        return $this;
    }

    /**
     * Get creationdatetime.
     *
     * @return \DateTime
     */
    public function getCreationdatetime()
    {
        return $this->creationdatetime;
    }

    /**
     * Set isenabled.
     *
     * @param bool $isenabled
     * @return Usr
     */
    public function setIsenabled($isenabled)
    {
        $this->isenabled = $isenabled;

        return $this;
    }

    /**
     * Get isenabled.
     *
     * @return bool
     */
    public function getIsenabled()
    {
        return $this->isenabled;
    }

    /**
     * Set lastlogondatetime.
     *
     * @param \DateTime $lastlogondatetime
     * @return Usr
     */
    public function setLastlogondatetime($lastlogondatetime)
    {
        $this->lastlogondatetime = $lastlogondatetime;

        return $this;
    }

    /**
     * Get lastlogondatetime.
     *
     * @return \DateTime
     */
    public function getLastlogondatetime()
    {
        return $this->lastlogondatetime;
    }

    /**
     * Set updatedate.
     *
     * @param \DateTime $updatedate
     * @return Usr
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
     * Set emailverified.
     *
     * @param int $emailverified
     * @return Usr
     */
    public function setEmailverified($emailverified)
    {
        $this->emailverified = $emailverified;

        return $this;
    }

    /**
     * Get emailverified.
     *
     * @return int
     */
    public function getEmailverified()
    {
        return $this->emailverified;
    }

    /**
     * Set logoncount.
     *
     * @param int $logoncount
     * @return Usr
     */
    public function setLogoncount($logoncount)
    {
        $this->logoncount = $logoncount;

        return $this;
    }

    /**
     * Get logoncount.
     *
     * @return int
     */
    public function getLogoncount()
    {
        return $this->logoncount;
    }

    /**
     * Set countryid.
     *
     * @param int $countryid
     * @return Usr
     */
    public function setCountryid($countryid)
    {
        $this->countryid = $countryid;

        return $this;
    }

    /**
     * Get countryid.
     *
     * @return int
     */
    public function getCountryid()
    {
        return $this->countryid;
    }

    /**
     * Set stateid.
     *
     * @param int $stateid
     * @return Usr
     */
    public function setStateid($stateid)
    {
        $this->stateid = $stateid;

        return $this;
    }

    /**
     * Get stateid.
     *
     * @return int
     */
    public function getStateid()
    {
        return $this->stateid;
    }

    /**
     * Set registrationip.
     *
     * @param string $registrationip
     * @return Usr
     */
    public function setRegistrationip($registrationip)
    {
        $this->registrationip = $registrationip;

        return $this;
    }

    /**
     * Get registrationip.
     *
     * @return string
     */
    public function getRegistrationip()
    {
        return $this->registrationip;
    }

    /**
     * Set lastlogonip.
     *
     * @param string $lastlogonip
     * @return Usr
     */
    public function setLastlogonip($lastlogonip)
    {
        $this->lastlogonip = $lastlogonip;

        return $this;
    }

    /**
     * Get lastlogonip.
     *
     * @return string
     */
    public function getLastlogonip()
    {
        return $this->lastlogonip;
    }

    /**
     * Set lastscreenwidth.
     *
     * @param int $lastscreenwidth
     * @return Usr
     */
    public function setLastscreenwidth($lastscreenwidth)
    {
        $this->lastscreenwidth = $lastscreenwidth;

        return $this;
    }

    /**
     * Get lastscreenwidth.
     *
     * @return int
     */
    public function getLastscreenwidth()
    {
        return $this->lastscreenwidth;
    }

    /**
     * Set lastscreenheight.
     *
     * @param int $lastscreenheight
     * @return Usr
     */
    public function setLastscreenheight($lastscreenheight)
    {
        $this->lastscreenheight = $lastscreenheight;

        return $this;
    }

    /**
     * Get lastscreenheight.
     *
     * @return int
     */
    public function getLastscreenheight()
    {
        return $this->lastscreenheight;
    }

    /**
     * Set lastuseragent.
     *
     * @param string $lastuseragent
     * @return Usr
     */
    public function setLastuseragent($lastuseragent)
    {
        $this->lastuseragent = $lastuseragent;

        return $this;
    }

    /**
     * Get lastuseragent.
     *
     * @return string
     */
    public function getLastuseragent()
    {
        return $this->lastuseragent;
    }

    /**
     * Set camefrom.
     *
     * @param int $camefrom
     * @return Usr
     */
    public function setCamefrom($camefrom)
    {
        $this->camefrom = $camefrom;

        return $this;
    }

    /**
     * Get camefrom.
     *
     * @return int
     */
    public function getCamefrom()
    {
        return $this->camefrom;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\SiteAd
     */
    public function getSiteAd()
    {
        return $this->SiteAd;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\SiteAd $SiteAd
     */
    public function setSiteAd($SiteAd)
    {
        $this->SiteAd = $SiteAd;
    }

    /**
     * Set accountlevel.
     *
     * @param int $accountlevel
     * @return Usr
     */
    public function setAccountlevel($accountlevel)
    {
        $this->accountlevel = $accountlevel;

        return $this;
    }

    /**
     * Get accountlevel.
     *
     * @return int
     */
    public function getAccountlevel()
    {
        if ($this->forcePlus) {
            return ACCOUNT_LEVEL_AWPLUS;
        }

        return $this->accountlevel;
    }

    /**
     * Set primaryfunctionality.
     *
     * @param int $primaryfunctionality
     * @return Usr
     */
    public function setPrimaryfunctionality($primaryfunctionality)
    {
        $this->primaryfunctionality = $primaryfunctionality;

        return $this;
    }

    /**
     * Get primaryfunctionality.
     *
     * @return int
     */
    public function getPrimaryfunctionality()
    {
        return $this->primaryfunctionality;
    }

    /**
     * Set emailtcsubscribe.
     *
     * @param int $emailtcsubscribe
     * @return Usr
     */
    public function setEmailtcsubscribe($emailtcsubscribe)
    {
        $this->emailtcsubscribe = $emailtcsubscribe;

        return $this;
    }

    /**
     * Get emailtcsubscribe.
     *
     * @return int
     */
    public function getEmailtcsubscribe()
    {
        return $this->emailtcsubscribe;
    }

    /**
     * Set defaulttab.
     *
     * @param string $defaulttab
     * @return Usr
     */
    public function setDefaulttab($defaulttab)
    {
        $this->defaulttab = $defaulttab;

        return $this;
    }

    /**
     * Get defaulttab.
     *
     * @return string
     */
    public function getDefaulttab()
    {
        return $this->defaulttab;
    }

    /**
     * Set goalclass.
     *
     * @param int $goalclass
     * @return Usr
     */
    public function setGoalclass($goalclass)
    {
        $this->goalclass = $goalclass;

        return $this;
    }

    /**
     * Get goalclass.
     *
     * @return int
     */
    public function getGoalclass()
    {
        return $this->goalclass;
    }

    /**
     * Set referer.
     *
     * @param string $referer
     * @return Usr
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer.
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set autogatherplans.
     *
     * @param bool $autogatherplans
     * @return Usr
     */
    public function setAutogatherplans($autogatherplans)
    {
        $this->autogatherplans = $autogatherplans;

        return $this;
    }

    /**
     * Get autogatherplans.
     *
     * @return bool
     */
    public function getAutogatherplans()
    {
        return $this->autogatherplans;
    }

    /**
     * Set publicplans.
     *
     * @param bool $publicplans
     * @return Usr
     */
    public function setPublicplans($publicplans)
    {
        $this->publicplans = $publicplans;

        return $this;
    }

    /**
     * Get publicplans.
     *
     * @return bool
     */
    public function getPublicplans()
    {
        return $this->publicplans;
    }

    /**
     * Set dateformat.
     *
     * @param int $dateformat
     * @return Usr
     */
    public function setDateformat($dateformat)
    {
        $this->dateformat = $dateformat;

        return $this;
    }

    /**
     * Get dateformat.
     *
     * @return int
     */
    public function getDateformat()
    {
        return $this->dateformat;
    }

    /**
     * Set thousandsseparator.
     *
     * @param string $thousandsseparator
     * @return Usr
     */
    public function setThousandsseparator($thousandsseparator)
    {
        $this->thousandsseparator = $thousandsseparator;

        return $this;
    }

    /**
     * Get thousandsseparator.
     *
     * @return string
     */
    public function getThousandsseparator()
    {
        return $this->thousandsseparator;
    }

    public function getDecimalPoint()
    {
        if (!isset(self::$decimalPoints[$this->thousandsseparator])) {
            return '.';
        }

        return self::$decimalPoints[$this->thousandsseparator];
    }

    /**
     * Set lockoutstart.
     *
     * @param \DateTime $lockoutstart
     * @return Usr
     */
    public function setLockoutstart($lockoutstart)
    {
        $this->lockoutstart = $lockoutstart;

        return $this;
    }

    /**
     * Get lockoutstart.
     *
     * @return \DateTime
     */
    public function getLockoutstart()
    {
        return $this->lockoutstart;
    }

    /**
     * Set loginattempts.
     *
     * @param int $loginattempts
     * @return Usr
     */
    public function setLoginattempts($loginattempts)
    {
        $this->loginattempts = $loginattempts;

        return $this;
    }

    /**
     * Get loginattempts.
     *
     * @return int
     */
    public function getLoginattempts()
    {
        return $this->loginattempts;
    }

    /**
     * Set homeairport.
     *
     * @param string $homeairport
     * @return Usr
     */
    public function setHomeairport($homeairport)
    {
        $this->homeairport = $homeairport;

        return $this;
    }

    /**
     * Get homeairport.
     *
     * @return string
     */
    public function getHomeairport()
    {
        return $this->homeairport;
    }

    /**
     * Set picturever.
     *
     * @param int $picturever
     * @return Usr
     */
    public function setPicturever($picturever)
    {
        $this->picturever = $picturever;

        return $this;
    }

    /**
     * Get picturever.
     *
     * @return int|UploadedFile
     */
    public function getPicturever()
    {
        return $this->picturever;
    }

    /**
     * Set pictureext.
     *
     * @param string $pictureext
     * @return Usr
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
     * Set refcode.
     *
     * @param string $refcode
     * @return Usr
     */
    public function setRefcode($refcode)
    {
        $this->refcode = $refcode;

        return $this;
    }

    /**
     * Get refcode.
     *
     * @return string
     */
    public function getRefcode()
    {
        return $this->refcode;
    }

    /**
     * Set accounts.
     *
     * @param int $accounts
     * @return Usr
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
     * Set providers.
     *
     * @param int $providers
     * @return Usr
     */
    public function setProviders($providers)
    {
        $this->providers = $providers;

        return $this;
    }

    /**
     * Get providers.
     *
     * @return int
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Set useragents.
     *
     * @param int $useragents
     * @return Usr
     */
    public function setUseragents($useragents)
    {
        $this->useragents = $useragents;

        return $this;
    }

    /**
     * Get useragents.
     *
     * @return int
     */
    public function getUseragents()
    {
        return $this->useragents;
    }

    /**
     * Set skin.
     *
     * @param string $skin
     * @return Usr
     */
    public function setSkin($skin)
    {
        $this->skin = $skin;

        return $this;
    }

    /**
     * Get skin.
     *
     * @return string
     */
    public function getSkin()
    {
        return $this->skin;
    }

    /**
     * Set browserkey.
     *
     * @param string $browserkey
     * @return Usr
     */
    public function setBrowserkey($browserkey)
    {
        $this->browserkey = $browserkey;

        return $this;
    }

    /**
     * Get browserkey.
     *
     * @return string
     */
    public function getBrowserkey()
    {
        return $this->browserkey;
    }

    /**
     * Set inbeta.
     *
     * @param bool $inbeta
     * @return Usr
     */
    public function setInbeta($inbeta)
    {
        $this->inbeta = $inbeta;

        return $this;
    }

    /**
     * Get inbeta.
     *
     * @return bool
     */
    public function getInbeta()
    {
        return $this->inbeta;
    }

    /**
     * Set savepassword.
     *
     * @param int $savepassword
     * @return Usr
     */
    public function setSavepassword($savepassword)
    {
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
     * Set betaapproved.
     *
     * @param bool $betaapproved
     * @return Usr
     */
    public function setBetaapproved($betaapproved)
    {
        $this->betaapproved = $betaapproved;

        return $this;
    }

    /**
     * Get betaapproved.
     *
     * @return bool
     */
    public function getBetaapproved()
    {
        return $this->betaapproved;
    }

    /**
     * Set oldemailverified.
     *
     * @param bool $oldemailverified
     * @return Usr
     */
    public function setOldemailverified($oldemailverified)
    {
        $this->oldemailverified = $oldemailverified;

        return $this;
    }

    /**
     * Get oldemailverified.
     *
     * @return bool
     */
    public function getOldemailverified()
    {
        return $this->oldemailverified;
    }

    /**
     * Set extensionversion.
     *
     * @param string $extensionversion
     * @return Usr
     */
    public function setExtensionversion($extensionversion)
    {
        $this->extensionversion = $extensionversion;

        return $this;
    }

    /**
     * Get extensionversion.
     *
     * @return string
     */
    public function getExtensionversion()
    {
        return $this->extensionversion;
    }

    /**
     * Set extensionbrowser.
     *
     * @param string $extensionbrowser
     * @return Usr
     */
    public function setExtensionbrowser($extensionbrowser)
    {
        $this->extensionbrowser = $extensionbrowser;

        return $this;
    }

    /**
     * Get extensionbrowser.
     *
     * @return string
     */
    public function getExtensionbrowser()
    {
        return $this->extensionbrowser;
    }

    /**
     * Set extensionlastusedate.
     *
     * @param \DateTime $extensionlastusedate
     * @return Usr
     */
    public function setExtensionlastusedate($extensionlastusedate)
    {
        $this->extensionlastusedate = $extensionlastusedate;

        return $this;
    }

    /**
     * Get extensionlastusedate.
     *
     * @return \DateTime
     */
    public function getExtensionlastusedate()
    {
        return $this->extensionlastusedate;
    }

    /**
     * Set lastemailreaddate.
     *
     * @param \DateTime $lastemailreaddate
     * @return Usr
     */
    public function setLastemailreaddate($lastemailreaddate)
    {
        $this->lastemailreaddate = $lastemailreaddate;

        return $this;
    }

    /**
     * Get lastemailreaddate.
     *
     * @return \DateTime
     */
    public function getLastemailreaddate()
    {
        return $this->lastemailreaddate;
    }

    /**
     * Set mismanagement.
     *
     * @param bool $mismanagement
     * @return Usr
     */
    public function setMismanagement($mismanagement)
    {
        $this->mismanagement = $mismanagement;

        return $this;
    }

    /**
     * Get mismanagement.
     *
     * @return bool
     */
    public function getMismanagement()
    {
        return $this->mismanagement;
    }

    /**
     * Set enablemobilelog.
     *
     * @param bool $enablemobilelog
     * @return Usr
     */
    public function setEnablemobilelog($enablemobilelog)
    {
        $this->enablemobilelog = $enablemobilelog;

        return $this;
    }

    /**
     * Get enablemobilelog.
     *
     * @return bool
     */
    public function getEnablemobilelog()
    {
        return $this->enablemobilelog;
    }

    /**
     * Set offershowdate.
     *
     * @param \DateTime $offershowdate
     * @return Usr
     */
    public function setOffershowdate($offershowdate)
    {
        $this->offershowdate = $offershowdate;

        return $this;
    }

    /**
     * Get offershowdate.
     *
     * @return \DateTime
     */
    public function getOffershowdate()
    {
        return $this->offershowdate;
    }

    /**
     * Set resetpasswordcode.
     *
     * @param string $resetpasswordcode
     * @return Usr
     */
    public function setResetpasswordcode($resetpasswordcode)
    {
        $this->resetpasswordcode = $resetpasswordcode;

        return $this;
    }

    /**
     * Get resetpasswordcode.
     *
     * @return string
     */
    public function getResetpasswordcode()
    {
        return $this->resetpasswordcode;
    }

    /**
     * Set resetpassworddate.
     *
     * @param \DateTime $resetpassworddate
     * @return Usr
     */
    public function setResetpassworddate($resetpassworddate)
    {
        $this->resetpassworddate = $resetpassworddate;

        return $this;
    }

    /**
     * Get resetpassworddate.
     *
     * @return \DateTime
     */
    public function getResetpassworddate()
    {
        return $this->resetpassworddate;
    }

    /**
     * Set itinerarycalendarcode.
     *
     * @param string $itinerarycalendarcode
     * @return Usr
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
     * Set accexpirecalendarcode.
     *
     * @param string $accexpirecalendarcode
     * @return Usr
     */
    public function setAccexpirecalendarcode($accexpirecalendarcode)
    {
        $this->accexpirecalendarcode = $accexpirecalendarcode;

        return $this;
    }

    /**
     * Get accexpirecalendarcode.
     *
     * @return string
     */
    public function getAccexpirecalendarcode()
    {
        return $this->accexpirecalendarcode;
    }

    /**
     * Set plansbuilddate.
     *
     * @param \DateTime $plansbuilddate
     * @return Usr
     */
    public function setPlansbuilddate($plansbuilddate)
    {
        $this->plansbuilddate = $plansbuilddate;

        return $this;
    }

    /**
     * Get plansbuilddate.
     *
     * @return \DateTime
     */
    public function getPlansbuilddate()
    {
        return $this->plansbuilddate;
    }

    /**
     * Set plansupdatedate.
     *
     * @param \DateTime $plansupdatedate
     * @return Usr
     */
    public function setPlansupdatedate($plansupdatedate)
    {
        $this->plansupdatedate = $plansupdatedate;

        return $this;
    }

    /**
     * Get plansupdatedate.
     *
     * @return \DateTime
     */
    public function getPlansupdatedate()
    {
        return $this->plansupdatedate;
    }

    /**
     * Set itineraryadddate.
     *
     * @param \DateTime $itineraryadddate
     * @return Usr
     */
    public function setItineraryadddate($itineraryadddate)
    {
        $this->itineraryadddate = $itineraryadddate;

        return $this;
    }

    /**
     * Get itineraryadddate.
     *
     * @return \DateTime
     */
    public function getItineraryadddate()
    {
        return $this->itineraryadddate;
    }

    /**
     * Set itineraryupdatedate.
     *
     * @param \DateTime $itineraryupdatedate
     * @return Usr
     */
    public function setItineraryupdatedate($itineraryupdatedate)
    {
        $this->itineraryupdatedate = $itineraryupdatedate;

        return $this;
    }

    /**
     * Get itineraryupdatedate.
     *
     * @return \DateTime
     */
    public function getItineraryupdatedate()
    {
        return $this->itineraryupdatedate;
    }

    /**
     * Set lastdesktoplogon.
     *
     * @param \DateTime $lastdesktoplogon
     * @return Usr
     */
    public function setLastdesktoplogon($lastdesktoplogon)
    {
        $this->lastdesktoplogon = $lastdesktoplogon;

        return $this;
    }

    /**
     * Get lastdesktoplogon.
     *
     * @return \DateTime
     */
    public function getLastdesktoplogon()
    {
        return $this->lastdesktoplogon;
    }

    /**
     * Set planschangedate.
     *
     * @param \DateTime $planschangedate
     * @return Usr
     */
    public function setPlanschangedate($planschangedate)
    {
        $this->planschangedate = $planschangedate;

        return $this;
    }

    /**
     * Get planschangedate.
     *
     * @return \DateTime
     */
    public function getPlanschangedate()
    {
        return $this->planschangedate;
    }

    /**
     * Set plansmaildate.
     *
     * @param \DateTime $plansmaildate
     * @return Usr
     */
    public function setPlansmaildate($plansmaildate)
    {
        $this->plansmaildate = $plansmaildate;

        return $this;
    }

    /**
     * Get plansmaildate.
     *
     * @return \DateTime
     */
    public function getPlansmaildate()
    {
        return $this->plansmaildate;
    }

    /**
     * Set PayPalRecurringProfileID.
     *
     * @param string $payPalRecurringProfileID
     * @return Usr
     */
    public function setPaypalrecurringprofileid($payPalRecurringProfileID)
    {
        $this->paypalrecurringprofileid = $payPalRecurringProfileID;

        return $this;
    }

    /**
     * Get PayPalRecurringProfileID.
     *
     * @return string
     */
    public function getPaypalrecurringprofileid()
    {
        return $this->paypalrecurringprofileid;
    }

    /**
     * Set ReferralID.
     *
     * @param string $referralID
     * @return Usr
     */
    public function setReferralid($referralID)
    {
        $this->referralid = $referralID;

        return $this;
    }

    /**
     * @return int
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @param int
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function clearSubscription(): void
    {
        $this->setSubscription(null);
        $this->setSubscriptionType(null);
        $this->setSubscriptionPeriod(null);
        $this->setSubscriptionPrice(null);
        $this->setPaypalrecurringprofileid(null);
        $this->setFirstSubscriptionCartItem(null);
        $this->setLastSubscriptionCartItem(null);
        $this->setIosReceipt(null);
        $this->setFailedRecurringPayments(0);
    }

    public function getSubscriptionType(): ?int
    {
        return $this->subscriptionType;
    }

    public function setSubscriptionType(?int $subscriptionType): self
    {
        $this->subscriptionType = $subscriptionType;

        return $this;
    }

    /**
     * Get ReferralID.
     *
     * @return string
     */
    public function getReferralid()
    {
        return $this->referralid;
    }

    /**
     * Set RecurringPaymentAmount.
     *
     * @param int $recurringPaymentAmount
     * @return Usr
     */
    public function setRecurringpaymentamount($recurringPaymentAmount)
    {
        $this->recurringpaymentamount = $recurringPaymentAmount;

        return $this;
    }

    /**
     * Get RecurringPaymentAmount.
     *
     * @return int
     */
    public function getRecurringpaymentamount()
    {
        return $this->recurringpaymentamount;
    }

    public function getPlusExpirationDate(): ?\DateTime
    {
        return $this->plusExpirationDate;
    }

    public function setPlusExpirationDate(?\DateTimeInterface $plusExpirationDate): self
    {
        $this->plusExpirationDate = $plusExpirationDate;

        return $this;
    }

    public function getAt201ExpirationDate(): ?\DateTime
    {
        return $this->at201ExpirationDate;
    }

    public function setAt201ExpirationDate(?\DateTime $at201ExpirationDate): self
    {
        $this->at201ExpirationDate = $at201ExpirationDate;

        return $this;
    }

    public function hasAt201Access(): bool
    {
        if ($this->at201ExpirationDate instanceof \DateTime) {
            return $this->at201ExpirationDate->getTimestamp() > time();
        }

        return false;
    }

    /**
     * Set socialadid.
     *
     * @return Usr
     */
    public function setSocialadid(?Socialad $socialadid = null)
    {
        $this->socialadid = $socialadid;

        return $this;
    }

    /**
     * Get socialadid.
     *
     * @return \AwardWallet\MainBundle\Entity\Socialad
     */
    public function getSocialadid()
    {
        return $this->socialadid;
    }

    /**
     * Set goalid.
     *
     * @return Usr
     */
    public function setGoalid(?Goal $goalid = null)
    {
        $this->goalid = $goalid;

        return $this;
    }

    /**
     * Get goalid.
     *
     * @return \AwardWallet\MainBundle\Entity\Goal
     */
    public function getGoalid()
    {
        return $this->goalid;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array($role, $this->getRoles());
    }

    public function getGroupCodes()
    {
        $groups = [];

        if (!is_null($this->groups)) {
            foreach ($this->groups as $grp) {
                $groups[] = $grp->getCode();
            }
        }

        return $groups;
    }

    /**
     * Add groups.
     *
     * @return Usr
     */
    public function addGroup(Sitegroup $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove groups.
     */
    public function removeGroup(Sitegroup $group)
    {
        $this->groups->removeElement($group);
    }

    public function clearGroups(): void
    {
        $this->groups->clear();
    }

    /**
     * Get Roles.
     *
     * @return string[] An array of roles
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->getGroupCodes() as $groupCode) {
            $roles[] = 'ROLE_' . $groupCode;
        }

        return $roles;
    }

    /**
     * Get Password.
     *
     * @return string Password
     */
    public function getPassword()
    {
        return $this->getPass();
    }

    /**
     * Get Salt.
     *
     * @return string Salt
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * Get Username.
     *
     * @return string Username
     */
    public function getUsername()
    {
        return $this->getLogin();
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        return md5($this->getUsername()) == md5($user->getUsername());
    }

    /**
     * Set BookerInfo.
     *
     * @return Usr
     */
    public function setBookerInfo(?AbBookerInfo $bookerInfo = null)
    {
        if ($bookerInfo) {
            $this->BookerInfo = new ArrayCollection([$bookerInfo]);
        } else {
            $this->BookerInfo = new ArrayCollection();
        }

        return $this;
    }

    /**
     * @return AbBookerInfo|null
     */
    public function getBookerInfo()
    {
        return $this->BookerInfo->count() ? $this->BookerInfo->first() : null;
    }

    /**
     * @return BusinessInfo
     */
    public function getBusinessInfo()
    {
        return $this->businessInfo;
    }

    /**
     * @param BusinessInfo $businessInfo
     */
    public function setBusinessInfo($businessInfo)
    {
        $this->businessInfo = $businessInfo;

        return $this;
    }

    public function isSiteAdmin()
    {
        return 7 == $this->getUserid();
    }

    public function getAvatarLink(string $size): ?string
    {
        return self::generateAvatarLink(
            $this->getUserid(),
            $this->getPicturever(),
            $this->getPictureext(),
            $size
        );
    }

    public static function generateAvatarLink($userId, $pictureVer, $pictureExt, $size): ?string
    {
        return !empty($pictureVer) ?
            PicturePath("/images/uploaded/user", $size, $userId, $pictureVer, $pictureExt, "file") :
            null;
    }

    public function isBooker()
    {
        // @TODO: valid approarch? should we instead watch for Group 'booking business' ?
        return is_null($this->getBookerInfo()) ? false : true;
    }

    public function isBusiness()
    {
        return $this->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS;
    }

    public function setLocale()
    {
        // TODO: implementation
    }

    public function getLocale()
    {
        $region = $this->getRegion();
        $lang = $this->getLanguage();

        if ($region) {
            if (in_array($lang, ['zh_CN', 'zh_TW']) || (strlen($lang) > 2)) {
                return $lang;
            }

            $locale = $lang . '_' . $region;
            $allLocales = Locales::getNames();

            if (array_key_exists($locale, $allLocales)) {
                return $locale;
            }
        }

        return $lang;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDefaultValues()
    {
        $this->creationdatetime = new \DateTime();
    }

    /**
     * @deprecated use repository method instead!
     * @see \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::isUserBusinessAdmin
     * @return bool
     */
    public function isBusinessAdmin()
    {
        DeprecationUtils::alert('Usr_isBusinessAdmin');
        $business = $this->getBusiness([ACCESS_ADMIN]);

        return isset($business);
    }

    /**
     * @return Usr|null
     * @deprecated use repository method instead!
     * @see \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::getBusinessByUser
     */
    public function getBusiness(array $accessLevels = [ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY])
    {
        $key = $this->getBusinessKeyByLevel($accessLevels);

        if (!array_key_exists($key, $this->_BusinessByLevel)) {
            DeprecationUtils::alert('Usr_getBusiness_uncached');
            $this->_BusinessByLevel[$key] = null;

            foreach ($this->connections as $connection) {
                /** @var Useragent $connection */
                if ($connection->getIsapproved() == 1 && in_array($connection->getAccesslevel(), $accessLevels)) {
                    $linkedUser = $connection->getClientid();

                    if (!empty($linkedUser) && $linkedUser->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS) {
                        $this->_BusinessByLevel[$key] = $linkedUser;
                    }
                }
            }
        }

        return $this->_BusinessByLevel[$key];
    }

    /**
     * @internal this method used for memoization purposes only! Use UsrRepository methods!
     * @see \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::getBusinessByUser
     * @return bool
     */
    public function _hasBusinessByLevel(array $accessLevels)
    {
        return array_key_exists($this->getBusinessKeyByLevel($accessLevels), $this->_BusinessByLevel);
    }

    /**
     * @internal this method used for memoization purposes only! Use UsrRepository methods!
     * @see \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::getBusinessByUser
     * @return Usr|null
     */
    public function _getBusinessByLevel(array $accessLevels)
    {
        return $this->_BusinessByLevel[$this->getBusinessKeyByLevel($accessLevels)];
    }

    /**
     * @internal this method used for memoization purposes only! Use UsrRepository methods!
     * @see \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::getBusinessByUser
     * @return Usr|null
     */
    public function _setBusinessByLevel(?Usr $business = null, array $accessLevels)
    {
        return $this->_BusinessByLevel[$this->getBusinessKeyByLevel($accessLevels)] = $business;
    }

    /**
     * @return bool
     */
    public function isConnectedBusinessHasRole($role)
    {
        if ($this->isBusiness()) {
            return false;
        }

        return $this->getConnections()->exists(function ($k, $connection) use ($role) {
            /** @var Useragent $connection */
            $linkedUser = $connection->getClientid();

            return $connection->getIsapproved() == 1
                && !empty($linkedUser)
                && $linkedUser->isBusiness()
                && $linkedUser->hasRole($role);
        });
    }

    /**
     * @return Usr|null
     * @deprecated use repository method instead!
     * @see \AwardWallet\MainBundle\Entity\Repositories\UsrRepository::getBookerByUser
     */
    public function getBooker()
    {
        DeprecationUtils::alert('Usr_getBooker');
        $business = $this->getBusiness();

        return ($business && $business->isBooker()) ? $business : null;
    }

    public function getFullName()
    {
        if ($this->getAccountlevel() == ACCOUNT_LEVEL_BUSINESS) {
            return $this->getCompany();
        } else {
            return trim($this->getFirstName() . ($this->getMidname() ? (' ' . $this->getMidname()) : '') . ' ' . $this->getLastName());
        }
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return ('' === $this->firstname) ? $this->login : $this->firstname;
    }

    /**
     * you can use this method as:
     * 	findFamilyMemberByName("Alexi", "Vereschaga")
     *  or
     *  findFamilyMemberByName("Alexi Vereschaga", null).
     *
     * @param string $firstName
     * @param string|null $lastName
     * @return Useragent|null
     */
    public function findFamilyMemberByName($firstName, $lastName)
    {
        $name = $firstName;

        if (!empty($lastName)) {
            $name .= " " . $lastName;
        }

        foreach ($this->connections as $connection) {
            /** @var Useragent $connection */
            if ($connection->getClientid() === null
            && strcasecmp($connection->getFirstname() . " " . $connection->getLastname(), $name) == 0) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * find family member by email alias, for addresses like SiteAdmin.KMankovich@awardwallet.com
     * KMankovich - alias.
     *
     * @param string $alias
     * @return Useragent|null
     */
    public function findFamilyMemberByAlias($alias)
    {
        foreach ($this->connections as $connection) {
            /** @var Useragent $connection */
            if ($connection->getClientid() === null
            && strcasecmp($connection->getAlias(), $alias) == 0) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * @param int $userId
     * @return Useragent|null
     */
    public function findUserAgent($userId)
    {
        foreach ($this->connections as $connection) {
            /** @var Useragent $connection */
            if ($connection->getClientid() && $connection->getClientid()->getUserid() === $userId) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * get email address, where to send reservations.
     *
     * @param Useragent|null $familyMember
     */
    public function getPlansEmail($familyMember)
    {
        $result = $this->getLogin();

        if (!empty($familyMember)) {
            $result .= "." . $familyMember->getAlias();
        }

        return $result . '@awardwallet.com';
    }

    /**
     * Set GoogleAuthSecret.
     *
     * @param string $googleAuthSecret
     * @return Usr
     */
    public function setGoogleAuthSecret($googleAuthSecret)
    {
        $this->googleAuthSecret = $googleAuthSecret;

        return $this;
    }

    /**
     * Get GoogleAuthSecret.
     *
     * @return string
     */
    public function getGoogleAuthSecret()
    {
        return $this->googleAuthSecret;
    }

    /**
     * @return string
     */
    public function getGoogleAuthRecoveryCode()
    {
        return $this->googleAuthRecoveryCode;
    }

    /**
     * @param string $googleAuthRecoveryCode
     * @return $this
     */
    public function setGoogleAuthRecoveryCode($googleAuthRecoveryCode)
    {
        $this->googleAuthRecoveryCode = $googleAuthRecoveryCode;

        return $this;
    }

    /**
     * @return bool
     */
    public function enabled2Factor()
    {
        return !is_null($this->googleAuthSecret) && !is_null($this->googleAuthRecoveryCode);
    }

    public function twoFactorAllowed(): bool
    {
        return !empty($this->pass);
    }

    /**
     * Add connections.
     *
     * @return Usr
     */
    public function addConnection(Useragent $connections)
    {
        $this->connections[] = $connections;

        return $this;
    }

    /**
     * Remove connections.
     */
    public function removeConnection(Useragent $connections)
    {
        $this->connections->removeElement($connections);
    }

    /**
     * Get connections.
     *
     * @return \Doctrine\Common\Collections\Collection|Useragent[]
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Bad performance in business.
     *
     * @return Useragent[]
     */
    public function getPossibleOwners()
    {
        $result = [];

        foreach ($this->connections as $agent) {
            if ($agent->isPossibleOwner()) {
                $result[] = $agent;
            }
        }
        sort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }

    /**
     * @return Useragent[]
     */
    public function getFamilyMembers()
    {
        $result = [];

        foreach ($this->connections as $agent) {
            if ($agent->isFamilyMember()) {
                $result[] = $agent;
            }
        }
        sort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }

    /**
     * @return Useragent[]
     */
    public function getConnectedUsers()
    {
        $result = [];

        foreach ($this->connections as $agent) {
            if (!$agent->isFamilyMember()) {
                $result[] = $agent;
            }
        }
        sort($result, SORT_STRING | SORT_FLAG_CASE);

        return $result;
    }

    public function getEmailVerificationHash()
    {
        return sha1($this->userid . $this->creationdatetime->getTimestamp() . $this->email);
    }

    /**
     * Add cart.
     *
     * @return Usr
     */
    public function addCart(Cart $cart)
    {
        $cart->setUser($this);
        $this->carts[] = $cart;

        return $this;
    }

    /**
     * Remove cart.
     */
    public function removeCart(Cart $cart)
    {
        $this->carts->removeElement($cart);
    }

    public function getCarts()
    {
        return $this->carts;
    }

    /**
     * Check the user paid for at least one type of item or not.
     *
     * @param int|array $types
     * @return bool
     */
    public function paidFor($types)
    {
        if (!is_array($types)) {
            $types = [$types];
        }
        $carts = $this->getCarts();

        return $carts->exists(function ($k, $cart) use ($types) {
            /** @var Cart $cart */
            return $cart->isPaid() && $cart->hasItemsByType($types);
        });
    }

    /**
     * Check the user has used the coupon.
     *
     * @return bool
     */
    public function usedCoupon(Coupon $coupon)
    {
        return $this->getCarts()->exists(function ($k, $cart) use ($coupon) {
            /** @var Cart $cart */
            return
                $cart->isPaid()
                && $cart->getCoupon() == $coupon;
        });
    }

    /**
     * Can I order onecard for $5 ? offer valid for users auto-charged within a week.
     *
     * @return bool
     */
    public function eligibleForOnecard()
    {
        // refs #13333
        // discount no longer applies
        return false;
    }

    /**
     * Add business transaction.
     *
     * @return Usr
     */
    public function addBusinessTransaction(BusinessTransaction $transaction)
    {
        $transaction->setUser($this);
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Remove business transaction.
     */
    public function removeBusinessTransaction(BusinessTransaction $transaction)
    {
        $this->transactions->removeElement($transaction);
    }

    public function getBusinessTransactions()
    {
        return $this->transactions;
    }

    /**
     * @return Usr
     */
    public function getDefaultBooker($fallBackToBusiness = false)
    {
        if (!empty($this->DefaultBooker) || !$fallBackToBusiness) {
            return $this->DefaultBooker;
        }

        return $this->OwnedByBusiness;
    }

    /**
     * @param Usr $DefaultBooker
     */
    public function setDefaultBooker($DefaultBooker)
    {
        $this->DefaultBooker = $DefaultBooker;
    }

    /**
     * @return Usr
     */
    public function getOwnedByBusiness()
    {
        return $this->OwnedByBusiness;
    }

    /**
     * @param Usr $OwnedByBusiness
     */
    public function setOwnedByBusiness($OwnedByBusiness)
    {
        $this->OwnedByBusiness = $OwnedByBusiness;

        if (empty($OwnedByBusiness)) {
            $this->setOwnedByManager(null);
        } else {
            if ($OwnedByBusiness->isBooker()) {
                $this->setDefaultBooker($OwnedByBusiness);
            }
        }
    }

    /**
     * @return Usr
     */
    public function getOwnedByManager()
    {
        return $this->OwnedByManager;
    }

    /**
     * @param Usr $OwnedByManager
     */
    public function setOwnedByManager($OwnedByManager)
    {
        $this->OwnedByManager = $OwnedByManager;
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize([
            $this->userid,
            $this->login,
            $this->email,
            $this->pass,
            $this->googleAuthSecret,
        ]);
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        [
            $this->userid,
            $this->login,
            $this->email,
            $this->pass,
            $this->googleAuthSecret
        ] = unserialize($serialized);
    }

    /**
     * @return int
     */
    public function getFailedRecurringPayments()
    {
        return $this->failedRecurringPayments;
    }

    /**
     * @param int $failedRecurringPayments
     * @return Usr
     */
    public function setFailedRecurringPayments($failedRecurringPayments)
    {
        $this->failedRecurringPayments = $failedRecurringPayments;

        return $this;
    }

    /**
     * @return Useragent
     */
    public function getConnectionWith(Usr $user)
    {
        if ($this->accountlevel === ACCOUNT_LEVEL_BUSINESS) {
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->eq("clientid", $user));
            $connections = $this->connections->matching($criteria);
        } else {
            $connections = $this->connections->filter(function (Useragent $agent) use ($user) {
                $client = $agent->getClientid();

                return !empty($client) && $client->getUserid() == $user->getUserid();
            });
        }

        if ($connections->count() != 1) {
            return null;
        }

        return $connections->first();
    }

    /**
     * @return bool
     */
    public function isConnectedTo(Owner $owner)
    {
        if ($this !== $owner->getUser() && null === $this->getConnectionWith($owner->getUser())) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $default
     * @return \DateTimeZone
     */
    public function getDateTimeZone($default = 'Etc/GMT')
    {
        if (empty($this->dateTimeZone)) {
            $this->dateTimeZone = new \DateTimeZone($default);
        }

        return $this->dateTimeZone;
    }

    public function getProviderFilter($field = "p.State")
    {
        $filter = "$field > 0 or $field is null";

        if ($this->betaapproved) {
            $filter .= " or $field = " . PROVIDER_IN_BETA;
        }

        if ($this->hasRole('ROLE_STAFF')) {
            $filter .= " or $field = " . PROVIDER_TEST;
        }

        return '(' . $filter . ')';
    }

    /**
     * @return Usr
     */
    public function getBetaInviter()
    {
        return $this->betaInviter;
    }

    /**
     * @return $this
     */
    public function setBetaInviter(Usr $user)
    {
        $this->betaInviter = $user;

        return $this;
    }

    /**
     * @return int
     */
    public function getBetaInvitesCount()
    {
        return $this->betaInvitesCount;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function setBetaInvitesCount($count)
    {
        $this->betaInvitesCount = $count;

        return $this;
    }

    /**
     * Callback validator, company != login in business registration.
     *
     * @Assert\Callback(groups="business_register")
     */
    public function validate(ExecutionContextInterface $context)
    {
        if (strtolower($this->getCompany()) == strtolower($this->getLogin())) {
            $context->buildViolation('company.username.same')
            ->atPath('company')
            ->addViolation();
        }
    }

    public function getItineraryForwardingEmail($host = 'email.AwardWallet.com')
    {
        return sprintf('%s@%s', $this->login, $host);
    }

    public function getAvatarSrc()
    {
        $ver = $this->getPicturever();

        return !empty($ver) ? PicturePath("/images/uploaded/user", "small", $this->getUserid(), $this->getPicturever(), $this->getPictureext(), "file") : "";
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        if (strlen($this->region) > 2) {
            return substr($this->region, -2, 2);
        }

        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return UserQuestion[]|Collection
     */
    public function getSecurityQuestions()
    {
        return $this->securityQuestions;
    }

    /**
     * @param UserQuestion[]|Collection $questions
     */
    public function setSecurityQuestions($questions): self
    {
        $this->securityQuestions = $questions;

        return $this;
    }

    /**
     * @return TimelineShare[]|PersistentCollection
     */
    public function getSharedTimelines()
    {
        return $this->sharedTimelines;
    }

    public function getLastKnownIp()
    {
        return $this->getLastlogonip() ? $this->getLastlogonip() : $this->getRegistrationip();
    }

    /**
     * @return TimelineShare|bool
     */
    public function getTimelineShareWith(Usr $whose, ?Useragent $whoseFamilyMember = null)
    {
        $connection = $this->getConnectionWith($whose);

        if (!empty($connection) && $connection->isItinerariesShared()) {
            foreach ($connection->getSharedTimelines() as $sharedTimeline) {
                if (empty($whoseFamilyMember)) {
                    if (empty($sharedTimeline->getFamilyMember())) {
                        return $sharedTimeline;
                    }
                } else {
                    if (!empty($sharedTimeline->getFamilyMember())
                        && $sharedTimeline->getFamilyMember()->getUseragentid() == $whoseFamilyMember->getUseragentid()) {
                        return $sharedTimeline;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getInviteCouponsCorrection()
    {
        return $this->inviteCouponsCorrection;
    }

    public function setEmailexpiration(int $emailexpiration)
    {
        $this->emailexpiration = $emailexpiration;

        return $this;
    }

    public function getEmailexpiration(): int
    {
        return $this->emailexpiration;
    }

    /**
     * Set emailrewards.
     *
     * @param int $emailrewards
     * @return Usr
     */
    public function setEmailrewards($emailrewards)
    {
        $this->emailrewards = $emailrewards;

        return $this;
    }

    /**
     * Get emailrewards.
     *
     * @return int
     */
    public function getEmailrewards()
    {
        return $this->emailrewards;
    }

    /**
     * Set emailnewplans.
     *
     * @param bool $emailnewplans
     * @return Usr
     */
    public function setEmailnewplans($emailnewplans)
    {
        $this->emailnewplans = $emailnewplans;

        return $this;
    }

    /**
     * Get emailnewplans.
     *
     * @return bool
     */
    public function getEmailnewplans()
    {
        return $this->emailnewplans;
    }

    /**
     * Set emailplanschanges.
     *
     * @param bool $emailplanschanges
     * @return Usr
     */
    public function setEmailplanschanges($emailplanschanges)
    {
        $this->emailplanschanges = $emailplanschanges;

        return $this;
    }

    /**
     * Get emailplanschanges.
     *
     * @return bool
     */
    public function getEmailplanschanges()
    {
        return $this->emailplanschanges;
    }

    /**
     * Set checkinreminder.
     *
     * @param bool $checkinreminder
     * @return Usr
     */
    public function setCheckinreminder($checkinreminder)
    {
        $this->checkinreminder = $checkinreminder;

        return $this;
    }

    /**
     * Get checkinreminder.
     *
     * @return bool
     */
    public function getCheckinreminder()
    {
        return $this->checkinreminder;
    }

    /**
     * @return bool
     */
    public function isEmailBookingMessages()
    {
        return $this->emailBookingMessages;
    }

    /**
     * @param bool $emailBookingMessages
     * @return Usr
     */
    public function setEmailBookingMessages($emailBookingMessages)
    {
        $this->emailBookingMessages = $emailBookingMessages;

        return $this;
    }

    /**
     * Set emailproductupdates.
     *
     * @param bool $emailproductupdates
     * @return Usr
     */
    public function setEmailproductupdates($emailproductupdates)
    {
        $this->emailproductupdates = $emailproductupdates;

        return $this;
    }

    /**
     * Get emailproductupdates.
     *
     * @return bool
     */
    public function getEmailproductupdates()
    {
        return $this->emailproductupdates;
    }

    /**
     * Set emailoffers.
     *
     * @param bool $emailoffers
     * @return Usr
     */
    public function setEmailoffers($emailoffers)
    {
        $this->emailoffers = $emailoffers;

        return $this;
    }

    /**
     * Get emailoffers.
     *
     * @return bool
     */
    public function getEmailoffers()
    {
        return $this->emailoffers;
    }

    public function getEmailNewBlogPosts(): int
    {
        return $this->emailNewBlogPosts;
    }

    public function setEmailNewBlogPosts(int $emailNewBlogPosts): self
    {
        $this->emailNewBlogPosts = $emailNewBlogPosts;

        return $this;
    }

    /**
     * Set emailInviteeReg.
     *
     * @param bool $emailInviteeReg
     * @return Usr
     */
    public function setEmailInviteeReg($emailInviteeReg)
    {
        $this->emailInviteeReg = $emailInviteeReg;

        return $this;
    }

    /**
     * Get emailInviteeReg.
     *
     * @return bool
     */
    public function getEmailInviteeReg()
    {
        return $this->emailInviteeReg;
    }

    /**
     * @return bool
     */
    public function isEmailConnectedAlert()
    {
        return $this->emailConnectedAlert;
    }

    /**
     * @param bool $emailConnectedAlert
     * @return Usr
     */
    public function setEmailConnectedAlert($emailConnectedAlert)
    {
        $this->emailConnectedAlert = $emailConnectedAlert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailFamilyMemberAlert()
    {
        return $this->emailFamilyMemberAlert;
    }

    /**
     * @param bool $emailFamilyMemberAlert
     * @return Usr
     */
    public function setEmailFamilyMemberAlert($emailFamilyMemberAlert)
    {
        $this->emailFamilyMemberAlert = $emailFamilyMemberAlert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpDisableAll()
    {
        return $this->wpDisableAll;
    }

    /**
     * @param bool $wpDisableAll
     * @return $this
     */
    public function setWpDisableAll($wpDisableAll)
    {
        $this->wpDisableAll = $wpDisableAll;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpNewPlans()
    {
        return $this->wpNewPlans;
    }

    /**
     * @param bool $wpNewPlans
     * @return $this
     */
    public function setWpNewPlans($wpNewPlans)
    {
        $this->wpNewPlans = $wpNewPlans;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpPlanChanges()
    {
        return $this->wpPlanChanges;
    }

    /**
     * @param bool $wpPlanChanges
     * @return $this
     */
    public function setWpPlanChanges($wpPlanChanges)
    {
        $this->wpPlanChanges = $wpPlanChanges;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpProductUpdates()
    {
        return $this->wpProductUpdates;
    }

    /**
     * @param bool $wpProductUpdates
     * @return $this
     */
    public function setWpProductUpdates($wpProductUpdates)
    {
        $this->wpProductUpdates = $wpProductUpdates;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpOffers()
    {
        return $this->wpOffers;
    }

    /**
     * @param bool $wpOffers
     * @return $this
     */
    public function setWpOffers($wpOffers)
    {
        $this->wpOffers = $wpOffers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpExpire()
    {
        return $this->wpExpire;
    }

    /**
     * @param bool $wpExpire
     * @return $this
     */
    public function setWpExpire($wpExpire)
    {
        $this->wpExpire = $wpExpire;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpBookingMessages()
    {
        return $this->wpBookingMessages;
    }

    /**
     * @param bool $wpBookingMessages
     * @return $this
     */
    public function setWpBookingMessages($wpBookingMessages)
    {
        $this->wpBookingMessages = $wpBookingMessages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpNewBlogPosts()
    {
        return $this->wpNewBlogPosts;
    }

    /**
     * @param bool $wpNewBlogPosts
     * @return $this
     */
    public function setWpNewBlogPosts($wpNewBlogPosts)
    {
        $this->wpNewBlogPosts = $wpNewBlogPosts;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpInviteeReg()
    {
        return $this->wpInviteeReg;
    }

    /**
     * @param bool $wpInviteeReg
     * @return Usr
     */
    public function setWpInviteeReg($wpInviteeReg)
    {
        $this->wpInviteeReg = $wpInviteeReg;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpConnectedAlert()
    {
        return $this->wpConnectedAlert;
    }

    /**
     * @param bool $wpConnectedAlert
     * @return Usr
     */
    public function setWpConnectedAlert($wpConnectedAlert)
    {
        $this->wpConnectedAlert = $wpConnectedAlert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpFamilyMemberAlert()
    {
        return $this->wpFamilyMemberAlert;
    }

    /**
     * @param bool $wpFamilyMemberAlert
     * @return Usr
     */
    public function setWpFamilyMemberAlert($wpFamilyMemberAlert)
    {
        $this->wpFamilyMemberAlert = $wpFamilyMemberAlert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpRewardsActivity()
    {
        return $this->wpRewardsActivity;
    }

    /**
     * @param bool $wpRewardsActivity
     * @return $this
     */
    public function setWpRewardsActivity($wpRewardsActivity)
    {
        $this->wpRewardsActivity = $wpRewardsActivity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWpCheckins()
    {
        return $this->wpCheckins;
    }

    /**
     * @param bool $wpCheckins
     * @return $this
     */
    public function setWpCheckins($wpCheckins)
    {
        $this->wpCheckins = $wpCheckins;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpDisableAll()
    {
        return $this->mpDisableAll;
    }

    /**
     * @param bool $mpDisableAll
     * @return Usr
     */
    public function setMpDisableAll($mpDisableAll)
    {
        $this->mpDisableAll = $mpDisableAll;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpExpire()
    {
        return $this->mpExpire;
    }

    /**
     * @param bool $mpExpire
     * @return Usr
     */
    public function setMpExpire($mpExpire)
    {
        $this->mpExpire = $mpExpire;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpRewardsActivity()
    {
        return $this->mpRewardsActivity;
    }

    /**
     * @param bool $mpRewardsActivity
     * @return Usr
     */
    public function setMpRewardsActivity($mpRewardsActivity)
    {
        $this->mpRewardsActivity = $mpRewardsActivity;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpNewPlans()
    {
        return $this->mpNewPlans;
    }

    /**
     * @param bool $mpNewPlans
     * @return Usr
     */
    public function setMpNewPlans($mpNewPlans)
    {
        $this->mpNewPlans = $mpNewPlans;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpPlanChanges()
    {
        return $this->mpPlanChanges;
    }

    /**
     * @param bool $mpPlanChanges
     * @return Usr
     */
    public function setMpPlanChanges($mpPlanChanges)
    {
        $this->mpPlanChanges = $mpPlanChanges;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpCheckins()
    {
        return $this->mpCheckins;
    }

    /**
     * @param bool $mpCheckins
     * @return Usr
     */
    public function setMpCheckins($mpCheckins)
    {
        $this->mpCheckins = $mpCheckins;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpBookingMessages()
    {
        return $this->mpBookingMessages;
    }

    /**
     * @param bool $mpBookingMessages
     * @return Usr
     */
    public function setMpBookingMessages($mpBookingMessages)
    {
        $this->mpBookingMessages = $mpBookingMessages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpProductUpdates()
    {
        return $this->mpProductUpdates;
    }

    /**
     * @param bool $mpProductUpdates
     * @return Usr
     */
    public function setMpProductUpdates($mpProductUpdates)
    {
        $this->mpProductUpdates = $mpProductUpdates;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpOffers()
    {
        return $this->mpOffers;
    }

    /**
     * @param bool $mpOffers
     * @return Usr
     */
    public function setMpOffers($mpOffers)
    {
        $this->mpOffers = $mpOffers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpNewBlogPosts()
    {
        return $this->mpNewBlogPosts;
    }

    /**
     * @param bool $mpNewBlogPosts
     * @return Usr
     */
    public function setMpNewBlogPosts($mpNewBlogPosts)
    {
        $this->mpNewBlogPosts = $mpNewBlogPosts;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpInviteeReg()
    {
        return $this->mpInviteeReg;
    }

    /**
     * @param bool $mpInviteeReg
     * @return Usr
     */
    public function setMpInviteeReg($mpInviteeReg)
    {
        $this->mpInviteeReg = $mpInviteeReg;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpConnectedAlert()
    {
        return $this->mpConnectedAlert;
    }

    /**
     * @param bool $mpConnectedAlert
     * @return Usr
     */
    public function setMpConnectedAlert($mpConnectedAlert)
    {
        $this->mpConnectedAlert = $mpConnectedAlert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpFamilyMemberAlert()
    {
        return $this->mpFamilyMemberAlert;
    }

    /**
     * @param bool $mpFamilyMemberAlert
     * @return Usr
     */
    public function setMpFamilyMemberAlert($mpFamilyMemberAlert)
    {
        $this->mpFamilyMemberAlert = $mpFamilyMemberAlert;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMpRetailCards()
    {
        return $this->mpRetailCards;
    }

    /**
     * @param bool $mpRetailCards
     * @return Usr
     */
    public function setMpRetailCards($mpRetailCards)
    {
        $this->mpRetailCards = $mpRetailCards;

        return $this;
    }

    /**
     * @return string
     */
    public function getIosReceipt()
    {
        return $this->iosReceipt;
    }

    /**
     * @param string $iosReceipt
     * @return Usr
     */
    public function setIosReceipt($iosReceipt)
    {
        $this->iosReceipt = $iosReceipt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIosRestoredReceipt()
    {
        return $this->iosRestoredReceipt;
    }

    /**
     * @param bool $iosRestoredReceipt
     * @return Usr
     */
    public function setIosRestoredReceipt($iosRestoredReceipt)
    {
        $this->iosRestoredReceipt = $iosRestoredReceipt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDiscountedUpgradeBefore()
    {
        return $this->discountedUpgradeBefore;
    }

    /**
     * @param \DateTime $discountedUpgradeBefore
     */
    public function setDiscountedUpgradeBefore($discountedUpgradeBefore)
    {
        $this->discountedUpgradeBefore = $discountedUpgradeBefore;
    }

    /**
     * @return bool
     */
    public function isSplashAdsDisabled()
    {
        return $this->splashAdsDisabled;
    }

    /**
     * @param bool $splashAdsDisabled
     */
    public function setSplashAdsDisabled($splashAdsDisabled)
    {
        $this->splashAdsDisabled = $splashAdsDisabled;
    }

    /**
     * @return bool
     */
    public function isLinkAdsDisabled()
    {
        return $this->linkAdsDisabled;
    }

    /**
     * @param bool $linkAdsDisabled
     */
    public function setLinkAdsDisabled($linkAdsDisabled)
    {
        $this->linkAdsDisabled = $linkAdsDisabled;
    }

    /**
     * @return bool
     */
    public function isListAdsDisabled()
    {
        return $this->listAdsDisabled;
    }

    /**
     * @param bool $listAdsDisabled
     */
    public function setListAdsDisabled($listAdsDisabled)
    {
        $this->listAdsDisabled = $listAdsDisabled;
    }

    public function isBlogPostAds(): bool
    {
        return $this->isBlogPostAds;
    }

    public function setIsBlogPostAds(bool $isBlogPostAds): self
    {
        $this->isBlogPostAds = $isBlogPostAds;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAwPlus()
    {
        return $this->accountlevel == ACCOUNT_LEVEL_AWPLUS;
    }

    public function isFree()
    {
        return $this->accountlevel == ACCOUNT_LEVEL_FREE;
    }

    public function setPromo500k($val)
    {
        $this->promo500k = $val;

        return $this;
    }

    public function getSearchHints(): ?array
    {
        return $this->searchHints;
    }

    public function setSearchHints(array $data): self
    {
        $this->searchHints = $data;

        return $this;
    }

    /**
     * Массив со следующей структурой:
     * ```php
     * [
     *     'oauth_request_token' => '',
     *     'oauth_request_secret' => '',
     *     'oauth_access_token' => '',
     *     'oauth_access_secret' => ''
     * ]
     * ```.
     */
    public function getTripitOauthToken(): ?array
    {
        return $this->tripitOauthToken;
    }

    public function setTripitOauthToken(array $data): self
    {
        $this->tripitOauthToken = $data;

        return $this;
    }

    public function getTripitLastSync(): ?\DateTime
    {
        return $this->tripitLastSync;
    }

    public function setTripitLastSync(?\DateTime $tripitLastSync): self
    {
        $this->tripitLastSync = $tripitLastSync;

        return $this;
    }

    public function getZipCodeUpdateDate(): ?\DateTime
    {
        return $this->zipCodeUpdateDate;
    }

    public function setZipCodeUpdateDate(?\DateTime $zipCodeUpdateDate): self
    {
        $this->zipCodeUpdateDate = $zipCodeUpdateDate;

        return $this;
    }

    public function isFraud()
    {
        return $this->fraud;
    }

    public function setFraud($val)
    {
        $this->fraud = $val;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getChangePasswordDate()
    {
        return $this->changePasswordDate;
    }

    public function setChangePasswordDate(\DateTime $changePasswordDate)
    {
        $this->changePasswordDate = $changePasswordDate;

        return $this;
    }

    public function getChangePasswordMethod(): int
    {
        return $this->changePasswordMethod;
    }

    public function setChangePasswordMethod(int $changePasswordMethod)
    {
        $this->changePasswordMethod = $changePasswordMethod;

        return $this;
    }

    public function forceAwPlus()
    {
        $this->forcePlus = true;
    }

    /**
     * @return \DateTime|null
     */
    public function getTripAlertsStartDate()
    {
        return $this->tripAlertsStartDate;
    }

    /**
     * @return string|null
     */
    public function getTripAlertsHash()
    {
        return $this->tripAlertsHash;
    }

    /**
     * @return MobileDevice[]
     */
    public function getDevices(): array
    {
        return $this->devices;
    }

    /**
     * @return $this
     */
    public function addDevice(MobileDevice $device)
    {
        if (!$this->devices->contains($device)) {
            $this->devices->add($device);
            $device->setUser($this);
        }

        return $this;
    }

    public function hasMobileDevices(): bool
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->in('deviceType', MobileDevice::TYPES_MOBILE));

        return !$this->devices->matching($criteria)->isEmpty();
    }

    public function isPasswordChangedByResetLinkAfterLastLogon(): bool
    {
        return
            (null !== $this->lastlogondatetime)
            && (null !== $this->changePasswordDate)
            && ($this->changePasswordMethod == self::CHANGE_PASSWORD_METHOD_LINK)
            && ($this->lastlogondatetime->getTimestamp() <= $this->changePasswordDate->getTimestamp());
    }

    public function setBalanceWatchCredits(int $credit): self
    {
        $this->balanceWatchCredits = $credit < 0 ? 0 : $credit;

        return $this;
    }

    public function getBalanceWatchCredits(): int
    {
        $credits = (int) $this->balanceWatchCredits;

        return $credits < 0 ? 0 : $credits;
    }

    public function getTravelerProfile(): array
    {
        return $this->travelerProfile ?: [
            'travelerNumber' => null,
            'dateOfBirth' => null,
            'seatPreference' => null,
            'mealPreference' => null,
            'homeAirport' => null,
            'passport' => [
                'name' => null,
                'number' => null,
                'issueDate' => null,
                'country' => null,
                'expirationDate' => null,
            ],
        ];
    }

    public function setTravelerProfile(array $travelerProfile): void
    {
        $this->travelerProfile = $travelerProfile;
    }

    public function getLastSpendAnalysisEmail(): ?\DateTime
    {
        return $this->lastSpendAnalysisEmailDate;
    }

    /**
     * @return $this
     */
    public function setLastSpendAnalysisEmail(?\DateTime $lastSpendAnalysisEmailDate)
    {
        $this->lastSpendAnalysisEmailDate = $lastSpendAnalysisEmailDate;

        return $this;
    }

    public function isUpdater3k(): bool
    {
        return true;
    }

    public function setUpgradeSkippedCount(int $count): self
    {
        $this->upgradeSkippedCount = $count;

        return $this;
    }

    public function getUpgradeSkippedCount(): int
    {
        return $this->upgradeSkippedCount;
    }

    /**
     * @return Collection|UserOAuth[]
     */
    public function getOAuth(): Collection
    {
        return $this->oauth ?: new ArrayCollection();
    }

    public function isDeclinedMailboxAccess(string $oauthProvider, string $oauthUserId): bool
    {
        return $this->oauth->exists(function ($index, UserOAuth $oauth) use ($oauthProvider, $oauthUserId) {
            return $oauth->getProvider() === $oauthProvider && $oauth->getProviderUserId() === $oauthUserId && $oauth->isDeclinedMailboxAccess();
        });
    }

    public function getValidMailboxesCount(): int
    {
        return $this->validMailboxesCount;
    }

    public function setValidMailboxesCount(int $validMailboxesCount): void
    {
        $this->validMailboxesCount = $validMailboxesCount;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getAvailableCardsUpdateDate(): ?\DateTime
    {
        return $this->availableCardsUpdateDate;
    }

    public function setAvailableCardsUpdateDate(?\DateTime $availableCardsUpdateDate): self
    {
        $this->availableCardsUpdateDate = $availableCardsUpdateDate;

        return $this;
    }

    public function isAutoDetectLoungeCards(): bool
    {
        return $this->autoDetectLoungeCards;
    }

    public function setAutoDetectLoungeCards(bool $autoDetectLoungeCards): self
    {
        $this->autoDetectLoungeCards = $autoDetectLoungeCards;

        return $this;
    }

    public function isHavePriorityPassCard(): bool
    {
        return $this->havePriorityPassCard;
    }

    public function setHavePriorityPassCard(bool $havePriorityPassCard): self
    {
        $this->havePriorityPassCard = $havePriorityPassCard;

        return $this;
    }

    public function isHaveDragonPassCard(): bool
    {
        return $this->haveDragonPassCard;
    }

    public function setHaveDragonPassCard(bool $haveDragonPassCard): self
    {
        $this->haveDragonPassCard = $haveDragonPassCard;

        return $this;
    }

    public function isHaveLoungeKeyCard(): bool
    {
        return $this->haveLoungeKeyCard;
    }

    public function setHaveLoungeKeyCard(bool $haveLoungeKeyCard): self
    {
        $this->haveLoungeKeyCard = $haveLoungeKeyCard;

        return $this;
    }

    public function isUsGreeting(): bool
    {
        return $this->usGreeting;
    }

    public function setUsGreeting(bool $usGreeting): self
    {
        $this->usGreeting = $usGreeting;

        return $this;
    }

    public function isUs(): bool
    {
        return $this->isUs;
    }

    public function setIsUs(bool $isUs): self
    {
        $this->isUs = $isUs;

        return $this;
    }

    public function getRegistrationPlatform(): ?int
    {
        return $this->registrationPlatform;
    }

    public function setRegistrationPlatform(?int $registrationPlatform): self
    {
        $this->registrationPlatform = $registrationPlatform;

        return $this;
    }

    public function getRegistrationMethod(): ?int
    {
        return $this->registrationMethod;
    }

    public function setRegistrationMethod(?int $registrationMethod): self
    {
        $this->registrationMethod = $registrationMethod;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        // can't add new column to Usr, it locks prod
        if ($this->defaulttab === 'All' || $this->defaulttab === 'Active') {
            return null;
        }

        return $this->defaulttab;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): void
    {
        // can't add new column to Usr, it locks prod
        if ($stripeCustomerId === null) {
            $this->defaulttab = 'All';

            return;
        }

        $this->defaulttab = $stripeCustomerId;
    }

    public function getOldPaypalRecurringProfileId(): ?string
    {
        return $this->oldPaypalRecurringProfileId;
    }

    public function getSubscriptionPrice(): ?float
    {
        return $this->subscriptionPrice;
    }

    public function setSubscriptionPrice(?float $subscriptionPrice): self
    {
        $this->subscriptionPrice = $subscriptionPrice;

        return $this;
    }

    public function getSubscriptionPeriod(): ?int
    {
        return $this->subscriptionPeriod;
    }

    public function setSubscriptionPeriod(?int $subscriptionPeriod): self
    {
        $this->subscriptionPeriod = $subscriptionPeriod;

        return $this;
    }

    public function setFirstSubscriptionCartItem(?CartItem $firstSubscriptionCartItem): self
    {
        $this->firstSubscriptionCartItem = $firstSubscriptionCartItem;

        return $this;
    }

    public function setLastSubscriptionCartItem(?CartItem $lastSubscriptionCartItem): self
    {
        $this->lastSubscriptionCartItem = $lastSubscriptionCartItem;

        return $this;
    }

    public function getLastSubscriptionCartItem(): ?CartItem
    {
        return $this->lastSubscriptionCartItem;
    }

    public function getFirstSubscriptionCartItem(): ?CartItem
    {
        return $this->firstSubscriptionCartItem;
    }

    public function getFieldsBeforeDowngrade(): ?array
    {
        return $this->fieldsBeforeDowngrade;
    }

    public function setFieldsBeforeDowngrade(?array $fieldsBeforeDowngrade): self
    {
        $this->fieldsBeforeDowngrade = $fieldsBeforeDowngrade;

        return $this;
    }

    public function setNextBillingDate(?\DateTime $nextBillingDate): Usr
    {
        $this->nextBillingDate = $nextBillingDate;

        return $this;
    }

    public function getNextBillingDate(): ?\DateTime
    {
        return $this->nextBillingDate;
    }

    public function setPaypalSuspendedUntilDate(?\DateTime $paypalSuspendedUntilDate): Usr
    {
        $this->paypalSuspendedUntilDate = $paypalSuspendedUntilDate;

        return $this;
    }

    public function getPaypalSuspendedUntilDate(): ?\DateTime
    {
        return $this->paypalSuspendedUntilDate;
    }

    public function hasAnyActiveSubscription(): bool
    {
        return !empty($this->getSubscription())
            && !empty($this->getSubscriptionType());
    }

    public function getActiveSubscriptionCart(): ?Cart
    {
        return $this->getLastSubscriptionCartItem() ? $this->getLastSubscriptionCartItem()->getCart() : null;
    }

    public function hasActiveAwPlusSubscription(): bool
    {
        return $this->hasAnyActiveSubscription()
            && $this->getSubscriptionType() == Usr::SUBSCRIPTION_TYPE_AWPLUS;
    }

    public function hasActiveAt201Subscription(): bool
    {
        return $this->hasAnyActiveSubscription()
            && $this->getSubscriptionType() == Usr::SUBSCRIPTION_TYPE_AT201;
    }

    public function hasActiveDesktopSubscription(): bool
    {
        return $this->hasAnyActiveSubscription() && in_array(
            $this->getSubscription(),
            [Usr::SUBSCRIPTION_PAYPAL, Usr::SUBSCRIPTION_SAVED_CARD, Usr::SUBSCRIPTION_STRIPE]
        );
    }

    public function hasActivePayPalSubscription(): bool
    {
        return $this->hasAnyActiveSubscription() && $this->getSubscription() == Usr::SUBSCRIPTION_PAYPAL;
    }

    public function hasActiveStripeSubscription(): bool
    {
        return $this->hasAnyActiveSubscription() && $this->getSubscription() == Usr::SUBSCRIPTION_STRIPE;
    }

    public function hasActiveIosSubscription(): bool
    {
        if (!$this->hasAnyActiveSubscription() || $this->getSubscription() != Usr::SUBSCRIPTION_MOBILE) {
            return false;
        }

        $activeCart = $this->getActiveSubscriptionCart();

        if (!$activeCart) {
            throw new \RuntimeException(sprintf('User %d has active mobile subscription, but no active cart', $this->getId()));
        }

        return $activeCart->getPaymenttype() == Cart::PAYMENTTYPE_APPSTORE;
    }

    public function hasActiveAndroidSubscription(): bool
    {
        if (!$this->hasAnyActiveSubscription() || $this->getSubscription() != Usr::SUBSCRIPTION_MOBILE) {
            return false;
        }

        $activeCart = $this->getActiveSubscriptionCart();

        if (!$activeCart) {
            throw new \RuntimeException(sprintf('User %d has active mobile subscription, but no active cart', $this->getId()));
        }

        return $activeCart->getPaymenttype() == Cart::PAYMENTTYPE_ANDROIDMARKET;
    }

    public function canCancelActiveSubscription(): bool
    {
        return $this->hasAnyActiveSubscription()
            && (
                $this->hasActiveDesktopSubscription()
                || $this->hasActiveAndroidSubscription()
            );
    }

    /**
     * @return string
     */
    private function getBusinessKeyByLevel(array $accessLevels)
    {
        if (count($accessLevels) > 1) {
            sort($accessLevels);
        }

        return implode(".", $accessLevels);
    }
}
