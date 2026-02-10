<?php

// formats
// see public php, session-based

// database encoding
define("ENCODING_NAMES", "utf8");
define("ENCODING_CHARACTERS", "utf8");

// database user ids to exclude from stats
$USERS_TO_EXCLUDE = [5, 13, 24, 26, 7, 9, 12, 47, 58, 81];

// web site parameters
define("SITE_NAME", "AwardWallet.com");
define("SUPPORT_EMAIL", "support@" . SITE_NAME);
define("TECHSUPPORT_EMAIL", "techsupport@" . SITE_NAME);
define("SECURITY_EMAIL", "security@" . SITE_NAME);
define("FROM_EMAIL", "info@" . SITE_NAME);
define("DO_NOT_REPLY_EMAIL", "do.not.reply@" . SITE_NAME);
$Config[CONFIG_ERROR_EMAIL] = 'error@' . SITE_NAME;
$Config[CONFIG_SALES_EMAIL] = 'info@' . SITE_NAME;
$Config[CONFIG_BCC_EMAIL] = 'notifications@' . SITE_NAME;
$Config[CONFIG_CONTACT_BCC] = 'notifications@' . SITE_NAME;
$Config[CONFIG_SECURE_EMAIL] = 'alexi@' . SITE_NAME;
$Config[CONFIG_HTML_EDITOR_CLASS] = 'TCKEditorFieldManager';
$Config[CONFIG_PASSWORD_ENCODING] = 'symfonyPasswordEncoding';

// email
define('EMAIL_IMAGES_PATH', '/images/email/design');
define("EMAIL_HEADERS", "Content-Type: text/plain; charset=utf-8\nDate: " . date('r') . " \nFrom: " . SITE_NAME . " <" . FROM_EMAIL . ">\nReply-To: " . SITE_NAME . " <" . FROM_EMAIL . ">\nBcc: notifications@awardwallet.com");
define("EMAIL_PHYSICAL_ADDRESS_PLAIN", "AwardWallet LLC, 17 S. Commerce Way 20112, Lehigh Valley, PA 18002");
define("AW_MERCHANT", "AWARDWALLET");

// color scheme - design related
define("COLOR_GRAY", "#999999");
define("COLOR_RED", "Red");
define("FORM_TITLE_COLOR", "#538fc3");
define('HEADER_FOOTER_HEIGHT', 450);
define('PAGE_WIDTH', 765);
define('ERROR_BG', '#faeada');

// thumbnails and pictures
define("THUMBNAIL_WIDTH", 64);
define("THUMBNAIL_HEIGHT", 64);
define("PICTURE_WIDTH", 800);
define("PICTURE_HEIGHT", 800);
define("MEDIUM_WIDTH", 250);
define("MEDIUM_HEIGHT", 250);
define("ALBUM_ROOT", "/www/new_site/images/uploaded/album");
define("ALBUM_VIRTUAL_ROOT", "/images/uploaded/album");
define('THUMBNAIL_CROP', true);

// determine site mode
define('SITE_MODE_PERSONAL', 1);
define('SITE_MODE_BUSINESS', 2);

// if(isset($_SERVER['HTTP_HOST']) && (stripos($_SERVER['HTTP_HOST'], "business.") === 0))
if (isset($_SERVER['HTTP_HOST']) && preg_match('/^business[-\w]*\./i', $_SERVER['HTTP_HOST'])) {
    define('SITE_MODE', SITE_MODE_BUSINESS);
} else {
    define('SITE_MODE', SITE_MODE_PERSONAL);
}

// determine branded mode
define('SITE_BRAND_NONE', 'awardwallet');
define('SITE_BRAND_CWT', 'cwt');
define('SITE_BRAND_BWR', 'bwr');

