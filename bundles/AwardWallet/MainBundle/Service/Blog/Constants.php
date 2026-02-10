<?php

namespace AwardWallet\MainBundle\Service\Blog;

class Constants
{
    public const URL = 'https://awardwallet.com/blog/';
    public const URL_CUSTOM = 'https://awardwallet.com/';

    public const API_URL = 'https://awardwallet.com/blog/wp-json/';
    public const API_URL_GET_MENU = 'data/menu';
    public const API_URL_GET_ALL_TAGS = 'tag/get-all-tags';
    public const API_URL_GET_CREDIT_CARDS = 'credit-cards/get';

    public const CODE_ACCESS_DENIED = 'access_denied';

    public const REQUEST_TIMEOUT = 5;
    public const BUSY = '_wait';

    public const ICON_BASE_PATH = '/images/email/blog/category/';

    public const CATEGORY_OTHER_TIPS_ONCE_NAME = 'Tips';

    public const CATEGORY_PROGRAM_GUIDES_ID = 12068;
    public const CATEGORY_AW_TIPS_AND_TRICKS_ID = 12029;
    public const CATEGORY_TRAVEL_BOOKING_TIPS_ID = 12067;
    public const CATEGORY_CARDS_OFFERS_AND_GUIDES_ID = 12055;
    public const CATEGORY_NEWS_AND_PROMOTIONS_ID = 12069;
    public const CATEGORY_OTHER_TIPS_ID = 12054;
    public const CATEGORY_PODCAST_ID = 12056;
    public const CATEGORY_UNCATEGORIZED_ID = 12038;

    public const CATEGORY_SLUG = [
        self::CATEGORY_PROGRAM_GUIDES_ID => 'award-programs',
        self::CATEGORY_AW_TIPS_AND_TRICKS_ID => 'awardwallet',
        self::CATEGORY_TRAVEL_BOOKING_TIPS_ID => 'booking-travel',
        self::CATEGORY_CARDS_OFFERS_AND_GUIDES_ID => 'credit-cards',
        self::CATEGORY_NEWS_AND_PROMOTIONS_ID => 'news',
        self::CATEGORY_OTHER_TIPS_ID => 'tips',
        self::CATEGORY_PODCAST_ID => 'podcast',
        self::CATEGORY_UNCATEGORIZED_ID => 'uncategorized',
    ];

    public const CATEGORY_NAMES = [
        self::CATEGORY_NEWS_AND_PROMOTIONS_ID => 'News and Promotions',
        self::CATEGORY_CARDS_OFFERS_AND_GUIDES_ID => 'Credit Cards Offers and Guides',
        self::CATEGORY_PROGRAM_GUIDES_ID => 'Program Guides',
        self::CATEGORY_AW_TIPS_AND_TRICKS_ID => 'AwardWallet Tips and Tricks',
        self::CATEGORY_TRAVEL_BOOKING_TIPS_ID => 'Travel Booking Tips',
        self::CATEGORY_OTHER_TIPS_ID => 'Other Tips',
        self::CATEGORY_PODCAST_ID => 'Podcast',
        self::CATEGORY_UNCATEGORIZED_ID => '',
    ];

    public const CATEGORY_ICON = [
        self::CATEGORY_NEWS_AND_PROMOTIONS_ID => self::ICON_BASE_PATH . 'news-promotions.png',
        self::CATEGORY_CARDS_OFFERS_AND_GUIDES_ID => self::ICON_BASE_PATH . 'credit-cards.png',
        self::CATEGORY_PROGRAM_GUIDES_ID => self::ICON_BASE_PATH . 'program-guides.png',
        self::CATEGORY_AW_TIPS_AND_TRICKS_ID => self::ICON_BASE_PATH . 'aw-trips-tricks.png',
        self::CATEGORY_TRAVEL_BOOKING_TIPS_ID => self::ICON_BASE_PATH . 'travel-booking.png',
        self::CATEGORY_OTHER_TIPS_ID => self::ICON_BASE_PATH . 'other-tips.png',
        self::CATEGORY_PODCAST_ID => self::ICON_BASE_PATH . 'podcast.png',
        self::CATEGORY_UNCATEGORIZED_ID => self::ICON_BASE_PATH . 'aw-trips-tricks.png',
    ];

    // TODO: redo
    public const CATEGORIES_ORDER = [
        self::CATEGORY_NEWS_AND_PROMOTIONS_ID => [
            'name' => 'News and Promotions',
            'icon' => self::ICON_BASE_PATH . 'news-promotions.png',
        ],
        self::CATEGORY_CARDS_OFFERS_AND_GUIDES_ID => [
            'name' => 'Credit Cards Offers and Guides',
            'icon' => self::ICON_BASE_PATH . 'credit-cards.png',
        ],
        self::CATEGORY_PROGRAM_GUIDES_ID => [
            'name' => 'Program Guides',
            'icon' => self::ICON_BASE_PATH . 'program-guides.png',
        ],
        self::CATEGORY_AW_TIPS_AND_TRICKS_ID => [
            'name' => 'AwardWallet Tips and Tricks',
            'icon' => self::ICON_BASE_PATH . 'aw-trips-tricks.png',
        ],
        self::CATEGORY_TRAVEL_BOOKING_TIPS_ID => [
            'name' => 'Travel Booking Tips',
            'icon' => self::ICON_BASE_PATH . 'travel-booking.png',
        ],
        self::CATEGORY_OTHER_TIPS_ID => [
            'name' => 'Other Tips',
            'icon' => self::ICON_BASE_PATH . 'other-tips.png',
        ],
        self::CATEGORY_PODCAST_ID => [
            'name' => 'Podcast',
            'icon' => self::ICON_BASE_PATH . 'podcast.png',
        ],
        self::CATEGORY_UNCATEGORIZED_ID => [
            'name' => '',
            'icon' => self::ICON_BASE_PATH . 'aw-trips-tricks.png',
        ],
    ];

    public const META_AW_PROVIDER_KEY = 'aw_provider';
    public const META_AW_SUBACCOUNT_KEY = 'aw_subaccount';
    public const META_AW_DEP_KEY = 'aw_dep';
    public const META_AW_ARR_KEY = 'aw_arr';

    public const META_FLIGHT_ROUTE_KEY = 'flight_route';
}
