<?php

namespace AwardWallet\MainBundle\Service\ExpirationDate;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\CurrencyRepository;
use AwardWallet\MainBundle\Entity\Repositories\SubaccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\FrameworkExtension\Translator\EntityTranslator;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\Mapper;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\BlogPost;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\Currency;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\DateTimeAgo;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\Group;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\Property;
use AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount\Translatable;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpirationDate implements TranslationContainerInterface
{
    public const MODE_EMAIL = 1;
    public const MODE_CALENDAR = 2;

    public const TARGET_USER = 'U';
    public const TARGET_FM = 'UA';
    public const TARGET_BUSINESS = 'B';
    public const KIND_ACCOUNT = 'A';
    public const KIND_SUBACCOUNT = 'S';
    public const KIND_COUPON = 'C';
    public const KIND_DOCUMENT_INSURANCE_CARD = 'IC';
    public const KIND_DOCUMENT_VISA = 'VA';
    public const KIND_DOCUMENT_DRIVERS_LICENSE = 'DL';
    public const KIND_DOCUMENT_PRIORITY_PASS = 'PP';

    public const PASSPORT_NOTICES_MONTHS = [6, 9, 12];
    public const BALANCE_NOTIFICATION_DAYS_LAST_WEEK = [1, 2, 3, 4, 5, 6, 7, 30, 60, 90];
    public const BALANCE_NOTIFICATION_DAY_SEVEN_DAYS_BEFORE = [7, 30, 60, 90];

    private LoggerInterface $logger;

    private EntityManagerInterface $em;

    private Connection $connection;

    private string $host;

    private RouterInterface $router;

    private BalanceFormatter $balanceFormatter;

    private UsrRepository $usrRep;

    private AccountRepository $accountRep;

    private SubaccountRepository $subaccountRep;

    private CurrencyRepository $curRep;

    /**
     * @var int[]
     */
    private array $usersIds = [];

    /**
     * @var int[]
     */
    private array $accountIds = [];

    /**
     * @var callable|null
     */
    private $filter;

    private ?\DateTime $startDate;

    private ?int $startUser;

    private ?int $endUser;

    private bool $allowTestProvider = false;

    private bool $calendarMode = false;

    private Mapper $accountListMapper;

    private TranslatorInterface $translator;

    private EntityTranslator $entityTranslator;

    private Formatter $intervalFormatter;

    private LocalizeService $localizer;

    private BlogPostInterface $blogPost;

    private SafeExecutorFactory $safeExecutorFactory;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        RouterInterface $router,
        BalanceFormatter $balanceFormatter,
        string $protoAndHost,
        Mapper $accountListMapper,
        TranslatorInterface $translator,
        EntityTranslator $entityTranslator,
        Formatter $intervalFormatter,
        LocalizeService $localizer,
        BlogPostInterface $blogPost,
        SafeExecutorFactory $safeExecutorFactory
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->connection = $em->getConnection();
        $this->host = $protoAndHost;
        $this->router = $router;
        $this->balanceFormatter = $balanceFormatter;
        $this->accountListMapper = $accountListMapper;
        $this->translator = $translator;
        $this->entityTranslator = $entityTranslator;
        $this->intervalFormatter = $intervalFormatter;
        $this->localizer = $localizer;
        $this->blogPost = $blogPost;
        $this->safeExecutorFactory = $safeExecutorFactory;

        $this->usrRep = $em->getRepository(Usr::class);
        $this->accountRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $this->subaccountRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class);
        $this->curRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Currency::class);

        $this->setStartDate(new \DateTime());
    }

    /**
     * @return \int[]
     */
    public function getUsersIds(): array
    {
        return $this->usersIds;
    }

    /**
     * @param \int[] $usersIds
     */
    public function setUsersIds(array $usersIds): ExpirationDate
    {
        $this->usersIds = $usersIds;

        return $this;
    }

    public function getFilter(): ?callable
    {
        return $this->filter;
    }

    public function setFilter(?callable $filter): ExpirationDate
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return \int[]
     */
    public function getAccountIds(): array
    {
        return $this->accountIds;
    }

    /**
     * @param \int[] $accountIds
     */
    public function setAccountIds(array $accountIds): ExpirationDate
    {
        $this->accountIds = $accountIds;

        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): ExpirationDate
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getStartUser(): int
    {
        return $this->startUser;
    }

    public function setStartUser(int $startUser): ExpirationDate
    {
        $this->startUser = $startUser;

        return $this;
    }

    public function getEndUser(): int
    {
        return $this->endUser;
    }

    public function setEndUser(int $endUser): ExpirationDate
    {
        $this->endUser = $endUser;

        return $this;
    }

    public function isAllowTestProvider(): bool
    {
        return $this->allowTestProvider;
    }

    public function setAllowTestProvider(bool $allowTestProvider): ExpirationDate
    {
        $this->allowTestProvider = $allowTestProvider;

        return $this;
    }

    public function prepareExpire(Expire $expire): iterable
    {
        $prepared = [];
        $prepared['ProviderID'] = new Property('ProviderID', $expire->ProviderID, false);
        $prepared['ProviderKind'] = new Property('ProviderKind', $expire->ProviderKind, false);
        $prepared['ChangeCount'] = new Property('ChangeCount', $expire->ChangeCount, false);
        $prepared['Expire'] = new Property('Expire', $expire, false);

        if (!is_null($expire->ExpirationAutoSet) && $expire->ExpirationAutoSet == EXPIRATION_UNKNOWN) {
            $prepared['ExpireUnknown'] = new Property('ExpireUnknown', true, false);
        }

        $user = $this->usrRep->find($expire->UserID);
        $locale = $user ? $user->getLocale() : null;
        $now = clone $this->startDate;

        switch ($expire->Kind) {
            case 'A': // account
                $account = $this->accountRep->find($expire->ID);

                if (!$account) {
                    return;
                }

                $expiringValue = $expire->Value ? filterBalance($expire->Value, false) : floatval($expire->Balance);

                if (empty($expiringValue)) {
                    return;
                }

                $prepared['ProgramName'] = new Property(
                    new Translatable('account.program', [], 'messages'),
                    $expire->Name
                );
                $prepared['Owner'] = new Property(
                    new Translatable('award.account.owner', [], 'messages'),
                    $expire->UserName
                );
                $prepared['ExpiringBalance'] = new Property(
                    new Translatable('expiring_balance'),
                    $this->balanceFormatter->formatAccount($account, $expiringValue)
                );

                if (isset($expire->Value) && filterBalance($expire->Value, false) != filterBalance($expire->Balance, false)) {
                    $prepared['TotalBalance'] = new Property(
                        new Translatable('total_balance'),
                        $this->balanceFormatter->formatAccount($account, floatval($expire->Balance))
                    );
                }
                $prepared['PointsExpire'] = new Property(
                    new Translatable('points_expire'),
                    new DateTimeAgo($expire->ExpirationDate)
                );

                if (!empty($expire->SuccessCheckDate)) {
                    $prepared['LastAccountUpdate'] = new Property(
                        new Translatable('last_account_update'),
                        new DateTimeAgo($expire->SuccessCheckDate)
                    );
                }

                if (!empty($expire->BlogIdsMileExpiration) && is_array($expire->BlogIdsMileExpiration)) {
                    $blogPosts = $this->safeExecutorFactory->make(
                        fn () => $this->blogPost->fetchPostById($expire->BlogIdsMileExpiration, false)
                    )->runOrValue([]);

                    if (!empty($blogPosts) && is_array($blogPosts)) {
                        $blogPost = reset($blogPosts);
                        $prepared['BlogPost'] = new Property(
                            'BlogPost',
                            new BlogPost(
                                $blogPost['title'],
                                $blogPost['postURL'],
                                $blogPost['imageURL']
                            ),
                            false
                        );
                    }
                }

                $currency = null;

                if (is_numeric($expire->CurrencyID)) {
                    $prepared['Currency'] = new Property(
                        'Currency',
                        new Currency(
                            $expiringValue,
                            $currency = $this->curRep->find($expire->CurrencyID)
                        ),
                        false
                    );
                }

                if (!isset($currency)) {
                    $currency = $this->curRep->find(\AwardWallet\MainBundle\Entity\Currency::POINTS_ID);
                }

                $header = $this->translator->trans('balance_expiration.alert_title.with-currency', [
                    '%balance%' => $prepared['ExpiringBalance']->getValue(),
                    '%currency%' => mb_strtolower($this->entityTranslator->transChoice(
                        $currency,
                        'plural',
                        $expiringValue,
                        [],
                        null,
                        $locale
                    )),
                    '%programName%' => $expire->Name,
                    '%expiration_period%' => $this->intervalFormatter->formatDuration(
                        clone $now,
                        date_create($expire->ExpirationDate),
                        true,
                        false,
                        false,
                        $locale
                    ),
                    '%date%' => $this->localizer->formatDateTime(
                        date_create($expire->ExpirationDate),
                        LocalizeService::FORMAT_LONG,
                        null,
                        $locale
                    ),
                ], 'email', $locale);

                break;

            case 'T':
            case 'C': // coupon
            case self::KIND_DOCUMENT_INSURANCE_CARD:
            case self::KIND_DOCUMENT_VISA:
            case self::KIND_DOCUMENT_DRIVERS_LICENSE:
            case self::KIND_DOCUMENT_PRIORITY_PASS:
                $expire = $this->documentModify($expire, $locale);

                $prepared['ProgramName'] = new Property(
                    new Translatable('account.program', [], 'messages'),
                    $expire->Name
                );
                $prepared['Owner'] = new Property(
                    new Translatable('award.account.owner', [], 'messages'),
                    $expire->UserName
                );

                if ($expire->Kind === 'C') {
                    $parameters = [
                        '%providerName%' => $expire->ShortName,
                        '%expiration_period%' => $this->intervalFormatter->formatDuration(
                            clone $now,
                            date_create($expire->ExpirationDate),
                            true,
                            false,
                            false,
                            $locale
                        ),
                        '%date%' => $this->localizer->formatDateTime(
                            date_create($expire->ExpirationDate),
                            LocalizeService::FORMAT_LONG,
                            null,
                            $locale
                        ),
                    ];

                    if (!empty($expire->Notes)) {
                        $prepared['ExpiringCoupon'] = new Property(
                            new Translatable('expiring_coupon'),
                            $expire->Notes
                        );
                    }

                    if (!empty($expire->Value)) {
                        $prepared['CouponValue'] = new Property(
                            new Translatable('coupon_value'),
                            $expire->Value
                        );
                    }

                    if (!empty($expire->TypeName)) {
                        $parameters['%couponName%'] = $expire->TypeName;
                    }

                    if (isset($parameters['%couponName%'])) {
                        $header = $this->translator->trans('balance_expiration.alert_title_coupon', $parameters, 'email', $locale);
                    } else {
                        $header = $this->translator->trans('balance_expiration.alert_title_coupon.without-coupon-name', $parameters, 'email', $locale);
                    }
                }

                $prepared['CouponExpires'] = new Property(
                    new Translatable('expires'),
                    new DateTimeAgo($expire->ExpirationDate)
                );

                if (!empty($expire->SuccessCheckDate)) {
                    $prepared['LastAccountUpdate'] = new Property(
                        new Translatable('last_account_update'),
                        new DateTimeAgo($expire->SuccessCheckDate)
                    );
                }

                break;

            case 'D': // deal
                $deal = $this->connection->executeQuery("
                    SELECT
                        ap.Val
                    FROM
                        AccountProperty ap
                        INNER JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID
                    WHERE
                        ap.SubAccountID = ?
                        AND pp.Code = 'Certificates'
                ", [$expire->ID], [\PDO::PARAM_INT])->fetch(\PDO::FETCH_ASSOC);

                if ($deal !== false) {
                    $cert = @unserialize($deal['Val']);

                    if ($cert !== false) {
                        usort($cert, function ($a, $b) {
                            if ($a['ExpiresAt'] == $b['ExpiresAt']) {
                                return 0;
                            }

                            return ($a['ExpiresAt'] > $b['ExpiresAt']) ? 1 : -1;
                        });
                        $exp = null;

                        foreach ($cert as $coupon) {
                            if ($coupon['Used'] == false && $now->getTimestamp() < $coupon['ExpiresAt'] && $coupon['ExpiresAt'] == $expire->ExpirationDate) {
                                $exp = true;

                                break;
                            }
                        }

                        if (is_null($exp)) {
                            $this->logger->info('Coupons are not active. Next...');

                            //                            $this->log('Coupons are not active. Next...');
                            break;
                        }
                    }
                }
                $prepared['ProgramName'] = new Property(
                    new Translatable('account.program', [], 'messages'),
                    $expire->Name
                );
                $prepared['Owner'] = new Property(
                    new Translatable('award.account.owner', [], 'messages'),
                    $expire->UserName
                );
                $prepared['ExpiringCoupon'] = new Property(
                    new Translatable('expiring_coupon'),
                    $expire->Value
                );
                $prepared['CouponExpires'] = new Property(
                    new Translatable('coupon_expires'),
                    new DateTimeAgo($expire->ExpirationDate)
                );

                $header = $this->translator->trans('balance_expiration.alert_title', [
                    '%providerName%' => $expire->ShortName,
                    '%expiration_period%' => $this->intervalFormatter->formatDuration(
                        clone $now,
                        date_create($expire->ExpirationDate),
                        true,
                        false,
                        false,
                        $locale
                    ),
                    '%date%' => $this->localizer->formatDateTime(
                        date_create($expire->ExpirationDate),
                        LocalizeService::FORMAT_LONG,
                        null,
                        $locale
                    ),
                    '%couponName%' => $expire->Value,
                ], 'email', $locale);

                break;

            case 'S': // subaccount
                $subaccount = $this->subaccountRep->find($expire->ID);

                if (!$subaccount) {
                    return;
                }

                $prepared['ProgramName'] = new Property(
                    new Translatable('account.program', [], 'messages'),
                    $expire->Name
                );
                $prepared['Owner'] = new Property(
                    new Translatable('award.account.owner', [], 'messages'),
                    $expire->UserName
                );
                $prepared['ExpiringBalance'] = new Property(
                    new Translatable('expiring_balance'),
                    isset($expire->Value)
                        ? new Group([$expire->Notes, $this->balanceFormatter->formatSubAccount($subaccount, $expire->Value)], ' - ')
                        : (
                            isset($expire->Balance) && floatval($expire->Balance) > 0
                            ? new Group([$expire->Notes, $this->balanceFormatter->formatSubAccount($subaccount, floatval($expire->Balance))], ' - ')
                            : $expire->Notes
                        )
                );

                if (isset($expire->Value, $expire->Balance) && filterBalance($expire->Value, false) != filterBalance($expire->Balance, false)) {
                    $prepared['TotalBalance'] = new Property(
                        new Translatable('total_balance'),
                        $this->balanceFormatter->formatSubAccount($subaccount, floatval($expire->Balance))
                    );
                }
                $prepared['CouponExpires'] = new Property(
                    new Translatable('coupon_expires'),
                    new DateTimeAgo($expire->ExpirationDate)
                );

                $parameters = [
                    '%providerName%' => $expire->ShortName,
                    '%expiration_period%' => $this->intervalFormatter->formatDuration(
                        clone $now,
                        date_create($expire->ExpirationDate),
                        true,
                        false,
                        false,
                        $locale
                    ),
                    '%date%' => $this->localizer->formatDateTime(
                        date_create($expire->ExpirationDate),
                        LocalizeService::FORMAT_LONG,
                        null,
                        $locale
                    ),
                ];
                $expiringBalance = $prepared['ExpiringBalance']->getValue();

                if ($expiringBalance instanceof Group) {
                    [$name, $quantity] = $expiringBalance->getItems();
                    $parameters['%couponName%'] = $name;
                    $parameters['%quantity%'] = $quantity;
                    $header = $this->translator->trans('balance_expiration.alert_title.with-quantity', $parameters, 'email', $locale);
                } else {
                    $parameters['%couponName%'] = $expiringBalance;
                    $header = $this->translator->trans('balance_expiration.alert_title', $parameters, 'email', $locale);
                }

                break;
        }

        if (isset($header)) {
            $prepared['Header'] = new Property('Header', htmlspecialchars_decode($header), false);
        }

        $planEmailProgramIDs = [
            26,  // United
            7,   // Delta Personal
            16,  // Southwest Airlines
        ];
        $expDateProposal = '';

        if (in_array($expire->ProviderID, $planEmailProgramIDs)) {
            if ('A' === $expire->Kind) {
                $editLink = $this->host . $this->router->generate('aw_account_edit', [
                    'accountId' => $expire->ID,
                ]);
                $expDateProposal = new Translatable(
                    'balance_expiration.forwarded-email',
                    [
                        '%name%' => $this->calendarMode ? $expire->Name : htmlspecialchars($expire->Name),
                        '%link_on%' => '<a href="' . $editLink . '">',
                        '%link_off%' => '</a>',
                        '%link%' => $editLink,
                    ],
                    'email',
                    null,
                    false
                );
            }
        } else {
            $path = null;

            switch ($expire->Kind) {
                case 'A':
                    $path = '#/?account=' . $expire->ID;

                    break;

                case 'T':
                case 'C':
                case self::KIND_DOCUMENT_INSURANCE_CARD:
                case self::KIND_DOCUMENT_VISA:
                case self::KIND_DOCUMENT_DRIVERS_LICENSE:
                case self::KIND_DOCUMENT_PRIORITY_PASS:
                    $path = '#/?coupon=' . $expire->ID;

                    break;

                case 'S':
                    if (!empty($expire->ParentID)) {
                        $path = sprintf('#/?account=%d&subaccount=%d', $expire->ParentID, $expire->ID);
                    }

                    break;
            }

            if (isset($path)) {
                $detailsLink = $this->host . $this->router->generate('aw_account_list') . $path;
                $expDateProposal = new Translatable('balance_expiration.login-update-v2', [
                    '%link_on%' => '<a href="' . $detailsLink . '">',
                    '%link_off%' => '</a>',
                ], 'email', null, false);
            }
        }

        $visible = array_filter($prepared, function ($val) {
            return $val instanceof Property && $val->isVisible();
        });

        if ($visible && count($visible) > 1 && !empty($expire->LastSuccessCheckDays) && !empty($expire->SavePassword)) {
            if ($expire->ExpirationAutoSet == EXPIRATION_USER) {
                $prepared['Note'] = new Property(
                    new Translatable('award.account.note', [], 'messages'),
                    new Translatable('balance_expiration.set-manually'),
                    false
                );
            } elseif (!empty($expire->ErrorCode)) {
                $isAA = $expire->ProviderID === 1;

                if ($expire->ErrorCode == ACCOUNT_QUESTION) {
                    if ($expire->SavePassword == SAVE_PASSWORD_DATABASE || $isAA) {
                        $prepared['Note'] = new Property(
                            new Translatable('award.account.note', [], 'messages'),
                            new Group([
                                new Translatable('balance_expiration.question', [
                                    '%span_on%' => $this->calendarMode ? '' : '<b>',
                                    '%span_off%' => $this->calendarMode ? '' : '</b>',
                                    '%span2_on%' => $this->calendarMode ? '' : '<span style="color:#fff;background:#cc3d5e;padding:2px 5px; display:inline-block">',
                                    '%span2_off%' => $this->calendarMode ? '' : '</span>',
                                    '%value%' => $this->calendarMode ? $expire->ErrorMessage : htmlspecialchars(CleanXMLValue($expire->ErrorMessage)),
                                    '%count%' => $expire->LastSuccessCheckDays,
                                ], 'email', $expire->LastSuccessCheckDays, false),
                                $expDateProposal,
                            ], ' '),
                            false
                        );
                    } elseif ($expire->SavePassword == SAVE_PASSWORD_LOCALLY) {
                        $prepared['Note'] = new Property(
                            new Translatable('award.account.note', [], 'messages'),
                            new Group([
                                new Translatable('balance_expiration.question.local-password', [
                                    '%span_on%' => $this->calendarMode ? '' : '<b>',
                                    '%span_off%' => $this->calendarMode ? '' : '</b>',
                                    '%span2_on%' => $this->calendarMode ? '' : '<span style="color:#fff;background:#cc3d5e;padding:2px 5px; display:inline-block">',
                                    '%span2_off%' => $this->calendarMode ? '' : '</span>',
                                    '%value%' => $this->calendarMode ? $expire->ErrorMessage : htmlspecialchars(CleanXMLValue($expire->ErrorMessage)),
                                    '%count%' => $expire->LastSuccessCheckDays,
                                ], 'email', $expire->LastSuccessCheckDays, false),
                                $expDateProposal,
                            ], ' '),
                            false
                        );
                    }
                } elseif ($expire->ErrorCode == ACCOUNT_CHECKED || $expire->ErrorCode == ACCOUNT_UNCHECKED) {
                    if ($expire->SavePassword == SAVE_PASSWORD_DATABASE || $isAA) {
                        $prepared['Note'] = new Property(
                            new Translatable('award.account.note', [], 'messages'),
                            new Group([
                                new Translatable('balance_expiration.checked-unchecked', [
                                    '%span_on%' => $this->calendarMode ? '' : '<b>',
                                    '%span_off%' => $this->calendarMode ? '' : '</b>',
                                    // abs here for testing with --date option, when there
                                    // will be negative values
                                    '%count%' => abs($expire->LastSuccessCheckDays),
                                ], 'email', abs($expire->LastSuccessCheckDays), false),
                                $expDateProposal,
                            ], ' '),
                            false
                        );
                    } elseif ($expire->SavePassword == SAVE_PASSWORD_LOCALLY) {
                        $prepared['Note'] = new Property(
                            new Translatable('award.account.note', [], 'messages'),
                            new Group([
                                new Translatable('balance_expiration.checked-unchecked.local-password', [
                                    '%span_on%' => $this->calendarMode ? '' : '<b>',
                                    '%span_off%' => $this->calendarMode ? '' : '</b>',
                                    '%count%' => $expire->LastSuccessCheckDays,
                                ], 'email', $expire->LastSuccessCheckDays, false),
                                $expDateProposal,
                            ], ' '),
                            false
                        );
                    }
                } elseif (!empty($expire->ErrorMessage)) {
                    if ($expire->SavePassword == SAVE_PASSWORD_DATABASE || $isAA) {
                        $prepared['Note'] = new Property(
                            new Translatable('award.account.note', [], 'messages'),
                            new Group([
                                new Translatable('balance_expiration.error', [
                                    '%span_on%' => $this->calendarMode ? '' : '<b>',
                                    '%span_off%' => $this->calendarMode ? '' : '</b>',
                                    '%span2_on%' => $this->calendarMode ? '' : '<span style="color:#fff;background:#cc3d5e;padding:2px 5px; display:inline-block">',
                                    '%span2_off%' => $this->calendarMode ? '' : '</span>',
                                    '%value%' => $this->calendarMode ? $expire->ErrorMessage : htmlspecialchars(CleanXMLValue($expire->ErrorMessage)),
                                    '%count%' => $expire->LastSuccessCheckDays,
                                ], 'email', $expire->LastSuccessCheckDays, false),
                                $expDateProposal,
                            ], ' '),
                            false
                        );
                    } elseif ($expire->SavePassword == SAVE_PASSWORD_LOCALLY) {
                        $prepared['Note'] = new Property(
                            new Translatable('award.account.note', [], 'messages'),
                            new Group([
                                new Translatable('balance_expiration.error.local-password', [
                                    '%span_on%' => $this->calendarMode ? '' : '<b>',
                                    '%span_off%' => $this->calendarMode ? '' : '</b>',
                                    '%span2_on%' => $this->calendarMode ? '' : '<span style="color:#fff;background:#cc3d5e;padding:2px 5px; display:inline-block">',
                                    '%span2_off%' => $this->calendarMode ? '' : '</span>',
                                    '%value%' => $this->calendarMode ? $expire->ErrorMessage : htmlspecialchars(CleanXMLValue($expire->ErrorMessage)),
                                    '%count%' => $expire->LastSuccessCheckDays,
                                ], 'email', $expire->LastSuccessCheckDays, false),
                                $expDateProposal,
                            ], ' '),
                            false
                        );
                    }
                }
            }
        }

        if (count(array_filter($prepared, function ($val) {
            return $val instanceof Property && $val->isVisible();
        })) > 0) {
            yield $prepared;
        }
    }

    public function getStmt(int $typeMode)
    {
        if (!in_array($typeMode, [self::MODE_EMAIL, self::MODE_CALENDAR])) {
            throw new \Exception('Wrong type mode');
        }

        $this->calendarMode = ($typeMode === self::MODE_CALENDAR);
        $sqls = $params = $paramsTypes = [];

        foreach ([
            // accounts
            $this->buildAccountsToUsersSQL(),
            $this->buildAccountsToFamilyMembersSQL(),
            $this->buildAccountsToBusinessSQL(),

            // subaccounts
            $this->buildSubAccountsToUsersSQL(),
            $this->buildSubAccountsToFamilyMembersSQL(),
            $this->buildSubAccountsToBusinessSQL(),

            // coupons and passports
            $this->buildCouponsToUsersSQL(),
            $this->buildCouponsToFamilyMembersSQL(),
            $this->buildCouponsToBusinessSQL(),
        ] as $builder) {
            /** @var QueryBuilder $builder */
            $sqls[] = sprintf('(%s)', $builder->getSQL());
            $params += $builder->getParameters();
            $paramsTypes += $builder->getParameterTypes();
        }

        $stmt = $this->connection->executeQuery(
            sprintf('
                %s
                ORDER BY Email, UserAgentID
            ', implode(' UNION ', $sqls)),
            $params,
            $paramsTypes
        );

        return $stmt;
    }

    public static function getTranslationMessages(): array
    {
        return [
            (new Message('balance_expiration.alert_title', 'email'))->setDesc('‼️%couponName% from %providerName% expiring in %expiration_period% on %date% ‼️'),
            (new Message('balance_expiration.alert_title.with-quantity', 'email'))->setDesc('‼️%quantity% %couponName% from %providerName% expiring in %expiration_period% on %date% ‼️'),
            (new Message('balance_expiration.alert_title.with-currency', 'email'))->setDesc('‼️%balance% %programName% %currency% expiring in %expiration_period% on %date% ‼️'),
            (new Message('balance_expiration.alert_title.default', 'email'))->setDesc('‼️Award program point expiration notice ‼️'),
            (new Message('balance_expiration.alert_title_coupon', 'email'))->setDesc('‼️%providerName% %couponName% expiring in %expiration_period% on %date% ‼️'),
            (new Message('balance_expiration.alert_title_coupon.without-coupon-name', 'email'))->setDesc('‼️%providerName% expiring in %expiration_period% on %date% ‼️'),
        ];
    }

    private function buildAccountsToUsersSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('au');
        $now = $this->now();

        $builder
            ->select("
                'A' AS Kind,
                'U' AS TargetKind,
                a.AccountID AS ID,
                NULL AS ParentID,
                a.ProviderID,
                p.Kind AS ProviderKind,
                a.ChangeCount,
                COALESCE( p.DisplayName, a.ProgramName ) AS Name,
                COALESCE( p.ShortName, a.ProgramName ) AS ShortName,
                a.Balance,
                (SELECT ap.Val FROM AccountProperty ap
                    JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
                    WHERE
                    pp.Kind = :{$k}propertyKind AND ap.Val > 0 AND ap.AccountID = a.AccountID LIMIT 1) AS Value,
                a.ExpirationDate,
                p.BlogIdsMileExpiration,
                u.UserID,
                IF(a.UserAgentID IS NOT NULL, CONCAT( ua.FirstName, ' ', ua.LastName ), CONCAT( u.FirstName, ' ', u.LastName )) AS UserName,
                u.Email,
                u.EmailVerified,
                u.EmailFamilyMemberAlert,
                ua.Email AS uaEmail,
                ua.SendEmails AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                NULL AS ShareCode,
                NULL AS UserAgentID,
                DATEDIFF( a.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), a.ExpirationDate) AS Months,
                a.ExpirationAutoSet,
                a.Login,
                NULL AS Notes,
                COALESCE(a.CurrencyID, c.CurrencyID) AS CurrencyID,
                COALESCE(c2.Name, c.Name) AS Currency,
                DATEDIFF( NOW(), a.UpdateDate ) AS LastCheckDays,
                DATEDIFF( NOW(), a.SuccessCheckDate ) AS LastSuccessCheckDays,
                a.SuccessCheckDate AS SuccessCheckDate,
                a.ErrorCode AS ErrorCode,
                a.ErrorMessage AS ErrorMessage,
                a.SavePassword AS SavePassword,
                NULL AS TypeID,
                NULL AS TypeName,
                NULL AS CustomFields
            ")->from("
                Account a
                LEFT OUTER JOIN Provider p ON a.ProviderID = p.ProviderID
                LEFT JOIN Currency c ON c.CurrencyID = p.Currency
                LEFT JOIN Currency c2 ON c2.CurrencyID = a.CurrencyID
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID,
                Usr u
            ");
        $builder->setParameter(":{$k}propertyKind", PROPERTY_KIND_EXPIRING_BALANCE, \PDO::PARAM_INT);
        $builder->where(
            $e->and(
                $e->eq('a.UserID', 'u.UserID'),
                $e->gte('a.State', ":{$k}accountDisabled"),
                $e->neq('a.DontTrackExpiration', 1),
                $e->gt('a.Balance', 0),
                $e->neq('u.AccountLevel', ":{$k}accountLevelBusiness"),
                $e->or(
                    $e->lt('a.SuccessCheckDate', 'NOW()'),
                    $e->isNull('a.SuccessCheckDate')
                ),
                $e->or(
                    $e->lt('a.UpdateDate', 'NOW()'),
                    $e->isNull('a.UpdateDate')
                ),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}accountDisabled", ACCOUNT_DISABLED, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}accountLevelBusiness", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_USER, self::KIND_ACCOUNT);
        } else {
            $this->addProviderFilter($builder, $k);
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addBalanceDateFilter($builder, 'a', 'u');
        }

        return $builder;
    }

    private function buildAccountsToFamilyMembersSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('aua');
        $now = $this->now();

        $builder
            ->select("
                'A' AS Kind,
                'UA' AS TargetKind,
                a.AccountID AS ID,
                NULL AS ParentID,
                a.ProviderID,
                p.Kind AS ProviderKind,
                a.ChangeCount,
                COALESCE( p.DisplayName, a.ProgramName ) AS Name,
                COALESCE( p.ShortName, a.ProgramName ) AS ShortName,
                a.Balance,
                (SELECT ap.Val FROM AccountProperty ap
                        JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
                        WHERE
                        pp.Kind = :{$k}propertyKind AND ap.Val > 0 AND ap.AccountID = a.AccountID LIMIT 1) AS Value,
                a.ExpirationDate,
                p.BlogIdsMileExpiration,
                u.UserID,
                CONCAT( ua.FirstName, ' ', ua.LastName ) AS UserName,
                ua.Email AS Email,
                NULL AS EmailVerified,
                NULL AS EmailFamilyMemberAlert,
                NULL AS uaEmail,
                NULL AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                ua.ShareCode,
                ua.UserAgentID,
                DATEDIFF( a.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), a.ExpirationDate) AS Months,
                a.ExpirationAutoSet,
                a.Login,
                NULL AS Notes,
                COALESCE(a.CurrencyID, c.CurrencyID) AS CurrencyID,
                COALESCE(c2.Name, c.Name) AS Currency,
                DATEDIFF( NOW(), a.UpdateDate ) AS LastCheckDays,
                DATEDIFF( NOW(), a.SuccessCheckDate) AS LastSuccessCheckDays,
                a.SuccessCheckDate AS SuccessCheckDate,
                a.ErrorCode AS ErrorCode,
                a.ErrorMessage AS ErrorMessage,
                a.SavePassword AS SavePassword,
                NULL AS TypeID,
                NULL AS TypeName,
                NULL AS CustomFields
            ")->from("
                Account a
                LEFT OUTER JOIN Provider p ON a.ProviderID = p.ProviderID
                LEFT JOIN Currency c ON c.CurrencyID = p.Currency
                LEFT JOIN Currency c2 ON c2.CurrencyID = a.CurrencyID
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID,
                Usr u
            ");
        $builder->setParameter(":{$k}propertyKind", PROPERTY_KIND_EXPIRING_BALANCE, \PDO::PARAM_INT);
        $builder->where(
            $e->and(
                $e->eq('a.UserID', 'u.UserID'),
                $e->gte('a.State', ":{$k}accountDisabled"),
                $e->neq('a.DontTrackExpiration', 1),
                $e->gt('a.Balance', 0),
                $e->isNotNull('a.UserAgentID'),
                $e->or(
                    $e->lt('a.SuccessCheckDate', 'NOW()'),
                    $e->isNull('a.SuccessCheckDate')
                ),
                $e->or(
                    $e->lt('a.UpdateDate', 'NOW()'),
                    $e->isNull('a.UpdateDate')
                ),
                $this->calendarMode ? $e->eq('1', 1) : $e->eq('ua.SendEmails', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->isNotNull('ua.Email'),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}accountDisabled", ACCOUNT_DISABLED, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_FM, self::KIND_ACCOUNT);
        } else {
            $this->addProviderFilter($builder, $k);
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addBalanceDateFilter($builder, 'a', 'u');
        }

        return $builder;
    }

    private function buildAccountsToBusinessSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('ab');
        $now = $this->now();

        $builder
            ->select("
                'A' AS Kind,
                'B' AS TargetKind,
                a.AccountID AS ID,
                NULL AS ParentID,
                a.ProviderID,
                p.Kind AS ProviderKind,
                a.ChangeCount,
                COALESCE( p.DisplayName, a.ProgramName ) AS Name,
                COALESCE( p.ShortName, a.ProgramName ) AS ShortName,
                a.Balance,
                (SELECT ap.Val FROM AccountProperty ap
                        JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
                        WHERE
                        pp.Kind = :{$k}propertyKind AND ap.Val > 0 AND ap.AccountID = a.AccountID LIMIT 1) AS Value,
                a.ExpirationDate,
                p.BlogIdsMileExpiration,
                u.UserID,
                IF(a.UserAgentID IS NOT NULL, CONCAT( ua2.FirstName, ' ', ua2.LastName ), u.Company) AS UserName,
                u2.Email,
                NULL AS EmailVerified,
                NULL AS EmailFamilyMemberAlert,
                NULL AS uaEmail,
                NULL AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                NULL AS ShareCode,
                NULL AS UserAgentID,
                DATEDIFF( a.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), a.ExpirationDate) AS Months,
                a.ExpirationAutoSet,
                a.Login,
                NULL AS Notes,
                COALESCE(a.CurrencyID, c.CurrencyID) AS CurrencyID,
                COALESCE(c2.Name, c.Name) AS Currency,
                DATEDIFF( NOW(), a.UpdateDate ) AS LastCheckDays,
                DATEDIFF( NOW(), a.SuccessCheckDate) AS LastSuccessCheckDays,
                a.SuccessCheckDate AS SuccessCheckDate,
                a.ErrorCode AS ErrorCode,
                a.ErrorMessage AS ErrorMessage,
                a.SavePassword AS SavePassword,
                NULL AS TypeID,
                NULL AS TypeName,
                NULL AS CustomFields
            ")->from("
                Account a
                LEFT OUTER JOIN Provider p ON a.ProviderID = p.ProviderID
                LEFT JOIN Currency c ON c.CurrencyID = p.Currency
                LEFT JOIN Currency c2 ON c2.CurrencyID = a.CurrencyID
                LEFT JOIN UserAgent ua2 ON a.UserAgentID = ua2.UserAgentID
                LEFT JOIN UserAgent ua ON a.UserID = ua.ClientID
                LEFT JOIN Usr u2 ON ua.AgentID = u2.UserID,
                Usr u
            ");
        $builder->setParameter(":{$k}propertyKind", PROPERTY_KIND_EXPIRING_BALANCE, \PDO::PARAM_INT);
        $builder->where(
            $e->and(
                $e->eq('a.UserID', 'u.UserID'),
                $e->gte('a.State', ":{$k}accountDisabled"),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailVerified', ":{$k}emailNdr"),
                $e->neq('a.DontTrackExpiration', 1),
                $e->gt('a.Balance', 0),
                $e->isNull('a.UserAgentID'),
                $e->eq('u.AccountLevel', ":{$k}accountLevelBusiness"),
                $e->eq('ua.AccessLevel', ":{$k}accessLevel"),
                $e->or(
                    $e->lt('a.SuccessCheckDate', 'NOW()'),
                    $e->isNull('a.SuccessCheckDate')
                ),
                $e->or(
                    $e->lt('a.UpdateDate', 'NOW()'),
                    $e->isNull('a.UpdateDate')
                ),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u2.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}accountDisabled", ACCOUNT_DISABLED, \PDO::PARAM_INT);

        if (!$this->calendarMode) {
            $builder->setParameter(":{$k}emailNdr", EMAIL_NDR, \PDO::PARAM_INT);
        }
        $builder->setParameter(":{$k}accountLevelBusiness", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}accessLevel", ACCESS_ADMIN, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_BUSINESS, self::KIND_ACCOUNT);
        } else {
            $this->addProviderFilter($builder, $k);
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addBalanceDateFilter($builder, 'a', 'u2');
        }

        return $builder;
    }

    private function buildSubAccountsToUsersSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('su');
        $now = $this->now();

        $builder
            ->select("
                'S' AS Kind,
                'U' AS TargetKind,
                sa.SubAccountID AS ID,
                a.AccountID AS ParentID,
                a.ProviderID,
                p.Kind AS ProviderKind,
                sa.ChangeCount,
                p.DisplayName AS Name,
                p.ShortName AS ShortName,
                sa.Balance,
                (SELECT ap.Val FROM AccountProperty ap
                        JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
                        WHERE
                        pp.Kind = :{$k}propertyKind AND ap.Val > 0 AND ap.AccountID = a.AccountID AND ap.SubAccountID = sa.SubAccountID LIMIT 1) AS Value,
                sa.ExpirationDate,
                NULL AS BlogIdsMileExpiration,
                a.UserID,
                IF(a.UserAgentID IS NOT NULL, CONCAT( ua.FirstName, ' ', ua.LastName ), CONCAT( u.FirstName, ' ', u.LastName )) AS UserName,
                u.Email,
                u.EmailVerified,
                u.EmailFamilyMemberAlert,
                ua.Email AS uaEmail,
                ua.SendEmails AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                NULL AS ShareCode,
                NULL AS UserAgentID,
                DATEDIFF( sa.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), sa.ExpirationDate) AS Months,
                sa.ExpirationAutoSet,
                sa.Kind AS Login,
                sa.DisplayName AS Notes,
                NULL AS CurrencyID,
                NULL AS Currency,
                DATEDIFF( NOW(), a.UpdateDate ) AS LastCheckDays,
                DATEDIFF( NOW(), a.SuccessCheckDate ) AS LastSuccessCheckDays,
                a.SuccessCheckDate AS SuccessCheckDate,
                a.ErrorCode AS ErrorCode,
                a.ErrorMessage AS ErrorMessage,
                a.SavePassword AS SavePassword,
                NULL AS TypeID,
                NULL AS TypeName,
                NULL AS CustomFields
            ")->from("
                Account a
                JOIN Provider p ON a.ProviderID = p.ProviderID
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
                JOIN SubAccount sa ON sa.AccountID = a.AccountID
                JOIN Usr u ON a.UserID = u.UserID
            ");
        $builder->setParameter(":{$k}propertyKind", PROPERTY_KIND_EXPIRING_BALANCE, \PDO::PARAM_INT);
        $builder->where(
            $e->and(
                $e->or(
                    $e->isNull('sa.Balance'),
                    $e->gt('sa.Balance', 0)
                ),
                $e->neq('u.AccountLevel', ":{$k}accountLevelBusiness"),
                $e->or(
                    $e->lt('a.SuccessCheckDate', 'NOW()'),
                    $e->isNull('a.SuccessCheckDate')
                ),
                $e->or(
                    $e->lt('a.UpdateDate', 'NOW()'),
                    $e->isNull('a.UpdateDate')
                ),
                $e->neq('sa.IsHidden', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('p.DontSendEmailsSubaccExpDate', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}accountLevelBusiness", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_USER, self::KIND_SUBACCOUNT);
        } else {
            $this->addProviderFilter($builder, $k, false);
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addBalanceDateFilter($builder, 'sa', 'u');
        }

        return $builder;
    }

    private function buildSubAccountsToFamilyMembersSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('sua');
        $now = $this->now();

        $builder
            ->select("
                'S' AS Kind,
                'UA' AS TargetKind,
                sa.SubAccountID AS ID,
                a.AccountID AS ParentID,
                a.ProviderID,
                p.Kind AS ProviderKind,
                sa.ChangeCount,
                p.DisplayName AS Name,
                p.ShortName AS ShortName,
                sa.Balance,
                (SELECT ap.Val FROM AccountProperty ap
                        JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
                        WHERE
                        pp.Kind = :{$k}propertyKind AND ap.Val > 0 AND ap.AccountID = a.AccountID AND ap.SubAccountID = sa.SubAccountID LIMIT 1) AS Value,
                sa.ExpirationDate,
                NULL AS BlogIdsMileExpiration,
                a.UserID,
                CONCAT( ua.FirstName, ' ', ua.LastName ) AS UserName,
                ua.Email AS Email,
                NULL AS EmailVerified,
                NULL AS EmailFamilyMemberAlert,
                NULL AS uaEmail,
                NULL AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                ua.ShareCode,
                ua.UserAgentID,
                DATEDIFF( sa.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), sa.ExpirationDate) AS Months,
                sa.ExpirationAutoSet,
                sa.Kind AS Login,
                sa.DisplayName AS Notes,
                NULL AS CurrencyID,
                NULL AS Currency,
                DATEDIFF( NOW(), a.UpdateDate ) AS LastCheckDays,
                DATEDIFF( NOW(), a.SuccessCheckDate ) AS LastSuccessCheckDays,
                a.SuccessCheckDate AS SuccessCheckDate,
                a.ErrorCode AS ErrorCode,
                a.ErrorMessage AS ErrorMessage,
                a.SavePassword AS SavePassword,
                NULL AS TypeID,
                NULL AS TypeName,
                NULL AS CustomFields
            ")->from("
                Account a
                JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN SubAccount sa ON sa.AccountID = a.AccountID
                JOIN Usr u ON a.UserID = u.UserID
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
            ");
        $builder->setParameter(":{$k}propertyKind", PROPERTY_KIND_EXPIRING_BALANCE, \PDO::PARAM_INT);
        $builder->where(
            $e->and(
                $e->or(
                    $e->isNull('sa.Balance'),
                    $e->gt('sa.Balance', 0)
                ),
                $e->or(
                    $e->lt('a.SuccessCheckDate', 'NOW()'),
                    $e->isNull('a.SuccessCheckDate')
                ),
                $e->or(
                    $e->lt('a.UpdateDate', 'NOW()'),
                    $e->isNull('a.UpdateDate')
                ),
                $e->neq('sa.IsHidden', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailVerified', ":{$k}emailNdr"),
                $e->isNotNull('a.UserAgentID'),
                $this->calendarMode ? $e->eq('1', 1) : $e->eq('ua.SendEmails', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->isNotNull('ua.Email'),
                $e->neq('p.DontSendEmailsSubaccExpDate', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );

        if (!$this->calendarMode) {
            $builder->setParameter(":{$k}emailNdr", EMAIL_NDR, \PDO::PARAM_INT);
        }

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_FM, self::KIND_SUBACCOUNT);
        } else {
            $this->addProviderFilter($builder, $k, false);
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addBalanceDateFilter($builder, 'sa', 'u');
        }

        return $builder;
    }

    private function buildSubAccountsToBusinessSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('sb');
        $now = $this->now();

        $builder
            ->select("
                'S' AS Kind,
                'B' AS TargetKind,
                sa.SubAccountID AS ID,
                a.AccountID AS ParentID,
                a.ProviderID,
                p.Kind AS ProviderKind,
                sa.ChangeCount,
                p.DisplayName AS Name,
                p.ShortName AS ShortName,
                sa.Balance,
                (SELECT ap.Val FROM AccountProperty ap
                        JOIN ProviderProperty pp ON pp.ProviderPropertyID = ap.ProviderPropertyID
                        WHERE
                        pp.Kind = :{$k}propertyKind AND ap.Val > 0 AND ap.AccountID = a.AccountID AND ap.SubAccountID = sa.SubAccountID LIMIT 1) AS Value,
                sa.ExpirationDate,
                NULL AS BlogIdsMileExpiration,
                a.UserID,
                IF(a.UserAgentID IS NOT NULL, CONCAT( ua2.FirstName, ' ', ua2.LastName ), u.Company) AS UserName,
                u2.Email,
                NULL AS EmailVerified,
                NULL AS EmailFamilyMemberAlert,
                NULL AS uaEmail,
                NULL AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                NULL AS ShareCode,
                NULL AS UserAgentID,
                DATEDIFF( sa.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), sa.ExpirationDate) AS Months,
                sa.ExpirationAutoSet,
                sa.Kind AS Login,
                sa.DisplayName AS Notes,
                NULL AS CurrencyID,
                NULL AS Currency,
                DATEDIFF( NOW(), a.UpdateDate ) AS LastCheckDays,
                DATEDIFF( NOW(), a.SuccessCheckDate ) AS LastSuccessCheckDays,
                a.SuccessCheckDate AS SuccessCheckDate,
                a.ErrorCode AS ErrorCode,
                a.ErrorMessage AS ErrorMessage,
                a.SavePassword AS SavePassword,
                NULL AS TypeID,
                NULL AS TypeName,
                NULL AS CustomFields
            ")->from("
                Account a
                JOIN Provider p ON a.ProviderID = p.ProviderID
                JOIN SubAccount sa ON sa.AccountID = a.AccountID
                JOIN Usr u ON a.UserID = u.UserID
                LEFT JOIN UserAgent ua2 ON a.UserAgentID = ua2.UserAgentID
                LEFT JOIN UserAgent ua ON a.UserID = ua.ClientID
                LEFT JOIN Usr u2 ON ua.AgentID = u2.UserID
            ");
        $builder->setParameter(":{$k}propertyKind", PROPERTY_KIND_EXPIRING_BALANCE, \PDO::PARAM_INT);

        $builder->where(
            $e->and(
                $e->or(
                    $e->isNull('sa.Balance'),
                    $e->gt('sa.Balance', 0)
                ),
                $e->or(
                    $e->lt('a.SuccessCheckDate', 'NOW()'),
                    $e->isNull('a.SuccessCheckDate')
                ),
                $e->or(
                    $e->lt('a.UpdateDate', 'NOW()'),
                    $e->isNull('a.UpdateDate')
                ),
                $e->neq('sa.IsHidden', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailVerified', ":{$k}emailNdr"),
                $e->isNull('a.UserAgentID'),
                $e->eq('u.AccountLevel', ":{$k}accountLevelBusiness"),
                $e->eq('ua.AccessLevel', ":{$k}accessLevel"),
                $e->neq('p.DontSendEmailsSubaccExpDate', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );

        if (!$this->calendarMode) {
            $builder->setParameter(":{$k}emailNdr", EMAIL_NDR, \PDO::PARAM_INT);
        }
        $builder->setParameter(":{$k}accountLevelBusiness", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}accessLevel", ACCESS_ADMIN, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_BUSINESS, self::KIND_SUBACCOUNT);
        } else {
            $this->addProviderFilter($builder, $k, false);
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addBalanceDateFilter($builder, 'sa', 'u2');
        }

        return $builder;
    }

    private function buildCouponsToUsersSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('cu');
        $now = $this->now();

        $builder
            ->select("
                CASE 
                    WHEN a.TypeID = :{$k}passportType
                        THEN 'P'
                    WHEN a.TypeID = :{$k}trustedTravelerType
                        THEN 'T'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type
                        THEN 'IC'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_VISA . "Type
                        THEN 'VA'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type
                        THEN 'DL'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type
                        THEN 'PP'
                    ELSE 'C'
                END AS Kind,
                'U' AS TargetKind,
                a.ProviderCouponID AS ID,
                NULL AS ParentID,
                NULL AS ProviderID,
                NULL AS ProviderKind,
                NULL AS ChangeCount,
                a.ProgramName AS Name,
                a.ProgramName AS ShortName,
                0 AS Balance,
                Value,
                a.ExpirationDate,
                NULL AS BlogIdsMileExpiration,
                u.UserID,
                IF(a.UserAgentID IS NOT NULL, CONCAT( ua.FirstName, ' ', ua.LastName ), CONCAT( u.FirstName, ' ', u.LastName )) AS UserName,
                u.Email,
                u.EmailVerified,
                u.EmailFamilyMemberAlert,
                ua.Email AS uaEmail,
                ua.SendEmails AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                NULL AS ShareCode,
                NULL AS UserAgentID,
                DATEDIFF( a.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), a.ExpirationDate) AS Months,
                NULL AS ExpirationAutoSet,
                NULL AS Login,
                a.Description AS Notes,
                a.CurrencyID,
                c.Name AS Currency,
                NULL AS LastCheckDays,
                NULL AS LastSuccessCheckDays,
                connected.SuccessCheckDate AS SuccessCheckDate,
                NULL AS ErrorCode,
                NULL AS ErrorMessage,
                NULL AS SavePassword,
                TypeID,
                TypeName,
                CustomFields
            ")->from("
                ProviderCoupon a
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
                LEFT JOIN Currency c ON c.CurrencyID = a.CurrencyID
                LEFT JOIN Account connected ON connected.AccountID = a.AccountID,
                Usr u
            ");
        $builder->where(
            $e->and(
                $e->eq('a.UserID', 'u.UserID'),
                $e->neq('u.AccountLevel', ":{$k}accountLevelBusiness"),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}passportType", Providercoupon::TYPE_PASSPORT, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}trustedTravelerType", Providercoupon::TYPE_TRUSTED_TRAVELER, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type", Providercoupon::TYPE_INSURANCE_CARD, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_VISA . "Type", Providercoupon::TYPE_VISA, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type", Providercoupon::TYPE_DRIVERS_LICENSE, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type", Providercoupon::TYPE_PRIORITY_PASS, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}accountLevelBusiness", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_USER, self::KIND_COUPON);
        } else {
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k, true);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addCouponDateFilter($builder, $k, 'u');
        }

        return $builder;
    }

    private function buildCouponsToFamilyMembersSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('cua');
        $now = $this->now();

        $builder
            ->select("
                CASE 
                    WHEN a.TypeID = :{$k}passportType
                        THEN 'P'
                    WHEN a.TypeID = :{$k}trustedTravelerType
                        THEN 'T'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type
                        THEN 'IC'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_VISA . "Type
                        THEN 'VA'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type
                        THEN 'DL'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type
                        THEN 'PP'
                    ELSE 'C'
                END AS Kind,
                'UA' AS TargetKind,
                a.ProviderCouponID AS ID,
                NULL AS ParentID,
                NULL AS ProviderID,
                NULL AS ProviderKind,
                NULL AS ChangeCount,
                a.ProgramName AS Name,
                a.ProgramName AS ShortName,
                0 AS Balance,
                Value,
                a.ExpirationDate,
                NULL AS BlogIdsMileExpiration,
                u.UserID,
                CONCAT( ua.FirstName, ' ', ua.LastName ) AS UserName,
                ua.Email AS Email,
                NULL AS EmailVerified,
                NULL AS EmailFamilyMemberAlert,
                NULL AS uaEmail,
                NULL AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                ua.ShareCode,
                ua.UserAgentID,
                DATEDIFF( a.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), a.ExpirationDate) AS Months,
                NULL AS ExpirationAutoSet,
                NULL AS Login,
                a.Description AS Notes,
                a.CurrencyID,
                c.Name AS Currency,
                NULL AS LastCheckDays,
                NULL AS LastSuccessCheckDays,
                connected.SuccessCheckDate AS SuccessCheckDate,
                NULL AS ErrorCode,
                NULL AS ErrorMessage,
                NULL AS SavePassword,
                TypeID,
                TypeName,
                CustomFields
            ")->from("
                ProviderCoupon a
                LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
                LEFT JOIN Currency c ON c.CurrencyID = a.CurrencyID
                LEFT JOIN Account connected ON connected.AccountID = a.AccountID,
                Usr u
            ");
        $builder->where(
            $e->and(
                $e->eq('a.UserID', 'u.UserID'),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailVerified', ":{$k}emailNdr"),
                $e->isNotNull('a.UserAgentID'),
                $this->calendarMode ? $e->eq('1', 1) : $e->eq('ua.SendEmails', 1),
                $this->calendarMode ? $e->eq('1', 1) : $e->isNotNull('ua.Email'),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}passportType", Providercoupon::TYPE_PASSPORT, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}trustedTravelerType", Providercoupon::TYPE_TRUSTED_TRAVELER, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type", Providercoupon::TYPE_INSURANCE_CARD, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_VISA . "Type", Providercoupon::TYPE_VISA, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type", Providercoupon::TYPE_DRIVERS_LICENSE, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type", Providercoupon::TYPE_PRIORITY_PASS, \PDO::PARAM_INT);

        if (!$this->calendarMode) {
            $builder->setParameter(":{$k}emailNdr", EMAIL_NDR, \PDO::PARAM_INT);
        }

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_FM, self::KIND_COUPON);
        } else {
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k, true);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addCouponDateFilter($builder, $k, 'u');
        }

        return $builder;
    }

    private function buildCouponsToBusinessSQL(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $e = $builder->expr();
        $k = uniqid('cb');
        $now = $this->now();

        $builder
            ->select("
                CASE 
                    WHEN a.TypeID = :{$k}passportType
                        THEN 'P'
                    WHEN a.TypeID = :{$k}trustedTravelerType
                        THEN 'T'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type
                        THEN 'IC'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_VISA . "Type
                        THEN 'VA'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type
                        THEN 'DL'
                    WHEN a.TypeID = :{$k}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type
                        THEN 'PP'
                    ELSE 'C'
                END AS Kind,
                'B' AS TargetKind,
                a.ProviderCouponID AS ID,
                NULL AS ParentID,
                NULL AS ProviderID,
                NULL AS ProviderKind,
                NULL AS ChangeCount,
                a.ProgramName AS Name,
                a.ProgramName AS ShortName,
                0 AS Balance,
                Value,
                a.ExpirationDate,
                NULL AS BlogIdsMileExpiration,
                u.UserID,
                IF(a.UserAgentID IS NOT NULL, CONCAT( ua2.FirstName, ' ', ua2.LastName ), u.Company) AS UserName,
                u2.Email,
                NULL AS EmailVerified,
                NULL AS EmailFamilyMemberAlert,
                NULL AS uaEmail,
                NULL AS uaSendEmails,
                a.UserAgentID AS Owner,
                u.AccountLevel,
                NULL AS ShareCode,
                NULL AS UserAgentID,
                DATEDIFF( a.ExpirationDate, $now ) AS Days,
                TIMESTAMPDIFF(MONTH, DATE($now), a.ExpirationDate) AS Months,
                NULL AS ExpirationAutoSet,
                NULL AS Login,
                a.Description AS Notes,
                a.CurrencyID,
                c.Name AS Currency,
                NULL AS LastCheckDays,
                NULL AS LastSuccessCheckDays,
                connected.SuccessCheckDate AS SuccessCheckDate,
                NULL AS ErrorCode,
                NULL AS ErrorMessage,
                NULL AS SavePassword,
                TypeID,
                TypeName,
                CustomFields
            ")->from("
                ProviderCoupon a
                LEFT JOIN UserAgent ua2 ON a.UserAgentID = ua2.UserAgentID
                LEFT JOIN UserAgent ua ON a.UserID = ua.ClientID
                LEFT JOIN Usr u2 ON ua.AgentID = u2.UserID
                LEFT JOIN Currency c ON c.CurrencyID = a.CurrencyID
                LEFT JOIN Account connected ON connected.AccountID = a.AccountID,
                Usr u
            ");
        $builder->where(
            $e->and(
                $e->eq('a.UserID', 'u.UserID'),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u.EmailVerified', ":{$k}emailNdr"),
                $e->isNull('a.UserAgentID'),
                $e->eq('u.AccountLevel', ":{$k}accountLevelBusiness"),
                $e->eq('ua.AccessLevel', ":{$k}accessLevel"),
                $this->calendarMode ? $e->eq('1', 1) : $e->neq('u2.EmailExpiration', Usr::EMAIL_EXPIRATION_NEVER),
            )
        );
        $builder->setParameter(":{$k}passportType", Providercoupon::TYPE_PASSPORT, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}trustedTravelerType", Providercoupon::TYPE_TRUSTED_TRAVELER, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type", Providercoupon::TYPE_INSURANCE_CARD, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_VISA . "Type", Providercoupon::TYPE_VISA, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type", Providercoupon::TYPE_DRIVERS_LICENSE, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type", Providercoupon::TYPE_PRIORITY_PASS, \PDO::PARAM_INT);

        if (!$this->calendarMode) {
            $builder->setParameter(":{$k}emailNdr", EMAIL_NDR, \PDO::PARAM_INT);
        }
        $builder->setParameter(":{$k}accountLevelBusiness", ACCOUNT_LEVEL_BUSINESS, \PDO::PARAM_INT);
        $builder->setParameter(":{$k}accessLevel", ACCESS_ADMIN, \PDO::PARAM_INT);

        $filter = $this->getFilter();

        if (is_callable($filter)) {
            $filter($builder, $k, self::TARGET_BUSINESS, self::KIND_COUPON);
        } else {
            $this->addUserFilter($builder, $k);
            $this->addAccountFilter($builder, $k, true);
            $this->addStartAndEndUsersFilter($builder, $k);
            $this->addCouponDateFilter($builder, $k, 'u2');
        }

        return $builder;
    }

    private function addCouponDateFilter(QueryBuilder $builder, string $pref, string $userAlias): void
    {
        $values = [
            ":{$pref}passportType",
            ":{$pref}" . Providercoupon::FIELD_KEY_INSURANCE_CARD . "Type",
            ":{$pref}" . Providercoupon::FIELD_KEY_VISA . "Type",
            ":{$pref}" . Providercoupon::FIELD_KEY_DRIVERS_LICENSE . "Type",
            ":{$pref}" . Providercoupon::FIELD_KEY_PRIORITY_PASS . "Type",
        ];
        $e = $builder->expr();
        $builder->andWhere(
            $e->or(
                $e->and(
                    $e->in('a.TypeID', $values),
                    $this->calendarMode ? $e->eq('1', 1) : $this->getPassportDateFilter($builder, $pref)
                ),
                $e->and(
                    $e->or(
                        $e->isNull('a.TypeID'),
                        $e->and(
                            $e->isNotNull('a.TypeID'),
                            $e->notIn('a.TypeID', $values),
                        )
                    ),
                    $this->getBalanceDateFilter($builder, 'a', $userAlias)
                )
            )
        );
        $builder->setParameter(":{$pref}passportType", Providercoupon::TYPE_PASSPORT, \PDO::PARAM_INT);
    }

    private function addProviderFilter(QueryBuilder $builder, string $pref, bool $nullableState = true): void
    {
        $e = $builder->expr();
        $providerFilters = [
            $e->gte('p.State', ":{$pref}providerStateEnabled"),
        ];
        $builder->setParameter(":{$pref}providerStateEnabled", PROVIDER_ENABLED, \PDO::PARAM_INT);

        if ($nullableState) {
            $providerFilters[] = $e->isNull('p.State');
        }

        if ($this->isAllowTestProvider()) {
            $providerFilters[] = $e->eq('p.State', ":{$pref}providerStateTest");
            $builder->setParameter(":{$pref}providerStateTest", PROVIDER_TEST, \PDO::PARAM_INT);
        }
        $builder->andWhere($e->or(...$providerFilters));
    }

    private function addUserFilter(QueryBuilder $builder, string $pref): void
    {
        if ($this->usersIds) {
            $builder->andWhere(
                $builder->expr()->in('a.UserID', ":{$pref}usersIds")
            );
            $builder->setParameter(":{$pref}usersIds", $this->usersIds, Connection::PARAM_INT_ARRAY);
        }
    }

    private function addAccountFilter(QueryBuilder $builder, string $pref, bool $isCoupon = false): void
    {
        if ($this->accountIds) {
            if ($isCoupon) {
                $builder->andWhere('0 = 1');
            } else {
                $builder->andWhere(
                    $builder->expr()->in('a.AccountID', ":{$pref}accountsIds")
                );
                $builder->setParameter(":{$pref}accountsIds", $this->accountIds, Connection::PARAM_INT_ARRAY);
            }
        }
    }

    private function addBalanceDateFilter(QueryBuilder $builder, string $targetAlias, string $userAlias): void
    {
        $builder->andWhere($this->getBalanceDateFilter($builder, $targetAlias, $userAlias));
    }

    private function getBalanceDateFilter(QueryBuilder $builder, string $targetAlias, string $userAlias)
    {
        $e = $builder->expr();

        if ($this->accountIds || $this->calendarMode) {
            return $e->isNotNull("{$targetAlias}.ExpirationDate");
            //            return $e->eq(1, 1);
        }

        return $e->or(
            $e->and(
                $e->eq("{$userAlias}.EmailExpiration", Usr::EMAIL_EXPIRATION_90_60_30_EVERY_DAY_7),
                $e->or(
                    ...array_map(
                        fn ($days) => $this->expirationFilter($days . ' DAY', $targetAlias),
                        self::BALANCE_NOTIFICATION_DAYS_LAST_WEEK
                    )
                )
            ),
            $e->and(
                $e->eq("{$userAlias}.EmailExpiration", Usr::EMAIL_EXPIRATION_90_60_30_7),
                $e->or(
                    ...array_map(
                        fn ($days) => $this->expirationFilter($days . ' DAY', $targetAlias),
                        self::BALANCE_NOTIFICATION_DAY_SEVEN_DAYS_BEFORE
                    )
                )
            ),
        );
    }

    private function getPassportDateFilter(QueryBuilder $builder, string $pref)
    {
        return $builder->expr()->or(
            $this->expirationAlternativeFilter(self::PASSPORT_NOTICES_MONTHS[0] . ' MONTH', $builder, $pref . 1),
            $this->expirationAlternativeFilter(self::PASSPORT_NOTICES_MONTHS[1] . ' MONTH', $builder, $pref . 2),
            $this->expirationAlternativeFilter(self::PASSPORT_NOTICES_MONTHS[2] . ' MONTH', $builder, $pref . 3)
        );
    }

    private function expirationAlternativeFilter(string $periodStart, QueryBuilder $builder, string $pref, string $alias = 'a'): string
    {
        $builder->setParameter(
            ":{$pref}from",
            (clone $this->startDate)->modify(sprintf('+%s', $periodStart))->format("Y-m-d"),
            \PDO::PARAM_STR
        );
        $builder->setParameter(
            ":{$pref}to",
            (clone $this->startDate)->modify(sprintf('+%s +1 day', $periodStart))->format("Y-m-d"),
            \PDO::PARAM_STR
        );

        return "({$alias}.ExpirationDate >= :{$pref}from AND {$alias}.ExpirationDate < :{$pref}to)";
    }

    private function expirationFilter(string $periodStart, string $alias = 'a'): string
    {
        $date = $this->now();

        return "({$alias}.ExpirationDate >= ADDDATE( DATE({$date}), INTERVAL $periodStart) AND {$alias}.ExpirationDate < ADDDATE( ADDDATE( DATE({$date}), INTERVAL $periodStart), INTERVAL 1 DAY ))";
    }

    private function now(): string
    {
        return sprintf("'%s'", $this->startDate->format('Y-m-d'));
    }

    private function addStartAndEndUsersFilter(QueryBuilder $builder, string $pref): void
    {
        $e = $builder->expr();

        if (isset($this->startUser)) {
            $builder->andWhere(
                $e->gte('a.UserID', ":{$pref}startUser")
            );
            $builder->setParameter(":{$pref}startUser", $this->startUser, \PDO::PARAM_INT);
        }

        if (isset($this->endUser)) {
            $builder->andWhere(
                $e->lt('a.UserID', ":{$pref}endUser")
            );
            $builder->setParameter(":{$pref}endUser", $this->endUser, \PDO::PARAM_INT);
        }
    }

    private function documentModify(Expire $expire, ?string $locale): Expire
    {
        if (!in_array((int) $expire->TypeID, [
            Providercoupon::TYPE_INSURANCE_CARD,
            Providercoupon::TYPE_VISA,
            Providercoupon::TYPE_DRIVERS_LICENSE,
            Providercoupon::TYPE_PRIORITY_PASS,
        ], true)) {
            return $expire;
        }

        $accountFields = $this->accountListMapper->formatDocumentProperties([
            'DisplayName' => $expire->Name,
            'ExpirationDateTime' => $expire->ExpirationDate,
            'CustomFields' => json_decode($expire->CustomFields, true),
            'Properties' => [],
        ], $locale);

        $expire->Name = $accountFields['DisplayName'];

        return $expire;
    }
}