if (isset($_SERVER['HTTP_HOST']) && (stripos($_SERVER['HTTP_HOST'], "cwt.") === 0)) {
    define('SITE_BRAND', SITE_BRAND_CWT);
} elseif (isset($_SERVER['HTTP_HOST']) && (stripos($_SERVER['HTTP_HOST'], "bwr.") === 0)) {
    define('SITE_BRAND', SITE_BRAND_BWR);
} else {
    define('SITE_BRAND', SITE_BRAND_NONE);
}

// miles
define("MIN_MILES_TO_SELL", 30000);
define("MAX_MILES_TO_SELL", 200000);
define("MILES_TO_SELL_STEP", 10000);

// sales
define("SALE_PENDING", 1);
define("SALE_COMPLETE", 2);
define("SALE_CANCELLED_BY_USER", 3);
define("SALE_CANCELLED_BY_PARTNER", 4);
// define('SECONDS_PER_DAY', 60*60*24);
define('TRANSACTION_RESPONSE_PERIOD', 10);

// booking expiration time in minutes
define("AIR_EXPIRATION", 25);
define("HOTEL_EXPIRATION", 25);
define("CAR_EXPIRATION", 25);

// save password mode
define('SAVE_PASSWORD_DATABASE', 1);
define('SAVE_PASSWORD_LOCALLY', 2);
$SAVE_PASSWORD = [
    SAVE_PASSWORD_DATABASE => "With AwardWallet.com",
    SAVE_PASSWORD_LOCALLY => "Locally on this computer",
];

// reviews
define("DEFAULT_REVIEW_TEXT", "Maximum 2000 Characters");

// stats
define("NUMBER_OF_PROGRAMS_TO_ADD", 30);
define("NUMBER_OF_PROGRAMS_TO_MULTIPLY", 12);
define("MINUTES_TO_CHECK_BALANCE", 4);

// cart
define('ACCOUNT_LEVEL_FREE', 1);
define('ACCOUNT_LEVEL_AWPLUS', 2);
define('ACCOUNT_LEVEL_BUSINESS', 3);
$arAccountLevel = [
    ACCOUNT_LEVEL_FREE => "Regular",
    ACCOUNT_LEVEL_AWPLUS => "AwardWallet Plus",
    ACCOUNT_LEVEL_BUSINESS => "AwardWallet Business",
];

/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS", 1); // \AwardWallet\MainBundle\Entity\CartItem\AwPlus::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_TOP10", 2); // \AwardWallet\MainBundle\Entity\CartItem\Donation::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_20", 3); // \AwardWallet\MainBundle\Entity\CartItem\AwPlus20Year::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_1", 4); // \AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWB", 5); // \AwardWallet\MainBundle\Entity\CartItem\AwBusiness::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWB_PLUS", 6); // \AwardWallet\MainBundle\Entity\CartItem\AwBusinessPlus::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_ONE_CARD", 7); // \AwardWallet\MainBundle\Entity\CartItem\OneCard::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_ONE_CARD_SHIPPING", 8); // \AwardWallet\MainBundle\Entity\CartItem\OneCardShipping::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_BOOKING", 9); // \AwardWallet\MainBundle\Entity\CartItem\Booking::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_DISCOUNT", 12); // \AwardWallet\MainBundle\Entity\CartItem\Discount::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_TRIAL", 10); // \AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_1_ONE_CARD", 11); // \AwardWallet\MainBundle\Entity\Coupon::SERVICE_AWPLUS_1_YEAR_AND_ONE_CARD
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_ONE_CARD", 13); // \AwardWallet\MainBundle\Entity\Coupon::SERVICE_AWPLUS_ONE_CARD
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_RECURRING", 14); // \AwardWallet\MainBundle\Entity\CartItem\AwPlusRecurring::TYPE
/**
 * @deprecated
 */
define("CART_ITEM_AWPLUS_SUBSCRIPTION", 17); // \AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription::TYPE

define('BUSINESS_TRIAL_PERIOD', 3); // months

