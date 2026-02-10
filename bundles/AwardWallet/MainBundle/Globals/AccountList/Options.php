<?php

namespace AwardWallet\MainBundle\Globals\AccountList;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Usr;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @NoDI
 */
class Options
{
    // all option names constants MUST start with OPTION_
    public const OPTION_USER = 'user';
    public const OPTION_USERAGENT = 'useragent';
    public const OPTION_AGENTID = 'agentId';
    public const OPTION_LOCALE = 'locale';
    public const OPTION_GROUPBY = 'groupBy';
    public const OPTION_ORDERBY = 'orderBy';
    public const OPTION_ORDER = 'order';
    public const OPTION_PER_PAGE = 'perPage';
    public const OPTION_PAGE = 'page';
    public const OPTION_WITHOUT_GROUP = 'withoutGroup';
    public const OPTION_FILTER_ERROR = 'filterError';
    public const OPTION_FILTER_RECENT = 'filterRecent';
    public const OPTION_FILTER_PROGRAM = 'filterProgram';
    public const OPTION_FILTER_OWNER = 'filterOwner';
    public const OPTION_FILTER_ACCOUNT = 'filterAccount';
    public const OPTION_FILTER_STATUS = 'filterStatus';
    public const OPTION_FILTER_BALANCE = 'filterBalance';
    public const OPTION_FILTER_EXPIRE = 'filterExpire';
    public const OPTION_FILTER_LAST_UPDATE_DATE = 'filterLastUpdate';
    public const OPTION_FORMAT_FORCE_ERRORS = 'format.forceErrors';
    public const OPTION_FILTER = 'filter';
    public const OPTION_COUPON_FILTER = 'couponFilter';
    public const OPTION_STATEFILTER = 'stateFilter';
    public const OPTION_CLIENTIP = 'clientIP';
    public const OPTION_CALLBACK = 'callback';
    public const OPTION_LOAD_PHONES = 'load.phones';
    public const OPTION_LOAD_SUBACCOUNTS = 'load.subaccounts';
    public const OPTION_LOAD_PROPERTIES = 'load.properties';
    public const OPTION_LOAD_HAS_ACTIVE_TRIPS = 'load.hasActiveTrips';
    public const OPTION_LOAD_CARD_IMAGES = 'load.cardImages';
    public const OPTION_LOAD_LOYALTY_LOCATIONS = 'load.loyaltyLocations';
    public const OPTION_LOAD_HISTORY_PRESENCE = 'load.historyPresence';
    public const OPTION_LOAD_BALANCE_CHANGES_COUNT = 'load.balanceChangesCount';
    public const OPTION_LOAD_PENDING_SCAN_DATA = 'load.PendingScanData';
    public const OPTION_JOINS = 'joins';
    public const OPTION_EXTRA_ACCOUNT_FIELDS = 'extraAccountFields';
    public const OPTION_EXTRA_COUPON_FIELDS = 'extraCouponFields';
    public const OPTION_GROUP = 'group';
    public const OPTION_DENY_PROVIDERS = 'denyProviders';
    public const OPTION_ALLIANCEID = 'allianceId';
    public const OPTION_ACCOUNT_IDS = 'accountIds';
    public const OPTION_AS_OBJECT = 'asObject';
    public const OPTION_FORMATTER = 'formatter';
    public const OPTION_CHANGE_PERIOD = 'changePeriod';
    public const OPTION_CHANGE_PERIOD_DESC = 'changePeriodDesc';
    public const OPTION_ONLY_ACTIVE_DEALS = 'onlyActiveDeals';
    public const OPTION_COMMENT_LENGTH_LIMIT = 'commentLengthLimit';
    public const OPTION_INDEXED_BY_HID = 'indexedByHID';
    public const OPTION_SKIP_ON_FAILURE = 'skipOnFailure';
    public const OPTION_SAFE_EXECUTOR_FACTORY = 'safeExecutorFactory';
    public const OPTION_LOAD_MILE_VALUE = 'load.MileValue';
    public const OPTION_LOAD_BLOG_POSTS = 'load.blogPosts';
    public const OPTION_LOAD_MERCHANT_RECOMMENDATIONS = 'load.MerchantRecommendations';

    public const OPTION_DQL = 'DQL';

    // all option values constants MUST start with VALUE_
    public const VALUE_GROUPBY_USER = 1;
    public const VALUE_GROUPBY_KIND = 2;

    public const VALUE_ORDERBY_CARRIER = 1;
    public const VALUE_ORDERBY_PROGRAM = 2;
    public const VALUE_ORDERBY_BALANCE = 3;
    public const VALUE_ORDERBY_EXPIRATION = 4;
    public const VALUE_ORDERBY_LASTUPDATE = 5;

    public const VALUE_ORDERBY_CARRIER_DESC = 11;
    public const VALUE_ORDERBY_PROGRAM_DESC = 12;
    public const VALUE_ORDERBY_BALANCE_DESC = 13;
    public const VALUE_ORDERBY_EXPIRATION_DESC = 14;
    public const VALUE_ORDERBY_LASTUPDATE_DESC = 15;
    public const VALUE_ORDERBY_LASTCHANGEDATE_DESC = 16;

    public const VALUE_PHONES_NOLOAD = 0; // do not load phones at all
    public const VALUE_PHONES_SUPPORT = 1; // load only customer support phone
    public const VALUE_PHONES_FULL = 2; // load all phones

    public const VALUE_DEFAULT_PER_PAGE = 100;

    /**
     * @var array<string, mixed|null>
     */
    private $storage;

    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }

    /**
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    public function get(string $name, $defaultValue = null)
    {
        self::checkOption($name);

        if ($this->has($name)) {
            return $this->storage[$name];
        }

        return $defaultValue;
    }

    /**
     * @param mixed|null $value
     */
    public function set(string $name, $value): self
    {
        self::checkOption($name);
        $this->storage[$name] = $value;

        // set locale from user
        if ($name === self::OPTION_USER && $value instanceof Usr) {
            $this->storage[self::OPTION_LOCALE] = $value->getLocale();
        }

        return $this;
    }

    public function has(string $name): bool
    {
        self::checkOption($name);

        return \array_key_exists($name, $this->storage);
    }

    public function updateFrom(Options $another): self
    {
        $this->storage = \array_merge($this->storage, $another->storage);

        return $this;
    }

    private static function checkOption(string $name): void
    {
        static $optionsMap;

        if (!isset($optionsMap)) {
            $optionsMap =
                it((new \ReflectionClass(self::class))->getConstants())
                ->flip()
                ->filter(function (string $name): bool {
                    return 0 === \strpos($name, 'OPTION_');
                })
                ->toArrayWithKeys();
        }

        if (!isset($optionsMap[$name])) {
            @trigger_error("Unknown AccountList option: " . $name, \E_USER_WARNING);
        }
    }
}
