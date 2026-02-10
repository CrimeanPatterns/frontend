<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\FrameworkExtension\Translator\AbstractTranslatable;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;

/**
 * @NoDI
 */
class Content
{
    public const TYPE_BOOKING = 1;
    public const TYPE_NEW_ITINERARY = 2;
    public const TYPE_CHANGED_ITINERARY = 3;
    public const TYPE_REWARDS_ACTIVITY = 4;
    public const TYPE_ACCOUNT_EXPIRATION = 5;
    public const TYPE_CHECKIN_REMINDER = 6;
    public const TYPE_MEMBERSHIP_EXPIRES = 7;
    public const TYPE_ONE_TIME_CODE = 8;
    public const TYPE_PRODUCT_UPDATES = 9;
    public const TYPE_OFFER = 10;
    public const TYPE_BLOG_POST = 11;
    public const TYPE_INVITEE_REG = 12;
    public const TYPE_FLIGHT_DELAY = 13;
    public const TYPE_FLIGHT_TIME_CHANGED = 14;
    public const TYPE_FLIGHT_BAGGAGE_CHANGE = 15;
    public const TYPE_LEG_ARRIVED = 16;
    public const TYPE_HOTEL_PHONE = 17;
    public const TYPE_FLIGHT_CANCELLATION = 18;
    public const TYPE_FLIGHT_REINSTATED = 19;
    public const TYPE_CONNECTION_INFO = 20;
    public const TYPE_CONNECTION_INFO_GATE_CHANGE = 21;
    public const TYPE_FLIGHT_DEPARTURE = 22;
    public const TYPE_FLIGHT_BOARDING = 23;
    public const TYPE_FLIGHT_DEPARTURE_GATE_CHANGE = 24;
    public const TYPE_BALANCE_WATCH = 25;
    public const TYPE_PASSPORT_EXPIRATION = 26;
    public const TYPE_REAUTH_CODE = 27;
    public const TYPE_PRECHECKIN_REMINDER = 28;
    public const TYPE_CITI_CARD_OFFER_ACTIVATED = 29;

    public const ANDROID_CHANNELS = [
        self::TYPE_BOOKING => 'booking_activity',
        self::TYPE_NEW_ITINERARY => 'new_reservation',
        self::TYPE_CHANGED_ITINERARY => 'change_alert',
        self::TYPE_REWARDS_ACTIVITY => 'rewards_activity',
        self::TYPE_ACCOUNT_EXPIRATION => 'balance_expiration',
        self::TYPE_CHECKIN_REMINDER => 'checkin',
        self::TYPE_MEMBERSHIP_EXPIRES => 'promo', // todo: change
        self::TYPE_ONE_TIME_CODE => 'otc',
        self::TYPE_PRODUCT_UPDATES => 'promo',
        self::TYPE_OFFER => 'promo',
        self::TYPE_BLOG_POST => 'blog_post',
        self::TYPE_INVITEE_REG => 'promo', // todo: change
        self::TYPE_FLIGHT_DELAY => 'change_alert',
        self::TYPE_FLIGHT_TIME_CHANGED => 'change_alert',
        self::TYPE_FLIGHT_BAGGAGE_CHANGE => 'change_alert',
        self::TYPE_LEG_ARRIVED => 'flight_connection',
        self::TYPE_HOTEL_PHONE => 'flight_connection',
        self::TYPE_FLIGHT_CANCELLATION => 'change_alert',
        self::TYPE_FLIGHT_REINSTATED => 'change_alert',
        self::TYPE_CONNECTION_INFO => 'flight_connection',
        self::TYPE_CONNECTION_INFO_GATE_CHANGE => 'change_alert',
        self::TYPE_FLIGHT_DEPARTURE_GATE_CHANGE => 'change_alert',
        self::TYPE_FLIGHT_DEPARTURE => 'dep',
        self::TYPE_FLIGHT_BOARDING => 'boarding',
        self::TYPE_PASSPORT_EXPIRATION => 'balance_expiration',
        self::TYPE_REAUTH_CODE => 'otc',
        self::TYPE_PRECHECKIN_REMINDER => 'checkin',
        self::TYPE_CITI_CARD_OFFER_ACTIVATED => 'citi_card_offer_activated',
    ];

    public const TYPE_NAMES = [
        self::TYPE_BOOKING => 'booking',
        self::TYPE_NEW_ITINERARY => 'new_itinerary',
        self::TYPE_CHANGED_ITINERARY => 'changed_itinerary',
        self::TYPE_REWARDS_ACTIVITY => 'rewards_activity',
        self::TYPE_ACCOUNT_EXPIRATION => 'account_expiration',
        self::TYPE_CHECKIN_REMINDER => 'checkin_reminder',
        self::TYPE_MEMBERSHIP_EXPIRES => 'membership_expires',
        self::TYPE_ONE_TIME_CODE => 'one_time_code',
        self::TYPE_PRODUCT_UPDATES => 'product_updates',
        self::TYPE_OFFER => 'offer',
        self::TYPE_BLOG_POST => 'blog_post',
        self::TYPE_INVITEE_REG => 'invitee_reg',
        self::TYPE_FLIGHT_DELAY => 'flight_delay',
        self::TYPE_FLIGHT_TIME_CHANGED => 'flight_time_changed',
        self::TYPE_FLIGHT_BAGGAGE_CHANGE => 'flight_baggage_change',
        self::TYPE_LEG_ARRIVED => 'leg_arrived',
        self::TYPE_HOTEL_PHONE => 'hotel_phone',
        self::TYPE_FLIGHT_CANCELLATION => 'flight_cancellation',
        self::TYPE_FLIGHT_REINSTATED => 'flight_reinstated',
        self::TYPE_CONNECTION_INFO => 'connection_info',
        self::TYPE_CONNECTION_INFO_GATE_CHANGE => 'connection_info_gate_change',
        self::TYPE_FLIGHT_DEPARTURE_GATE_CHANGE => 'flight_departure_gate_change',
        self::TYPE_FLIGHT_DEPARTURE => 'flight_departure',
        self::TYPE_FLIGHT_BOARDING => 'flight_boarding',
        self::TYPE_BALANCE_WATCH => 'balance_watch',
        self::TYPE_PASSPORT_EXPIRATION => 'passport_expiration',
        self::TYPE_REAUTH_CODE => 'reauth_code',
        self::TYPE_PRECHECKIN_REMINDER => 'pre_checkin_reminder',
        self::TYPE_CITI_CARD_OFFER_ACTIVATED => 'citi_card_offer_activated',
    ];
    public const TARGET_PAY = 1;

