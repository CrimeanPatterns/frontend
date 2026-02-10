<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Service\EmailTemplate\AccountProperty\PropertyKind;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Circle;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Square;

/**
 * @NoDI()
 */
class Options
{
    /**
     * Users who pay full $30 price yearly.
     */
    public const SUBSCRIPTION_TYPE_FULL_30 = 0;
    /**
     * Users who pay $10 yearly or $5 semi-annually.
     */
    public const SUBSCRIPTION_TYPE_EARLY_SUPPORTER = 1;
    /**
     * Users who pay AT201 Price.
     */
    public const SUBSCRIPTION_TYPE_AT201 = 2;
    public $userId = [];

    public $notBusiness = false;

    /**
     * @var bool Ignore option "Occasional, important product updates"
     */
    public $ignoreEmailProductUpdates = false;
    public bool $onlyDoNotSendNDR = false;

    public $hasPrepaidAwPlus = false;

    /**
     * @var bool Ignore option "Lucrative offers"
     */
    public $ignoreEmailOffers = false;

    public $ignoreEmailLog = false;

    public $emailType = EmailTemplate::TYPE_OTHER;

    public $ignoreGroupDoNotCommunicate = false;

    /**
     * @var int EmailTemplateID
     */
    public $messageId;

    /**
     * @var string[]
     */
    public $countries = [];

    /**
     * State codes like WA, CA, ny, fl etc. lowercase\uppercase doesn't matter.
     *
     * @var string[]
     */
    public array $statesCodes = [];

    /**
     * @var int[]
     */
    public $userIdSplit = [];

    /**
     * @var string[]
     */
    public $notCountries = [];

    /**
     * @var string[]
     */
    public $hasNotUserFullname = [];

    /**
     * @var string[]
     */
    public $hasUserFullname = [];

    /**
     * @var bool
     */
    public $hasBusinessCard = false;

    /**
     * @var bool
     */
    public $hasNoBusinessCard = false;

    /**
     * @var bool|null
     */
    public $businessDetected;

    /**
     * @var int[]
     */
    public $hasAccountFromProviders = [];

    /**
     * @var int[]
     */
    public $hasAccountFromProvidersByKind = [];

    /**
     * @var int[]
     */
    public $hasNotAccountFromProviders = [];

    /**
     * @var ?bool
     */
    public $accountFromProvidersLinkedToFamilyMember;

    /**
     * @var int[]
     */
    public $hasNotEmails = [];

    /**
     * @var int[]
     */
    public $notUserId = [];

    /**
     * @var bool
     */
    public $awPlusUpgraded;

    /**
     * @var bool
     * @deprecated use Usr.Subscription based query-options
     */
    public $awPlusActiveSubscription;
    public ?bool $hasSubscription = null;
    /**
     * @var ?self::SUBSCRIPTION_TYPE_*
     */
    public ?int $hasSubscriptionType = null;

    /**
     * @var ?(self::SUBSCRIPTION_TYPE_FULL_30|self::SUBSCRIPTION_TYPE_EARLY_SUPPORTER)
     */
    public ?int $vipHasEverSubscriptionType = null;
    public ?bool $hasEverSubscription = null;
    /**
     * @var bool
     */
    public $paid;

    /**
     * @var array
     */
    public $hasAccountPropertyContains = [];

    /**
     * @var array<string, array<string, callable(string): string>|\SplObjectStorage<PropertyKind, callable(string): string>>
     */
    public $hasAccountPropertyExpr = [];

    /**
     * @var array<string, callable(string): string>
     */
    public $hasBalanceExpr = [];

    /**
     * @var array
     */
    public $hasNotAccountPropertyContains = [];

    /**
     * @var array
     */
    public $hasNotAccountHistoryContains = [];

    /**
     * @var list<string>
     */
    public $hasMerchantsLike = [];

    public ?bool $hasDisneyTransactions = null;

    /**
     * @var array
     */
    public $hasNotSubAccountContains = [];

    /**
     * @var bool
     */
    public $hasFicoScore = false;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var bool
     */
    public $useReadReplica = false;

    /**
     * @var array
     */
    public $hasTripTarget = [];

    /**
     * @var \Closure
     */
    public $builderTransformator;

    /**
     * @var string[]
     */
    public $refCode = [];

    /**
     * @var string[]
     */
    public $businessInterestRefCode = [];

    /**
     * @var string[]
     */
    public $notRefCode = [];

    /**
     * @var int
     */
    public $minEliteLevel;

    /**
     * @var int
     */
    public $maxEliteLevel;

    /**
     * @var string[]
     */
    public $exclusionDataProviders = [];

    /**
     * @var int[]
     */
    public $excludedCreditCards = [];
    public ?bool $hasVIPUpgrade = null;
    public ?bool $hasSupporter3MUpgrade = null;

    /**
     * @var bool
     */
    public $exclusionMode = false;

    /**
     * @var bool
     */
    public $usUsers2 = false;

    /**
     * @var string[]
     */
    public $airHelpCompensationEpoch = [];

    /**
     * @var string[]
     */
    public $airHelpCompensationLocalesCheck = [];

    /**
     * @var string[]
     */
    public $countCriteriaFields = ['UserID'];

    public int $offerIdUsers = 0;

    /**
     * @var Square[]|Circle[]
     */
    public $nearPoints = [];

    /**
     * @var Square[]|Circle[]
     */
    public $nearPointsExtTable = [];
}
