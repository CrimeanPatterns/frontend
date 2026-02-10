<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\BlockFactory;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\DocumentBlockEncoderFactory;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ProgramStatusResolver;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\BarcodeCreator;
use AwardWallet\MainBundle\Globals\DateTimeUtils;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Repository\CreditCardRepository;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Security\Voter\SessionVoter;
use AwardWallet\MainBundle\Service\AccountHistory\MerchantMobileRecommendation;
use AwardWallet\MainBundle\Service\AccountHistory\OfferCreditCardItem;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\BalanceWatch\Timeout;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\MileValueUserInfo;
use AwardWallet\MainBundle\Service\MileValue\ProviderMileValueItem;
use AwardWallet\MainBundle\Service\MobileExtensionList;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use AwardWallet\MobileBundle\View\DateFormatted;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileMapper extends Mapper
{
    public const MAP_ACCOUNT = 1;
    public const MAP_COUPON = 2;

    public const BARCODE_TYPE = 'barcode';
    public const QRCODE_TYPE = 'qrcode';

    // map from server-known format to client format
    public const BARCODE_MAP = [
        BAR_CODE_UPC_A => "UPC_A",
        BAR_CODE_CODE_39 => "CODE_39",
        BAR_CODE_EAN_13 => "EAN_13",
        BAR_CODE_CODE_128 => "CODE_128",
        BAR_CODE_INTERLEAVED => "ITF",
        BAR_CODE_PDF_417 => "PDF_417",
        'pdf417' => "PDF_417",
        BAR_CODE_GS1_128 => "CODE_128",
        BAR_CODE_QR => "QR_CODE",
    ];

    protected const DATA_TEMPLATE = [
        '_' => null,
        'TableName' => null,
        'Error' => null,
        'Access' => null,
        'Autologin' => null,
        'BarCode' => null,
        'ProviderID' => null,
        'ProviderCode' => null,
        'BackgroundColor' => null,
        'FontColor' => null,
        'AccentColor' => null,
        'Border_LM' => null,
        'Border_DM' => null,
        'Balance' => null,
        'BalancePush' => null,
        'Disabled' => self::MAP_ACCOUNT,
        'LastBalance' => null,
        'TotalBalance' => null,
        'Description' => null,
        'DisplayName' => null,
        'ExpirationDate' => null,
        'ExpirationDateFormatted' => 'ExpirationDate',
        'ExpirationDatePush' => null,
        'ExpirationState' => null,
        'ExpirationUpgrade' => null,
        'EliteStatus' => null,
        'FID' => null,
        'ID' => null,
        'Blocks' => null,
        'PreviewBlocks' => null,
        'Kind' => null,
        'LastChange' => null,
        'LastChangeRaw' => null,
        'LastChangeDateTs' => 'LastChangeDate',
        'SuccessCheckDateTs' => 'SuccessCheckDate',
        'LastUpdatedDateTs' => null,
        'Login' => null,
        'Number' => null,
        'Notice' => null,
        'ShareUserAgentID' => null,
        'Status' => null,
        'Phones' => null,
        'UserAgentID' => null,
        'UserID' => null,
        'UserName' => null,
        'FamilyName' => null,
        'LoginURL' => null,
        'IATACode' => null,
        'isCustom' => null,
        'Locations' => null,
        'CreditCardsRecommendation' => null,
        'BarCodeCustom' => null,
        'BarCodeParsed' => null,
        'CouponType' => null,
        'LinkedToAccountID' => 'ParentAccount',
        'HasHistory' => null,
        'HasBalanceChart' => null,
        'BalanceWatchEndDate' => null,
        'Type' => null,
        'Documents' => null,
        'PwnedNotice' => null,
        'CountryOfIssue' => null,
        'SavePassword' => null,
        'SubAccountsArray' => [
            self::MAP_COLLECTION,
            'TableName' => null,
            'Access' => null,
            'AccountState' => null,
            'ProviderID' => null,
            'LastBalance' => null,
            'Balance' => null,
            'BalancePush' => null,
            'LastChange' => null,
            'LastChangeRaw' => null,
            'LastChangeDateTs' => 'LastChangeDate',

            'Description' => null,
            'DisplayName' => null,

            'ExpirationDate' => null,
            'ExpirationDateFormatted' => 'ExpirationDate',
            'ExpirationDatePush' => null,
            'ExpirationState' => null,
            'ExpirationUpgrade' => null,

            'Blocks' => null,
            'PreviewBlocks' => null,
            'Kind' => null,
            'Login' => null,
            'Number' => null,
            'ShareUserAgentID' => null,
            'Status' => null,
            'SubAccountID' => null,
            'MobileAutoLogin' => null,
            'Locations' => null,
            'BarCodeCustom' => null,
            'BarCodeParsed' => null,
            'HasHistory' => null,
            'HasBalanceChart' => null,
        ],
        'LastDurationWithoutPlans' => 'UpdateDurationWithoutPlans',
        'Question' => null,
        'revealUrl' => null,
        'ScanBarcode' => null,
        'ShowPictures' => null,
        'BarcodeType' => null,
        'VaccineCardAccount' => null,
        'MileValue' => null,
        'MileValueSet' => null,
    ];

    protected const ACCOUNT_BLOCKS_TEMPLATE = [
        BlockFactory::BLOCK_BARCODE => [],
        BlockFactory::BLOCK_DISABLED => null,
        BlockFactory::BLOCK_NOTICE => null,
        BlockFactory::BLOCK_BALANCE_WATCH => null,
        BlockFactory::BLOCK_OWNER => null,
        BlockFactory::BLOCK_BALANCE => null,
        BlockFactory::BLOCK_CASH_VALUE => null,
        BlockFactory::BLOCK_EXPIRATION_DATE => null,
        BlockFactory::BLOCK_STATUS => null,
        BlockFactory::BLOCK_ACCOUNT_NUMBER => null,
        BlockFactory::BLOCK_MILE_VALUE => [],
        BlockFactory::BLOCK_PROPERTIES => [], // in-place
        BlockFactory::BLOCK_LOGIN => null,
        BlockFactory::BLOCK_SUCCESS_CHECK_DATE => null,
        BlockFactory::BLOCK_IATA => null,
        BlockFactory::BLOCK_COMMENT => null,
        BlockFactory::BLOCK_CARD_IMAGE => null,
        BlockFactory::BLOCK_SET_LOCATION_LINK => null,
        BlockFactory::BLOCK_LOCATIONS => null,
        BlockFactory::BLOCK_BLOG_TOP_POSTS => null,
        BlockFactory::BLOCK_BLOG_PROMOTIONS => null,
    ];

    protected const COUPON_BLOCKS_TEMPLATE = [
        BlockFactory::BLOCK_BARCODE => [],
        BlockFactory::BLOCK_OWNER => null,
        BlockFactory::BLOCK_TYPE => null,
        BlockFactory::BLOCK_CARD_NUMBER => null,
        BlockFactory::BLOCK_PIN => null,
        BlockFactory::BLOCK_COUPON_VALUE => null,
        BlockFactory::BLOCK_EXPIRATION_DATE => null,
        BlockFactory::BLOCK_CARD_IMAGE => null,
        BlockFactory::BLOCK_SET_LOCATION_LINK => null,
        BlockFactory::BLOCK_LOCATIONS => null,
        BlockFactory::BLOCK_DESCRIPTION => null,
    ];

    protected const ACCOUNT_PREVIEW_BLOCKS_TEMPLATE = [
        BlockFactory::BLOCK_BALANCE => null,
        BlockFactory::BLOCK_LOGIN => null,
        BlockFactory::BLOCK_ACCOUNT_NUMBER => null,
        BlockFactory::BLOCK_STATUS => null,
        BlockFactory::BLOCK_EXPIRATION_DATE => null,
    ];

    protected const COUPON_PREVIEW_BLOCKS_TEMPLATE = [
        BlockFactory::BLOCK_PASSPORT_NAME => null,
        BlockFactory::BLOCK_PASSPORT_NUMBER => null,
        BlockFactory::BLOCK_PASSPORT_ISSUE_DATE => null,
        BlockFactory::BLOCK_PASSPORT_ISSUE_COUNTRY => null,
        BlockFactory::BLOCK_TRUSTED_TRAVELER_NUMBER => null,
        BlockFactory::BLOCK_EXPIRATION_DATE => null,
    ];

    protected const SUBACCOUNT_BLOCKS_TEMPLATE = [
        BlockFactory::BLOCK_BARCODE => [],
        BlockFactory::BLOCK_OWNER => null,
        BlockFactory::BLOCK_BALANCE => null,
        BlockFactory::BLOCK_ACCOUNT_NUMBER => null,
        BlockFactory::BLOCK_EXPIRATION_DATE => null,
        BlockFactory::BLOCK_PROPERTIES => [], // in-place
        BlockFactory::BLOCK_CARD_IMAGE => null,
        BlockFactory::BLOCK_BLOG_POSTS => null,
        BlockFactory::BLOCK_SET_LOCATION_LINK => null,
        BlockFactory::BLOCK_LOCATIONS => null,
    ];

    protected const LOYALTY_FONT_COLOR_MAP = [
        PROVIDER_KIND_DOCUMENT => 'ffffff',
    ];

    protected const LOYALTY_BACKGROUND_COLOR_MAP = [
        PROVIDER_KIND_DOCUMENT => '4684c4',
    ];

    protected const CUSTOM_ACCOUNT_LINKED_PROVIDER_PROPERTIES_MAP = [
        'CustomAccountProviderCode' => 'ProviderCode',
        'CustomAccountProviderFontColor' => 'FontColor',
        'CustomAccountProviderBackgroundColor' => 'BackgroundColor',
        'CustomAccountProviderAccentColor' => 'AccentColor',
        'CustomAccountProviderBorder_LM' => 'Border_LM',
        'CustomAccountProviderBorder_DM' => 'Border_DM',
    ];

    public $accountStat = [
        'AccountsByNumber' => [],
        'EliteStatusByProvider' => [],
    ];

    /**
     * @var ProviderRepository
     */
    protected $providerRep;

    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @var Desanitizer
     */
    protected $desanitizer;
    /**
     * @var LocalPasswordsManager
     */
    protected $localPasswordsManager;
    /**
     * @var ApiVersioningService
     */
    protected $apiVersioning;
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;
    /**
     * @var AccountRepository
     */
    protected $accountRepository;

    private MileValueUserInfo $mileValueUserInfo;
    private \ReflectionClass $providerReflClass;
    /**
     * @var array<string, \ReflectionProperty>
     */
    private array $providerReflPropertiesMap = [];
    private LegacyUrlGenerator $legacyUrlGenerator;
    private SafeExecutorFactory $safeExecutorFactory;
    private MobileExtensionList $mobileExtensionList;
    private CreditCardRepository $creditCardRepository;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        ProviderTranslator $providerTranslator,
        LocalizeService $localizer,
        SessionVoter $sessionVoter,
        DateTimeIntervalFormatter $intervalFormatter,
        ExpirationDateResolver $expirationResolver,
        ProgramStatusResolver $statusResolver,
        BalanceFormatter $balanceFormatter,
        RouterInterface $router,
        LocalPasswordsManager $localPasswordsManager,
        ApiVersioningService $apiVersioning,
        AuthorizationCheckerInterface $authorizationChecker,
        AccountRepository $accountRepository,
        Desanitizer $desanitizer,
        Timeout $bwTimeout,
        UserMailboxCounter $userMailboxCounter,
        ProviderRepository $providerRepository,
        MileValueUserInfo $mileValueUserInfo,
        LegacyUrlGenerator $legacyUrlGenerator,
        SafeExecutorFactory $safeExecutorFactory,
        PropertyFormatter $propertyFormatter,
        MobileExtensionList $mobileExtensionList,
        ClockInterface $clock,
        MileValueCards $mileValueCards,
        MileValueService $mileValueService,
        CreditCardRepository $creditCardRepository
    ) {
        parent::__construct(
            $em,
            $translator,
            $providerTranslator,
            $localizer,
            $sessionVoter,
            $intervalFormatter,
            $expirationResolver,
            $statusResolver,
            $balanceFormatter,
            $bwTimeout,
            $userMailboxCounter,
            $propertyFormatter,
            $clock,
            $mileValueCards,
            $mileValueService
        );

        $this->router = $router;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->apiVersioning = $apiVersioning;
        $this->authorizationChecker = $authorizationChecker;
        $this->accountRepository = $accountRepository;
        $this->desanitizer = $desanitizer;
        $this->providerRep = $providerRepository;
        $this->mileValueUserInfo = $mileValueUserInfo;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->mobileExtensionList = $mobileExtensionList;
        $this->clock = $clock;
        $this->creditCardRepository = $creditCardRepository;
    }

    public function map(MapperContext $mapperContext, $accountID, $accountFields, $accountsIds)
    {
        $loadedData = $mapperContext->loaderContext->accounts[$accountID];

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_FAMILY_NAME)) {
            if (isset($accountFields['FamilyMemberName']) && !empty($accountFields['FamilyMemberName'])) {
                $accountFields['FamilyName'] = $accountFields['FamilyMemberName'];
            }

            if (StringUtils::isNotEmpty($accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD]['passportName'] ?? null)) {
                $accountFields['UserName'] = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD]['passportName'];
            }

            if (StringUtils::isNotEmpty($accountFields['CustomFields'][Providercoupon::FIELD_KEY_PASSPORT]['passportName'] ?? null)) {
                $accountFields['UserName'] = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_PASSPORT]['passportName'];
            }

            if (StringUtils::isNotEmpty($accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD]['passportName'] ?? null)) {
                $accountFields['UserName'] = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD]['passportName'];
            }

            if (StringUtils::isNotEmpty($accountFields['CustomFields'][Providercoupon::FIELD_KEY_INSURANCE_CARD]['nameOnCard'] ?? null)) {
                $accountFields['UserName'] = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_INSURANCE_CARD]['nameOnCard'];
            }

            if (StringUtils::isNotEmpty($accountFields['CustomFields'][Providercoupon::FIELD_KEY_DRIVERS_LICENSE]['fullName'] ?? null)) {
                $accountFields['UserName'] = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_DRIVERS_LICENSE]['fullName'];
            }
        } else {
            if (isset($accountFields['FamilyMemberName']) && !empty($accountFields['FamilyMemberName'])) {
                $accountFields['UserName'] = $accountFields['FamilyMemberName'];
            }
        }

        $accountFields = parent::map($mapperContext, $accountID, $accountFields, $accountsIds);

        if (isset($accountFields['ShareUserAgentID'])) {
            $accountFields['UserAgentID'] = $accountFields['ShareUserAgentID'];
        }

        $type = 'Account' === $accountFields['TableName'] ? self::MAP_ACCOUNT : self::MAP_COUPON;

        if ('Account' === $accountFields['TableName']) {
            $access = $accountFields['Access'];
            $accountFields['Access'] = array_intersect_key(
                $access,
                [
                    'edit' => null,
                    'delete' => null,
                    'autologin' => null,
                    'update' => null,
                ]
            );
            $accountFields['Access']['autologinV3'] = $access['autologinExtensionV3'];

            if ($access['update'] && !$access['eligibleUpdate']) {
                $accountFields['Access']['update'] = false;
            }

            if (
                !isset($accountFields['ProviderID'])
                && !$this->apiVersioning->supports(MobileVersions::CUSTOM_ACCOUNTS)
            ) {
                $accountFields['Access'] = [
                    'edit' => false,
                    'delete' => false,
                    'update' => false,
                    'autologin' => false,
                ];
            }

            $accountFields['Access']['updateAll'] =
                $accountFields['Access']['update']
                && !$loadedData['Disabled']
                && isset($loadedData['ProviderID'])
                && (
                    !isset($loadedData['RawUpdateDate'])
                    || ((new \DateTime($loadedData['RawUpdateDate']))->modify('+1 month') < $this->clock->current()->getAsDateTime())
                );

            unset($accountFields['LoginURL']); // TODO: fix this ugly method call order
        } else {
            $access = $accountFields['Access'];

            if ($this->apiVersioning->supports(MobileVersions::CUSTOM_ACCOUNTS)) {
                $accountFields['Access'] = array_intersect_key(
                    $access,
                    [
                        'edit' => null,
                        'delete' => null,
                    ]
                );
            } else {
                $accountFields['Access'] = [
                    'edit' => false,
                    'delete' => false,
                ];
            }

            $accountFields['Access']['update'] = false;
            $accountFields['Access']['autologin'] = false;
        }

        self::renameFields(array_filter($mapperContext->dataTemplate), $accountFields, $type);

        if (
            isset($accountFields['ParentAccount'])
            && ($parentAccount = $this->accountRepository->find($accountFields['ParentAccount']))
            && !$this->authorizationChecker->isGranted('READ_NUMBER', $parentAccount)
        ) {
            unset($accountFields['ParentAccount']);
        }

        $accountFields = $this->desanitizer->tryDesanitizeArray(
            $accountFields,
            [
                'DisplayName',
                'Balance',
                'LastBalance',
                'LastChange',
            ],
            Desanitizer::TAGS | Desanitizer::CHARS
        );

        if (isset($accountFields['TotalBalance'])) {
            $accountFields['TotalBalance'] = (float) $accountFields['TotalBalance'];
        }

        $this->mapCashTotals($accountFields);

        return $accountFields;
    }

    public function alterTemplate(MapperContext $mapperContext)
    {
        parent::alterTemplate($mapperContext);

        $alter = self::DATA_TEMPLATE;

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_TOTALS_IMPROVEMENTS)) {
            $alter = array_merge($alter, [
                'TotalUSDCashRaw' => null,
                'TotalUSDCashChange' => null,
            ]);
        }

        $mapperContext->alterDataTemplateBy($alter);
    }

    public static function renameFields($renameRules, array &$entityFields, $type)
    {
        foreach ($renameRules as $oldKey => $newKey) {
            if (!array_key_exists($oldKey, $entityFields) || ($oldKey === $newKey)) {
                continue;
            }

            switch (true) {
                case is_scalar($newKey):
                    if (is_string($newKey)) {
                        // rename field
                        $entityFields[$newKey] = &$entityFields[$oldKey]; // refcount + 1
                        unset($entityFields[$oldKey]); // refcount - 1
                    } elseif (
                        is_int($newKey)
                        && $newKey !== $type
                    ) {
                        // remove if types mismatch
                        unset($entityFields[$oldKey]);
                    }

                    break;

                case is_array($newKey) && is_array($entityFields[$oldKey]):
                    if (
                        isset($newKey[0])
                        && (self::MAP_COLLECTION === $newKey[0])
                    ) {
                        foreach ($entityFields[$oldKey] as &$collectionItemFields) {
                            self::renameFields($renameRules, $collectionItemFields, $type);
                        }

                        unset($collectionItemFields);
                    } else {
                        self::renameFields($renameRules, $entityFields[$oldKey], $type);
                    }

                    break;
            }
        }
    }

    protected function mapAccount(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapAccount($mapperContext, $accountID, $accountFields);
        $isChangedOverPeriod = isset($accountFields['ChangedOverPeriodPositive']);
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);
        unset($accountFields['Properties']['CustomerSupportPhone']);

        $accountFields['Disabled'] = ((bool) $accountFields['Disabled']);

        $blockTemplate = $this->getSortedBlocks();
        $this->mapCommonBlocks($mapperContext, $accountFields, $blockTemplate, self::ACCOUNT_PREVIEW_BLOCKS_TEMPLATE);

        $autologinOnly = isset($accountFields['ProviderID']) && 1 != $accountFields["CanCheck"] && 1 != $accountFields['CanCheckBalance']; // see ProgramStatusResolver::getStatus TYPE_ONLY_AUTOLOGIN usage
        $accountFields['Error'] =
            (isset($accountFields['ProviderID']) && ACCOUNT_CHECKED != $accountFields['ErrorCode'] && !$autologinOnly);

        if (
            isset($accountFields['ProviderID'])
            && $accountFields['Access']['autologin']
        ) {
            $autologinData = [];

            $isCredentialsExist =
                !StringHandler::isEmpty($accountFields['Login'])
                || (
                    SAVE_PASSWORD_DATABASE === (int) $accountFields['SavePassword'] ?
                    !StringHandler::isEmpty($accountFields['Pass']) :
                    $this->localPasswordsManager->hasPassword($accountFields['ID'])
                );
            $isCredentialsExist = $isCredentialsExist && !$accountFields['DisableClientPasswordAccess'];

            $autologinData['desktopExtension'] = $isCredentialsExist && isset($accountFields['MobileAutoLogin']) && (MOBILE_AUTOLOGIN_DESKTOP_EXTENSION == $accountFields['MobileAutoLogin']);
            $autologinData['mobileExtension'] = $isCredentialsExist && isset($accountFields['MobileAutoLogin']) && (MOBILE_AUTOLOGIN_EXTENSION == $accountFields['MobileAutoLogin']
                    && in_array($accountFields['ProviderCode'], $this->mobileExtensionList->getMobileExtensionsList()));

            $autologinData['loginUrl'] =
                $this->router->generate('aw_account_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL) .
                "?ID={$accountFields['ID']}" .
                "&fromApp=1";
            $accountFields['Autologin'] = $autologinData;
        } elseif (isset($accountFields['Countries'][$accountFields['Login2']])) {
            $accountFields['Access']['autologin'] = true;
            $accountFields['Autologin']['loginUrl'] = $accountFields['Countries'][$accountFields['Login2']]['LoginURL'];
        } elseif (
            isset($accountFields['LoginURL'])
            && !StringHandler::isEmpty($accountFields['LoginURL'])
        ) {
            $accountFields['Access']['autologin'] = true;
            $accountFields['Autologin']['loginUrl'] = $accountFields['LoginURL'];
        }

        if (isset($accountFields['ProviderID'])) {
            foreach (['Login', 'Number'] as $Number) {
                if (!isset($accountFields[$Number])) {
                    continue;
                }
                $value = strtolower(str_replace([' ', "\r", "\n", '-'], '', $accountFields[$Number]));

                if ('' !== $value) {
                    $mapperContext->accountStat['AccountsByNumber'][$accountFields['ProviderID']][$value] = $accountID;
                }
            }
        }

        if (isset($accountFields['Phones']) && is_array($accountFields['Phones'])) {
            $phones = [];

            foreach ($accountFields['Phones'] as $phone) {
                $phones[] = [
                    'phone' => $phone['Phone'],
                    'name' => $phone['Name'],
                    'region' => $phone['RegionCaption'],
                ];
            }
            $accountFields['Phones'] = $phones;
        }

        if (isset($accountFields['HasHistory'])) {
            $accountFields['HasHistory'] =
                ($accountFields['Access']['read_extproperties'] ?? false)
                && $accountFields['HasHistory']
                && (
                    ($user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS)
                    || $this->apiVersioning->supports(MobileVersions::ACCOUNT_HISTORY_FREE_USER_STUB)
                );
        }

        if (
            isset($accountFields['Access']['edit'])
            && $accountFields['Access']['edit']
            && StringUtils::isNotEmpty($accountFields['PwnedTimes'] ?? null)
            && ($accountFields['PwnedTimes'] > 0)
            && (SAVE_PASSWORD_LOCALLY !== (int) $accountFields['SavePassword'])
        ) {
            $accountFields['PwnedNotice'] = $this->translator->trans('checked-hacked-passwords', [
                '%bold_on%' => '<strong>',
                '%bold_off%' => '</strong>',
                '%count%' => $accountFields['PwnedTimes'],
                '%count_formatted%' => $this->localizer->formatNumber($accountFields['PwnedTimes'], null, $locale),
            ], 'messages', $locale);
        }

        $accountFields['HasBalanceChart'] =
            ($accountFields['Access']['read_extproperties'] ?? false)
            && !$accountFields['isCustom']
            && (($accountFields['BalanceChangesCount'] ?? 0) > 1);

        $accountFields['revealUrl'] =
            (
                ($accountFields['Access']['read_password'] ?? false)
                && StringUtils::isNotEmpty(
                    (SAVE_PASSWORD_DATABASE === (int) $accountFields['SavePassword']) ?
                        $accountFields['Pass'] :
                        $this->localPasswordsManager->getPassword($accountFields['ID'])
                )
            ) ?
                $this->router->generate('awm_get_pass', ['accountId' => $accountFields['ID']], UrlGeneratorInterface::ABSOLUTE_URL) :
                null;

        $accountFields['ScanBarcode'] = true;
        $accountFields['BarcodeType'] = self::BARCODE_TYPE;
        $accountFields['MileValueSet'] = isset($accountFields['ProviderID']);

        if (!$isChangedOverPeriod) {
            $accountFields['LastChange'] = null;
            $accountFields['LastChangeRaw'] = null;
        }

        if (isset($accountFields['MileValue'])) {
            $mileValue = $accountFields['MileValue'];
            $accountFields['MileValue'] = [
                'TotalValue' => $mileValue['approximate']['raw'] ?? null,
                'Value' => $mileValue['approximate']['value'] ?? null,
                'TotalLastChange' => $isChangedOverPeriod ?
                    ($mileValue['balanceChange']['raw'] ?? null) :
                    null,
                'LastChange' => $isChangedOverPeriod ?
                    ($mileValue['balanceChange']['value'] ?? null) :
                    null,
            ];
        }

        if ($this->authorizationChecker->isGranted('ROLE_STAFF') && $ccRecommendations = $this->formatCCRecommendations($mapperContext, $accountID, $accountFields)) {
            $accountFields['CreditCardsRecommendation'] = $ccRecommendations;
        }

        $accountFields = $this->mapLinkedProviderProperties($accountFields);

        return $accountFields;
    }

    protected function mapCoupon(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapCoupon($mapperContext, $accountID, $accountFields);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);
        $blocksTemplate = $this->getCouponBlocksTemplate($accountFields);
        $previewBlocksTemplate = $this->getCouponPreviewBlocksTemplate($accountFields);
        $this->mapCommonBlocks($mapperContext, $accountFields, $blocksTemplate, $previewBlocksTemplate);
        // ↓↓↓ DO NOT TOUCH WITHOUT JS DEVELOPER APPROVAL ↓↓↓
        $accountFields['Balance'] = htmlspecialchars($accountFields['Balance']);
        $accountFields['Login'] = htmlspecialchars_decode($accountFields['Login']);
        // ↑↑↑ DO NOT TOUCH WITHOUT JS DEVELOPER APPROVAL ↑↑↑
        $accountFields['FontColor'] = self::LOYALTY_FONT_COLOR_MAP[$accountFields['Kind']] ?? null;
        $accountFields['BackgroundColor'] = self::LOYALTY_BACKGROUND_COLOR_MAP[$accountFields['Kind']] ?? null;
        $accountFields['AccentColor'] = null;
        $accountFields['Border_LM'] = false;
        $accountFields['Border_DM'] = false;

        $isQrCodeDocument = \in_array($accountFields['TypeID'], [
            Providercoupon::TYPE_VACCINE_CARD,
            Providercoupon::TYPE_PRIORITY_PASS,
            Providercoupon::TYPE_DRIVERS_LICENSE,
        ]);
        $accountFields['Type'] = Providercoupon::DOCUMENT_TYPE_TO_KEY_MAP[$accountFields['TypeID']] ?? 'coupon';
        $accountFields['ScanBarcode'] = $isQrCodeDocument || !isset(ProviderCoupon::DOCUMENT_TYPES[$accountFields['TypeID']]);
        $accountFields['BarcodeType'] = $isQrCodeDocument ? self::QRCODE_TYPE : self::BARCODE_TYPE;
        $accountFields['VaccineCardAccount'] =
            \mb_stripos($disease = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD]['disease'] ?? '', 'covid') !== false
            || \mb_stripos($disease, 'coronavirus') !== false;
        $accountFields['ShowPictures'] = \in_array(
            $accountFields['TypeID'],
            [
                Providercoupon::TYPE_VISA,
                Providercoupon::TYPE_PASSPORT,
                Providercoupon::TYPE_VACCINE_CARD,
            ]
        );

        $accountFields['Documents'] =
            it($accountFields['DocumentImages'] ?? [])
            ->map(function (array $documentImage): array {
                return [
                    'id' => (int) $documentImage['DocumentImageID'],
                    'file' => $documentImage['FileName'],
                    'width' => (int) $documentImage['Width'],
                    'height' => (int) $documentImage['Height'],
                ];
            })
            ->toArray();

        if (
            (Providercoupon::TYPE_PASSPORT == $accountFields['TypeID'])
            && StringUtils::isNotEmpty($passportName = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_PASSPORT]['name'] ?? null)
        ) {
            $accountFields['Login'] = $passportName;
        } elseif (
            (Providercoupon::TYPE_TRUSTED_TRAVELER == $accountFields['TypeID'])
            && StringUtils::isNotEmpty($travelerNumber = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_TRUSTED_TRAVELER]['travelerNumber'] ?? null)
        ) {
            $accountFields['Login'] = $travelerNumber;
        } elseif (
            (Providercoupon::TYPE_VISA == $accountFields['TypeID'])
            && StringUtils::isNotEmpty($visaNumber = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_VISA]['visaNumber'] ?? null)
        ) {
            $accountFields['Login'] = $visaNumber;
        } elseif (
            (Providercoupon::TYPE_INSURANCE_CARD == $accountFields['TypeID'])
            && StringUtils::isNotEmpty($insuranceMemberNumber = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_INSURANCE_CARD]['memberNumber'] ?? null)
        ) {
            if (StringUtils::isNotEmpty($insuranceGroupNumber = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_INSURANCE_CARD]['groupNumber'] ?? null)) {
                $insuranceMemberNumber = "{$insuranceMemberNumber} ({$insuranceGroupNumber})";
            }

            $accountFields['Login'] = $insuranceMemberNumber;
        } elseif (
            (Providercoupon::TYPE_DRIVERS_LICENSE == $accountFields['TypeID'])
            && StringUtils::isNotEmpty($driversLicenseNumber = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_DRIVERS_LICENSE]['licenseNumber'] ?? null)
        ) {
            $accountFields['Login'] = $driversLicenseNumber;
        } elseif (Providercoupon::TYPE_VACCINE_CARD == $accountFields['TypeID']) {
            foreach (
                [
                    ['secondDoseVaccine', 'secondDoseDate'],
                    ['firstDoseVaccine', 'firstDoseDate'],
                ] as [$vaccineName, $vaccineDate]
            ) {
                if (
                    StringUtils::isAllNotEmpty(
                        $vaccineName = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD][$vaccineName] ?? null,
                        $vaccineDate = $accountFields['CustomFields'][Providercoupon::FIELD_KEY_VACCINE_CARD][$vaccineDate] ?? null,
                    )
                    && ($vaccineDate = $this->localizer->formatDateTime(DateTimeUtils::fromSerializedArray($vaccineDate), 'medium', null, $locale) ?: null)
                ) {
                    $accountFields['Login'] = "{$vaccineName} ({$vaccineDate})";

                    break;
                }
            }
        } elseif (null === $accountFields['TypeID'] && !empty($accountFields['TypeName'])) {
            $accountFields['Login'] = $accountFields['TypeName'];
        }

        $accountFields = $this->mapLinkedProviderProperties($accountFields);

        return $accountFields;
    }

    protected function mapLinkedProviderProperties(array $accountFields): array
    {
        foreach (self::CUSTOM_ACCOUNT_LINKED_PROVIDER_PROPERTIES_MAP as $customProviderField => $customField) {
            if (\array_key_exists($customProviderField, $accountFields)) {
                $accountFields[$customField] = $accountFields[$customProviderField];
            }
        }

        return $accountFields;
    }

    protected function filter(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::filter($mapperContext, $accountID, $accountFields);

        if ('Account' == $accountFields['TableName']) {
            if (isset($accountFields['SubAccountsArray']) && \is_array($accountFields['SubAccountsArray']) && isset($accountFields['Complex'])) {
                \uasort(
                    $accountFields['SubAccountsArray'],
                    // sort predicate deliberately ignores Unhideable property, because client will resort SubAccountsArray using DisplayName when attaching coupon.
                    fn (array $subAccount1, array $subAccount2) => $subAccount1['DisplayName'] <=> $subAccount2['DisplayName'],
                );
            }

            $blocksMap = &$accountFields['BlocksMap'];

            if (isset($accountFields['MainProperties']['Status']['Status'])) {
                $statusData = [
                    'Name' => $this->desanitizer->tryFullDesanitize($accountFields['MainProperties']['Status']['Status']),
                ];
                $eliteLevelsCount = $accountFields['EliteLevelsCount'];

                if (
                    (null !== $eliteLevelsCount)
                    && (-1 !== $eliteLevelsCount)
                    && isset($accountFields['EliteLevelsCount'], $accountFields['Rank'])
                ) {
                    $eliteLevelsCount = intval($accountFields['EliteLevelsCount']);
                    $eliteRank = intval($accountFields['Rank']);

                    if ($eliteRank > $eliteLevelsCount) {
                        $eliteRank = $eliteLevelsCount;
                    }
                    $statusData['Rank'] = $eliteRank;
                    $statusData['LevelsCount'] = $eliteLevelsCount;
                    $accountFields['EliteStatus'] = $statusData;
                    $mapperContext->accountStat['EliteStatusByProvider'][$accountFields['ProviderID']][$accountFields['ID']] = $eliteRank;
                }
            } elseif (isset($accountFields['CustomEliteLevel'])) {
                $accountFields['EliteStatus'] = [
                    'Name' => $accountFields['CustomEliteLevel'],
                    'Rank' => 0,
                    'LevelsCount' => 0,
                ];
            }

            // "low-level" access check, wait for voters implementation
            if (isset($accountFields['AccessLevel'])) {
                $filterFields = [];
                $filterBlocks = [];

                switch ($accountFields['AccessLevel']) {
                    case ACCESS_READ_BALANCE_AND_STATUS:
                        $filterFields = [
                            'Login',
                            'Number',
                            'BarCode',
                            'BarCodeCustom',
                            'BarCodeParsed',

                            'Error',
                            'Notice',
                            'Disabled',

                            'ExpirationDate',
                            'ExpirationState',
                            'ExpirationDateFormatted',
                            'ExpirationDatePush',
                            'ExpirationUpgrade',

                            'LastChangeDate',
                            'LastChangeDateTs',
                            'SuccessCheckDate',
                            'SuccessCheckDateTs',

                            'Phones',
                        ];

                        $filterBlocks = [
                            BlockFactory::BLOCK_NOTICE,
                            BlockFactory::BLOCK_DISABLED,
                            BlockFactory::BLOCK_ACCOUNT_NUMBER,
                            BlockFactory::BLOCK_LOGIN,
                            BlockFactory::BLOCK_BARCODE,
                            BlockFactory::BLOCK_SUCCESS_CHECK_DATE,
                            BlockFactory::BLOCK_EXPIRATION_DATE,
                            BlockFactory::BLOCK_PROPERTIES,
                            BlockFactory::BLOCK_COMMENT,
                        ];

                        break;

                    case ACCESS_READ_NUMBER:
                        $filterFields = [
                            'ExpirationDate',
                            'ExpirationState',
                            'ExpirationDateFormatted',
                            'ExpirationDatePush',
                            'ExpirationUpgrade',

                            'LastChangeDate',
                            'LastChangeDateTs',
                            'SuccessCheckDate',
                            'SuccessCheckDateTs',

                            'Disabled',
                            'Notice',
                            'Error',

                            'Balance',
                            'LastBalance',
                            'LastChange',
                            'LastChangeRaw',
                            'TotalBalance',
                            'MileValue',

                            'Phones',
                        ];

                        $filterBlocks = [
                            BlockFactory::BLOCK_DISABLED,
                            BlockFactory::BLOCK_NOTICE,
                            BlockFactory::BLOCK_BALANCE,
                            BlockFactory::BLOCK_SUCCESS_CHECK_DATE,
                            BlockFactory::BLOCK_EXPIRATION_DATE,
                            BlockFactory::BLOCK_PROPERTIES,
                            BlockFactory::BLOCK_COMMENT,
                            BlockFactory::BLOCK_MILE_VALUE,
                        ];

                        break;
                }

                $this->filterSensitiveData($filterBlocks, self::ACCOUNT_BLOCKS_TEMPLATE, self::ACCOUNT_PREVIEW_BLOCKS_TEMPLATE, $filterFields, $accountFields);

                if (isset($accountFields["SubAccountsArray"]) && is_array($accountFields["SubAccountsArray"])) {
                    foreach ($accountFields['SubAccountsArray'] as &$subAccount) {
                        $this->filterSensitiveData($filterBlocks, self::SUBACCOUNT_BLOCKS_TEMPLATE, self::ACCOUNT_PREVIEW_BLOCKS_TEMPLATE, $filterFields, $subAccount);
                    }
                    unset($subAccount);
                }
            }
        }

        if (
            ('Coupon' === $accountFields['TableName'])
            && isset($accountFields['AccessLevel'])
        ) {
            $filterFields = [];
            $filterBlocks = [];

            switch ($accountFields['AccessLevel']) {
                case ACCESS_READ_BALANCE_AND_STATUS:
                    $filterFields = [
                        'Login',
                        'Description',

                        'ExpirationDate',
                        'ExpirationState',
                        'ExpirationDateFormatted',
                        'ExpirationDatePush',
                        'ExpirationUpgrade',

                        'PIN',
                    ];

                    $filterBlocks = [
                        BlockFactory::BLOCK_DISABLED,
                        BlockFactory::BLOCK_ACCOUNT_NUMBER,
                        BlockFactory::BLOCK_LOGIN,
                        BlockFactory::BLOCK_BARCODE,
                        BlockFactory::BLOCK_EXPIRATION_DATE,
                        BlockFactory::BLOCK_PROPERTIES,
                        BlockFactory::BLOCK_COMMENT,
                        BlockFactory::BLOCK_DESCRIPTION,
                        BlockFactory::BLOCK_PIN,
                    ];

                    break;

                case ACCESS_READ_NUMBER:
                    $filterFields = [
                        'ExpirationDate',
                        'ExpirationState',
                        'ExpirationDateFormatted',
                        'ExpirationDatePush',
                        'ExpirationUpgrade',

                        'Balance',
                        'TotalBalance',
                        'PIN',
                    ];

                    $filterBlocks = [
                        BlockFactory::BLOCK_DISABLED,
                        BlockFactory::BLOCK_NOTICE,
                        BlockFactory::BLOCK_BALANCE,
                        BlockFactory::BLOCK_SUCCESS_CHECK_DATE,
                        BlockFactory::BLOCK_EXPIRATION_DATE,
                        BlockFactory::BLOCK_PROPERTIES,
                        BlockFactory::BLOCK_COMMENT,
                        BlockFactory::BLOCK_COUPON_VALUE,
                        BlockFactory::BLOCK_PIN,
                    ];

                    break;
            }

            $this->filterSensitiveData($filterBlocks, self::COUPON_BLOCKS_TEMPLATE, self::COUPON_PREVIEW_BLOCKS_TEMPLATE, $filterFields, $accountFields);
        }

        // remove nullable properties
        $foldKeys = [
            'TotalBalance',
        ];

        foreach ($foldKeys as $key) {
            if (array_key_exists($key, $accountFields) && null === $accountFields[$key]) {
                unset($accountFields[$key]);
            }
        }

        return $accountFields;
    }

    protected function filterSensitiveData(array $blocksFilter, array $blocksTemplate, array $previewBlocksTemplate, array $fieldsFilter, array &$target)
    {
        foreach ([
            ['Blocks', 'BlocksMap', $blocksTemplate],
            ['PreviewBlocks', 'PreviewBlocksMap', $previewBlocksTemplate],
        ] as [$blocksKey, $blocksMapKey, $template]) {
            if (!isset($target[$blocksMapKey])) {
                continue;
            }

            $blocksMap = $target[$blocksMapKey];

            $needsReindex = self::unsetBlocks(
                $blocksFilter,
                $blocksMap,
                $template
            );

            if ($needsReindex) {
                $target[$blocksKey] = array_values(array_filter($target[$blocksKey]));
            }

            foreach ($fieldsFilter as $field) {
                unset($target[$field]);
            }

            unset($target[$blocksMapKey]);
        }
    }

    protected function mapSubAccounts(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapSubAccounts($mapperContext, $accountID, $accountFields);
        $user = $mapperContext->options->get(Options::OPTION_USER);

        if (
            !isset($accountFields["SubAccountsArray"])
            || !is_array($accountFields["SubAccountsArray"])
        ) {
            return $accountFields;
        }

        if ($accountFields["SubAccountsArray"] ?? []) {
            $subAccountsArray = array_map(
                function ($subAccount) use ($accountFields, $mapperContext, $user) {
                    $subAccount['UserName'] = $accountFields['UserName'];
                    $subAccount['Kind'] = $accountFields['Kind'];
                    $isChangedOverPeriod = isset($subAccount['ChangedOverPeriodPositive']);

                    $this->mapCommonBlocks($mapperContext, $subAccount, self::SUBACCOUNT_BLOCKS_TEMPLATE, self::ACCOUNT_PREVIEW_BLOCKS_TEMPLATE);

                    if (isset($subAccount['HasHistory'])) {
                        $subAccount['HasHistory'] =
                            ($accountFields['Access']['read_extproperties'] ?? false)
                            && $subAccount['HasHistory']
                            && (
                                ($user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS)
                                || $this->apiVersioning->supports(MobileVersions::ACCOUNT_HISTORY_FREE_USER_STUB)
                            );
                    }

                    if (!$isChangedOverPeriod) {
                        $subAccount['LastChange'] = null;
                        $subAccount['LastChangeRaw'] = null;
                    }

                    $subAccount['HasBalanceChart'] =
                        ($accountFields['Access']['read_extproperties'] ?? false)
                        && (($subAccount['BalanceChangesCount'] ?? 0) > 1);

                    return $this->desanitizer->tryDesanitizeArray(
                        $subAccount,
                        [
                            'DisplayName',
                            'Balance',
                            'LastBalance',
                            'LastChange',
                        ],
                        Desanitizer::TAGS | Desanitizer::CHARS
                    );
                },
                $accountFields['SubAccountsArray']
            );

            $accountFields["SubAccountsArray"] = $subAccountsArray;
        }

        return $accountFields;
    }

    protected static function unsetBlocks(array $keys, array &$blocksMap, array $blocksTemplate)
    {
        $needsReindex = false;

        foreach ($keys as $key) {
            if (!(array_key_exists($key, $blocksMap) && array_key_exists($key, $blocksTemplate))) {
                continue;
            }

            $needsReindex = true;

            if (is_array($blocksTemplate[$key])) {
                foreach ($blocksMap[$key] as &$block) {
                    $block = null;
                }
                unset($block);
            } else {
                $blocksMap[$key] = null;
            }
        }

        return $needsReindex;
    }

    protected function hasBlock($block, array $template)
    {
        return array_key_exists($block, $template);
    }

    /**
     * Merges arrays by specified keys.
     */
    protected static function array_merge_keys(array $keys, array $array)
    {
        $argv = func_get_args();

        $keys = array_fill_keys($keys, null);
        $arrays = array_slice($argv, 1);

        foreach ($arrays as $index => &$array) {
            if (!is_array($array)) {
                $index--;

                throw new \InvalidArgumentException("Argument {$index} is not an array");
            }
            $array = array_intersect_key($array, $keys);
        }
        unset($array);

        if (1 === count($arrays)) {
            return $arrays[0];
        } else {
            return call_user_func_array('array_merge', $arrays);
        }
    }

    protected function makeProviderStub(array $fields): Provider
    {
        $provider = new Provider();

        foreach ($fields as $key => $value) {
            $key = \strtolower($key);
            $methodName = 'set' . $key;

            if (\method_exists($provider, $methodName)) {
                $provider->{$methodName}($value);
            } else {
                if (!isset($this->providerReflPropertiesMap[$key])) {
                    if (!isset($this->providerReflClass)) {
                        $this->providerReflClass = $providerReflClass = new \ReflectionClass(Provider::class);
                    }

                    $this->providerReflPropertiesMap[$key] = $property = $providerReflClass->getProperty($key);
                    $property->setAccessible(true);
                } else {
                    $property = $this->providerReflPropertiesMap[$key];
                }

                $property->setValue($provider, $value);
            }
        }

        return $provider;
    }

    private function formatCCRecommendations(MapperContext $mapperContext, $accountID, $accountFields): array
    {
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);
        /** @var array{Merchant, MerchantMobileRecommendation} $merchantRecommendation */
        $merchantRecommendation = $accountFields['MerchantRecommendation'] ?? null;

        if (!$merchantRecommendation) {
            return [];
        }

        [$merchant, $merchantRecommendationData] = $merchantRecommendation;

        $result = [];
        $bestHaveCard = $bestValueCard = null;
        $isTransactionsExists = $merchantRecommendationData->isTransactionsExists();

        if (($topRecommended = $merchantRecommendationData->getTopHasUserCard())
            && ($formatted = $this->formatCCRecommendation($topRecommended, $locale))
        ) {
            $bestHaveCard = $formatted;
        }

        if (
            ($topRecommended = $merchantRecommendationData->getTopRecommended())
            && ($formatted = $this->formatCCRecommendation($topRecommended, $locale))
        ) {
            $bestValueCard = $formatted;
        }

        if (null !== $bestHaveCard
            && null !== $bestValueCard
            && $bestHaveCard['value'] === $bestValueCard['value']
        ) {
            $bestValueCard = null;
        }

        if (!$isTransactionsExists && !empty($bestValueCard) && empty($bestHaveCard)) {
            $bestValueCard['isOptimal'] = true;
        } elseif (!empty($bestHaveCard) && empty($bestValueCard)) {
            $bestHaveCard['isHighValue'] = true;
        } elseif (!empty($bestHaveCard) && !empty($bestValueCard)) {
            $bestHaveCard['isHighValue'] = true;
            $bestValueCard['isBestCard'] = true;
        }

        $bestHaveCard ? $result[] = $bestHaveCard : null;
        $bestValueCard ? $result[] = $bestValueCard : null;

        return $result;
    }

    private function formatCCRecommendation(OfferCreditCardItem $cardItem, ?string $locale): ?array
    {
        $mileValue = isset($cardItem->minValue) ?
            "({$cardItem->minValue}¢ - {$cardItem->maxValue}¢ per dollar)" :
            "({$cardItem->value}¢ per dollar)";

        return [
            'name' => $cardItem->name,
            'picture' => $this->legacyUrlGenerator->generateAbsoluteUrl($cardItem->picturePath),
            'multiplier' => $cardItem->multiplier,
            'value' => $cardItem->value,
            'valueFormatted' => $this->localizer->formatNumber($cardItem->value, 2) . ' ' . ProviderMileValueItem::CURRENCY_CENT,
            'valueInfo' => trim($this->translator->trans(/** @Desc("%value% per dollar") */ 'value-per-dollar', [
                '%value%' => '',
            ])),
            'pointName' => $cardItem->pointName,
            'link' => $cardItem->link,
        ];
    }

    private function mapCommonBlocks(MapperContext $mapperContext, array &$entityFields, array $origBlocksTemplate, array $origPreviewBlocksTemplate)
    {
        $blocksTemplate = $origBlocksTemplate;
        $previewBlocksTemplate = $origPreviewBlocksTemplate;

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_DETAILS_REDESIGN_2024_SUMMER)) {
            unset($blocksTemplate[BlockFactory::BLOCK_OWNER]);
            unset($blocksTemplate[BlockFactory::BLOCK_BALANCE]);
            unset($blocksTemplate[BlockFactory::BLOCK_COUPON_VALUE]);
        }

        /** @var Usr $user */
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $locale = $mapperContext->options->get(Options::OPTION_LOCALE);

        if (!isset($entityFields['BlocksMap'])) {
            $entityFields['BlocksMap'] = $origBlocksTemplate;
            $entityFields['PreviewBlocksMap'] = $origPreviewBlocksTemplate;
        }
        $blocksMap = &$entityFields['BlocksMap'];
        $previewBlocksMap = &$entityFields['PreviewBlocksMap'];
        $previewBlocksIndexesMap = [];

        if (!isset($entityFields['Blocks'])) {
            $entityFields['Blocks'] = [];
        }

        $blocks = &$entityFields['Blocks'];

        if (!isset($entityFields['PreviewBlocks'])) {
            $entityFields['PreviewBlocks'] = [];
        }

        $previewBlocks = &$entityFields['PreviewBlocks'];
        $isCoupon = ('Coupon' === ($entityFields['TableName'] ?? ''));
        $blocksProviderMap = $isCoupon ?
            $this->getCouponCustomPropertiesProviderMap($entityFields, $locale) :
            [];

        $isRegionalSettingsEnabled = $this->apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS);
        $isRedesign2024Summer = $this->apiVersioning->supports(MobileVersions::ACCOUNT_DETAILS_REDESIGN_2024_SUMMER);
        $locationBlock = [];
        $__encoderContext = new EncoderContext();

        foreach ($origBlocksTemplate as $blockKind => $blockTemplate) {
            switch ($blockKind) {
                case BlockFactory::BLOCK_BARCODE:
                    $parsedBarcode = null;

                    if (
                        isset($entityFields['Properties']['BarCode']['Val']) && '' !== $entityFields['Properties']['BarCode']['Val']
                        && isset($entityFields['Properties']['BarCodeType']['Val']) && '' !== $entityFields['Properties']['BarCodeType']['Val']
                    ) {
                        $parsedBarcode = [
                            'BarCodeType' => $entityFields['Properties']['BarCodeType']['Val'],
                            'BarCodeData' => $entityFields['Properties']['BarCode']['Val'],
                        ];
                    } elseif (
                        isset($entityFields['BarCode']) && '' !== $entityFields['BarCode']
                        && isset($entityFields['MainProperties']['Login']) && '' !== $entityFields['MainProperties']['Login']
                        // exclude Login looking as email
                        && (false === \filter_var($entityFields['MainProperties']['Login'], \FILTER_VALIDATE_EMAIL))
                    ) {
                        $parsedBarcode = [
                            'BarCodeData' => $entityFields['MainProperties']['Login'],
                            'BarCodeType' => $entityFields['BarCode'],
                        ];
                    }

                    if ($this->apiVersioning->supports(MobileVersions::BARCODES)) {
                        unset($entityFields['BarCode']);
                        $customBarcode = [
                            'BarCodeData' => null,
                            'BarCodeType' => null,
                        ];

                        if (isset(
                            $entityFields['CustomLoyaltyProperties']['BarCodeData'],
                            $entityFields['CustomLoyaltyProperties']['BarCodeType']
                        )) {
                            $customBarcode = [
                                'BarCodeData' => $entityFields['CustomLoyaltyProperties']['BarCodeData'],
                                'BarCodeType' => $entityFields['CustomLoyaltyProperties']['BarCodeType'],
                            ];
                        }

                        if ($parsedBarcode) {
                            if ($parsedBarcode['BarCodeType'] === BAR_CODE_QR) {
                                $dataLength = mb_strlen($parsedBarcode['BarCodeData']);
                                $parsedBarCodeIsValid = $dataLength <= 150 && $dataLength > 0;
                            } else {
                                $barcodeCreator = new BarcodeCreator($this->root);
                                $barcodeCreator->setFormat($parsedBarcode['BarCodeType']);
                                $barcodeCreator->setNumber($parsedBarcode['BarCodeData']);

                                try {
                                    $barcodeCreator->validate();
                                    $parsedBarCodeIsValid = true;
                                } catch (\Exception $e) {
                                    $parsedBarCodeIsValid = false;
                                }
                            }

                            if ($parsedBarCodeIsValid) {
                                if (array_key_exists($parsedBarcode['BarCodeType'], self::BARCODE_MAP)) {
                                    $parsedBarcode['BarCodeType'] = self::BARCODE_MAP[$parsedBarcode['BarCodeType']];
                                }

                                $entityFields['BarCodeParsed'] = $parsedBarcode;
                                $blockData[] = BlockFactory::createBlock(
                                    BlockFactory::BLOCK_BARCODE,
                                    null,
                                    [
                                        'Linked' => true,
                                        'BarCodeData' => 'BarCodeParsed.BarCodeData',
                                        'BarCodeType' => 'BarCodeParsed.BarCodeType',
                                    ],
                                    '!BarCodeCustom.BarCodeData'
                                );
                            }
                        }

                        if (isset($customBarcode['BarCodeData'], $customBarcode['BarCodeType'])) {
                            $entityFields['BarCodeCustom'] = $customBarcode;
                        }

                        $blockData[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_BARCODE,
                            null,
                            [
                                'Linked' => true,
                                'Custom' => true,
                                'BarCodeData' => 'BarCodeCustom.BarCodeData',
                                'BarCodeType' => 'BarCodeCustom.BarCodeType',
                            ],
                            'BarCodeCustom.BarCodeData'
                        );

                        if (
                            !$this->apiVersioning->supports(MobileVersions::LOCATION_STORAGE)
                        ) {
                            break;
                        }

                        $locations = $entityFields['StoreLocations'] ?? [];

                        if (
                            isset($entityFields['Access']['edit'])
                            && !$entityFields['Access']['edit']
                            && !$locations
                        ) {
                            break;
                        }

                        if (isset($entityFields['BarCodeCustom'])
                            && isset($entityFields['BarCodeCustom']['BarCodeData'], $entityFields['BarCodeCustom']['BarCodeType'])) {
                            $bcData = $entityFields['BarCodeCustom']['BarCodeData'];
                            $bcType = $entityFields['BarCodeCustom']['BarCodeType'];
                        } elseif (isset($entityFields['BarCodeParsed'])
                            && isset($entityFields['BarCodeParsed']['BarCodeData'], $entityFields['BarCodeParsed']['BarCodeType'])) {
                            $bcData = $entityFields['BarCodeParsed']['BarCodeData'];
                            $bcType = $entityFields['BarCodeParsed']['BarCodeType'];
                        } else {
                            $bcData = $bcType = null;
                        }

                        foreach ($locations as $location) {
                            $location["Tracked"] = isset($location['Tracked']) && $location['Tracked'] != 0;

                            if (isset($bcData, $bcType) && $location["Tracked"]) {
                                $entityFields['Locations'][] = array_intersect_key($location, array_flip([
                                    "LocationID", "LocationName", "Lat", "Lng", "Radius", "Tracked",
                                ]));
                            }
                            $locationBlock[] = array_intersect_key($location, array_flip(["LocationID", "LocationName", "Tracked"]));
                        }
                    } elseif ($parsedBarcode && $parsedBarcode['BarCodeType'] !== BAR_CODE_QR) {
                        $barcodeCreator = new BarcodeCreator($this->root);
                        $barcodeCreator->setFormat($parsedBarcode['BarCodeType']);
                        $barcodeCreator->setNumber(str_replace(['-'], '', $parsedBarcode['BarCodeData']));

                        try {
                            $barcodeCreator->validate();
                            $barcodeCreator->draw();
                            $barcodeVal = $barcodeCreator->getBinaryText();
                            $blockData[] = BlockFactory::createBlock(
                                BlockFactory::BLOCK_BARCODE,
                                null,
                                $barcodeVal
                            );
                            $entityFields['BarCode'] = $barcodeVal;
                        } catch (\Exception $e) {
                            $entityFields['BarCode'] = null;
                        }
                    }

                    break;

                case BlockFactory::BLOCK_LOCATIONS:
                    if ($locationBlock) {
                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LOCATIONS,
                            null,
                            $locationBlock,
                            '(BarCodeParsed && !BarCodeCustom.BarCodeData) || BarCodeCustom.BarCodeData'
                        );
                    }

                    break;

                case BlockFactory::BLOCK_NOTICE:
                    if (
                        isset($entityFields['ErrorCode'])
                        && (ACCOUNT_CHECKED == $entityFields['ErrorCode'])
                    ) {
                        break;
                    }

                    $errorMessage = $this->statusResolver->getStatus($user, $entityFields, $locale);

                    if (empty($errorMessage)) {
                        break;
                    }

                    if (
                        (PROVIDER_RETAIL == $entityFields['ProviderState'])
                        && (ProgramStatusResolver::TYPE_ONLY_AUTOLOGIN == $errorMessage['Type'])
                    ) {
                        break;
                    }

                    $noticeData = [
                        'Title' => null,
                        'Message' => null,
                        'DateInfo' => null,
                    ];

                    if (isset($errorMessage['Title'])) {
                        $noticeData['Title'] = $errorMessage['Title'];
                    }

                    if (isset($errorMessage['Error'])) {
                        if (isset($errorMessage['BeforeError'])) {
                            $noticeData['Message'] = $errorMessage['BeforeError'] . "\n";
                        }
                        $noticeData['Message'] .= trim($errorMessage['Error']) . "\n";
                    }

                    if (isset($errorMessage['Description'])) {
                        $noticeData['Message'] = trim($noticeData['Message'] . "\n" . $errorMessage['Description']);
                    }

                    if (isset($errorMessage['DateInfo'])) {
                        $noticeData['DateInfo'] = strip_tags($errorMessage['DateInfo']);
                    }

                    if (isset($noticeData['Message'])) {
                        $noticeData['Message'] = trim($noticeData['Message']);
                    }

                    $noticeData = array_filter($noticeData);

                    if (!$noticeData) {
                        break;
                    }

                    $entityFields['Notice'] = $noticeData;

                    $blockData = BlockFactory::createBlock(
                        (
                            (
                                empty($entityFields['ProviderID'])
                                || (PROVIDER_RETAIL == $entityFields['ProviderState'])
                                || (\ACCOUNT_WARNING == $entityFields['ErrorCode'])
                            )
                            && $this->apiVersioning->supports(MobileVersions::ACCOUNT_BLOCK_WARNING)
                        ) ?
                            BlockFactory::BLOCK_WARNING :
                            BlockFactory::BLOCK_NOTICE,
                        null,
                        $noticeData
                    );

                    break;

                case BlockFactory::BLOCK_DISABLED:
                    if (!$entityFields['Disabled']) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_DISABLED,
                        null,
                        $disabledData = (
                            $isRedesign2024Summer ?
                            [
                                'Title' => $this->translator->trans(/** @Desc("Disabled account.") */ 'account.disabled.title', [], 'mobile', $locale),
                                'Message' => $this->translator->trans(/** @Desc("This account is not being updated by AwardWallet.") */ 'account.disabled', [], 'mobile', $locale),
                                'Hint' => $this->translator->trans(/** @Desc("To update please uncheck the <a href=""%url%"">""Disabled""</a> checkbox") */ 'award.account.list.updating.disabled.2', [], 'mobile', $locale),
                            ] :
                            [
                                'Title' => $this->translator->trans('account.disabled', [], 'messages', $locale),
                                'Message' => $this->translator->trans('award.account.list.updating.disabled.2', [], 'messages', $locale),
                            ]
                        )
                    );

                    $entityFields['Disabled'] = $disabledData;

                    break;

                case BlockFactory::BLOCK_OWNER:
                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans('account.label.owner', [], 'messages', $locale),
                        $entityFields['UserName']
                    );

                    break;

                case BlockFactory::BLOCK_BALANCE:
                    if (
                        !isset($entityFields['Balance'])
                        || StringUtils::isEmpty($entityFields['Balance'])
                    ) {
                        break;
                    }

                    if (isset($entityFields['BalanceRaw'])) {
                        $entityFields['BalancePush'] = $this->desanitizer->tryFullDesanitize($entityFields['Balance']);
                    }

                    $balanceBlockData = self::array_merge_keys(['LastChange', 'LastChangeRaw'], $entityFields);
                    $balanceBlockData = array_merge(
                        $balanceBlockData,
                        self::array_merge_keys(['Balance', 'BalanceRaw'], $entityFields)
                    );

                    foreach (['Balance', 'LastChange'] as $sanitizedKey) {
                        if (isset($balanceBlockData[$sanitizedKey])) {
                            $balanceBlockData[$sanitizedKey] = $this->desanitizer->tryFullDesanitize($balanceBlockData[$sanitizedKey]);
                        }
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_BALANCE,
                        $this->translator->trans('award.account.balance', [], 'messages', $locale),
                        $balanceBlockData
                    );

                    break;

                case BlockFactory::BLOCK_PROPERTIES:
                    if (
                        !isset($entityFields['Properties'])
                        || (
                            isset($entityFields['AccessLevel'])
                            && in_array($entityFields['AccessLevel'], [ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_NUMBER])
                        )
                    ) {
                        break;
                    }

                    $blockData = [];

                    foreach ($entityFields['Properties'] as &$property) {
                        if (PROPERTY_VISIBLE != $property['Visible']) {
                            continue;
                        }

                        if (PROPERTY_KIND_STATUS == $property['Kind']) {
                            continue;
                        }

                        $needUpgrade = ACCOUNT_LEVEL_FREE == $user->getAccountlevel();
                        $propertyBlock = BlockFactory::createBlock(
                            $needUpgrade ? BlockFactory::BLOCK_TYPE_UPGRADE : BlockFactory::BLOCK_TYPE_STRING,
                            $property['Name'], // TODO: property name translations!
                            $needUpgrade ?
                                $this->translator->trans('please-upgrade', [], 'messages', $locale) :
                                (string) $this->propertyFormatter->format($property['Val'], $property['Type'] ?? null, $locale)
                        );

                        if (
                            $isRedesign2024Summer
                            && (\PROPERTY_KIND_NAME == $property['Kind'])
                        ) {
                            $propertyBlock['Icon'] = 'footer-picture';
                        }

                        $blockData[] = $propertyBlock;
                    } unset($property);

                    break;

                case BlockFactory::BLOCK_BLOG_POSTS:
                    if (empty($entityFields['Blogs']['BlogIds'])
                        || $this->apiVersioning->notSupports(MobileVersions::SUBACCOUNT_BLOG_POSTS)
                    ) {
                        break;
                    }

                    $blockLinks = [];

                    foreach ($entityFields['Blogs']['BlogIds'] as $blogPost) {
                        $blockLinks[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LINK,
                            BlockFactory::BLOCK_LINK,
                            [
                                'title' => $blogPost['title'],
                                'url' => StringUtils::replaceVarInLink($blogPost['postURL'], [
                                    'cid' => 'subacc-details-post',
                                    'mid' => 'mobile',
                                ], true),
                                'image' => $blogPost['imageURL'] ?? null,
                            ]
                        );
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_LINKS,
                        null,
                        $blockLinks,
                    );

                    break;

                case BlockFactory::BLOCK_BLOG_TOP_POSTS:
                    if ($this->apiVersioning->notSupports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)
                        || empty($entityFields['Blogs']['BlogPostID'])
                    ) {
                        break;
                    }

                    $blockLinks = [];

                    foreach ($entityFields['Blogs']['BlogPostID'] as $blogPost) {
                        $blockLinks[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LINK,
                            BlockFactory::BLOCK_LINK,
                            [
                                'title' => $blogPost['title'],
                                'url' => StringUtils::replaceVarInLink($blogPost['postURL'], [
                                    'cid' => 'acct-details-top-posts',
                                    'mid' => 'mobile',
                                ], true),
                                'class' => 'silver',
                                'image' => $blogPost['imageURL'] ?? null,
                            ]
                        );
                    }

                    if (!empty($entityFields['Blogs']['BlogIdsMilesPurchase'])) {
                        $blogPost = $entityFields['Blogs']['BlogIdsMilesPurchase'][0];
                        $blockLinks[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LINK,
                            BlockFactory::BLOCK_LINK,
                            [
                                'title' => $this->translator->trans(/** @Desc("Looking to buy %provider% %currency%") */ 'looking-to-buy',
                                    [
                                        '%provider%' => $entityFields['DisplayName'],
                                        '%currency%' => $entityFields['ProviderCurrencies'] ?? mb_strtolower($this->translator->trans('points', [], 'messages', $locale)),
                                    ], 'mobile', $locale),
                                'url' => StringUtils::replaceVarInLink($blogPost['postURL'], [
                                    'cid' => 'acct-details-buy',
                                    'mid' => 'mobile',
                                ], true),
                                'image' => $blogPost['imageURL'] ?? null,
                            ]
                        );
                    }

                    if (!empty($entityFields['Blogs']['BlogIdsMilesTransfers'])) {
                        $blogPost = $entityFields['Blogs']['BlogIdsMilesTransfers'][0];
                        $blockLinks[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LINK,
                            BlockFactory::BLOCK_LINK,
                            [
                                'title' => $this->translator->trans(/** @Desc("Looking to transfer %provider% %currency%") */ 'looking-to-transfer',
                                    [
                                        '%provider%' => $entityFields['DisplayName'],
                                        '%currency%' => $entityFields['ProviderCurrencies'] ?? mb_strtolower($this->translator->trans('points', [], 'messages', $locale)),
                                    ], 'mobile', $locale),
                                'url' => StringUtils::replaceVarInLink($blogPost['postURL'], [
                                    'cid' => 'acct-details-trans',
                                    'mid' => 'mobile',
                                ], true),
                                'image' => $blogPost['imageURL'] ?? null,
                            ]
                        );
                    }

                    if (!empty($entityFields['Blogs']['ids']['BlogTagsID'])) {
                        $blockLinks[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LINK,
                            BlockFactory::BLOCK_LINK,
                            [
                                'title' => $this->translator->trans('see-all-posts-on', [
                                    '%provider%' => $entityFields['DisplayName'],
                                ], 'messages', $locale),
                                'url' => $this->legacyUrlGenerator->generateAbsoluteUrl(
                                    '/blog/?rTagId=' . $entityFields['Blogs']['ids']['BlogTagsID'][0]
                                ),
                                'class' => 'blue',
                            ]
                        );
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_LINKS,
                        $this->translator->trans('top-blog-posts', [], 'messages', $locale),
                        $blockLinks,
                    );

                    break;

                case BlockFactory::BLOCK_BLOG_PROMOTIONS:
                    if ($this->apiVersioning->notSupports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)
                        || empty($entityFields['Blogs']['BlogIdsPromos'])
                    ) {
                        break;
                    }

                    $blockLinks = [];

                    foreach ($entityFields['Blogs']['BlogIdsPromos'] as $blogPost) {
                        $blockLinks[] = BlockFactory::createBlock(
                            BlockFactory::BLOCK_LINK,
                            BlockFactory::BLOCK_LINK,
                            [
                                'title' => $blogPost['title'],
                                'url' => StringUtils::replaceVarInLink($blogPost['postURL'], [
                                    'cid' => 'acct-details-promos',
                                    'mid' => 'mobile',
                                ], true),
                                'class' => 'silver',
                                'image' => $blogPost['imageURL'] ?? null,
                            ]
                        );
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_LINKS,
                        $this->translator->trans('title', [], 'promotions', $locale),
                        $blockLinks,
                    );

                    break;

                case BlockFactory::BLOCK_SPACER:
                    if ($this->apiVersioning->notSupports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)) {
                        break;
                    }
                    $blockData = BlockFactory::createBlock(BlockFactory::BLOCK_SPACER);

                    break;

                case BlockFactory::BLOCK_CASH_VALUE:
                    $mileValueData = $entityFields['MileValue'] ?? null;

                    if ($this->apiVersioning->notSupports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)
                        || $entityFields['isCustom']
                        || empty($mileValueData['approximate'])) {
                        break;
                    }

                    $valueBlockData = [
                        'formLink' => $this->legacyUrlGenerator->generateAbsoluteUrl($mileValueData['awEstimate']['link']),
                        'Balance' => $mileValueData['approximate']['value'],
                        'BalanceRaw' => $mileValueData['approximate']['raw'],
                    ];

                    if ($isRedesign2024Summer) {
                        $valueBlockData['Icon'] = 'cash-value';
                    }

                    if (!empty($mileValueData['balanceChange'])) {
                        $valueBlockData['LastChange'] = $mileValueData['balanceChange']['value'];
                        $valueBlockData['LastChangeRaw'] = $mileValueData['balanceChange']['raw'];
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_BALANCE,
                        $this->translator->trans('approximate-cash-value', [], 'messages', $locale),
                        $valueBlockData
                    );

                    break;

                case BlockFactory::BLOCK_MILE_VALUE:
                    $mileValueData = $entityFields['MileValue'] ?? null;

                    if (empty($mileValueData) || $entityFields['isCustom']) {
                        break;
                    }

                    $blockData = [];

                    if ($this->apiVersioning->notSupports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)) {
                        if ($this->apiVersioning->notSupports(MobileVersions::MILE_VALUE_ACCOUNT_INFO)) {
                            break;
                        }

                        if (isset($mileValueData['approximate'])) {
                            $blockData[] = BlockFactory::createBlock(
                                BlockFactory::BLOCK_TYPE_STRING,
                                $this->translator->trans('approximate-value', [], 'messages', $locale),
                                $mileValueData['approximate']['value']
                            );
                        }

                        if (isset($mileValueData['awEstimate'])) {
                            $blockData[] = BlockFactory::createBlock(
                                BlockFactory::BLOCK_TYPE_SUB_TITLE,
                                \mb_strtoupper($this->desanitizer->tryDesanitize($mileValueData['providerTitle'], Desanitizer::TAGS | Desanitizer::CHARS)),
                            );
                            $estimate = BlockFactory::createBlock(
                                $this->apiVersioning->supports(MobileVersions::ACCOUNT_DETAILS_MILE_VALUE_CUSTOM_BLOCK_TYPE) ?
                                    BlockFactory::BLOCK_TYPE_MILE_VALUE :
                                    BlockFactory::BLOCK_TYPE_TEXT_PROPERTY,
                                $this->translator->trans('awardWallet-estimate', [], 'messages', $locale),
                                $mileValueData['awEstimate']['value']
                            );
                            $estimate['formLink'] = $this->legacyUrlGenerator->generateAbsoluteUrl($mileValueData['awEstimate']['link']);
                            $blockData[] = $estimate;
                        }
                    } elseif (isset($mileValueData['awEstimate'])) {
                        $estimate = BlockFactory::createBlock(
                            $this->apiVersioning->supports(MobileVersions::ACCOUNT_DETAILS_MILE_VALUE_CUSTOM_BLOCK_TYPE) ?
                                BlockFactory::BLOCK_TYPE_MILE_VALUE :
                                BlockFactory::BLOCK_TYPE_TEXT_PROPERTY,
                            $this->translator->trans('estimated-point-value', [], 'messages', $locale),
                            $mileValueData['awEstimate']['value']
                        );

                        if (empty($mileValueData['isSimulated'])) {
                            $estimate['formLink'] = $this->legacyUrlGenerator->generateAbsoluteUrl($mileValueData['awEstimate']['link']);
                        }

                        if (isset($estimate) && $isRedesign2024Summer) {
                            $estimate['Icon'] = 'icon-coins-new';
                        }

                        $blockData[] = $estimate;
                    }

                    break;

                case BlockFactory::BLOCK_LOGIN:
                    if (
                        !isset($entityFields['MainProperties']['Login'])
                        || StringHandler::isEmpty($entityFields['MainProperties']['Login'])
                    ) {
                        break;
                    }
                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_LOGIN,
                        $this->translator->trans('account.label.login', [], 'messages', $locale),
                        $entityFields['Login']
                    );

                    if ($isRedesign2024Summer) {
                        $blockData['Icon'] = 'user';
                    }

                    break;

                case BlockFactory::BLOCK_EXPIRATION_DATE:
                    if (!isset($entityFields['ExpirationState'])) {
                        unset($entityFields['ExpirationState']);
                        unset($entityFields['ExpirationDate']);

                        break;
                    }

                    if (!in_array(
                        $entityFields['ExpirationDateTs'],
                        [
                            ExpirationDateResolver::EXPIRE_DONT_EXPIRE_TS,
                            ExpirationDateResolver::EXPIRE_EMPTY_TS,
                            ExpirationDateResolver::EXPIRE_UNKNOWN_TS,
                        ],
                        true
                    )) {
                        if ($isRegionalSettingsEnabled) {
                            $entityFields['ExpirationDateFormatted'] = new DateFormatted(
                                $entityFields['ExpirationDateTs'],
                                $this->localizer->formatDateTime($entityFields['ExpirationDateTime'], 'medium', null, $locale)
                            );
                        } else {
                            $entityFields['ExpirationDate'] = $entityFields['ExpirationDateTs'];
                        }
                    } elseif ($isRegionalSettingsEnabled) {
                        $entityFields['ExpirationDateFormatted'] = new DateFormatted(null, $entityFields['ExpirationDate']);
                    }

                    if (
                        (ACCOUNT_LEVEL_FREE == $user->getAccountlevel())
                        && true === $entityFields['ExpirationKnown']
                    ) {
                        if ($mapperContext->expirationDatesVisible > EXPIRATION_DATE_LIMIT) {
                            if ($isRegionalSettingsEnabled) {
                                $entityFields['ExpirationDateFormatted'] = new DateFormatted(
                                    null,
                                    $this->translator->trans('please-upgrade', [], 'messages', $locale)
                                );
                            } else {
                                $entityFields['ExpirationDate'] = $this->translator->trans('please-upgrade', [], 'messages', $locale);
                            }

                            $entityFields['ExpirationState'] = 'soon';
                            $entityFields['ExpirationUpgrade'] = true;
                        } else {
                            $mapperContext->expirationDatesVisible++;
                        }
                    }

                    if (!empty($entityFields['Blogs']['BlogIdsMileExpiration'])
                        && (
                            !$this->isPassport($entityFields)
                            || $this->isPassport($entityFields, Country::UNITED_STATES)
                        )) {
                        if ($this->apiVersioning->supports(MobileVersions::EXPIRATION_BLOG_FIELDS)) {
                            $isAppendExpirationPassportBlog = $this->isPassport($entityFields, Country::UNITED_STATES) && $this->apiVersioning->supports(MobileVersions::EXPIRATION_PASSPORT_BLOG_BLOCK);
                            $blogPost = array_values($entityFields['Blogs']['BlogIdsMileExpiration'])[0];
                            $expirationBlog = [
                                'blogLink' => StringUtils::replaceVarInLink(
                                    $blogPost['postURL'],
                                    ['cid' => 'acct-details-exp', 'mid' => 'mobile'],
                                    true
                                ),
                                'blogTitle' => $blogPost['title'],
                                'blogImage' => $blogPost['imageURL'] ?? $blogPost['thumbnail'],
                            ];
                        } else {
                            $expirationBlog = [
                                'formLink' => StringUtils::replaceVarInLink(
                                    array_values($entityFields['Blogs']['BlogIdsMileExpiration'])[0]['postURL'],
                                    ['cid' => 'acct-details-exp', 'mid' => 'mobile'],
                                    true
                                ),
                            ];
                        }
                    } elseif (
                        (Providercoupon::TYPE_TRUSTED_TRAVELER == ($entityFields['TypeID'] ?? 0))
                        && $this->apiVersioning->supports(MobileVersions::DOCUMENTS_IMPROVEMENTS_2025_APRIL)
                    ) {
                        $expirationBlog = [
                            'blogLink' => 'https://awardwallet.com/blog/tsa-precheck/',
                            'blogTitle' => 'How to Get TSA PreCheck for Nothing Out of Pocket',
                        ];
                    }

                    if (isset($entityFields['ExpirationUpgrade']) && $entityFields['ExpirationUpgrade']) {
                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_TYPE_UPGRADE,
                            $this->translator->trans('account.label.expiration', [], 'messages', $locale),
                            $isRegionalSettingsEnabled ?
                                $entityFields['ExpirationDateFormatted']->fmt :
                                $entityFields['ExpirationDate']
                        );
                    } else {
                        if (!StringUtils::isEmpty(
                            $isRegionalSettingsEnabled ?
                                $entityFields['ExpirationDateFormatted']->fmt :
                                $entityFields['ExpirationDate']
                        )) {
                            $blockData = BlockFactory::createBlock(
                                BlockFactory::BLOCK_EXPIRATION_DATE,
                                $this->translator->trans('account.label.expiration', [], 'messages', $locale),
                                array_merge([
                                    'ExpirationDate' => $isRegionalSettingsEnabled ?
                                        $entityFields['ExpirationDateFormatted'] :
                                        $entityFields['ExpirationDate'],
                                    'ExpirationState' => $entityFields['ExpirationState'],
                                    'ExpirationDetails' => isset($entityFields['ExpirationDetails']) && !StringUtils::isEmpty($entityFields['ExpirationDetails']) ?
                                        $entityFields['ExpirationDetails'] :
                                        null,
                                ], $expirationBlog ?? [])
                            );
                        } else {
                            unset(
                                $entityFields['ExpirationDate'],
                                $entityFields['ExpirationDateFormatted'],
                                $entityFields['ExpirationState']
                            );
                        }
                    }

                    if (isset($blockData) && $isRedesign2024Summer) {
                        $blockData['Icon'] = 'hours';
                    }

                    if (
                        $isRegionalSettingsEnabled
                        && isset($entityFields['ExpirationDateFormatted'])
                        && (ExpirationDateResolver::EXPIRE_UNKNOWN_TS != $entityFields['ExpirationDateTs'])
                    ) {
                        $entityFields['ExpirationDatePush'] = $entityFields['ExpirationDateFormatted']->fmt;
                    }

                    unset($entityFields['ExpirationDateTs']);

                    break;

                case BlockFactory::BLOCK_ACCOUNT_NUMBER:
                    if (!(isset($entityFields['MainProperties']['Number']['Number']) && isset($entityFields['Login']))) {
                        break;
                    }

                    if (
                        str_replace(' ', '', $entityFields['MainProperties']['Number']['Number']) ===
                        str_replace(' ', '', $entityFields['Login'])
                    ) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_ACCOUNT_NUMBER,
                        $this->translator->trans('award.account.number', [], 'messages', $locale),
                        $number = $entityFields['MainProperties']['Number']['Number']
                    );
                    $entityFields['Number'] = $number;

                    break;

                case BlockFactory::BLOCK_STATUS:
                    $eliteStatus = $entityFields['MainProperties']['Status']['Status'] ??
                        $entityFields['CustomEliteLevel'] ??
                        null;

                    if (!isset($eliteStatus)) {
                        break;
                    }

                    if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)) {
                        $valueBlockData = ['eliteStatusName' => $this->desanitizer->tryFullDesanitize($eliteStatus)];

                        if (isset($entityFields['Properties']['NextEliteLevel']['Val'])) {
                            $needUpgrade = ACCOUNT_LEVEL_FREE === $user->getAccountlevel();
                            $valueBlockData['nextEliteLevel'] = $needUpgrade
                                ? $this->translator->trans('please-upgrade', [], 'messages', $locale)
                                : $this->desanitizer->tryFullDesanitize($entityFields['Properties']['NextEliteLevel']['Val']);
                            unset($entityFields['Properties']['NextEliteLevel']);
                        }

                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_ELITE_STATUS,
                            $this->translator->trans('award.account.list.column.status', [], 'messages', $locale),
                            $valueBlockData
                        );
                    } else {
                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_TYPE_STRING,
                            $this->translator->trans('award.account.list.column.status', [], 'messages', $locale),
                            $eliteStatus
                        );
                    }

                    break;

                case BlockFactory::BLOCK_SUCCESS_CHECK_DATE:
                    if ($isRegionalSettingsEnabled) {
                        if (empty($entityFields['LastUpdatedDateTs'])) {
                            break;
                        }

                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_TYPE_DATE,
                            $this->translator->trans(/** @Desc("Last updated") */ 'award.account.last-updated', [], 'mobile', $locale),
                            $value = new DateFormatted(
                                $entityFields['LastUpdatedDateTs'],
                                $this->localizer->formatDateTime(
                                    $this->localizer->correctDateTime(new \DateTime('@' . $entityFields['LastUpdatedDateTs'])),
                                    'medium',
                                    null,
                                    $locale
                                )
                            )
                        );
                    } else {
                        if (empty($entityFields['SuccessCheckDateTs'])) {
                            break;
                        }

                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_TYPE_DATE,
                            $this->translator->trans(/** @Desc("Last updated") */ 'award.account.last-updated', [], 'mobile', $locale),
                            $value = $entityFields['SuccessCheckDateTs']
                        );
                    }

                    if ($isRedesign2024Summer) {
                        $blockData['Icon'] = 'update';
                    }

                    break;

                case BlockFactory::BLOCK_DESCRIPTION:
                    if (!(isset($entityFields['Description']) && '' !== $entityFields['Description'])) {
                        break;
                    }
                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans('coupon.note', [], 'messages', $locale),
                        $entityFields['Description']
                    );

                    if (!isset($entityFields['Login'])) {
                        $entityFields['Login'] = $entityFields['Description'];
                    }

                    break;

                case BlockFactory::BLOCK_COUPON_VALUE:
                    $entityFields['Balance'] = $entityFields['Value'];

                    if (empty($entityFields['Value'])) {
                        break;
                    }
                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_BALANCE,
                        $this->translator->trans('award.account.list.coupon.value', [], 'messages', $locale),
                        [
                            'Balance' => $entityFields['Value'],
                        ]
                    );

                    break;

                case BlockFactory::BLOCK_COMMENT:
                    if (!isset($entityFields['comment'])) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans('account.label.comment', [], 'messages', $locale),
                        $this->desanitizer->tryFullDesanitize($entityFields['comment'])
                    );

                    break;

                case BlockFactory::BLOCK_CARD_IMAGE:
                    if (
                        !$this->apiVersioning->supports(MobileVersions::CARD_IMAGES)
                        || (PROVIDER_KIND_CREDITCARD === (int) $entityFields['Kind'])
                    ) {
                        break;
                    }

                    $value = [];
                    $cardImages = $entityFields['CardImages'] ?? [];

                    if (
                        isset($entityFields['Access']['edit'])
                        && !$entityFields['Access']['edit']
                        && !$cardImages
                    ) {
                        break;
                    }

                    $isNative = $this->apiVersioning->supports(MobileVersions::NATIVE_APP);

                    foreach (
                        [
                            CardImage::KIND_FRONT => ['Front', $this->translator->trans(/** @Desc("Front") */ 'card-pictures.front.title', [], 'messages', $locale)],
                            CardImage::KIND_BACK => ['Back', $this->translator->trans(/** @Desc("Back") */ 'card-pictures.back.title', [], 'messages', $locale)],
                        ] as $kind => [$kindKey, $kindLabel]
                    ) {
                        if (!isset($cardImages[$kind])) {
                            if ($isNative) {
                                $value[$kindKey] = [
                                    'Label' => $kindLabel,
                                ];
                            }

                            continue;
                        }

                        $cardImage = $cardImages[$kind];
                        $value[$kindKey] = [
                            'CardImageId' => $cardImage['CardImageID'],
                            'Url' => $this->router->generate('awm_card_image_download', ['cardImageId' => $cardImage['CardImageID']], UrlGenerator::ABSOLUTE_URL),
                            'Label' => $kindLabel,
                            'FileName' => $cardImage['FileName'],
                        ];
                    }

                    if ($value) {
                        $blockData = BlockFactory::createBlock(
                            BlockFactory::BLOCK_CARD_IMAGE,
                            $this->translator->trans(/** @Desc("Pictures of the card") */ 'card-pictures.title', [], 'messages', $locale),
                            $value
                        );
                    }

                    break;

                case BlockFactory::BLOCK_SET_LOCATION_LINK:
                    if (!$this->apiVersioning->supports(MobileVersions::SET_LOCATION_LINK)) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(BlockFactory::BLOCK_SET_LOCATION_LINK);

                    break;

                case BlockFactory::BLOCK_IATA:
                    if (StringUtils::isEmpty($entityFields['IATACode'])) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans(/** @Desc("IATA Code") */ 'iata.code.short', [], 'mobile', $locale),
                        $entityFields['IATACode']
                    );

                    break;

                case BlockFactory::BLOCK_TYPE:
                    if (empty($entityFields['AccountStatus'])) {
                        break;
                    }

                    $entityFields['CouponType'] = $entityFields['AccountStatus'];

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans('coupon.type', [], 'messages', $locale),
                        $entityFields['AccountStatus']
                    );

                    break;

                case BlockFactory::BLOCK_CARD_NUMBER:
                    if (empty($entityFields['LoginFieldFirst'])) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans('coupon.cardnumber', [], 'messages', $locale),
                        $entityFields['LoginFieldFirst']
                    );
                    $entityFields['Login'] = $entityFields['LoginFieldFirst'];

                    break;

                case BlockFactory::BLOCK_PIN:
                    if (empty($entityFields['PIN'])) {
                        break;
                    }

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_TYPE_STRING,
                        $this->translator->trans('coupon.pin', [], 'messages', $locale),
                        (string) $entityFields['PIN']
                    );

                    break;

                case BlockFactory::BLOCK_BALANCE_WATCH:
                    if (!$this->apiVersioning->supports(MobileVersions::ACCOUNT_BALANCE_WATCH)) {
                        break;
                    }

                    $startDate = $entityFields['BalanceWatchStartDate'];

                    if (!isset($startDate)) {
                        break;
                    }

                    $balanceWatchEndUnixTime = $entityFields['BalanceWatchExpirationDate'];

                    if (\time() > $balanceWatchEndUnixTime) {
                        break;
                    }

                    $entityFields['BalanceWatchEndDate'] = $balanceWatchEndUnixTime;

                    $blockData = BlockFactory::createBlock(
                        BlockFactory::BLOCK_BALANCE_WATCH,
                        null,
                        ['EndDate' => $balanceWatchEndUnixTime]
                    );

                    break;

                case BlockFactory::BLOCK_PASSPORT_NAME:
                case BlockFactory::BLOCK_PASSPORT_NUMBER:
                case BlockFactory::BLOCK_PASSPORT_ISSUE_DATE:
                case BlockFactory::BLOCK_PASSPORT_ISSUE_COUNTRY:
                case BlockFactory::BLOCK_TRUSTED_TRAVELER_NUMBER:
                case BlockFactory::BLOCK_VACCINE_PASSPORT_NAME:
                case BlockFactory::BLOCK_VACCINE_DATE_OF_BIRTH:
                case BlockFactory::BLOCK_VACCINE_PASSPORT_NUMBER:
                case BlockFactory::BLOCK_VACCINE_CERTIFICATE_ISSUED:
                case BlockFactory::BLOCK_VACCINE_COUNTRY_OF_ISSUE:
                case BlockFactory::BLOCK_VACCINE_1ST_DOSE_DATE:
                case BlockFactory::BLOCK_VACCINE_1ST_DOSE_VACCINE:
                case BlockFactory::BLOCK_VACCINE_2ND_DOSE_DATE:
                case BlockFactory::BLOCK_VACCINE_2ND_DOSE_VACCINE:
                case BlockFactory::BLOCK_VACCINE_BOOSTER_DATE:
                case BlockFactory::BLOCK_VACCINE_BOOSTER_VACCINE:
                case BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_DATE:
                case BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_VACCINE:
                case BlockFactory::BLOCK_INSURANCE_TYPE:
                case BlockFactory::BLOCK_INSURANCE_COMPANY:
                case BlockFactory::BLOCK_INSURANCE_NAME_ON_CARD:
                case BlockFactory::BLOCK_INSURANCE_MEMBER_NUMBER:
                case BlockFactory::BLOCK_INSURANCE_GROUP_NUMBER:
                case BlockFactory::BLOCK_INSURANCE_POLICY_HOLDER:
                case BlockFactory::BLOCK_INSURANCE_TYPE_2:
                case BlockFactory::BLOCK_INSURANCE_EFFECTIVE_DATE:
                case BlockFactory::BLOCK_INSURANCE_MEMBER_SERVICE_PHONE:
                case BlockFactory::BLOCK_INSURANCE_PREAUTH_PHONE:
                case BlockFactory::BLOCK_INSURANCE_OTHER_PHONE:
                case BlockFactory::BLOCK_VISA_COUNTRY:
                case BlockFactory::BLOCK_VISA_NUMBER_ENTRIES:
                case BlockFactory::BLOCK_VISA_FULL_NAME:
                case BlockFactory::BLOCK_VISA_ISSUE_DATE:
                case BlockFactory::BLOCK_VISA_VALID_FROM:
                case BlockFactory::BLOCK_VISA_NUMBER:
                case BlockFactory::BLOCK_VISA_CATEGORY:
                case BlockFactory::BLOCK_VISA_DURATION_IN_DAYS:
                case BlockFactory::BLOCK_VISA_ISSUED_IN:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_COUNTRY:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_STATE:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_INTERNATIONAL_LICENSE:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_LICENSE_NUMBER:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_DATE_OF_BIRTH:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_ISSUE_DATE:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_FULL_NAME:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_SEX:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_EYES:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_HEIGHT:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_CLASS:
                case BlockFactory::BLOCK_DRIVERS_LICENSE_ORGAN_DONOR:
                case BlockFactory::BLOCK_PRIORITY_PASS_ACCOUNT_NUMBER:
                case BlockFactory::BLOCK_PRIORITY_PASS_IS_SELECT:
                case BlockFactory::BLOCK_PRIORITY_PASS_CREDIT_CARD:
                    if (!\array_key_exists($blockKind, $blocksProviderMap)) {
                        break;
                    }

                    $blockData = $blocksProviderMap[$blockKind]->encode($entityFields, $__encoderContext);

                    break;

                default:
                    throw new \RuntimeException("Invalid block kind '{$blockKind}'");
            }

            if (!isset($blockData)) {
                continue;
            }

            $needAddToMainBlocks = \array_key_exists($blockKind, $blocksTemplate);
            $blocksMap[$blockKind] = &$blockData;
            $needAddToPreview = \array_key_exists($blockKind, $previewBlocksTemplate);

            if ($needAddToPreview) {
                $previewBlocksMap[$blockKind] = &$blockData;
                $previewBlocksIndexesMap[$blockKind] = \count($previewBlocks);
            }

            if (is_array($blockTemplate)) {
                foreach ($blockData as &$blockDataItem) {
                    if ($needAddToMainBlocks) {
                        $blocks[] = &$blockDataItem;
                    }

                    if ($needAddToPreview) {
                        $previewBlocks[] = &$blockDataItem;
                    }
                }

                unset($blockDataItem);
            } else {
                if ($needAddToMainBlocks) {
                    $blocks[] = &$blockData;
                }

                if ($needAddToPreview) {
                    $previewBlocks[] = &$blockData;
                }
            }

            unset($blockData);
        }

        if ((isset($isAppendExpirationPassportBlog) && true === $isAppendExpirationPassportBlog) && !empty($expirationBlog)) {
            $blocks[] = BlockFactory::createBlock(
                BlockFactory::BLOCK_LINK,
                BlockFactory::BLOCK_LINK,
                [
                    'title' => $expirationBlog['blogTitle'],
                    'url' => $expirationBlog['blogLink'],
                    'image' => $expirationBlog['blogImage'],
                ]
            );
        }

        if ($this->apiVersioning->supports(MobileVersions::SET_LOCATION_LINK)) {
            $index = null;

            foreach ($blocks as $key => $block) {
                if ($block['Kind'] === BlockFactory::BLOCK_SET_LOCATION_LINK) {
                    $index = $key;

                    break;
                }
            }

            // insert at the end if both blocks are missing
            if (isset($index)) {
                $existsBeforeCardImage = ($blocks[$index - 1]['Kind'] ?? null) === BlockFactory::BLOCK_CARD_IMAGE;
                $existsAfterLinks = ($blocks[$index + 1]['Kind'] ?? null) === BlockFactory::BLOCK_LINKS;

                if (!$existsBeforeCardImage && !$existsAfterLinks) {
                    $blocks[] = $blocks[$index];
                    unset($blocks[$index]);
                    $blocks = \array_values($blocks);
                }
            }
        }

        if (isset($previewBlocksIndexesMap[BlockFactory::BLOCK_ACCOUNT_NUMBER], $previewBlocksIndexesMap[BlockFactory::BLOCK_LOGIN])) {
            unset($previewBlocks[$previewBlocksIndexesMap[BlockFactory::BLOCK_LOGIN]]);
            $previewBlocks = \array_values($previewBlocks);
        }
    }

    private function getCouponBlocksTemplate(array $accountFields): array
    {
        switch ($accountFields['TypeID'] ?? null) {
            case Providercoupon::TYPE_TRUSTED_TRAVELER:
                return [
                    BlockFactory::BLOCK_OWNER => null,
                    BlockFactory::BLOCK_TYPE => null,
                    BlockFactory::BLOCK_TRUSTED_TRAVELER_NUMBER => null,
                    BlockFactory::BLOCK_CARD_NUMBER => null,
                    BlockFactory::BLOCK_PIN => null,
                    BlockFactory::BLOCK_COUPON_VALUE => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                    BlockFactory::BLOCK_BARCODE => [],
                    BlockFactory::BLOCK_CARD_IMAGE => null,
                    BlockFactory::BLOCK_SET_LOCATION_LINK => null,
                    BlockFactory::BLOCK_LOCATIONS => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];

            case Providercoupon::TYPE_PASSPORT:
                return [
                    BlockFactory::BLOCK_OWNER => null,
                    BlockFactory::BLOCK_TYPE => null,
                    BlockFactory::BLOCK_PASSPORT_NAME => null,
                    BlockFactory::BLOCK_PASSPORT_NUMBER => null,
                    BlockFactory::BLOCK_PASSPORT_ISSUE_DATE => null,
                    BlockFactory::BLOCK_PASSPORT_ISSUE_COUNTRY => null,
                    BlockFactory::BLOCK_CARD_NUMBER => null,
                    BlockFactory::BLOCK_PIN => null,
                    BlockFactory::BLOCK_COUPON_VALUE => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                    BlockFactory::BLOCK_BARCODE => [],
                    BlockFactory::BLOCK_LOCATIONS => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];

            case Providercoupon::TYPE_VACCINE_CARD:
                return [
                    BlockFactory::BLOCK_BARCODE => [],

                    BlockFactory::BLOCK_VACCINE_PASSPORT_NAME => null,
                    BlockFactory::BLOCK_VACCINE_DATE_OF_BIRTH => null,
                    BlockFactory::BLOCK_VACCINE_PASSPORT_NUMBER => null,
                    BlockFactory::BLOCK_VACCINE_CERTIFICATE_ISSUED => null,
                    BlockFactory::BLOCK_VACCINE_COUNTRY_OF_ISSUE => null,
                    BlockFactory::BLOCK_VACCINE_1ST_DOSE_DATE => null,
                    BlockFactory::BLOCK_VACCINE_1ST_DOSE_VACCINE => null,
                    BlockFactory::BLOCK_VACCINE_2ND_DOSE_DATE => null,
                    BlockFactory::BLOCK_VACCINE_2ND_DOSE_VACCINE => null,
                    BlockFactory::BLOCK_VACCINE_BOOSTER_DATE => null,
                    BlockFactory::BLOCK_VACCINE_BOOSTER_VACCINE => null,
                    BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_DATE => null,
                    BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_VACCINE => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];

            case Providercoupon::TYPE_VISA:
                return [
                    BlockFactory::BLOCK_TYPE => null,
                    BlockFactory::BLOCK_BARCODE => [],
                    BlockFactory::BLOCK_VISA_COUNTRY => null,
                    BlockFactory::BLOCK_VISA_NUMBER_ENTRIES => null,
                    BlockFactory::BLOCK_VISA_FULL_NAME => null,
                    BlockFactory::BLOCK_VISA_ISSUE_DATE => null,
                    BlockFactory::BLOCK_VISA_VALID_FROM => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                    BlockFactory::BLOCK_VISA_NUMBER => null,
                    BlockFactory::BLOCK_VISA_CATEGORY => null,
                    BlockFactory::BLOCK_VISA_DURATION_IN_DAYS => null,
                    BlockFactory::BLOCK_VISA_ISSUED_IN => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];

            case Providercoupon::TYPE_INSURANCE_CARD:
                return [
                    BlockFactory::BLOCK_TYPE => null,
                    BlockFactory::BLOCK_BARCODE => [],
                    BlockFactory::BLOCK_INSURANCE_TYPE => null,
                    BlockFactory::BLOCK_INSURANCE_COMPANY => null,
                    BlockFactory::BLOCK_INSURANCE_NAME_ON_CARD => null,
                    BlockFactory::BLOCK_INSURANCE_MEMBER_NUMBER => null,
                    BlockFactory::BLOCK_INSURANCE_GROUP_NUMBER => null,
                    BlockFactory::BLOCK_INSURANCE_POLICY_HOLDER => null,
                    BlockFactory::BLOCK_INSURANCE_TYPE_2 => null,
                    BlockFactory::BLOCK_INSURANCE_EFFECTIVE_DATE => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                    BlockFactory::BLOCK_INSURANCE_MEMBER_SERVICE_PHONE => null,
                    BlockFactory::BLOCK_INSURANCE_PREAUTH_PHONE => null,
                    BlockFactory::BLOCK_INSURANCE_OTHER_PHONE => null,
                    BlockFactory::BLOCK_CARD_IMAGE => null,
                    BlockFactory::BLOCK_SET_LOCATION_LINK => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];

            case Providercoupon::TYPE_DRIVERS_LICENSE:
                return [
                    BlockFactory::BLOCK_BARCODE => [],
                    BlockFactory::BLOCK_TYPE => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_COUNTRY => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_STATE => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_INTERNATIONAL_LICENSE => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_LICENSE_NUMBER => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_DATE_OF_BIRTH => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_ISSUE_DATE => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_FULL_NAME => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_SEX => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_EYES => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_HEIGHT => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_CLASS => null,
                    BlockFactory::BLOCK_DRIVERS_LICENSE_ORGAN_DONOR => null,
                    BlockFactory::BLOCK_CARD_IMAGE => null,
                    BlockFactory::BLOCK_SET_LOCATION_LINK => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];

            case Providercoupon::TYPE_PRIORITY_PASS:
                return [
                    BlockFactory::BLOCK_BARCODE => [],
                    BlockFactory::BLOCK_PRIORITY_PASS_ACCOUNT_NUMBER => null,
                    BlockFactory::BLOCK_PRIORITY_PASS_IS_SELECT => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                    BlockFactory::BLOCK_PRIORITY_PASS_CREDIT_CARD => null,
                    BlockFactory::BLOCK_CARD_IMAGE => null,
                    BlockFactory::BLOCK_SET_LOCATION_LINK => null,
                    BlockFactory::BLOCK_DESCRIPTION => null,
                ];
        }

        return self::COUPON_BLOCKS_TEMPLATE;
    }

    /**
     * @return array<BlockFactory::BLOCK_*, EncoderInterface>
     */
    private function getCouponCustomPropertiesProviderMap(array &$entityFields, ?string $locale): array
    {
        if (!isset($entityFields['TypeID'])) {
            return [];
        }

        $documentType = $entityFields['TypeID'];
        $documentFieldKey = Providercoupon::DOCUMENT_TYPE_TO_FIELD_MAP[$documentType] ?? null;

        if (null === $documentFieldKey) {
            return [];
        }

        $tr = $this->translator;
        $f = new DocumentBlockEncoderFactory($this->localizer, $documentFieldKey, $locale);
        $countryLocalizer = fn ($countryId) => $this->getLocalizedCountry($countryId);

        switch ($documentType) {
            case Providercoupon::TYPE_TRUSTED_TRAVELER:
                $blocksMap = [
                    BlockFactory::BLOCK_TRUSTED_TRAVELER_NUMBER => $f->createStringEncoder('travelerNumber', $tr->trans('traveler_profile.number', [], 'messages', $locale)),
                ];

                break;

            case Providercoupon::TYPE_PASSPORT:
                $blocksMap = [
                    BlockFactory::BLOCK_PASSPORT_NAME => $f->createStringEncoder('name', $tr->trans('traveler_profile.passport-name', [], 'messages', $locale)),
                    BlockFactory::BLOCK_PASSPORT_NUMBER => $f->createStringEncoder('number', $tr->trans('traveler_profile.passport-number', [], 'messages', $locale)),
                    BlockFactory::BLOCK_PASSPORT_ISSUE_DATE => $f->createDateEncoder('issueDate', $tr->trans('traveler_profile.passport-issueDate', [], 'messages', $locale)),
                    BlockFactory::BLOCK_PASSPORT_ISSUE_COUNTRY =>
                        $f->createStringEncoder('country', $tr->trans('traveler_profile.passport-country', [], 'messages', $locale))
                        ->andThenIfExists(new CallableEncoder(function (array $countryBlock) use (&$entityFields) {
                            $entityFields['CountryOfIssue'] = $countryBlock['Val'];

                            return $countryBlock;
                        })),
                ];

                break;

            case Providercoupon::TYPE_VACCINE_CARD:
                $blocksMap = [
                    BlockFactory::BLOCK_VACCINE_PASSPORT_NAME => $f->createStringEncoder('passportName', $tr->trans('traveler_profile.passport-name', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_DATE_OF_BIRTH => $f->createDateEncoder('dateOfBirth', $tr->trans('traveler_profile.date-of-birth', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_PASSPORT_NUMBER => $f->createStringEncoder('passportNumber', $tr->trans('traveler_profile.passport-number', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_CERTIFICATE_ISSUED => $f->createDateEncoder('certificateIssued', $tr->trans('certificate-issued', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_COUNTRY_OF_ISSUE => $f->createCountryEncoder('countryIssue', $tr->trans('country-issue', [], 'messages', $locale), $countryLocalizer),
                    BlockFactory::BLOCK_VACCINE_1ST_DOSE_DATE => $f->createDateEncoder('firstDoseDate', $tr->trans('first-dose-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_1ST_DOSE_VACCINE => $f->createStringEncoder('firstDoseVaccine', $tr->trans('first-dose-vaccine', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_2ND_DOSE_DATE => $f->createDateEncoder('secondDoseDate', $tr->trans('second-dose-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_2ND_DOSE_VACCINE => $f->createStringEncoder('secondDoseVaccine', $tr->trans('second-dose-vaccine', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_BOOSTER_DATE => $f->createDateEncoder('boosterDate', $tr->trans('booster-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_BOOSTER_VACCINE => $f->createStringEncoder('boosterVaccine', $tr->trans('booster-vaccine', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_DATE => $f->createDateEncoder('secondBoosterDate', $tr->trans('second-booster-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_VACCINE => $f->createStringEncoder('secondBoosterVaccine', $tr->trans('second-booster-vaccine', [], 'messages', $locale)),
                ];

                break;

            case Providercoupon::TYPE_INSURANCE_CARD:
                $blocksMap = [
                    BlockFactory::BLOCK_INSURANCE_TYPE => $f->createOptionsEncoder('insuranceType', $tr->trans('insurance-type', [], 'messages', $locale), Providercoupon::INSURANCE_TYPE_LIST),
                    BlockFactory::BLOCK_INSURANCE_COMPANY => $f->createStringEncoder('insuranceCompany', $tr->trans('insurance-company', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_NAME_ON_CARD => $f->createStringEncoder('nameOnCard', $tr->trans('name-on-card', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_MEMBER_NUMBER => $f->createStringEncoder('memberNumber', $tr->trans('member-number-id', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_GROUP_NUMBER => $f->createStringEncoder('groupNumber', $tr->trans('group-number', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_POLICY_HOLDER => $f->createOptionsEncoder('policyHolder', $tr->trans('policy-holder', [], 'messages', $locale), Providercoupon::INSURANCE_POLICY_HOLDER_LIST),
                    BlockFactory::BLOCK_INSURANCE_TYPE_2 => $f->createOptionsEncoder('insuranceType2', $tr->trans('insurance-type', [], 'messages', $locale), Providercoupon::INSURANCE_TYPE2_LIST),
                    BlockFactory::BLOCK_INSURANCE_EFFECTIVE_DATE => $f->createDateEncoder('effectiveDate', $tr->trans('effective-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_MEMBER_SERVICE_PHONE => $f->createStringEncoder('memberServicePhone', $tr->trans('member-service-phone', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_PREAUTH_PHONE => $f->createStringEncoder('preauthPhone', $tr->trans('preauthorization-phone', [], 'messages', $locale)),
                    BlockFactory::BLOCK_INSURANCE_OTHER_PHONE => $f->createStringEncoder('otherPhone', $tr->trans('other-phone', [], 'messages', $locale)),
                ];

                break;

            case Providercoupon::TYPE_VISA:
                $blocksMap = [
                    BlockFactory::BLOCK_VISA_COUNTRY => $f->createCountryEncoder('countryVisa', $tr->trans('label.country', [], 'messages', $locale), $countryLocalizer),
                    BlockFactory::BLOCK_VISA_NUMBER_ENTRIES => $f->createStringEncoder('numberEntries', $tr->trans('number-of-entries', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_FULL_NAME => $f->createStringEncoder('fullName', $tr->trans('cart.full-name', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_ISSUE_DATE => $f->createDateEncoder('issueDate', $tr->trans('issue-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_VALID_FROM => $f->createDateEncoder('validFrom', $tr->trans('valid-from', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_NUMBER => $f->createStringEncoder('visaNumber', $tr->trans('visa-number', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_CATEGORY => $f->createStringEncoder('category', $tr->trans('coupon.category', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_DURATION_IN_DAYS => $f->createStringEncoder('durationInDays', $tr->trans('duration-in-days', [], 'messages', $locale)),
                    BlockFactory::BLOCK_VISA_ISSUED_IN => $f->createStringEncoder('issuedIn', $tr->trans('issued-in', [], 'messages', $locale)),
                ];

                break;

            case Providercoupon::TYPE_DRIVERS_LICENSE:
                $blocksMap = [
                    BlockFactory::BLOCK_DRIVERS_LICENSE_COUNTRY => $f->createCountryEncoder('country', $tr->trans('label.country', [], 'messages', $locale), $countryLocalizer),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_STATE => $f->createTransformerStringEncoder(
                        'state',
                        $tr->trans('cart.state', [], 'messages', $locale),
                        fn ($stateId) => $this->getState($stateId)
                    ),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_INTERNATIONAL_LICENSE => $f->createCheckboxEncoder('internationalLicense', $tr->trans('international-drivers-license', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_LICENSE_NUMBER => $f->createStringEncoder('licenseNumber', $tr->trans('driver-license-number', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_DATE_OF_BIRTH => $f->createDateEncoder('dateOfBirth', $tr->trans('traveler_profile.date-of-birth', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_ISSUE_DATE => $f->createDateEncoder('issueDate', $tr->trans('issue-date', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_FULL_NAME => $f->createStringEncoder('fullName', $tr->trans('cart.full-name', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_SEX => $f->createStringEncoder('sex', $tr->trans('sex', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_EYES => $f->createStringEncoder('eyes', $tr->trans('eyes', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_HEIGHT => $f->createStringEncoder('height', $tr->trans('height', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_CLASS => $f->createStringEncoder('class', $tr->trans('class', [], 'messages', $locale)),
                    BlockFactory::BLOCK_DRIVERS_LICENSE_ORGAN_DONOR => $f->createCheckboxEncoder('organDonor', $tr->trans('organ-donor', [], 'messages', $locale)),
                ];

                break;

            case Providercoupon::TYPE_PRIORITY_PASS:
                $blocksMap = [
                    BlockFactory::BLOCK_PRIORITY_PASS_ACCOUNT_NUMBER => $f->createStringEncoder('accountNumber', $tr->trans('priority-pass-number', [], 'messages', $locale)),
                    BlockFactory::BLOCK_PRIORITY_PASS_IS_SELECT => $f->createTransformerStringEncoder(
                        'isSelect',
                        $tr->trans('pass-type', [], 'messages', $locale),
                        fn ($input) => $input ? $tr->trans('aw.onecard.th.select', [], 'messages', $locale) : null
                    ),
                    BlockFactory::BLOCK_EXPIRATION_DATE => $f->createDateEncoder('expirationDate', $tr->trans('traveler_profile.passport-expiration', [], 'messages', $locale)),
                ];

                if ($this->apiVersioning->supports(MobileVersions::DOCUMENTS_IMPROVEMENTS_2025_APRIL)) {
                    $blocksMap[BlockFactory::BLOCK_PRIORITY_PASS_CREDIT_CARD] = $f->createTransformerTypeValueEncoder(
                        'creditCardId',
                        BlockFactory::BLOCK_PRIORITY_PASS_CREDIT_CARD,
                        function ($creditCardId) {
                            $creditCard = $this->creditCardRepository->find($creditCardId);

                            if (!$creditCard) {
                                return null;
                            }

                            $picturePath = $creditCard->getPicturePath('medium');

                            return [
                                'image' => $picturePath !== null ? $this->legacyUrlGenerator->generateAbsoluteUrl($picturePath) : null,
                                'name' => $creditCard->getCardFullName() ?? $creditCard->getName(),
                            ];
                        }
                    );
                }

                break;

            default:
                $blocksMap = [];

                break;
        }

        return $blocksMap;
    }

    private function getCouponPreviewBlocksTemplate(array $accountFields): array
    {
        switch ($accountFields['TypeID'] ?? null) {
            case Providercoupon::TYPE_VACCINE_CARD: return [
                BlockFactory::BLOCK_VACCINE_1ST_DOSE_DATE => null,
                BlockFactory::BLOCK_VACCINE_1ST_DOSE_VACCINE => null,
                BlockFactory::BLOCK_VACCINE_2ND_DOSE_DATE => null,
                BlockFactory::BLOCK_VACCINE_2ND_DOSE_VACCINE => null,
                BlockFactory::BLOCK_VACCINE_BOOSTER_DATE => null,
                BlockFactory::BLOCK_VACCINE_BOOSTER_VACCINE => null,
                BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_DATE => null,
                BlockFactory::BLOCK_VACCINE_2ND_BOOSTER_VACCINE => null,
            ];

            case Providercoupon::TYPE_TRUSTED_TRAVELER: return [
                BlockFactory::BLOCK_TRUSTED_TRAVELER_NUMBER => null,
                BlockFactory::BLOCK_EXPIRATION_DATE => null,
            ];

            case Providercoupon::TYPE_PASSPORT: return [
                BlockFactory::BLOCK_PASSPORT_NAME => null,
                BlockFactory::BLOCK_PASSPORT_NUMBER => null,
                BlockFactory::BLOCK_PASSPORT_ISSUE_DATE => null,
                BlockFactory::BLOCK_PASSPORT_ISSUE_COUNTRY => null,
                BlockFactory::BLOCK_EXPIRATION_DATE => null,
            ];

            case Providercoupon::TYPE_VISA: return [
                BlockFactory::BLOCK_VISA_COUNTRY => null,
                BlockFactory::BLOCK_VISA_ISSUE_DATE => null,
                BlockFactory::BLOCK_EXPIRATION_DATE => null,
                BlockFactory::BLOCK_VISA_NUMBER => null,
                BlockFactory::BLOCK_VISA_DURATION_IN_DAYS => null,
                BlockFactory::BLOCK_VISA_ISSUED_IN => null,
            ];

            case Providercoupon::TYPE_INSURANCE_CARD: return [
                BlockFactory::BLOCK_INSURANCE_TYPE => null,
                BlockFactory::BLOCK_INSURANCE_COMPANY => null,
                BlockFactory::BLOCK_INSURANCE_NAME_ON_CARD => null,
                BlockFactory::BLOCK_INSURANCE_MEMBER_NUMBER => null,
                BlockFactory::BLOCK_INSURANCE_GROUP_NUMBER => null,
                BlockFactory::BLOCK_INSURANCE_POLICY_HOLDER => null,
                BlockFactory::BLOCK_INSURANCE_TYPE_2 => null,
            ];

            case Providercoupon::TYPE_DRIVERS_LICENSE: return [
                BlockFactory::BLOCK_DRIVERS_LICENSE_COUNTRY => null,
                BlockFactory::BLOCK_DRIVERS_LICENSE_STATE => null,
                BlockFactory::BLOCK_DRIVERS_LICENSE_INTERNATIONAL_LICENSE => null,
                BlockFactory::BLOCK_DRIVERS_LICENSE_LICENSE_NUMBER => null,
                BlockFactory::BLOCK_DRIVERS_LICENSE_DATE_OF_BIRTH => null,
                BlockFactory::BLOCK_DRIVERS_LICENSE_ISSUE_DATE => null,
                BlockFactory::BLOCK_EXPIRATION_DATE => null,
            ];

            case Providercoupon::TYPE_PRIORITY_PASS:
                return [
                    BlockFactory::BLOCK_PRIORITY_PASS_ACCOUNT_NUMBER => null,
                    BlockFactory::BLOCK_PRIORITY_PASS_IS_SELECT => null,
                    BlockFactory::BLOCK_EXPIRATION_DATE => null,
                ];
        }

        return self::COUPON_PREVIEW_BLOCKS_TEMPLATE;
    }

    private function getSortedBlocks(): array
    {
        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_DETAILS_STATUS_LINKS)) {
            return self::ACCOUNT_BLOCKS_TEMPLATE;
        }

        // for version below 4.32 old block order
        return [
            BlockFactory::BLOCK_DISABLED => null,
            BlockFactory::BLOCK_NOTICE => null,
            BlockFactory::BLOCK_BALANCE_WATCH => null,
            BlockFactory::BLOCK_OWNER => null,
            BlockFactory::BLOCK_BALANCE => null,
            BlockFactory::BLOCK_MILE_VALUE => [],
            BlockFactory::BLOCK_LOGIN => null,
            BlockFactory::BLOCK_ACCOUNT_NUMBER => null,
            BlockFactory::BLOCK_BARCODE => [],
            BlockFactory::BLOCK_STATUS => null,
            BlockFactory::BLOCK_SUCCESS_CHECK_DATE => null,
            BlockFactory::BLOCK_EXPIRATION_DATE => null,
            BlockFactory::BLOCK_IATA => null,
            BlockFactory::BLOCK_PROPERTIES => [], // in-place
            BlockFactory::BLOCK_COMMENT => null,
            BlockFactory::BLOCK_CARD_IMAGE => null,
            BlockFactory::BLOCK_LOCATIONS => null,
        ];
    }

    private function mapCashTotals(&$fields)
    {
        if (!$this->apiVersioning->supports(MobileVersions::ACCOUNT_TOTALS_IMPROVEMENTS)) {
            return;
        }

        if (isset($fields['TotalUSDCashRaw'])) {
            $fields['TotalUSDCash'] = $fields['TotalUSDCashRaw'];
            unset($fields['TotalUSDCashRaw']);
        }
    }
}