    public static $wpUserFields = [
        self::TYPE_ACCOUNT_EXPIRATION => 'wpExpire',
        self::TYPE_REWARDS_ACTIVITY => 'wpRewardsActivity',
        self::TYPE_NEW_ITINERARY => 'wpNewPlans',
        self::TYPE_CHANGED_ITINERARY => 'wpPlanChanges',
        self::TYPE_CHECKIN_REMINDER => 'wpCheckins',
        self::TYPE_BOOKING => 'wpBookingMessages',
        self::TYPE_PRODUCT_UPDATES => 'wpProductUpdates',
        self::TYPE_OFFER => 'wpOffers',
        self::TYPE_BLOG_POST => 'wpNewBlogPosts',
        self::TYPE_INVITEE_REG => 'wpInviteeReg',
        self::TYPE_FLIGHT_DELAY => 'wpPlanChanges',
        self::TYPE_FLIGHT_TIME_CHANGED => 'wpPlanChanges',
        self::TYPE_FLIGHT_BAGGAGE_CHANGE => 'wpPlanChanges',
        self::TYPE_LEG_ARRIVED => 'wpCheckins',
        self::TYPE_HOTEL_PHONE => 'wpCheckins',
        self::TYPE_FLIGHT_CANCELLATION => 'wpPlanChanges',
        self::TYPE_FLIGHT_REINSTATED => 'wpPlanChanges',
        self::TYPE_CONNECTION_INFO => 'wpCheckins',
        self::TYPE_CONNECTION_INFO_GATE_CHANGE => 'wpPlanChanges',
        self::TYPE_FLIGHT_DEPARTURE_GATE_CHANGE => 'wpPlanChanges',
        self::TYPE_FLIGHT_DEPARTURE => 'wpCheckins',
        self::TYPE_FLIGHT_BOARDING => 'wpCheckins',
        self::TYPE_PASSPORT_EXPIRATION => 'wpExpire',
        self::TYPE_PRECHECKIN_REMINDER => 'wpCheckins',
    ];

    public static $mpUserFields = [
        self::TYPE_ACCOUNT_EXPIRATION => 'mpExpire',
        self::TYPE_REWARDS_ACTIVITY => 'mpRewardsActivity',
        self::TYPE_NEW_ITINERARY => 'mpNewPlans',
        self::TYPE_CHANGED_ITINERARY => 'mpPlanChanges',
        self::TYPE_CHECKIN_REMINDER => 'mpCheckins',
        self::TYPE_BOOKING => 'mpBookingMessages',
        self::TYPE_PRODUCT_UPDATES => 'mpProductUpdates',
        self::TYPE_OFFER => 'mpOffers',
        self::TYPE_BLOG_POST => 'mpNewBlogPosts',
        self::TYPE_INVITEE_REG => 'mpInviteeReg',
        self::TYPE_FLIGHT_DELAY => 'mpPlanChanges',
        self::TYPE_FLIGHT_TIME_CHANGED => 'mpPlanChanges',
        self::TYPE_FLIGHT_BAGGAGE_CHANGE => 'mpPlanChanges',
        self::TYPE_LEG_ARRIVED => 'mpCheckins',
        self::TYPE_HOTEL_PHONE => 'mpCheckins',
        self::TYPE_FLIGHT_CANCELLATION => 'mpPlanChanges',
        self::TYPE_FLIGHT_REINSTATED => 'mpPlanChanges',
        self::TYPE_CONNECTION_INFO => 'mpCheckins',
        self::TYPE_CONNECTION_INFO_GATE_CHANGE => 'mpPlanChanges',
        self::TYPE_FLIGHT_DEPARTURE_GATE_CHANGE => 'mpPlanChanges',
        self::TYPE_FLIGHT_DEPARTURE => 'mpCheckins',
        self::TYPE_FLIGHT_BOARDING => 'mpCheckins',
        self::TYPE_PASSPORT_EXPIRATION => 'mpExpire',
        self::TYPE_PRECHECKIN_REMINDER => 'mpCheckins',
    ];

    /** @var string|AbstractTranslatable */
    public $title;
    /** @var string|AbstractTranslatable */
    public $message;
    /** @var int */
    public $type;
    /** @var object|int */
    public $target;
    /**
     * @var Options
     */
    public $options;

    /**
     * Content constructor.
     *
     * @param string|AbstractTranslatable $title
     * @param string|AbstractTranslatable $message
     * @param int $type - one of TYPE_ constants
     * @param object|int $target - where to navigate after receiving notification
     * @param Options|null notification options $options
     */
    public function __construct($title = null, $message = null, $type = null, $target = null, ?Options $options = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->target = $target;
        $this->options = $options;
    }

    public static function getTypeName($id)
    {
        return self::TYPE_NAMES[$id];
    }
}