// \AwardWallet\MainBundle\Entity\Cartitem::$names
$arCartItemName = [
    CART_ITEM_AWPLUS => "Account upgrade from regular to AwardWallet Plus",
    CART_ITEM_AWPLUS_1 => "Account upgrade from regular to AwardWallet Plus for 1 year",
    CART_ITEM_AWPLUS_20 => "Account upgrade from regular to AwardWallet Plus for 20 years",
    CART_ITEM_AWB => "Business Account gets 100 users",
    CART_ITEM_AWB_PLUS => "Business Account gets the next 100 users",
    CART_ITEM_ONE_CARD => "OneCard Credit(s)",
    CART_ITEM_ONE_CARD_SHIPPING => "OneCard shipping",
    CART_ITEM_AWPLUS_TRIAL => "Account upgrade from regular to AwardWallet Plus, 3 months trial", /* checked */
    CART_ITEM_AWPLUS_RECURRING => "Recurring payment $%d every 6 months",
];
$arCartItemPrice = [
    CART_ITEM_AWPLUS => 5,
    CART_ITEM_AWPLUS_1 => 10,
];

define('CART_FLAG_RECURRING', 100); // \AwardWallet\MainBundle\Entity\CartItem\AwPlus::FLAG_RECURRING
define('CART_FLAG_RECURRING_ONECARD', 101); // \AwardWallet\MainBundle\Entity\CartItem\OneCard::FLAG_RECURRING_ONECARD

$arPaymentType[PAYMENTTYPE_BITCOIN] = "Bitcoin";
unset($arPaymentType[PAYMENTTYPE_MAILINCHECK]);

define('PAYPAL_PROFILE_PATH', "/usr/paypal/cert");
define('PAYPAL_TEST_PROFILE_CODE', 'test'); // sandbox
define('PAYPAL_TEST_PASSWORD', 'B3TN3E6SQ5KM95Y3');

define('ACCESS_READ_NUMBER', 0);
define('ACCESS_READ_BALANCE_AND_STATUS', 1);
define('ACCESS_READ_ALL', 2);
define('ACCESS_WRITE', 3);
define('ACCESS_ADMIN', 4);
define('ACCESS_NONE', 5);
define('ACCESS_BOOKING_MANAGER', 6);
define('ACCESS_BOOKING_VIEW_ONLY', 7);
define('ACCESS_BOOKING_ADMINISTRATOR', ACCESS_BOOKING_MANAGER); // ref #8434

define('TRIP_ACCESS_READ_ONLY', 0);
define('TRIP_ACCESS_FULL_CONTROL', 1);

$arAgentAccessLevelsPersonal = [
    ACCESS_READ_NUMBER => 'Read account numbers / usernames and elite statuses only',
    ACCESS_READ_BALANCE_AND_STATUS => 'Read account balances and elite statuses only',
    ACCESS_READ_ALL => 'Read all information excluding password',
    ACCESS_WRITE => 'Full control (edit, delete, auto-login, view password, manage travel)',
];
$arAgentAccessLevelsBusiness = [
    ACCESS_ADMIN => 'Full control (Administrator)', /* checked */
    ACCESS_NONE => 'No access (Regular member)',
];
$arAgentAccessLevelsBooking = [
    ACCESS_ADMIN => 'Full control (Administrator)', /* checked */
    ACCESS_BOOKING_MANAGER => 'Booking Administrator (Manager)',
    ACCESS_BOOKING_VIEW_ONLY => 'Booking View Only',
    ACCESS_NONE => 'No access (Regular member)',
];
$arAgentAccessLevelsAll = array_merge($arAgentAccessLevelsPersonal, $arAgentAccessLevelsBusiness);
$arAgentAccessLevels = $arAgentAccessLevelsPersonal;

if (SITE_MODE == SITE_MODE_BUSINESS) {
    $arAgentAccessLevels = $arAgentAccessLevelsBusiness;
}

