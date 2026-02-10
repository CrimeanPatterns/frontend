<?php

namespace AwardWallet\MainBundle\Globals\ApiVersioning;

use Herrera\Version\Parser;

class MobileVersions implements VersionsProviderInterface
{
    public const IOS = 'ios';
    public const ANDROID = 'android';

    public const NATIVE_APP = 'native.app';
    public const UPDATER_IGNORED_DETAILS = 'updater.ignored.details';
    public const UPDATER_CLIENT_CHECK = 'updater.client.check';
    public const DATA_TIMESTAMP_OFF = 'data.disable.timestamp';
    public const TIMELINE = 'timeline';
    public const UPDATER_CLIENT_CHECK_LOGS = 'updater.client.check.logs';
    public const UPDATER_EVENTS = 'updater.event';
    public const UPDATER_FORCED_TRIPS = 'updater.forced.trips';
    public const TIMELINE_PLANS_ITEMS = 'timeline.plans.items';
    public const OAUTH_PROVIDERS = 'oauth.providers';
    public const JS_FORM_EXTENSIONS = 'js.form.extensions';
    public const PROFILE_FORMS = 'profile_forms';
    public const BOOKING_VIEW = 'booking.view';
    public const FORM_LINKED_CHOICES = 'form_linked_choices';
    public const AWPLUS_VIA_COUPON_ROUTE = 'awplus_via_coupon_route';
    public const AWPLUS_VIA_COUPON_PROFILE_OVERVIEW = 'awplus_via_coupon_profile_overview';
    public const SCRIPTED_BOT_PROTECTION = 'scripted_bot_protection';
    public const TIMELINE_OWNER_NAME = 'timeline_owner_name';

    public const CUSTOM_ACCOUNTS = 'custom_accounts';
    public const FORM_DATE_PICKER = 'form_date_picker';
    public const FORM_TEXTAREA = 'form_textarea';
    public const ACCOUNT_BLOCK_WARNING = 'account_block_warning';
    public const GEO_NOTIFICATIONS = 'geo_notifications';
    public const TIMELINE_OFFERS = 'timeline_offers';

    public const AWPLUS_SUBSCRIBE = 'awplus_subscribe';
    public const COUPON_ERROR_EXTENSION = 'coupon_error_extension';

    public const ACCOUNT_FAMILY_NAME = 'account.family.name';
    public const TIMELINE_FLIGHT_PROGRESS = 'timeline_flight_progress';
    public const ADVANCED_NOTIFICATIONS_SETTINGS = 'advanced_notifications_settings';
    public const CARD_OFFERS = 'card_offers';
    public const AWPLUS_DESCRIPTION_LONG = 'awplus_description_long';
    public const CARD_IMAGES = 'card_images';
    public const TIMELINE_BLOCKS_V2 = 'timeline_blocks_v2';
    public const BARCODES = 'barcodes';
    public const ADS_SETTINGS = 'ads_settings';
    public const REGIONAL_SETTINGS = 'regional_settings';
    public const PHONE_CALL_FROM_NOTIFICATION = 'phone_in_notification';
    public const CARD_IMAGES_ON_FORM = 'card_images_on_form';

    public const BOT_PROTECTION_SOFT_MODE = 'bot_protection_soft_mode';
    public const BOT_PROTECTION_KEY_V1 = 'bot_protection_key_v1';
    public const BOT_PROTECTION_KEY_ANDROID_V1 = 'bot_protection_key_android_v1';
    public const BOT_PROTECTION_KEY_IOS_V1 = 'bot_protection_key_ios_v1';

    public const LOCATION_STORAGE = 'location_storage';
    public const PASSWORD_ACCESS = 'password_access';
    public const TIMELINE_TAXI_RIDE = 'timeline_taxi_ride';
    public const LINKED_COUPONS = 'linked_coupons';
    public const AD_CATEGORY_STRUCTURE = 'ad_category_structure';
    public const DETAILED_LOGIN_CHECK_RESPONSE = 'detailed_login_check_response';
    public const NATIVE_FORM_EXTENSION = 'native_form_extension';

    public const IOS_PUSH_ITLOGY_TEAM_CERTIFICATE = 'ios_push_itlogy_team_certificate';
    public const IOS_PUSH_AWARDWALLET_TEAM_CERTIFICATE = 'ios_push_awardwallet_team_certificate';
    public const TEXT_PROPERTY_BLOCK_SUBHINT = 'text_property_block_subhint';
    public const DESANITIZED_STRINGS = 'desanitized_strings';
    public const HIDE_SUBACCOUNTS = 'hide_subaccounts';
    public const CAPITALCARDS_AUTH_V2 = 'capitalcards_auth_v2';
    public const PROFILE_LOCATIONS_SIMPLE = 'profile_locations_simple';
    public const TIMELINE_NO_SHOW_MORE = 'timeline_no_show_more';
    public const NOTIFICATIONS_SETTINGS_INFO = 'notifications_settings_info';
    public const SEPARATORLESS_OAUTH_ACCOUNT_FORM = 'separatorless_oauth_account_form';
    public const NOTIFICATIONS_SETTINGS_REMOVE_GROUP_TITLE = 'notifications_settings_remove_group_title';
    public const LOGIN_CHECK_EMAIL_OTC_STEP_LABELS = 'login_check_email_otc_step_labels';

