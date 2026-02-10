<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\PageVisit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PageVisitLogger
{
    public const PAGE_ACCOUNT_LIST = 'Account List';
    public const PAGE_TRIPS = 'Trips';
    public const PAGE_AWARD_BOOKINGS = 'Award Bookings';
    public const PAGE_CREDIT_CARD_SPEND_ANALYSIS = 'Credit Card Spend Analysis';
    public const PAGE_MERCHANT_LOOKUP_TOOL = 'Merchant Lookup Tool';
    public const PAGE_TRANSACTION_ANALYZER = 'Transaction Analyzer';
    public const PAGE_REVERSE_MERCHANT_LOOKUP_TOOL = 'Reverse Merchant Lookup Tool';
    public const PAGE_TRANSFER_TIMES = 'Transfer Times';
    public const PAGE_PURCHASE_TIMES = 'Purchase Times';
    public const PAGE_POINT_MILE_VALUES = 'Point Mile Values';
    public const PAGE_TRAVEL_TRENDS = 'Travel Trends';
    public const PAGE_TRAVEL_SUMMARY_REPORT = 'Travel Summary Report';
    public const PAGE_AWARD_HOTEL_RESEARCH_TOOL = 'Award Hotel Research Tool';
    public const PAGE_FAQS = 'FAQs';
    public const PAGE_BLOG = 'Blog';
    public const PAGE_COMMUNITY = 'Community';
    public const PAGE_APIS = 'APIs';
    public const PAGE_CONTACT_US = 'Contact Us';
    public const PAGE_PROVIDER_HEALTH_DASHBOARD = 'Provider Health Dashboard';
    public const PAGE_LIST_OF_VISITED_COUNTRIES = 'List of Visited Countries';
    public const PAGE_CONNECTED_MEMBERS = 'Connected Members';
    public const PAGE_ADD_NEW_PERSON = 'Add New Person';
    public const PAGE_PRIVACY_NOTICE = 'Privacy Notice';
    public const PAGE_TERMS_OF_USE = 'Terms of Use';
    public const PAGE_ABOUT_US = 'About Us';
    public const PAGE_CREDIT_CARD_OFFERS = 'Credit Card Offers';
    public const PAGE_PROMOS = 'Promos';
    public const PAGE_TOOLS = 'Tools';
    public const PAGE_MY_PROFILE = 'My Profile';
    public const PAGE_LOGOUT = 'Log Out';
    public const PAGE_EDIT_PUSH_NOTIFICATIONS = 'Edit Push Notifications';
    public const PAGE_EDIT_EMAIL_NOTIFICATIONS = 'Edit Email Notifications';
    public const PAGE_ADD_CHANGE_CONNECTED_MAILBOXES = 'Add/Change Connected Mailboxes';
    public const PAGE_CHANGE_OTHER_SETTINGS = 'Change Other Settings';
    public const PAGE_PODCASTS = 'Podcast';

    private const GROUP_ACCOUNTS = 'Accounts';
    private const GROUP_TRIPS = 'Trips';
    private const GROUP_TOOLS = 'Tools';
    private const GROUP_CREDIT_CARDS = 'Credit Cards';
    private const GROUP_SERVICE = 'Service';
    private const GROUP_FOOTER = 'Footer';

    private const PAGES = [
        self::PAGE_ACCOUNT_LIST => ['name' => self::PAGE_ACCOUNT_LIST, 'group' => self::GROUP_ACCOUNTS],
        self::PAGE_TRIPS => ['name' => self::PAGE_TRIPS, 'group' => self::GROUP_TRIPS],
        self::PAGE_AWARD_BOOKINGS => ['name' => self::PAGE_AWARD_BOOKINGS, 'group' => self::GROUP_TOOLS],
        self::PAGE_CREDIT_CARD_SPEND_ANALYSIS => ['name' => self::PAGE_CREDIT_CARD_SPEND_ANALYSIS, 'group' => self::GROUP_CREDIT_CARDS],
        self::PAGE_MERCHANT_LOOKUP_TOOL => ['name' => self::PAGE_MERCHANT_LOOKUP_TOOL, 'group' => self::GROUP_CREDIT_CARDS],
        self::PAGE_TRANSACTION_ANALYZER => ['name' => self::PAGE_TRANSACTION_ANALYZER, 'group' => self::GROUP_CREDIT_CARDS],
        self::PAGE_REVERSE_MERCHANT_LOOKUP_TOOL => ['name' => self::PAGE_REVERSE_MERCHANT_LOOKUP_TOOL, 'group' => self::GROUP_CREDIT_CARDS],
        self::PAGE_TRANSFER_TIMES => ['name' => self::PAGE_TRANSFER_TIMES, 'group' => self::GROUP_TOOLS],
        self::PAGE_PURCHASE_TIMES => ['name' => self::PAGE_PURCHASE_TIMES, 'group' => self::GROUP_TOOLS],
        self::PAGE_POINT_MILE_VALUES => ['name' => self::PAGE_POINT_MILE_VALUES, 'group' => self::GROUP_TOOLS],
        self::PAGE_TRAVEL_TRENDS => ['name' => self::PAGE_TRAVEL_TRENDS, 'group' => self::GROUP_TOOLS],
        self::PAGE_TRAVEL_SUMMARY_REPORT => ['name' => self::PAGE_TRAVEL_SUMMARY_REPORT, 'group' => self::GROUP_TRIPS],
        self::PAGE_AWARD_HOTEL_RESEARCH_TOOL => ['name' => self::PAGE_AWARD_HOTEL_RESEARCH_TOOL, 'group' => self::GROUP_TOOLS],
        self::PAGE_FAQS => ['name' => self::PAGE_FAQS, 'group' => self::GROUP_SERVICE],
        self::PAGE_BLOG => ['name' => self::PAGE_BLOG, 'group' => self::GROUP_SERVICE],
        self::PAGE_COMMUNITY => ['name' => self::PAGE_COMMUNITY, 'group' => self::GROUP_SERVICE],
        self::PAGE_APIS => ['name' => self::PAGE_APIS, 'group' => self::GROUP_SERVICE],
        self::PAGE_CONTACT_US => ['name' => self::PAGE_CONTACT_US, 'group' => self::GROUP_SERVICE],
        self::PAGE_PROVIDER_HEALTH_DASHBOARD => ['name' => self::PAGE_PROVIDER_HEALTH_DASHBOARD, 'group' => self::GROUP_SERVICE],
        self::PAGE_LIST_OF_VISITED_COUNTRIES => ['name' => self::PAGE_LIST_OF_VISITED_COUNTRIES, 'group' => self::GROUP_TRIPS],
        self::PAGE_CONNECTED_MEMBERS => ['name' => self::PAGE_CONNECTED_MEMBERS, 'group' => self::GROUP_SERVICE],
        self::PAGE_ADD_NEW_PERSON => ['name' => self::PAGE_ADD_NEW_PERSON, 'group' => self::GROUP_SERVICE],
        self::PAGE_PRIVACY_NOTICE => ['name' => self::PAGE_PRIVACY_NOTICE, 'group' => self::GROUP_FOOTER],
        self::PAGE_TERMS_OF_USE => ['name' => self::PAGE_TERMS_OF_USE, 'group' => self::GROUP_FOOTER],
        self::PAGE_ABOUT_US => ['name' => self::PAGE_ABOUT_US, 'group' => self::GROUP_FOOTER],
        self::PAGE_CREDIT_CARD_OFFERS => ['name' => self::PAGE_CREDIT_CARD_OFFERS, 'group' => self::GROUP_SERVICE],
        self::PAGE_PROMOS => ['name' => self::PAGE_PROMOS, 'group' => self::GROUP_SERVICE],
        self::PAGE_TOOLS => ['name' => self::PAGE_TOOLS, 'group' => self::GROUP_SERVICE],
        self::PAGE_MY_PROFILE => ['name' => self::PAGE_MY_PROFILE, 'group' => self::GROUP_SERVICE],
        self::PAGE_LOGOUT => ['name' => self::PAGE_LOGOUT, 'group' => self::GROUP_SERVICE],
        self::PAGE_EDIT_PUSH_NOTIFICATIONS => ['name' => self::PAGE_EDIT_PUSH_NOTIFICATIONS, 'group' => self::GROUP_SERVICE],
        self::PAGE_EDIT_EMAIL_NOTIFICATIONS => ['name' => self::PAGE_EDIT_EMAIL_NOTIFICATIONS, 'group' => self::GROUP_SERVICE],
        self::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES => ['name' => self::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES, 'group' => self::GROUP_SERVICE],
        self::PAGE_CHANGE_OTHER_SETTINGS => ['name' => self::PAGE_CHANGE_OTHER_SETTINGS, 'group' => self::GROUP_SERVICE],
        self::PAGE_PODCASTS => ['name' => self::PAGE_PODCASTS, 'group' => self::GROUP_SERVICE],
    ];

    private const SCREEN_MAP = [
        'AccountsList' => self::PAGE_ACCOUNT_LIST,
        'Timeline' => self::PAGE_TRIPS,
        'TravelSummary' => self::PAGE_TRAVEL_SUMMARY_REPORT,
        'SpendAnalysis' => self::PAGE_CREDIT_CARD_SPEND_ANALYSIS,
        'TransactionAnalyzer' => self::PAGE_TRANSACTION_ANALYZER,
        'MerchantLookup' => self::PAGE_MERCHANT_LOOKUP_TOOL,
        'MerchantReverse' => self::PAGE_REVERSE_MERCHANT_LOOKUP_TOOL,
        'Blog' => self::PAGE_BLOG,
        'Transfer' => self::PAGE_TRANSFER_TIMES,
        'Purchase' => self::PAGE_PURCHASE_TIMES,
        'MileValue' => self::PAGE_POINT_MILE_VALUES,
        'BookingRequests' => self::PAGE_AWARD_BOOKINGS,
        'FAQs' => self::PAGE_FAQS,
        'AboutUs' => self::PAGE_ABOUT_US,
        'ContactUs' => self::PAGE_CONTACT_US,
        'PrivacyNotice' => self::PAGE_PRIVACY_NOTICE,
        'Terms' => self::PAGE_TERMS_OF_USE,
        'Tools' => self::PAGE_TOOLS,
        'Profile' => self::PAGE_MY_PROFILE,
        'Connections' => self::PAGE_CONNECTED_MEMBERS,
        'Podcasts' => self::PAGE_PODCASTS,
    ];

    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $tokenStorage;
    private AntiBruteforceLockerService $userLocker;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage,
        AntiBruteforceLockerService $securityAntibruteforcePageVisitLocker,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->userLocker = $securityAntibruteforcePageVisitLocker;
        $this->logger = $logger;
    }

    /**
     * Logs the visit from the mobile app.
     */
    public function logFromMobile(string $screenName, string $refCode = ''): void
    {
        if (!isset(self::SCREEN_MAP[$screenName])) {
            return;
        }

        if ($refCode !== '') {
            $user = $this->entityManager->getRepository(Usr::class)->findOneBy(['refcode' => $refCode]);

            if ($user) {
                $this->log(self::SCREEN_MAP[$screenName], true, $user);
            }

            return;
        }

        $this->log(self::SCREEN_MAP[$screenName], true);
    }

    /**
     * Logs the visit to the passed page for the current user.
     */
    public function log(string $pageName, bool $isMobile = false, ?Usr $user = null): void
    {
        $currentUser = $user ?? $this->tokenStorage->getUser();

        if (!$currentUser || !isset(self::PAGES[$pageName])) {
            return;
        }

        $error = $this->userLocker->checkForLockout((string) $currentUser->getId());

        if (!empty($error)) {
            throw new TooManyRequestsHttpException();
        }

        $connection = $this->entityManager->getConnection();

        $params = [
            ':pageName' => self::PAGES[$pageName]['name'],
            ':userId' => $currentUser->getId(),
            ':day' => date('Y-m-d'),
            ':isMobile' => $isMobile ? PageVisit::TYPE_MOBILE : PageVisit::TYPE_NOT_MOBILE,
        ];
        $result = $connection->executeStatement("INSERT INTO `PageVisit`(`PageName`, `UserID`, `Visits`, `Day`, `IsMobile`)
            VALUES (:pageName, :userId, 1, :day, :isMobile)
            ON DUPLICATE KEY UPDATE `Visits` = `Visits` + 1", $params);

        $this->logger->info('Added a new page visit: ' . json_encode($params) . ", result: $result");
    }
}