define('UNFILED_PLAN_TITLE', 'Unfiled items');
define('EXPIRATION_DATE_LIMIT', 3);

define('TRACKING_AWARDS', 1);
define('TRACKING_TRIPS', 2);

if (!isset($NO_DATABASE)) {
    global $CLOUD_CONFIG;

    if (isset($CLOUD_CONFIG['Maintenance']) && $CLOUD_CONFIG['Maintenance']
        && isset($_SERVER['REQUEST_METHOD'])
        && ($_SERVER['SCRIPT_NAME'] != '/maintenance.php') && ($_SERVER['SCRIPT_NAME'] != '/status.php')) {
        Redirect("/maintenance.php");
    }
}

// Rewards activity notifications

define('REWARDS_NOTIFICATION_NEVER', 0);
define('REWARDS_NOTIFICATION_WEEK', 1);
define('REWARDS_NOTIFICATION_MONTH', 2);
define('REWARDS_NOTIFICATION_DAY', 3);

// Checkin notifications
define('CHECKIN_REMINDER_OFF', 0);
define('CHECKIN_REMINDER_ON', 1);

// Language to locale
define('LANG_EN_LOCALE', 'en_US');
define('LANG_RU_LOCALE', 'ru_RU');

define('ADNAME_FULL', 1);
define('ADNAME_FIRST', 2);
define('ADNAME_HIDDEN', 3);
$arAdNameOptions = [
    ADNAME_FULL => "Show full name",
    ADNAME_FIRST => "Show first name, last initial only",
    ADNAME_HIDDEN => "Do not show my name",
];

define('FILE_VERSION', 599);

define('FACEBOOK_KEY', 'a2a0b714923bbb65ec64801655357854');
define('FACEBOOK_SECRET', '7c5adbf9f3dfcb426bcabd2a62798dac');

// ad kinds
define('ADKIND_SOCIAL', 1);
define('ADKIND_EMAIL', 2);
define('ADKIND_BALANCE_CHECK', 3);
define('ADKIND_RETENTION', 4);

// regional
define('DATEFORMAT_US', 1);
define('DATEFORMAT_EU', 2);

$Config[CONFIG_THROUGH_PROXY] = true;

define('SQL_USER_NAME', "coalesce(concat(trim(concat(ua.FirstName, ' ', coalesce(ua.MidName, ''))), ' ', ua.LastName), case when u.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . " then u.Company else concat(trim(concat(u.FirstName, ' ', coalesce(u.MidName, ''))), ' ', u.LastName) end )");
define('SQL_ACCOUNT_RAW_BALANCE', "trim(trailing '.' FROM trim(trailing '0' FROM ROUND(a.Balance, 10)))");
define('SQL_ACCOUNT_DISPLAY_NAME', 'COALESCE( p.DisplayName, a.ProgramName )');
define('SQL_COUPON_DISPLAY_NAME', 'c.ProgramName');

define('PERSONAL_INTERFACE_MAX_USERS', 20); // only personal interface!
define('PERSONAL_INTERFACE_MAX_ACCOUNTS', 200); // only personal interface!
define('MAX_ACCOUNTS_PER_PERSON', 200); // business and personal interfaces
define('MAX_LIKE_LP_PER_PERSON', 3); // business and personal interfaces

// removes restrictions (PERSONAL_INTERFACE_MAX_USERS, PERSONAL_INTERFACE_MAX_ACCOUNTS, MAX_ACCOUNTS_PER_PERSON, MAX_LIKE_LP_PER_PERSON)
global $eliteUsers;
$eliteUsers = [
    // Personal accounts
    7, // siteadmin
    103805, // tslatus
    19835, // lauterbrunnen53
    17816, // bdbsubscribe
    93937, // beaubo
    265996, // SFvalue
    74619, // act998
    10544, // EugeneV
    190406, // pmcarrion
    219804, // carlcmc
    266326, // Chazrick46
    480950, // chrismoss7
    608608, // gilhamj
    48759, // laurieb
    727724, // allouncers
    283158, // lisatolliver
    194491, // allen060421
    192365, // npasic75

    // Business accounts
];