    public const BANKOFAMERICA_AUTH = 'bankofamerica_auth';
    public const MAILBOX_SCANNER = 'mailbox_scanner';
    public const EXTENSION_NATIVE_EVENTS = 'extension_native_events';
    public const EXTENSION_SIGNED_NATIVE_EVENTS = 'extension_signed_native_events';

    public const MULTIPLE_JS_FORM_EXTENSIONS = 'multiple_js_form_extensions';
    public const ACCOUNT_BALANCE_WATCH = 'account_balance_watch';

    public const UPDATER_ASYNC_EVENTS = 'updater_async_events';

    public const UPGRADE_ACCOUNT_LEVEL_LINK = 'upgrade_account_link';
    public const FORM_INTERFACE_V2 = 'form_interface_v2';
    public const OTHER_SETTINGS_FORM_EXTENSION = 'other_settings_form_extension';
    public const TIMELINE_DETAILED_AIRLINE_INFO = 'timeline_detailed_airline_info';
    public const SPENT_ANALYSIS_FAKE_DATA_FOR_AW_FREE = 'spent_analysis_fake_data_for_aw_free';
    public const MAILBOX_OWNER = 'mailbox_owner';
    public const ACCOUNT_HISTORY_FREE_USER_STUB = 'account_history_free_user_stub';

    public const DOCUMENT_KIND = 'document_kind';
    public const PWNED_TIMES_INFO = 'pwned_times_info';
    public const TERMS_OF_USE_ON_REGISTER = 'terms_of_use_on_register';
    public const PROFILE_OVERVIEW_NEW_LINKS = 'profile_overview_new_links';
    public const PROFILE_OVERVIEW_INFO_LINKS = 'profile_overview_info_links';
    public const SPENT_ANALYSIS_OPEN_FOR_AW_FREE = 'spent_analysis_open_for_aw_free';
    public const BOOKING_MESSAGES_FORMAT_V2 = 'booking_messages_format_v2';

    public const AW_PLUS_TRIAL = 'aw_plus_trial';
    public const FLIGHT_DEALS = 'flight_deals';

    public const PUSH_NOTIFICATIONS_URL_FORMAT = 'push_notifications_url_format';
    public const TIMELINE_PARKINGS_ITEMS = 'timeline_parkings_items';
    public const TIMELINE_PARKINGS_ITEMS_ICON = 'timeline_parkings_items_icon';

