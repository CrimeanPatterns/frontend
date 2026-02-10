<?php

namespace AwardWallet\MainBundle\Service\Cache;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Features\FeaturesBitSet;
use AwardWallet\MainBundle\Service\Cache\Annotations\Tag;
use AwardWallet\MainBundle\Timeline\QueryOptions;

/**
 * Tags annotations used for cache manager console.
 *
 * @Tag(name="TAG_TIMELINE", desc="Common timeline dependency. Drops timeline for ALL users")
 * @Tag(name="TAG_TIMELINE_DESKTOP", desc="Desktop timeline. Drops timeline for desktop-users")
 * @Tag(name="TAG_TIMELINE_MOBILE", desc="Mobile timeline. Drops timeline for mobile-users")
 * @Tag(name="TAG_PROVIDERS", desc="State for all providers. Drops cache items for items, containing on provider's information")
 * @Tag(name="TAG_TRANSLATIONS", desc="Translations. Drops cache for items, containing translations")
 * @Tag(name="TAG_DATA_STRUCTURES", desc="Entities, cache logic etc.")
 * @Tag(name="TAG_ELITE_LEVELS", desc="Elite levels")
 * @Tag(name="TAG_REGIONS", desc="Regions")
 * @Tag(name="TAG_IATA", desc="IATA")
 * @Tag(name="TAG_AIRLINE", desc="Airline")
 * @Tag(name="TAG_LOYALTY_LOCATIONS", desc="Locations")
 * @Tag(name="TAG_MAILBOXES", desc="Mailboxes")
 * @Tag(name="TAG_CREDIT_CARDS_INFO", desc="Credit Cards multipliers for reverse lookup tool")
 * @Tag(name="TAG_MILE_VALUE", desc="Mile Value")
 * @NoDI()
 */
class Tags
{
    public const GLOBAL_TAGS_EXPIRATION = SECONDS_PER_DAY * 7;

    /**
     * Changing PREFIX constant may lead to global cache invalidation, use with caution.
     */
    public const PREFIX = 'tag_';

    /**
     * EVERY GLOBAL TAG MUST HAVE "TAG_" PREFIX IN CLASS-CONSTANT NAME.
     */
    public const TAG_TIMELINE = 'timeline_V58';
    public const TAG_TIMELINE_MOBILE = 'timeline_mobile_V131';
    public const TAG_TIMELINE_DESKTOP = 'timeline_desktop_V24';
    public const TAG_TIMELINE_COUNTER = 'v6';

    public const TAG_CREDIT_CARDS_INFO = 'credit_cards_info';
    public const TAG_PROVIDERS = 'providers_state';
    public const TAG_CREDITCARDS = 'creditcards';
    public const TAG_TRANSLATIONS = 'translations_V1';
    public const TAG_DATA_STRUCTURES = 'data_structures_V11';
    public const TAG_ELITE_LEVELS = 'elitelevels';
    public const TAG_REGIONS = 'regions_V1';
    public const TAG_LOYALTY_LOCATIONS = 'loyalty_locations';
    public const TAG_MAILBOXES = 'mailboxes_V2';
    public const TAG_MILE_VALUE = 'mile_value';
    public const TAG_IATA = 'iata';
    public const TAG_AIRLINE = 'airline';

    /**
     * @param Usr|int $user
     * @param Useragent|int $useragent
     * @param TimelineShare|int $timelineShare
     * @return array
     */
    public static function getTimelineTags($user, $useragent = null, $timelineShare = null)
    {
        $result = self::getTimelineCounterTags($user, $useragent);

        if (!empty($userAgent) && !empty($userAgent->getClientid())) {
            // add tags of connected user
            $result = array_merge($result, self::getTimelineCounterTags($useragent->getClientid(), null));
        }

        if (!empty($timelineShare)) {
            // add tags of shared plan
            $result = array_merge($result, self::getTimelineShareTags($timelineShare->getTimelineShareId()));
        }

        return array_merge($result, self::addTagPrefix([
            self::TAG_TRANSLATIONS,
            self::TAG_ELITE_LEVELS,
            self::getUserAccountsKey($user->getUserid(), $useragent ? $useragent->getUseragentid() : null),
            self::getUserAccountsKey($user->getUserid()),
        ]));
    }