// provider phones
define('PHONE_FOR_GENERAL', 1);
define('PHONE_FOR_RESERVATIONS', 2);
define('PHONE_FOR_CUSTOMER_SUPPORT', 3);
define('PHONE_FOR_MEMBER_SERVICES', 4);
define('PHONE_FOR_AWARD_TRAVEL', 5);
$phoneForOptions = [
    PHONE_FOR_GENERAL => 'General',
    PHONE_FOR_RESERVATIONS => 'Reservations',
    PHONE_FOR_CUSTOMER_SUPPORT => 'Customer support',
    PHONE_FOR_MEMBER_SERVICES => 'Member Services',
    PHONE_FOR_AWARD_TRAVEL => 'Award Travel',
];
global $phoneForOptions;

// regions
define('REGION_KIND_REGION', 1);
define('REGION_KIND_COUNTRY', 2);
define('REGION_KIND_STATE', 3);
define('REGION_KIND_CONTINENT', 7);
define('REGION_KIND_AIRLINE_REGION', 8);
define('REGION_KIND_AIRPORT', 9);
$regionKindOptions = [
    REGION_KIND_REGION => "Region",
    REGION_KIND_AIRLINE_REGION => "Airline Region",
    REGION_KIND_CONTINENT => "Continent",
    REGION_KIND_COUNTRY => "Country",
    REGION_KIND_STATE => "State",
    REGION_KIND_AIRPORT => "Airport",
];

// system message types
define('MSGTYPE_TC_NOTIFY', 1);
define('MAX_AWPLUS_PERIOD', '+2 year');

// aliases for providers
$providerMultiname = [
    'Marriott' => [
        'Courtyard Memphis East/Park Avenue',
        'Chicago Marriott Downtown Magnificent Mile',
        'Munich Marriott Hotel',
        'Courtyard By Marriott Germantown',
        'Warner Center Marriott Woodland Hills',
        'Paris Marriott Rive Gauche Hotel And Conference Center',
        'Courtyard By Marriott Austin - University Area',
        'Marriott Madinah',
    ],
    'Delta Air Lines Personal' => [
        'Delta',
        'Delta Air Lines',
    ],
    'China Southern' => [
        'China Southern Airlines',
    ],
    'China Eastern' => [
        'China Eastern Airlines',
    ],
    'AirTran' => [
        'AirTran Airways',
    ],
    'Continental Airlines' => [
        'Continental',
    ],
    'Alaska Air' => [
        'Alaska Airlines',
    ],
    'Radisson' => [
        'Radisson Hotel Nashville Airport',
    ],
    'Hyatt Hotels and Resorts' => [
        'Hyatt Escala Lodge at Park City',
        'Hyatt Place Uc Davis',
        'Hyatt Regency Toronto',
        'Hyatt Regency Cambridge',
        'Hyatt Regency Tulsa',
    ],
    'Best Western hotels, Best Western motels, Best Western inns' => [
        'BEST WESTERN PLUS Dallas Hotel and Conference Center',
        'BEST WESTERN PLUS Galleria Inn &amp; Suites',
    ],
    'AeroSvit Ukrainian Airlines' => [
        'Aerosvit Airlines',
    ],
    'La Quinta, Baymont, Woodfield Suites' => [
        'La Quinta Inn &amp; Suites Bismarck',
    ],
    'Starwood Hotels and Resorts, Four Points, Sheraton, Aloft, W Hotels, Le Meridien, Luxury Collection, Element, Westin, St. Regis' => [
        'Le Meridien Bruxelles',
    ],
    'Aegean Airlines' => [
        'Aegean',
    ],
    'Ukrainian International Airlines' => [
        'Ukraine International Airlines',
    ],
];