    public const TRAVEL_SUMMARY_REPORT = 'travel_summary_report';
    public const AVATAR_JPEG = 'avatar_jpeg';
    public const LOGIN_OAUTH = 'login_oauth';
    public const KEYCHAIN_REAUTH = 'keychain_reauth';
    public const UNLINK_OAUTH = 'unlink_oauth';
    public const DISCOVERED_ACCOUNTS = 'discovered_accounts';
    public const CAPITALCARDS_AUTH_V3 = 'capital_cards_auth_v3';
    public const KEYCHAIN_REAUTHENTICATION_EMPTY_ERROR = 'keychain_reauthentication_empty_error';
    public const TRANSACTION_ANALYZER_DATES_BY_DAY = 'transaction_analyzer_dates_by_day';
    public const TRANSACTION_ANALYZER_CASH_EQUIVALENT = 'transaction_analyzer_cash_equivalent';
    public const TIMELINE_CRUISE_LIST_ITEMS = 'timeline_cruise_list_items';
    public const MILE_VALUE_ACCOUNT_INFO = 'mile_value_account_info';
    public const MILE_VALUE_PROVIDER_CODE = 'mile_value_provider_code';
    public const TIMELINE_SOURCES = 'timeline_sources';
    public const TIMELINE_SAVINGS = 'savings';
    public const TRANSFER_TIMES_ROWS_TO_KEY = 'transfer_times_rows_to_key';
    public const TWO_FACTOR_AUTH_SETUP = 'two_factor_auth_setup';
    public const DOCUMENT_VACCINE_VISA_INSURANCE_TYPES = 'document_vaccine_visa_insurance_types';
    public const ANDROID_NEW_CONSUMABLES = 'android_new_consumables';
    public const ACCOUNT_DETAILS_MILE_VALUE_CUSTOM_BLOCK_TYPE = 'account_details_mile_value_custom_block_type';
    public const LOUNGES = 'lounges';
    public const ACCOUNT_DETAILS_STATUS_LINKS = 'account_details_elitestatus_links';
    public const PARKING_KIND = 'parking_kind';
    public const LOUNGE_OPENING_HOURS = 'lounge_opening_hours';
    public const MY_PROFILE_GROUP_TITLE = 'my_profile_group_title';
    public const DOCUMENT_PRIORITY_PASS = 'document_priority_pass';
    public const NO_FOREIGN_FEES_CARDS = 'no_foreign_fees_cards';
    public const ACCOUNT_TOTALS_IMPROVEMENTS = 'account_totals_improvements';
    public const ADD_ACCOUNT_WITH_STATUS_PROVIDER = 'add_account_with_status_provider';
    public const TRAVEL_PLAN_DURATION = 'travel_plan_duration';
    public const ITINERARY_NOTE_FILES = 'itinerary_note_files';
    public const ITINERARY_NOTE_AND_FILES = 'itinerary_note_and_files';
    public const ANDROID_WEBVIEW_CAPTCHA = 'android_webview_captcha';
    public const ACCOUNT_FORM_REDESIGN_2023_FALL = 'account_form_redesign_2023_fall';
    public const TIMELINE_AI_WARNING = 'timeline_ai_warning';
    public const LOUNGE_AUTO_DETECT_CARDS = 'lounge_auto_detect_cards';
    public const LOUNGE_NULLABLE_OPENED = 'lounge_nullable_opened';
    public const EXTENSION_V3_PARSER = 'extension_v3_parser';
    public const REACT_NATIVE_RENDER_HTML_6_TRANSIENT_RENDER_ENGINE = 'react_native_render_html_6_transient_render_engine';
    public const ACCOUNT_DETAILS_REDESIGN_2024_SUMMER = 'account_details_redesign_2024_summer';
    public const NOTIFICATIONS_SETTINGS_FOR_FREE_USER = 'notifications_settings_for_free_user';
    public const TIMELINE_SOURCES_TRIPIT = 'timeline_sources_tripit';
    public const APPEARANCE_SETTINGS = 'appearance_settings';
    public const TIMELINE_TRIP_TITLE_POINT = 'timeline_trip_title_point';
    public const AT201_SUBSCRIPTION_INFO = 'at201_subscription_info';
    public const SET_LOCATION_LINK = 'set_location_link';
    public const MOVE_TRAVEL_SEGMENTS = 'move_travel_segments';
    public const EXPIRATION_BLOG_FIELDS = 'expiration_date_blog_fields';
    public const EXPIRATION_PASSPORT_BLOG_BLOCK = 'expiration_passport_blog_block';
    public const LOUNGES_OFFLINE = 'lounges_offline';
    public const TIMELINE_READABLE_TRIP_AND_RESTAURANT_DATES = 'timeline_readable_trip_and_restaurant_dates';
    public const STAFF_TRACKED_LOCATIONS_INCREASED_MAX = 'staff_tracked_locations_increased_max';
    public const LOUNGE_BLOG_LINKS = 'lounge_blog_links';
    public const DOCUMENTS_IMPROVEMENTS_2025_APRIL = 'documents_improvements_2025_april';
    public const SPENT_ANALYSIS_FORMAT_V2 = 'spent_analysis_format_v2';
    public const ACCOUNT_BIG3_IMPROVEMENTS = 'account_big3_improvements';
    public const SUBACCOUNT_BLOG_POSTS = 'subaccount_blog_posts';
    public const LOUNGE_PRIORITY_PASS_RESTAURANT = 'lounge_priority_pass_restaurant';

    /**
     * @var array[Version => string[]]
     */
    private $storage;

    /**
     * @var bool
     */
    private $nativeApp;

    /**
     * @var string
     */
    private $platform;

    public function __construct(string $platform)
    {
        $platform = strtolower($platform);
        $this->nativeApp = in_array($platform, [self::IOS, self::ANDROID]);
        $this->platform = $platform;
    }