    /**
     * @param Usr|int $user
     * @param Useragent|int $useragent
     * @return string
     */
    public static function getTimelineKey(
        $user,
        $useragent = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        $maxSegments = null,
        $showDeleted = false,
        $format = null,
        ?FeaturesBitSet $formatOptions = null,
        $filterCallbackKey = null,
        $shareId = null,
        $showPlans = null,
        ?Plan $sharedPlan = null,
        ?Usr $loggedInUser = null,
        $locale = null
    ) {
        return implode('_', [
            'user_timeline_2' /* .rand(1, 100000) */ ,
            $user instanceof Usr ? $user->getUserid() : $user,
            null === $useragent ? 'null' : ($useragent instanceof Useragent ? $useragent->getUseragentid() : $useragent),
            empty($startDate) ? 'null' : $startDate->getTimestamp(),
            empty($endDate) ? 'null' : $endDate->getTimestamp(),
            $maxSegments,
            $showDeleted ? '1' : '0',
            self::nullable($format),
            self::nullable($formatOptions ? $formatOptions->all() : null),
            self::nullable($filterCallbackKey),
            self::nullable($shareId),
            self::nullable($showPlans),
            $sharedPlan ? $sharedPlan->getId() : 'null',
            empty($loggedInUser) ? "_anon" : $loggedInUser->getUserid(),
            self::nullable($locale),
        ]);
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public static function getTimelineKeyByOptions(QueryOptions $queryOptions, ?Usr $loggedInUser = null, $locale = null)
    {
        return self::getTimelineKey(
            $queryOptions->getUser(),
            $queryOptions->getUserAgent(),
            $queryOptions->getStartDate(),
            $queryOptions->getEndDate(),
            $queryOptions->getMaxSegments(),
            $queryOptions->isShowDeleted(),
            $queryOptions->getFormat(),
            $queryOptions->getFormatOptions(),
            ($filterCallback = $queryOptions->getFilterCallback()) ? $filterCallback->getCacheKey() : null,
            $queryOptions->getShareId(),
            $queryOptions->getShowPlans(),
            $queryOptions->getSharedPlan(),
            $loggedInUser,
            $locale
        );
    }

    /**
     * @param Usr|int $user
     * @param Useragent|int $useragent
     * @param bool $withPlans
     * @return string
     */
    public static function getTimelineCounterKey($user, $useragent = null, $withPlans)
    {
        return self::getTimelineGenericKey('user_timeline_count', $user, $useragent, $withPlans, false);
    }

    public static function getTimelineUserStartDateKey($user)
    {
        return 'user_timeline_start_date_v1_' . (($user instanceof Usr) ? $user->getId() : $user);
    }

    public static function getTimelineHasBeforeKey(\DateTime $dateTime, $user, $userAgent = null, $withPlans, $showDeleted)
    {
        return self::getTimelineGenericKey('has_before_' . $dateTime->getTimestamp(), $user, $userAgent, $withPlans, $showDeleted);
    }

    /**
     * @param Usr|int $user
     * @param Useragent|int $useragent
     * @return array
     */
    public static function getTimelineCounterTags($user, $useragent = null)
    {
        return self::addTagPrefix([
            self::getTimelineKey($user),
            self::getTimelineKey($user, $useragent),
            self::getUserAccountsKey($user, $useragent),
            self::TAG_PROVIDERS,
            self::TAG_DATA_STRUCTURES,
            self::TAG_TIMELINE,
        ]);
    }

    /**
     * @param int $userId
     * @return array
     */
    public static function getAllAccountsCounterTags($userId)
    {
        return self::addTagPrefix([
            self::getAllAccountsKey($userId),
            self::TAG_PROVIDERS,
            self::TAG_DATA_STRUCTURES,
        ]);
    }

    public static function getUserAccountsKey($userId, $userAgentId = null)
    {
        return self::implodeNullable([
            'user_accounts_v4',
            $userId instanceof Usr ? $userId->getUserid() : $userId,
            $userAgentId instanceof Useragent ? $userAgentId->getUseragentid() : $userAgentId,
        ]);
    }

    public static function getAllAccountsKey($userId)
    {
        return self::implodeNullable([
            'user_all_accounts_v4',
            $userId,
        ]);
    }

    public static function getPersonsWidgetKey($userId)
    {
        return 'persons_widget_' . $userId;
    }

    public static function getPersonsWidgetTags($userId)
    {
        return self::addTagPrefix([
            self::getPersonsWidgetKey($userId),
            self::TAG_DATA_STRUCTURES,
        ]);
    }

    public static function getConnectionsTags($userId)
    {
        return [
            'connections_v2_' . $userId,
        ];
    }

    public static function getUserMailboxesKey(int $userId, bool $validOnly): string
    {
        return 'mailbox_counter_v5_' . $userId . '_' . ($validOnly ? 'valid' : 'all');
    }

    public static function getUserMailboxesOwnersKey(int $userId): string
    {
        return 'mailbox_owners_v1_' . $userId;
    }

    public static function getUserMailboxesTags($userId): array
    {
        return [
            self::getUserMailboxesKey($userId, false),
            self::getUserMailboxesKey($userId, true),
            self::TAG_MAILBOXES,
        ];
    }

    public static function getUserAgentTags($userAgentId)
    {
        return [
            'useragent_' . $userAgentId,
        ];
    }

    public static function getTimelineShareTags($timelineShareId)
    {
        return [
            'timelineshare_' . $timelineShareId,
        ];
    }

    public static function getCreditCardAdKey($userId)
    {
        return 'creditcard_ads_v3_user_' . $userId;
    }

    public static function getLoyaltyLocationsKey($userId)
    {
        return 'loyalty_locations_user_' . $userId;
    }

    public static function getNeedTwoFactorAuthKey(int $userId)
    {
        return sprintf('need_two_factor_auth_%d', $userId);
    }

    public static function getNeedTwoFactorAuthTags(int $userId)
    {
        return array_merge(
            self::addTagPrefix([
                self::getNeedTwoFactorAuthKey($userId),
            ]),
            self::getConnectionsTags($userId)
        );
    }

    public static function getCreditCardAdTags($userId)
    {
        return self::addTagPrefix([
            self::getCreditCardAdKey($userId),
            self::TAG_PROVIDERS,
            self::TAG_CREDITCARDS,
            self::TAG_DATA_STRUCTURES,
            self::getUserAccountsKey($userId),
        ]);
    }

    public static function getLoyaltyLocationsTags($userId)
    {
        return self::addTagPrefix([
            self::getLoyaltyLocationsKey($userId),
            self::TAG_PROVIDERS,
            self::TAG_LOYALTY_LOCATIONS,
            self::TAG_DATA_STRUCTURES,
            self::getUserAccountsKey($userId),
        ]);
    }

    public static function getMobilePlansKey(Usr $user, $locale = null)
    {
        return 'mobile_plans_' . implode('_', [
            $user->getUserid(),
            self::nullable($locale),
        ]);
    }

    public static function getMobilePlansTags(Usr $user)
    {
        return array_unique(
            array_reduce(
                array_map(
                    function (?Useragent $useragent = null) use ($user) {
                        return self::getTimelineTags($user, $useragent);
                    },
                    array_merge([null], $user->getConnections()->getValues())
                ),
                'array_merge',
                []
            )
        );
    }

    /**
     * @param bool $withPlans
     * @return string
     */
    public static function getTimelineMapKey(Usr $user, ?Useragent $userAgent = null, $withPlans)
    {
        return "timeline_map_data_v3_" .
            $user->getUserid() . "_" .
            ($userAgent ? $userAgent->getUseragentid() : "null") . '_' .
            (int) $withPlans;
    }

    /**
     * @return array
     */
    public static function addTagPrefix(array $tags)
    {
        return array_map(function ($key) { return self::PREFIX . $key; }, $tags);
    }

    protected static function getTimelineGenericKey($prefix, $user, $useragent = null, $withPlans, $showDeleted)
    {
        return implode('_', [
            $prefix,
            $user instanceof Usr ? $user->getUserid() : $user,
            null === $useragent ? 'null' : ($useragent instanceof Useragent ? $useragent->getUseragentid() : $useragent),
            (int) $withPlans,
            (int) $showDeleted,
        ]);
    }

    private static function implodeNullable($parts, $glue = '_')
    {
        return implode($glue, array_map(function ($el) { return self::nullable($el); }, $parts));
    }

    private static function nullable($value)
    {
        return null === $value ? 'null' : (string) $value;
    }
}