const ONECARD_STATE_NEW = 1;
const ONECARD_STATE_PRINTING = 2;
const ONECARD_STATE_PRINTED = 3;
const ONECARD_STATE_BROKEN = 4;
const ONECARD_STATE_DELETED = 5;
const ONECARD_STATE_REFUNDED = 6;

// # Active Accounts Tab Property
define('ACTIVE_ACCOUNT_DEFAULT', 0);
define('ACTIVE_ACCOUNT_ADD', 1);
define('ACTIVE_ACCOUNT_REMOVE', 2);
$ActiveAccountsOptions = [
    ACTIVE_ACCOUNT_DEFAULT => 'Let AwardWallet Choose',
    ACTIVE_ACCOUNT_ADD => 'Add to Active Accounts',
    ACTIVE_ACCOUNT_REMOVE => 'Remove from Active Accounts',
];

define('AA_ERROR_TEXT', 'Unfortunately American Airlines forced us to stop supporting their loyalty programs.
				As a result you are not allowed to add any of your AAdvantage® related data including,
				but not limited to AAdvantage® number(s), login(s), and password(s) on AwardWallet.
				<br/>
				<br/>
				Instead of contacting us via the contact us page or emailing us in regards to this matter
				we kindly ask you to participate in our <a href="/forum/viewtopic.php?f=16&t=1940">discussion forum</a>.
				This way we don’t have to answer the same question many times.');

define('DELTA_ERROR_TEXT', 'Unfortunately Delta Airlines forced us to stop supporting their loyalty programs.
				<br/>
				<br/>
				Instead of contacting us via this Contact Us page or emailing us in regards to this matter we kindly
				ask you to participate in our <a href="/forum/viewtopic.php?f=16&t=2697">discussion forum</a> on
				this subject matter. This way we don’t have to answer the same question many times.
				<br><br>
				Also, there is a petition going on at change.org:
				<br><br>
				<a href="http://www.change.org/petitions/delta-airlines-reverse-your-recent-lockout-of-awardwallet-service#">http://www.change.org/petitions/delta-airlines-reverse-your-recent-lockout-of-awardwallet-service#</a>
				<br><br>
				If you care about this problem, please sign this petition.
				<br><br>
				Finally, please voice your opinion by tweeting it to <a href="https://twitter.com/Delta"
				target="_blank">https://twitter.com/Delta</a>');
// secret key foremail logo
define('SECRET_KEY_EMAIL_UNSUBSCRIBE_PERSONAL', 'LeRB4IR6aRXlOeTGZy06u6zzSO58CqwP');
define('SECRET_KEY_EMAIL_UNSUBSCRIBE_BUSINESS', 's3IP1of0lns2KbPPVPgAkdlDyH3G9PRX');
define('SECRET_KEY_EMAIL_UNSUBSCRIBE_BUSINESS_V2', 'FH&2LiPGvrwrXjgzJ90$hwH^PvN7pvBA');

// no Left Menu
define('NO_LEFT_MENU', isset($_GET['noLeftNavigation']) ? true : false);

// what version of wsdl we are usign
define('WSDL_API_VERSION', 4);

define('ACCOUNT_MISSING_PASSWORD_MESSAGE', 'You opted to save the password for this award program locally, this computer does not have it stored. In order to fix this problem please provide a password by editing this reward program. You should consider saving the password in the AwardWallet.com database in order to avoid this problem in the future.');

$decimalPoints = [
    ',' => ".",
    '.' => ",",
    ' ' => ",",
];

define('NO_RUSSIAN_REGEXP', '/^[^\p{Cyrillic}]+$/u');

$Config[CONFIG_FORM_CSRF_CHECK] = CSRF_CHECK_STRICT;

if (file_exists(__DIR__ . '/localConstants.php')) {
    require __DIR__ . '/localConstants.php';
}