    /**
     * @return array[Version => array]
     */
    public function getVersions()
    {
        if (null !== $this->storage) {
            return $this->storage;
        }

        return $this->storage = [
            [Parser::toVersion('3.6.0'), array_merge(
                [
                    static::UPDATER_IGNORED_DETAILS,
                    static::DATA_TIMESTAMP_OFF,
                    static::IOS_PUSH_ITLOGY_TEAM_CERTIFICATE,
                    static::OTHER_SETTINGS_FORM_EXTENSION,
                ],
                $this->nativeApp ? [static::NATIVE_APP, $this->platform] : []
            )],
            [Parser::toVersion('3.7.0'), $ver37 = array_merge(
                [
                    static::UPDATER_IGNORED_DETAILS,
                    static::DATA_TIMESTAMP_OFF,
                    static::UPDATER_CLIENT_CHECK,
                    static::BOT_PROTECTION_SOFT_MODE,
                    static::IOS_PUSH_ITLOGY_TEAM_CERTIFICATE,
                    static::OTHER_SETTINGS_FORM_EXTENSION,
                ],
                $this->nativeApp ? [static::NATIVE_APP, $this->platform] : []
            )],
            [Parser::toVersion('3.8.0'), $ver37],
            [Parser::toVersion('3.9.0'), $ver39 = array_merge($ver37, [static::TIMELINE])],
            [Parser::toVersion('3.10.0'), $ver310 = array_merge($ver39, [static::UPDATER_CLIENT_CHECK_LOGS, static::UPDATER_EVENTS, static::UPDATER_FORCED_TRIPS])],
            [Parser::toVersion('3.11.0'), $ver311 = array_merge($ver310, [static::TIMELINE_PLANS_ITEMS])],
            [Parser::toVersion('3.12.0'), $ver312 = array_merge($ver311, [static::OAUTH_PROVIDERS])],
            [Parser::toVersion('3.13.0'), $ver313 = array_merge($ver312, [static::JS_FORM_EXTENSIONS])],

            [Parser::toVersion('3.14.0'), $ver314 = array_merge($ver313, [
                static::PROFILE_FORMS,
                static::MY_PROFILE_GROUP_TITLE,
            ])],

            [Parser::toVersion('3.15.0'), $ver315 = array_merge($ver314, [
                static::FORM_LINKED_CHOICES,
            ])],

            [Parser::toVersion('3.16.0'), $ver316 = array_merge($ver315, [
                static::AWPLUS_VIA_COUPON_ROUTE,
                static::AWPLUS_VIA_COUPON_PROFILE_OVERVIEW,
                static::BOOKING_VIEW,
            ])],

            [Parser::toVersion('3.17.0'), $ver317 = array_merge($ver316, [
                static::SCRIPTED_BOT_PROTECTION,
                static::ACCOUNT_BLOCK_WARNING,
                static::CUSTOM_ACCOUNTS,
                static::FORM_DATE_PICKER,
                static::FORM_TEXTAREA,
            ])],

            [Parser::toVersion('3.18.0'), $ver318 = $this->disableIosFeatures(
                array_merge($ver317, [
                    static::AWPLUS_SUBSCRIBE,
                    static::GEO_NOTIFICATIONS,
                    static::ACCOUNT_FAMILY_NAME,
                    static::TIMELINE_OWNER_NAME,
                    static::COUPON_ERROR_EXTENSION,
                    static::TIMELINE_OFFERS,
                    static::TIMELINE_FLIGHT_PROGRESS,
                    static::CARD_OFFERS,
                ]),
                [
                    static::AWPLUS_VIA_COUPON_ROUTE,
                    static::AWPLUS_VIA_COUPON_PROFILE_OVERVIEW,
                ]
            )],

            [Parser::toVersion('3.18.1'), $ver318_1 = array_merge(
                $ver318,
                [static::AWPLUS_DESCRIPTION_LONG]
            )],

            [Parser::toVersion('3.19.0'), $ver319 = array_merge($ver318_1, [
                static::CARD_IMAGES,
            ])],

            [Parser::toVersion('3.20.0'), $ver320 = array_merge($ver319, [
                static::ADVANCED_NOTIFICATIONS_SETTINGS,
                static::BARCODES,
                static::TIMELINE_BLOCKS_V2,
                static::ADS_SETTINGS,
            ])],

            [Parser::toVersion('3.21.0'), $ver321 = array_merge($ver320, [
                static::REGIONAL_SETTINGS,
            ])],

            [Parser::toVersion('3.21.11'), $ver321_11 = array_merge($ver321, [
                static::PHONE_CALL_FROM_NOTIFICATION,
            ])],

            [Parser::toVersion('3.22.0'), $ver322 = array_merge($ver321_11, [
                static::CARD_IMAGES_ON_FORM,
            ])],
            [Parser::toVersion('3.23.0'), $ver323 = array_merge($ver322, [
                static::BOT_PROTECTION_KEY_V1,
            ])],
            [Parser::toVersion('3.23.1'), $ver323_1 = array_merge(
                $ver322,
                $this->enableFeaturesByPlatform(
                    [static::BOT_PROTECTION_KEY_ANDROID_V1],
                    [static::BOT_PROTECTION_KEY_IOS_V1],
                    []
                )
            )],

            [Parser::toVersion('3.24.0'), $ver324 = array_merge($ver323_1, [
                static::LOCATION_STORAGE,
            ])],

            [Parser::toVersion('3.24.14'), $ver324_14 = array_merge($ver324, [
                static::PASSWORD_ACCESS,
                static::TIMELINE_TAXI_RIDE,
            ])],

            [Parser::toVersion('3.25.0'), $ver325_0 = array_merge($ver324_14, [
                static::LINKED_COUPONS,
            ])],

            [Parser::toVersion('3.26.0'), $ver326_0 = array_merge($ver325_0, [
                static::DETAILED_LOGIN_CHECK_RESPONSE,
            ])],

            [Parser::toVersion('3.27.0'), $ver327_0 = array_merge($ver326_0, [
                static::IOS_PUSH_AWARDWALLET_TEAM_CERTIFICATE,
                static::TEXT_PROPERTY_BLOCK_SUBHINT,
            ])],

            [Parser::toVersion('3.28.0'), $ver328_0 = array_merge($ver327_0, [
                static::HIDE_SUBACCOUNTS,
            ])],

            [Parser::toVersion('3.29.0'), $ver329_0 = array_merge($ver328_0, [])],

            [Parser::toVersion('3.29.8'), $ver329_8 = array_merge($ver329_0, [
                static::BANKOFAMERICA_AUTH,
            ])],
            [Parser::toVersion('3.30.0'), $ver330_0 = array_merge($ver329_8, [
                static::MULTIPLE_JS_FORM_EXTENSIONS,
                static::ACCOUNT_BALANCE_WATCH,
                static::UPGRADE_ACCOUNT_LEVEL_LINK,
                static::FORM_INTERFACE_V2,
                static::PWNED_TIMES_INFO,
            ])],

            [Parser::toVersion('3.31.0'), $ver331_0 = \array_merge($ver330_0, [
                static::TERMS_OF_USE_ON_REGISTER,
                static::DOCUMENT_KIND,
                static::SPENT_ANALYSIS_OPEN_FOR_AW_FREE,
                static::AW_PLUS_TRIAL,
            ])],

            [Parser::toVersion('3.32.0'), $ver332_0 = $ver331_0],
            [Parser::toVersion('3.33.0'), $ver333_0 = \array_merge($ver332_0, [
                static::LOGIN_OAUTH,
                static::TRANSFER_TIMES_ROWS_TO_KEY,
            ])],
            [Parser::toVersion('3.34.0'), $ver334_0 = \array_merge($ver333_0, [
                static::PARKING_KIND,
                static::TIMELINE_PARKINGS_ITEMS,
                static::TIMELINE_PARKINGS_ITEMS_ICON,
            ])],

            [Parser::toVersion('4.0.0'), $ver400 = array_diff(array_merge($ver325_0, [
                static::AD_CATEGORY_STRUCTURE,
                static::IOS_PUSH_AWARDWALLET_TEAM_CERTIFICATE,
                static::DETAILED_LOGIN_CHECK_RESPONSE,
                static::NATIVE_FORM_EXTENSION,
                static::TEXT_PROPERTY_BLOCK_SUBHINT,
                static::DESANITIZED_STRINGS,
                static::HIDE_SUBACCOUNTS,
                static::CAPITALCARDS_AUTH_V2,
                static::PROFILE_LOCATIONS_SIMPLE,
                static::TIMELINE_NO_SHOW_MORE,
                static::NOTIFICATIONS_SETTINGS_INFO,
                static::SEPARATORLESS_OAUTH_ACCOUNT_FORM,
                static::NOTIFICATIONS_SETTINGS_REMOVE_GROUP_TITLE,
                static::LOGIN_CHECK_EMAIL_OTC_STEP_LABELS,
                static::TIMELINE_PARKINGS_ITEMS,
            ]), [static::OTHER_SETTINGS_FORM_EXTENSION])],

            [Parser::toVersion('4.0.4'), $ver404 = array_merge($ver400, [
                static::BANKOFAMERICA_AUTH,
            ])],

            [Parser::toVersion('4.1.0'), $ver410 = array_merge($ver404, [
            ])],

            [Parser::toVersion('4.2.0'), $ver420 = array_merge($ver410, [
            ])],

            [Parser::toVersion('4.3.0'), $ver430 = array_merge($ver420, [
                static::MAILBOX_SCANNER,
                static::EXTENSION_NATIVE_EVENTS,
            ])],

            [Parser::toVersion('4.3.7'), $ver437 = array_merge($ver430, [
                static::EXTENSION_SIGNED_NATIVE_EVENTS,
            ])],

            [Parser::toVersion('4.4.0'), $ver440 = array_merge($ver437, [
                static::MULTIPLE_JS_FORM_EXTENSIONS,
                static::ACCOUNT_BALANCE_WATCH,
                static::FORM_INTERFACE_V2,
            ])],

            [Parser::toVersion('4.5.0'), $ver450 = \array_merge($ver440, [
            ])],

            [Parser::toVersion('4.6.0'), $ver460 = \array_merge($ver450, [
                static::TIMELINE_DETAILED_AIRLINE_INFO,
                static::SPENT_ANALYSIS_FAKE_DATA_FOR_AW_FREE,
            ])],

            [Parser::toVersion('4.7.0'), $ver470 = \array_merge($ver460, [
                static::MAILBOX_OWNER,
            ])],

            [Parser::toVersion('4.7.3'), $ver473 = \array_merge($ver470, [
                static::ACCOUNT_HISTORY_FREE_USER_STUB,
            ])],

            [Parser::toVersion('4.8.0'), $ver480 = \array_merge($ver473, [
                static::PWNED_TIMES_INFO,
            ])],

            [Parser::toVersion('4.9.0'), $ver490 = \array_merge($ver480, [
                static::TERMS_OF_USE_ON_REGISTER,
                static::DOCUMENT_KIND,
            ])],

            [Parser::toVersion('4.10.0'), $ver410 = \array_merge($ver490, [
                static::PROFILE_OVERVIEW_NEW_LINKS,
                static::PROFILE_OVERVIEW_INFO_LINKS,
                static::SPENT_ANALYSIS_OPEN_FOR_AW_FREE,
            ])],

            [Parser::toVersion('4.11.0'), $ver411 = \array_merge($ver410, [
            ])],

            [Parser::toVersion('4.12.0'), $ver412 = array_merge($ver411, [
            ])],

            [Parser::toVersion('4.13.0'), $ver413 = array_merge($ver412, [
                static::BOOKING_MESSAGES_FORMAT_V2,
                static::AW_PLUS_TRIAL,
            ])],

            [Parser::toVersion('4.14.0'), $ver414 = array_merge($ver413, [
                static::FLIGHT_DEALS,
            ])],

            [Parser::toVersion('4.15.0'), $ver415 = $ver414],

            [Parser::toVersion('4.16.0'), $ver416 = array_merge($ver415, [
            ])],

            [Parser::toVersion('4.17.0'), $ver417 = array_merge($ver416, [
                static::PUSH_NOTIFICATIONS_URL_FORMAT,
            ])],

            [Parser::toVersion('4.18.0'), $ver418 = array_merge($ver417, [
                self::TIMELINE_PARKINGS_ITEMS_ICON,
            ])],

            [Parser::toVersion('4.19.0'), $ver419 = array_merge($ver418, [
                self::TRAVEL_SUMMARY_REPORT,
                self::AVATAR_JPEG,
            ])],

            [Parser::toVersion('4.20.0'), $ver420 = array_merge(
                $ver419,
                [
                    self::LOGIN_OAUTH,
                    self::KEYCHAIN_REAUTH,
                    self::UNLINK_OAUTH,
                ]
            )],

            [Parser::toVersion('4.21.0'), $ver421 = array_merge(
                $ver420,
                [
                    static::CAPITALCARDS_AUTH_V3,
                ]
            )],

            [Parser::toVersion('4.22.0'), $ver422 = array_merge(
                $ver421,
                [
                    static::DISCOVERED_ACCOUNTS,
                ]
            )],

            [Parser::toVersion('4.22.18'), $ver422_18 = array_merge(
                $ver422,
                [
                    static::KEYCHAIN_REAUTHENTICATION_EMPTY_ERROR,
                ]
            )],

            [Parser::toVersion('4.22.20'), $ver422_20 = array_merge(
                $ver422_18,
                $this->enableIosFeatures([static::AWPLUS_VIA_COUPON_ROUTE])
            )],

            [Parser::toVersion('4.23.0'), $ver423 = array_merge(
                $ver422_20,
                [
                    static::TRANSACTION_ANALYZER_DATES_BY_DAY,
                ]
            )],

            [Parser::toVersion('4.24.0'), $ver424 = array_merge(
                $ver423,
                [
                    static::MILE_VALUE_ACCOUNT_INFO,
                ]
            )],

            [Parser::toVersion('4.25.0'), $ver425 = array_merge(
                $ver424,
                [
                    static::UPDATER_ASYNC_EVENTS,
                ]
            )],
            [Parser::toVersion('4.26.0'), $ver426 = array_merge(
                $ver425,
                [
                    static::TIMELINE_CRUISE_LIST_ITEMS,
                ]
            )],
            [Parser::toVersion('4.27.0'), $ver427 = array_merge(
                $ver426,
                [
                    static::TIMELINE_SAVINGS,
                    static::TIMELINE_SOURCES,
                ]
            )],
            [Parser::toVersion('4.28.0'), $ver428 = array_merge(
                $ver427,
                [
                    static::DOCUMENT_VACCINE_VISA_INSURANCE_TYPES,
                ]
            )],
            [Parser::toVersion('4.29.0'), $ver429 = array_merge(
                $ver428,
                [
                    static::TWO_FACTOR_AUTH_SETUP,
                ],
                $this->enableAndroidFeatures([static::ANDROID_NEW_CONSUMABLES])
            )],
            [Parser::toVersion('4.30.0'), $ver430 = array_merge(
                $ver429,
                [
                    self::ACCOUNT_DETAILS_MILE_VALUE_CUSTOM_BLOCK_TYPE,
                ],
            )],
            [Parser::toVersion('4.31.0'), $ver431 = array_merge(
                $ver430,
                [
                ],
            )],
            [Parser::toVersion('4.32.0'), $ver432 = array_merge(
                $ver431,
                [
                    self::ACCOUNT_DETAILS_STATUS_LINKS,
                ],
            )],
            [Parser::toVersion('4.33.0'), $ver433 = array_merge(
                $ver432,
                [
                    self::PARKING_KIND,
                ],
            )],
            [Parser::toVersion('4.34.0'),  $ver434 = array_merge(
                $ver433,
                [
                ],
            )],

            [Parser::toVersion('4.35.0'), $ver435 = $this->disableFeaturesForAll(
                array_merge(
                    $ver434,
                    [
                        self::LOUNGES,
                        self::LOUNGE_OPENING_HOURS,
                    ],
                ),
                [
                    static::PROFILE_OVERVIEW_INFO_LINKS,
                    static::MY_PROFILE_GROUP_TITLE,
                ]
            )],

            [Parser::toVersion('4.36.0'), $ver436 = array_merge($ver435, [
                self::DOCUMENT_PRIORITY_PASS,
            ])],

            [Parser::toVersion('4.37.0'), $ver437 = array_merge($ver436, [
                self::ACCOUNT_TOTALS_IMPROVEMENTS,
            ])],

            [Parser::toVersion('4.38.0'), $ver438 = array_merge($ver437, [
                self::NO_FOREIGN_FEES_CARDS,
            ])],

            [Parser::toVersion('4.39.0'), $ver439 = array_merge($ver438, [])],

            [Parser::toVersion('4.40.0'), $ver440 = array_merge($ver439, [
                self::ADD_ACCOUNT_WITH_STATUS_PROVIDER,
                self::TRAVEL_PLAN_DURATION,
            ])],

            [Parser::toVersion('4.41.0'), $ver441 = array_merge($ver440, [
                self::ITINERARY_NOTE_FILES,
            ])],

            [Parser::toVersion('4.41.23'), $ver441_23 = array_merge($ver441,
                $this->enableAndroidFeatures([self::ANDROID_WEBVIEW_CAPTCHA])
            )],

            [Parser::toVersion('4.42.0'), $ver442 = array_merge($ver441_23, [
                self::ACCOUNT_FORM_REDESIGN_2023_FALL,
            ])],

            [Parser::toVersion('4.43.0'), $ver443 = array_merge($ver442, [
                self::TIMELINE_AI_WARNING,
            ])],

            [Parser::toVersion('4.44.0'), $ver444 = array_merge($ver443, [
                self::LOUNGE_AUTO_DETECT_CARDS,
                self::ITINERARY_NOTE_AND_FILES,
            ])],

            [Parser::toVersion('4.45.0'), $ver445 = array_merge($ver444, [
                self::LOUNGE_NULLABLE_OPENED,
            ])],

            [Parser::toVersion('4.46.0'), $ver446 = array_merge($ver445, [
                self::REACT_NATIVE_RENDER_HTML_6_TRANSIENT_RENDER_ENGINE,
            ])],

            [Parser::toVersion('4.47.0'), $ver447 = array_merge($ver446, [
                self::ACCOUNT_DETAILS_REDESIGN_2024_SUMMER,
            ])],

            [Parser::toVersion('4.48.0'), $ver448 = array_merge($ver447, [
                self::NOTIFICATIONS_SETTINGS_FOR_FREE_USER,
                self::TIMELINE_SOURCES_TRIPIT,
            ])],

            [Parser::toVersion('4.48.9'), $ver448_9 = array_merge($ver448, [
                self::APPEARANCE_SETTINGS,
                self::AT201_SUBSCRIPTION_INFO,
                self::SET_LOCATION_LINK,
            ])],

            [Parser::toVersion('4.48.10'), $ver448_10 = array_merge($ver448_9, [
                self::TIMELINE_TRIP_TITLE_POINT,
            ])],

            [Parser::toVersion('4.48.13'), $ver448_13 = array_merge($ver448_10, [
                self::MOVE_TRAVEL_SEGMENTS,
            ])],

            [Parser::toVersion('4.49.0'), $ver449 = array_merge($ver448_13, [
                self::MILE_VALUE_PROVIDER_CODE,
                self::TRANSACTION_ANALYZER_CASH_EQUIVALENT,
            ])],

            [Parser::toVersion('4.49.1'), $ver449_1 = array_merge($ver449, [
                self::LOUNGES_OFFLINE,
            ])],

            [Parser::toVersion('4.49.6'), $ver449_6 = array_merge($ver449_1, [
                self::TIMELINE_READABLE_TRIP_AND_RESTAURANT_DATES,
            ])],

            [Parser::toVersion('4.49.12'), $ver449_12 = array_merge($ver449_6, [
                self::STAFF_TRACKED_LOCATIONS_INCREASED_MAX,
            ])],

            [Parser::toVersion('4.49.17'), $ver449_17 = array_merge($ver449_12, [
            ])],

            [Parser::toVersion('4.50.0'), $ver450_0 = array_merge($ver449_17, [
                self::EXPIRATION_BLOG_FIELDS,
                self::LOUNGE_BLOG_LINKS,
            ])],

            [Parser::toVersion('4.51.0'), $ver451_0 = array_merge($ver450_0, [
                self::EXPIRATION_PASSPORT_BLOG_BLOCK,
            ])],

            [Parser::toVersion('4.52.0'), $ver452_0 = array_merge($ver451_0, [
                self::DOCUMENTS_IMPROVEMENTS_2025_APRIL,
            ])],

            [Parser::toVersion('4.53.0'), $ver453_0 = array_merge($ver452_0, [
            ])],

            [Parser::toVersion('4.54.0'), $ver454_0 = array_merge($ver453_0, [
                self::SPENT_ANALYSIS_FORMAT_V2,
            ])],

            [Parser::toVersion('4.55.0'), $ver455_0 = array_merge($ver454_0, [
                self::SUBACCOUNT_BLOG_POSTS,
            ])],

            [Parser::toVersion('4.55.5'), $lastStable = $ver455_5 = array_merge($ver455_0, [
                self::ACCOUNT_BIG3_IMPROVEMENTS,
            ])],

            [Parser::toVersion('4.99.0'), $ver499 = array_merge($lastStable, [
                self::EXTENSION_V3_PARSER,
                self::LOUNGE_PRIORITY_PASS_RESTAURANT,
            ])],
        ];
    }

