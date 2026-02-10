<?php

namespace AwardWallet\Manager;

use AwardWallet\MainBundle\Controller\Manager\User\TransactionListController;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Security\RoleManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HeaderMenu
{
    private array $allowedSchemas;

    private AuthorizationCheckerInterface $authorizationChecker;

    private AwTokenStorage $tokenStorage;

    private RouterInterface $router;

    private string $emailApiUrl;
    private ParameterRepository $paramRepository;

    public function __construct(
        RoleManager $roleManager,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorage $tokenStorage,
        RouterInterface $router,
        ParameterRepository $paramRepository,
        string $emailApiUrl
    ) {
        $this->allowedSchemas = $roleManager->getAllowedSchemas();
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->emailApiUrl = $emailApiUrl;
        $this->paramRepository = $paramRepository;
    }

    public function getMenu(): array
    {
        return $this->filterMenu($this->getFullMenu());
    }

    public function getJsonMenu(): string
    {
        return json_encode(
            $this->flattenMenu(
                $this->getMenu()
            )
        );
    }

    public function getFullMenu(): array
    {
        $token = $this->tokenStorage->getToken();

        if (is_null($token)) {
            throw new \LogicException('Token is not set');
        }

        /** @var Usr $user */
        $user = $token->getUser();

        if (!$user) {
            throw new \LogicException('User is not set');
        }

        return [
            'Support' => ['menu' => [
                'LP status' => ['url' => '/manager/providerStatus.php', 'schema' => 'providerStatus'],
                'LP status (v2)' => ['url' => '/manager/providerStatus2.php', 'schema' => 'providerStatus'],
                'Account' => ['url' => '/manager/loyalty/logs', 'schema' => 'logs', 'menu' => [
                    'Loyalty logs' => ['url' => '/manager/loyalty/logs', 'schema' => 'logs', 'role' => 'ROLE_MANAGE_LOGS_REWARD_AVAILABILITY'],
                    'Accounts' => ['url' => '/manager/list.php?Schema=AccountInfo', 'schema' => 'AccountInfo'],
                    'Account Info' => ['url' => '/manager/loginAccount.php', 'schema' => 'loginAccount'],
                    'Search Accounts' => ['url' => '/manager/account-by-region', 'schema' => 'AccountByRegion'],
                    'Accounts with UE' => ['url' => '/manager/account-with-ue', 'schema' => 'AccountByRegion'],
                    'Change user accounts settings' => ['url' => $this->router->generate('aw_manager_user_account_index'), 'schema' => 'loginAccount'],
                    'Account activity score' => ['url' => '/manager/accountActivity.php', 'schema' => 'activityScore'],
                    'Check account history' => ['url' => '/manager/checkAccountHistory.php', 'schema' => 'checkAccountHistory'],
                    'Reset account history' => ['url' => '/manager/resetAccountHistory.php', 'schema' => 'resetAccountHistory'],
                    'Questions' => ['url' => $this->router->generate("aw_manager_questions"), 'role' => 'ROLE_MANAGE_QUESTIONS'],
                    'Limit update date' => ['url' => $this->router->generate('aw_manager_user_account_index'), 'schema' => 'loginAccount'],
                ]],
                'Passwords' => ['url' => '/manager/passwordVault/', 'schema' => 'passwords', 'menu' => [
                    'Request' => ['url' => '/manager/passwordVault/requestPassword.php', 'schema' => 'passwords'],
                    'Request from WSDL' => ['url' => '/manager/passwordVault/requestWsdlPassword.php', 'schema' => 'wsdlPasswordRequest'],
                    'Request from Loyalty' => ['url' => $this->router->generate('aw_manager_loyalty_password_request'), 'schema' => 'wsdlPasswordRequest'],
                    'Chase codes' => ['url' => '/manager/chase', 'schema' => 'chase'],
                ]],
                'Itineraries' => ['url' => '/manager/itineraryCheckError', 'schema' => 'ItineraryCheckError', 'menu' => [
                    'Find Itineraries Accounts' => ['url' => '/manager/find-itineraries-provider', 'schema' => 'findItinerariesProvider'],
                    'Flight Status Detection' => ['url' => '/manager/flightStatusDetection.php', 'schema' => 'flightStatusDetection'],
                    'Itinerary Check Errors' => ['url' => '/manager/itineraryCheckError', 'schema' => 'ItineraryCheckError'],
                    'Parking Info' => ['url' => '/manager/list.php?Schema=ParkingInfo', 'schema' => 'ParkingInfo'],
                    'Rental Info' => ['url' => '/manager/list.php?Schema=RentalInfo', 'schema' => 'RentalInfo'],
                    'Reservation Info' => ['url' => '/manager/list.php?Schema=ReservationInfo', 'schema' => 'ReservationInfo'],
                    'Restaurant Info' => ['url' => '/manager/list.php?Schema=RestaurantInfo', 'schema' => 'RestaurantInfo'],
                    'Trip Info' => ['url' => '/manager/list.php?Schema=TripInfo&UserID=' . $user->getId(), 'schema' => 'TripInfo'],
                ]],
                'Reward Availability' => ['url' => '/manager/reward-availability-status', 'schema' => 'rewardAvailabilityStatus', 'role' => 'ROLE_MANAGE_REWARD_AVAILABILITY', 'menu' => [
                    'Reward Availability Status' => ['url' => '/manager/reward-availability-status', 'schema' => 'rewardAvailabilityStatus', 'role' => 'ROLE_MANAGE_REWARD_AVAILABILITY'],
                    'RA Accounts' => ['url' => '/manager/loyalty/ra-accounts', 'role' => 'ROLE_MANAGE_RA_ACCOUNT'],
                    'RA Register Accounts Manually' => ['url' => '/manager/loyalty/ra-accounts/register', 'role' => 'ROLE_MANAGE_RA_ACCOUNT'],
                    'RA Register Accounts Auto' => ['url' => '/manager/loyalty/ra-accounts/register/auto', 'role' => 'ROLE_MANAGE_RA_ACCOUNT_REGISTER'],
                    'RA Register Accounts Config' => ['url' => '/manager/loyalty/ra-accounts/register/config', 'role' => 'ROLE_MANAGE_RA_ACCOUNT_REGISTER'],
                    'RA Proxy Stats' => ['url' => '/manager/reward-availability-status/proxies', 'role' => 'ROLE_MANAGE_REWARD_AVAILABILITY'],
                    'RA Hot Sessions State' => ['url' => '/manager/loyalty/hot-session', 'role' => 'ROLE_MANAGE_RA_HOT_SESSION'],
                    'RA Flight Search Query' => ['url' => '/manager/list.php?Schema=RAFlightSearchQuery', 'schema' => 'RAFlightSearchQuery'],
                    'RA Flight Search Routes' => ['url' => '/manager/list.php?Schema=RAFlightSearchRoute&Archived=0', 'schema' => 'RAFlightSearchRoute'],
                    'RA Extension Parsing' => ['url' => '/manager/extension-parsing', 'schema' => 'rewardAvailabilityStatus', 'role' => 'ROLE_MANAGE_REWARD_AVAILABILITY'],
                ]],
                'Operations' => ['url' => '/manager/operations', 'schema' => 'operations'],
                'Operations (old)' => ['url' => '/manager/operations.php', 'schema' => 'operations'],
                'Impersonate' => ['url' => '/manager/impersonate', 'schema' => 'impersonate'],
                'FAQ' => ['url' => '/manager/list.php?Schema=Faq', 'schema' => 'Faq'],
                'Tip' => ['url' => $this->router->generate('tip_list'), 'schema' => 'Tip'],
                'Daily Statistics' => ['url' => '/manager/reports/dailyStatistics.php', 'schema' => 'dailyStatistics'],
                'Extension statistics' => ['url' => '/manager/extensionStat.php', 'schema' => 'extensionStat'],
                'Contact us archive' => ['url' => '/manager/list.php?Schema=BaseContactUs', 'schema' => 'BaseContactUs'],
                'FlightInfo statistics' => ['url' => '/manager/flightInfo', 'schema' => 'FlightInfo'],
                'Send emails' => ['url' => '/manager/voteMailer.php?type=fixed', 'schema' => 'voteMailer'],
                'User mailbox' => ['url' => '/manager/userMailbox', 'schema' => 'userMailbox'],
                'Transfer' => ['url' => '/manager/transferLogs.php', 'schema' => 'logs', 'menu' => [
                    'Transfer logs' => ['url' => '/manager/transferLogs.php', 'schema' => 'logs'],
                    // 'Transfer stats' => ['url' => '/manager/transfer/stat', 'schema' => 'transferStat'],
                ]],
                'Email Parsing' => ['menu' => [
                    'Email Parsing Test v2' => ['url' => $this->emailApiUrl . '/test/v2', 'schema' => 'manualParser'],
                    'Email parsing queue' => ['url' => '/manager/email/parser/list/all?show=all', 'schema' => 'manualParser'],
                    'Email queue breakdown' => ['url' => '/manager/email/parser/report', 'schema' => 'manualParser'],
                    'Email parsing stats' => ['url' => '/manager/email/stat', 'schema' => 'emailStat'],
                    'Monitoring Callback' => ['url' => '/manager/emailadmin/monitoringCallback', 'schema' => 'manualParser'],
                    'Email Stat graph' => ['url' => '/manager/emailadmin/graph/', 'schema' => 'manualParser'],
                    'Partners Settings' => ['url' => '/manager/emailadmin/settings/', 'schema' => 'manualParser'],
                    'Forward Addresses' => ['url' => '/manager/emailadmin/forward/', 'schema' => 'manualParser'],
                    'Unlock Mailbox' => ['url' => '/manager/emailadmin/mailbox/unlock/', 'schema' => 'manualParser'],
                    'Airport Alias' => ['url' => '/manager/emailadmin/aircode_alias/', 'schema' => 'manualParser'],
                    'Gpt Parsing List' => ['url' => '/manager/emailadmin/gpt/list', 'schema' => 'manualParser'],
                    'Gmail Forwarding Filter List' => ['url' => $this->router->generate('aw_manager_email_gmailFilter_list'), 'role' => 'ROLE_STAFF'],
                    'Ai Detector List' => ['url' => '/manager/emailadmin/aiDetector/list', 'role' => 'ROLE_STAFF'],
                    'AI Assisted Breakdown' => ['url' => '/manager/emailadmin/aiBreakdown/', 'role' => 'ROLE_STAFF'],
                    'Partner Mailbox Monitoring' => ['url' => '/manager/emailadmin/mailboxMonitoring/report', 'role' => 'ROLE_STAFF'],
                    'Provider Signals' => ['url' => '/manager/list.php?Schema=ProviderSignal', 'schema' => 'ProviderSignal', 'role' => 'ROLE_PROVIDERSIGNAL'],
                ]],
                'Mailbox scanner' => ['url' => $this->router->generate('aw_manager_scanner'), 'role' => 'ROLE_MANAGE_SCANNER'],
                'Expirations' => ['url' => '/manager/reports/expirations.php', 'schema' => 'expirations'],
                'Geocoding' => ['url' => '/manager/geocodingTest.php', 'schema' => 'geocodingTest'],
                'Award Travel 201' => ['url' => $this->router->generate('at_201_group_list'), 'role' => 'ROLE_STAFF_FBMODERATORS'],
            ]],
            'LPs' => ['menu' => [
                'LPs' => ['url' => '/manager/list.php?Schema=Provider', 'schema' => 'Provider'],
                'Properties' => ['url' => '/manager/list.php?Schema=ProviderProperty', 'schema' => 'ProviderProperty'],
                'Status property' => ['url' => '/manager/statusProperty.php', 'schema' => 'statusProperty'],
                'Properties Health' => ['url' => '/manager/reports/properties.php', 'schema' => 'PropertiesHealth'],
                'Coupon Types' => ['url' => '/manager/list.php?Schema=ProviderCouponType', 'schema' => 'ProviderCouponType'],
                'Currency' => ['url' => '/manager/list.php?Schema=Currency', 'schema' => 'Currency'],
                'Alliances' => ['url' => '/manager/list.php?Schema=Alliance', 'schema' => 'Alliance'],
                'Airline' => ['url' => '/manager/list.php?Schema=Airline', 'schema' => 'Airline'],
                'Elite levels' => [
                    'menu' => [
                        'Elite levels' => ['url' => '/manager/list.php?Schema=EliteLevel', 'schema' => 'EliteLevel'],
                        'Elite Level Progress' => ['url' => '/manager/list.php?Schema=EliteLevelProgress', 'schema' => 'EliteLevelProgress'],
                        'Missing Elite Levels Tab' => ['url' => '/manager/accountProperty.php', 'schema' => 'EliteLevel'],
                        'Card Image levels' => ['url' => '/manager/list.php?Schema=EliteLevelCards', 'schema' => 'EliteLevel'],
                    ],
                ],
                'Airline classes of service' => ['url' => '/manager/list.php?Schema=AirClassDictionary', 'schema' => 'AirClassDictionary'],
                'Mile/Point Value' => [
                    'menu' => [
                        'Mile Value' => ['url' => '/manager/list.php?Schema=MileValue', 'schema' => 'MileValue'],
                        'Hotel Point Value' => ['url' => '/manager/list.php?Schema=HotelPointValue', 'schema' => 'HotelPointValue'],
                        'Provider Mile Value' => ['url' => '/manager/list.php?Schema=ProviderMileValue', 'schema' => 'ProviderMileValue'],
                        'Airline Fare Class' => ['schema' => 'AirlineFareClass'],
                        'Flight Prices' => ['url' => '/manager/flight-prices', 'role' => 'ROLE_MANAGE_MILEVALUE'],
                        'Rewards Prices' => ['url' => '/manager/list.php?Schema=RewardsPrice', 'role' => 'ROLE_MANAGE_AWARDS'],
                        'Award Season' => ['url' => '/manager/list.php?Schema=AwardSeason', 'role' => 'ROLE_MANAGE_AWARDS'],
                        'Award Season Interval' => ['url' => '/manager/list.php?Schema=AwardSeasonInterval', 'role' => 'ROLE_MANAGE_AWARDS'],
                        'Award Types' => ['url' => '/manager/list.php?Schema=AwardType', 'role' => 'ROLE_MANAGE_AWARDS'],
                        'Award Chart' => ['url' => '/manager/list.php?Schema=AwardChart', 'role' => 'ROLE_MANAGE_AWARDS'],
                        'Airline Groups' => ['url' => '/manager/list.php?Schema=AirlineGroup', 'role' => 'ROLE_MANAGE_AWARDS'],
                        'Hotel Brands' => ['url' => '/manager/list.php?Schema=HotelBrand', 'schema' => 'HotelBrand'],
                        'Hotels' => ['url' => '/manager/list.php?Schema=Hotel', 'schema' => 'Hotel'],
                        'Report Status' => ['url' => $this->router->generate('aw_manager_milevalue_report_status'), 'schema' => 'MileValue'],
                    ],
                ],
                'History Fields' => ['url' => '/manager/historyFields.php', 'schema' => 'historyFields'],
                'Extension Mobile' => ['url' => '/manager/extensionMobile.php', 'schema' => 'extensionMobile'],
                'Phone Numbers' => ['url' => '/manager/list.php?Schema=ProviderPhone', 'schema' => 'ProviderPhone'],
                'Transfer Partners' => ['url' => '/manager/list.php?Schema=RewardsTransfer', 'schema' => 'RewardsTransfer'],
                'Transfer Stat' => ['url' => '/manager/list.php?Schema=TransferStat', 'schema' => 'TransferStat'],
                'Purchase Stat' => ['url' => '/manager/list.php?Schema=PurchaseStat', 'schema' => 'PurchaseStat'],
                'Mile Price' => ['url' => '/manager/list.php?Schema=MilePrice', 'schema' => 'MilePrice'],
                'Retail Providers' => ['url' => $this->router->generate('retail_provider_list'), 'schema' => 'retail_provider'],
                'Card Images' => ['menu' => [
                    'Card Image Detection' => ['url' => '/manager/cardImageDetection.php', 'schema' => 'cardImageDetection'],
                    'Card Image Parsing' => ['url' => '/manager/cardImageParsing.php', 'schema' => 'cardImageParsing'],
                    'Card Image Preview' => ['url' => '/manager/cardImagePreview.php', 'schema' => 'cardImagePreview'],
                    'Card Color Picker' => ['url' => '/manager/cardColorPicker.php', 'schema' => 'cardColorPicker'],
                ]],
                'Earning Potential' => ['menu' => [
                    'Credit Card' => ['url' => '/manager/list.php?Schema=CreditCard', 'schema' => 'CreditCard'],
                    'Credit Card Emails' => ['url' => '/manager/list.php?Schema=CreditCardEmail', 'schema' => 'CreditCardEmail'],
                    'Undetected Credit Cards' => ['url' => $this->router->generate('aw_manage_undetected_cards'), 'role' => 'ROLE_MANAGE_CREDITCARD'],
                    'Last Detected Credit Cards' => ['url' => $this->router->generate('aw_manage_last_detected_cards'), 'role' => 'ROLE_MANAGE_CREDITCARD'],
                    'Double Credit Cards Patterns' => ['url' => $this->router->generate('aw_manage_double_patterns'), 'role' => 'ROLE_MANAGE_CREDITCARD'],
                    'Credit Card Offers' => ['url' => '/manager/list.php?Schema=CreditCardOffer', 'role' => 'ROLE_MANAGE_CREDITCARD'],
                    'Merchant' => ['url' => '/manager/list.php?Schema=Merchant', 'schema' => 'earning_potential'],
                    'Merchant Patterns' => ['url' => '/manager/list.php?Schema=MerchantPattern', 'schema' => 'earning_potential'],
                    'Merchant Group' => ['url' => '/manager/list.php?Schema=MerchantGroup', 'schema' => 'MerchantGroup'],
                    'Credit Card Merchant Group' => ['url' => '/manager/list.php?Schema=CreditCardMerchantGroup', 'schema' => 'CreditCardMerchantGroup'],
                    //                    'Merchants' => ['url' => $this->router->generate('admin_awardwallet_main_merchant_list'), 'schema' => 'earning_potential'],
                    //                    'Merchant Aliases' => ['url' => $this->router->generate('admin_awardwallet_main_merchantalias_list'), 'schema' => 'earning_potential'],
                    'Merchant Report' => ['url' => $this->router->generate('aw_manager_merchant_report'), 'role' => 'ROLE_MANAGE_MERCHANT'],
                    'Retail Provider Merchant' => ['url' => '/manager/list.php?Schema=RetailProviderMerchant', 'schema' => 'RetailProviderMerchant'],
                    'Shopping Category' => ['url' => '/manager/list.php?Schema=ShoppingCategory', 'schema' => 'ShoppingCategory'],
                    'Shopping Category Group' => ['url' => '/manager/list.php?Schema=ShoppingCategoryGroup', 'schema' => 'earning_potential'],
                    'User Credit Cards' => ['url' => '/manager/list.php?Schema=UserCreditCard', 'schema' => 'UserCreditCard'],
                    'Card Matcher Report' => ['url' => '/manager/list.php?Schema=CardMatcherReport', 'schema' => 'CardMatcherReport'],
                    'Merchant Matcher Test' => ['url' => $this->router->generate('aw_merchant_matcher_test'), 'role' => 'ROLE_MANAGE_MERCHANT'],
                    'Merchant Category Detector Test' => ['url' => $this->router->generate('aw_merchant_category_detector_test'), 'role' => 'ROLE_MANAGE_MERCHANT'],
                    'Shopping Category Matcher Test' => ['url' => $this->router->generate('aw_shopping_category_matcher_test'), 'role' => 'ROLE_MANAGE_MERCHANT'],
                    'Credit Card Matcher Test' => ['url' => $this->router->generate('aw_credit_card_matcher_test'), 'role' => 'ROLE_MANAGE_CREDITCARD'],
                ]],
                'Multiplier Lookup' => (function () {
                    $transactionLinksList = [
                        'Transactions' => ['url' => $this->router->generate('aw_manager_transaction_list'), 'role' => 'ROLE_MANAGE_MERCHANT'],
                    ];

                    $tablesSuffix = $this->paramRepository->getParam(ParameterRepository::LAST_TRANSACTIONS_DATE);

                    if (null !== $tablesSuffix) {
                        $suffixData = TransactionListController::matchTableSuffix($tablesSuffix);

                        if ($suffixData) {
                            [$_, $days] = $suffixData;
                            $transactionLinksList["Transactions (last {$days} days)"] = [
                                'url' => $this->router->generate('aw_manager_transaction_list') . '?RecentOnly=1',
                                'role' => 'ROLE_MANAGE_MERCHANT',
                            ];
                        }
                    }

                    return [
                        'menu' => \array_merge(
                            $transactionLinksList,
                            ['Categories' => ['url' => '/manager/reports/categoriesLookup.php', 'schema' => 'earning_potential']]
                        ),
                    ];
                })(),
                'Reviews' => ['url' => '/manager/list.php?Schema=Review', 'schema' => 'Review'],
            ]],
            'Sales' => ['menu' => [
                'AA reports' => ['menu' => [
                    'Quarterly AA Compensation' => ['url' => '/manager/reports/aa/compensation.php', 'schema' => 'aacomp'],
                    'List of Joint Members' => ['url' => '/manager/reports/aa/membership.php', 'schema' => 'aamembers'],
                    'All advertisers' => ['url' => '/manager/reports/aa/advertising.php', 'schema' => 'aaadv'],
                ]],
                'Send push notifications' => ['url' => $this->router->generate('aw_manager_sendnotification_index'), 'schema' => 'notificationtemplate'],
                'Redirects' => ['url' => '/manager/list.php?Schema=Redirect', 'schema' => 'Redirect'],
                'Profit per user' => ['url' => '/manager/reports/profitPerUser.php', 'schema' => 'profitPerUser'],
                'Orders' => ['url' => '/manager/list.php?Schema=AdminCart', 'schema' => 'AdminCart'],
                'Coupons' => ['url' => '/manager/list.php?Schema=AdminCoupon', 'schema' => 'AdminCoupon'],
                'Create coupons' => ['url' => '/manager/createCoupons.php', 'schema' => 'AdminCoupon'],
                'Revenue Sources' => ['url' => '/manager/reports/payments.php', 'schema' => 'payments'],
                'Partner Referrals' => ['menu' => [
                    'Partner Referral Revenue' => ['url' => '/manager/list.php?Schema=UsersIncome', 'schema' => 'UsersIncome'],
                    'Partner Referral Transactions' => ['url' => '/manager/list.php?Schema=IncomeTransaction', 'schema' => 'IncomeTransaction'],
                ]],
                'Totals' => ['url' => '/manager/reports/totals.php', 'schema' => 'reportTotals'],
                'Ad stats' => ['url' => '/manager/reports/adStats.php', 'schema' => 'adStats'],
                'Monthly Ad Income' => ['url' => '/manager/list.php?Schema=AdIncome', 'schema' => 'AdIncome'],
                'Site leads' => ['url' => '/manager/list.php?Schema=BaseLead', 'schema' => 'BaseLead'],
                'AwardWallet Emails' => ['menu' => [
                    'Emails Templates' => ['url' => $this->router->generate('email_template_list'), 'schema' => 'email_template'],
                    'Sent mail' => ['url' => $this->router->generate('aw_manager_sentmail'), 'schema' => 'sentMail'],
                    'Email Viewer' => ['url' => $this->router->generate('aw_manager_emailviewer'), 'schema' => 'emailView'],
                    'Email Params' => ['url' => '/manager/list.php?Schema=EmailCustomParam', 'schema' => 'EmailCustomParam'],
                ]],
                'Ads' => ['menu' => [
                    'Banner Ads' => ['url' => '/manager/list.php?Schema=SocialAd', 'schema' => 'SocialAd'],
                    'Retention Ads' => ['url' => '/manager/list.php?Schema=RetentionAd', 'schema' => 'RetentionAd'],
                    'Offers' => ['url' => '/manager/list.php?Schema=Offer', 'schema' => 'offer'],
                ]],
                'LPs Extended' => ['url' => '/manager/reports/providerSchedule.php?State=14&Corporate=0&WSDL=1', 'schema' => 'providerSchedule'],
                'Loyalty Properties Schedule' => ['url' => '/manager/reports/DetailsPropertyKindInfo.php?State=14&Corporate=0&WSDL=1&missingProperties=1', 'schema' => 'providerDetailsPropsInfo'],
                'Reserv. Properties Schedule' => ['schema' => 'tripSchedule', 'url' => '/manager/reports/tripSchedule.php?State=14&WSDL=1&CanCheckItinerary=1&customAge=180&customProviders=66,695,882,955', 'desc' => '6-month search window for israel, eraalaska, zuji, wegolo', 'menu' => [
                    'admin Reserv. Properties Schedule' => ['url' => 'https://frontend-admin.awardwallet.com/manager/reports/tripSchedule.php?State=14&WSDL=1&CanCheckItinerary=1&customAge=180&customProviders=66,695,882,955', 'schema' => 'tripSchedule', 'desc' => '6-month search window for israel, eraalaska, zuji, wegolo'],
                ]],
                'History Properties Schedule' => ['url' => '/manager/reports/historyPropertiesSchedule.php?State=14&WSDL=1&CanCheckHistory=1', 'schema' => 'historySchedule', 'menu' => [
                    'admin History Properties Schedule' => ['url' => 'https://frontend-admin.awardwallet.com/manager/reports/historyPropertiesSchedule.php?State=14&WSDL=1&CanCheckHistory=1', 'schema' => 'historySchedule'],
                ]],
                'OneCard' => ['menu' => [
                    'OneCard report' => ['url' => '/manager/reports/onecard.php', 'schema' => 'onecard'],
                    'OneCard printing' => ['url' => '/manager/onecards.php?State=1&Sort1=ShippingName&SortOrder=Normal', 'schema' => 'onecards'],
                ]],
                'AwardWallet Bonus Points' => ['menu' => [
                    'Bonus Conversion' => ['url' => '/manager/list.php?Schema=BonusConversion', 'schema' => 'BonusConversion'],
                    'Bonus Conversion Provider' => ['url' => '/manager/list.php?Schema=BonusConversionProvider', 'schema' => 'BonusConversionProvider'],
                ]],
                'Media Contacts' => ['url' => '/manager/list.php?Schema=MediaContact', 'schema' => 'MediaContact'],
                'History stats' => ['url' => '/manager/history/stat', 'schema' => 'reportTotals'],
                'Cross Airlines Report' => ['url' => '/manager/history/analyzer/cross-airlines-report', 'schema' => 'reportTotals'],
                'QuinStreet Reports' => [
                    'menu' => [
                        // 'QuinStreet Credit Card' => ['url' => $this->router->generate('qs_credit_card_list'), 'schema' => 'QsCreditCard'],
                        // 'QuinStreet Transaction' => ['url' => $this->router->generate('qs_transaction_list'), 'schema' => 'QsCreditCard'],
                        'Missed Cards Approvals' => ['url' => '/manager/list.php?Schema=QsUserCards', 'schema' => 'QsUserCards'],
                        'QuinStreet Credit Card' => ['url' => '/manager/list.php?Schema=Qs_Credit_Card', 'schema' => 'Qs_Credit_Card'],
                        'QuinStreet Transaction' => ['url' => '/manager/list.php?Schema=Qs_Transaction', 'schema' => 'Qs_Transaction'],
                        'CC & FICO during CC Approvals' => ['url' => '/manager/list.php?Schema=QsTransactionState', 'schema' => 'QsTransactionState'],
                    ],
                ],
                'Loyalty Billing Reports' => ['url' => '/manager/loyalty-billing-report', 'role' => 'ROLE_MANAGE_LOYALTY_BILLING'],
                'Users' => [
                    'menu' => [
                        'User Stat' => ['url' => '/manager/user/stat', 'schema' => 'UserStat'],
                        'SubAccount Blog Post' => ['/manager/list.php?Schema=SubAccountType', 'schema' => 'SubAccountType'],
                        'Blog Report' => ['url' => '/manager/list.php?Schema=BlogUserReport', 'schema' => 'BlogUserReport'],
                        'Blog Link Click' => ['url' => '/manager/list.php?Schema=BlogLinkClick', 'schema' => 'BlogLinkClick'],
                        'Deleted Users' => ['url' => '/manager/list.php?Schema=UserDeleted', 'schema' => 'UserDeleted'],
                    ],
                ],
            ]],
            'Misc' => ['menu' => [
                'Rewards' => ['url' => '/manager/list.php?Schema=Award', 'schema' => 'Award'],
                'Regions' => ['url' => '/manager/list.php?Schema=Region', 'schema' => 'Region'],
                'Users' => ['url' => '/manager/list.php?Schema=UserAdmin', 'schema' => 'UserAdmin'],
                'Fake sAccounts' => ['url' => '/manager/reports/fakeAccounts.php', 'schema' => 'FakeAccounts', 'menu' => [
                    'Fake sAccounts admin server' => ['url' => 'https://frontend-admin.awardwallet.com/manager/reports/fakeAccounts.php', 'schema' => 'FakeAccounts'],
                ]],
                'Deals' => ['url' => '/manager/list.php?Schema=Deal', 'schema' => 'Deal'],
                'Award Booking' => ['schema' => 'AbInvoice', 'menu' => [
                    'Invoices' => ['url' => '/manager/list.php?Schema=AbInvoice&TransactionID=-', 'schema' => 'AbInvoice'],
                    'Transactions' => ['url' => '/manager/list.php?Schema=AbTransaction', 'schema' => 'AbTransaction'],
                    'Message Colors' => ['url' => '/manager/list.php?Schema=AbMessageColor', 'schema' => 'AbMessageColor'],
                    'Request Statuses' => ['url' => '/manager/list.php?Schema=AbRequestStatus', 'schema' => 'AbRequestStatus'],
                    'Share accounts' => ['url' => $this->router->generate('abshare_list'), 'schema' => 'abshare'],
                ]],
                'Send Email to Scanner' => ['url' => '/manager/addToEmailScanner.php', 'schema' => 'addToScanner'],
                'Press releases' => ['url' => '/manager/list.php?Schema=PressRelease', 'schema' => 'PressRelease'],
                'Cache control panel' => ['url' => '/manager/cache', 'schema' => 'cacheControl'],
                'Siege Mode' => ['url' => '/manager/security/siege-mode', 'role' => 'ROLE_MANAGE_SIEGE_MODE'],
                'Mail Relay' => ['url' => '/manager/mail-relay', 'role' => 'ROLE_MANAGE_MAIL_RELAY'],
                'Aircafts' => ['url' => '/manager/list.php?Schema=Aircraft', 'schema' => 'Aircraft'],
                'Airports' => ['url' => '/manager/list.php?Schema=Airport', 'schema' => 'Airport'],
                'RA Flight' => [
                    'url' => '/manager/list.php?Schema=RAFlight', 'schema' => 'RAFlight', 'role' => 'ROLE_MANAGE_RAFLIGHT', 'menu' => [
                        'RA Flight' => ['url' => '/manager/list.php?Schema=RAFlight', 'schema' => 'RAFlight', 'role' => 'ROLE_MANAGE_RAFLIGHT'],
                        'RA Flight Stat' => ['url' => '/manager/list.php?Schema=RAFlightStat', 'schema' => 'RAFlightStat', 'role' => 'ROLE_MANAGE_RAFLIGHT'],
                        'Award Price' => ['url' => '/manager/award-price', 'schema' => 'AwardPrice', 'role' => 'ROLE_MANAGE_AWARDPRICE'],
                        'RA Flight Hard Limit' => ['url' => '/manager/list.php?Schema=RAFlightHardLimit', 'schema' => 'RAFlightHardLimit', 'role' => 'ROLE_MANAGE_RAFLIGHTHARDLIMIT'],
                        'RA Flight Segment' => ['url' => '/manager/list.php?Schema=RAFlightSegment', 'schema' => 'RAFlightSegment', 'role' => 'ROLE_MANAGE_RAFLIGHTSEGMENT'],
                        'RA Flight RouteSearchVolume' => ['url' => '/manager/list.php?Schema=RAFlightRouteSearchVolume', 'schema' => 'RAFlightRouteSearchVolume', 'role' => 'ROLE_MANAGE_RAFLIGHTROUTESEARCHVOLUME'],
                        'RA Calendar' => ['url' => '/manager/list.php?Schema=RACalendar', 'schema' => 'RACalendar', 'role' => 'ROLE_MANAGE_RACALENDAR'],
                    ],
                ],
                'Lounges' => ['url' => '/manager/list.php?Schema=Lounge', 'schema' => 'Lounge'],
                'Lounges Sources' => ['url' => '/manager/list.php?Schema=LoungeSource', 'schema' => 'LoungeSource'],
                'Countries' => ['url' => '/manager/list.php?Schema=Country', 'schema' => 'Country'],
                'Push copies' => ['url' => $this->router->generate('aw_manager_push_copies'), 'schema' => 'PUSH_COPIES'],
                'FlightStats Usage' => ['url' => $this->router->generate('aw_manager_fs_stats'), 'schema' => 'fsUsage'],
                'Transfer Times' => ['url' => '/manager/list.php?Schema=BalanceWatch', 'schema' => 'BalanceWatch'],
                'Geocoding Test' => ['url' => '/manager/test-geocode', 'role' => 'ROLE_MANAGE_TEST_GEOCODE'],
            ]],
        ];
    }

    private function filterMenu(array $menu): array
    {
        $result = [];

        foreach ($menu as $title => $item) {
            if (isset($item['menu'])) {
                $item['menu'] = $this->filterMenu($item['menu']);

                if (empty($item['menu'])) {
                    continue;
                }
            }

            if (
                (isset($item['schema']) && in_array(strtolower($item['schema']), $this->allowedSchemas))
                || isset($item['menu'])
                || (isset($item['role']) && $this->authorizationChecker->isGranted($item['role']))
            ) {
                if (!isset($item['url']) && isset($item['schema'])) {
                    $item['url'] = '/manager/list.php?Schema=' . $item['schema'];
                }
                $result[$title] = $item;
            }
        }

        return $result;
    }

    private function flattenMenu($menu, $root = ['/']): array
    {
        $result = [];

        foreach ($menu as $title => $item) {
            $items = [];

            if (isset($item['menu'])) {
                $newRoot = $root;
                $newRoot[] = $title;
                $items = $this->flattenMenu($item['menu'], $newRoot);
            }

            if (isset($item['url'])) {
                $items[] = [
                    'url' => $item['url'],
                    'title' => $title,
                    'root' => $root,
                ];
            }

            $result = array_merge($result, $items);
        }

        return $result;
    }
}