    protected function enableFeaturesByPlatform(array $androidFeatures, array $iosFeatures, array $webFeatures): array
    {
        if (self::ANDROID === $this->platform) {
            return $androidFeatures;
        } elseif (self::IOS === $this->platform) {
            return $iosFeatures;
        } else {
            return $webFeatures;
        }
    }

    protected function enableAndroidFeatures(array $features): array
    {
        return $this->enableFeaturesByPlatform($features, [], []);
    }

    protected function enableIosFeatures(array $features): array
    {
        return $this->enableFeaturesByPlatform([], $features, []);
    }

    protected function enableWebFeatures(array $features): array
    {
        return $this->enableFeaturesByPlatform([], [], $features);
    }

    protected function disableFeaturesByPlatform(array $originalFeatures, array $disabledAndroidFeatures, array $disabledIosFeatures, array $disabledWebFeatures): array
    {
        if (self::ANDROID === $this->platform) {
            return $this->removeFromArray($originalFeatures, $disabledAndroidFeatures);
        } elseif (self::IOS === $this->platform) {
            return $this->removeFromArray($originalFeatures, $disabledIosFeatures);
        } else {
            return $this->removeFromArray($originalFeatures, $disabledWebFeatures);
        }
    }

    protected function disableFeaturesForAll(array $originalFeatures, array $disabledFeatures): array
    {
        return $this->removeFromArray($originalFeatures, $disabledFeatures);
    }

    protected function disableAndroidFeatures(array $originalFeatures, array $disabledAndroidFeatures): array
    {
        return $this->disableFeaturesByPlatform($originalFeatures, $disabledAndroidFeatures, [], []);
    }

    protected function disableIosFeatures(array $originalFeatures, array $disabledIosFeatures): array
    {
        return $this->disableFeaturesByPlatform($originalFeatures, [], $disabledIosFeatures, []);
    }

    protected function disableWebFeatures(array $originalFeatures, array $disabledWebFeatures): array
    {
        return $this->disableFeaturesByPlatform($originalFeatures, [], [], $disabledWebFeatures);
    }

    protected function removeFromArray(array $array, array $values): array
    {
        foreach ($values as $value) {
            $index = \array_search($value, $array);

            if ($index !== false) {
                unset($array[$index]);
            }
        }

        return $array;
    }
}
