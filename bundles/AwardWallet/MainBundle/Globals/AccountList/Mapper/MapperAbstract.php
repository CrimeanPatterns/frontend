<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ProgramStatusResolver;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Security\Utils;
use AwardWallet\MainBundle\Security\Voter\SessionVoter;
use AwardWallet\MainBundle\Service\BalanceFormatter;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use Clock\ClockInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class MapperAbstract implements MapperInterface, TranslationContainerInterface
{
    public const MAP_COLLECTION = 1;

    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    protected ProviderTranslator $providerTranslator;

    /**
     * @var LocalizeService
     */
    protected $localizer;

    /** @var BalanceFormatter */
    protected $balanceFormatter;

    /** @var DateTimeIntervalFormatter */
    protected $intervalFormatter;
    /**
     * @var string
     */
    protected $root;
    /**
     * @var AccountRepository
     */
    protected $accountRep;
    /**
     * @var ElitelevelRepository
     */
    protected $elRep;
    /**
     * @var ProviderRepository
     */
    protected $provRep;
    /**
     * @var ExpirationDateResolver
     */
    protected $expirationResolver;
    /**
     * @var ProgramStatusResolver
     */
    protected $statusResolver;
    /**
     * @var SessionVoter
     */
    protected $sessionVoter;

    /** @var array */
    protected $localizedCountries;

    protected PropertyFormatter $propertyFormatter;
    protected ClockInterface $clock;
    protected MileValueCards $mileValueCards;
    protected MileValueService $mileValueService;

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
        PropertyFormatter $propertyFormatter,
        ClockInterface $clock,
        MileValueCards $mileValueCards,
        MileValueService $mileValueService
    ) {
        global $sPath;

        $this->em = $em;
        $this->connection = $this->em->getConnection();
        $this->translator = $translator;
        $this->providerTranslator = $providerTranslator;
        $this->localizer = $localizer;
        $this->balanceFormatter = $balanceFormatter;
        $this->propertyFormatter = $propertyFormatter;
        $this->accountRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $this->elRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
        $this->provRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $this->root = $sPath;
        $this->sessionVoter = $sessionVoter;
        $this->intervalFormatter = $intervalFormatter;
        $this->expirationResolver = $expirationResolver;
        $this->statusResolver = $statusResolver;
        $this->clock = $clock;
        $this->mileValueCards = $mileValueCards;
        $this->mileValueService = $mileValueService;
    }

    abstract public function map(MapperContext $mapperContext, $accountID, $accountFields, $accountsIds);

    public function alterTemplate(MapperContext $mapperContext)
    {
    }

    public static function getTranslationMessages()
    {
        return [
            new Message('award.account.list.balance.error'),
            new Message('please-upgrade'),
            new Message('award.account.list.balance.upgrade'),
            new Message('award.account.list.goal.progress-tip'),
        ];
    }

    /**
     * @param $ids array of ids ['accounts' => x, 'coupons' => y]
     */
    protected function initPermissions(MapperContext $mapperContext, $ids)
    {
        $mapperContext->rights = [
            'account' => [],
            'coupon' => [],
        ];
        /** @var Usr $user */
        $user = $mapperContext->options->get(Options::OPTION_USER);

        // accounts
        if (isset($ids['accounts']) && sizeof($ids['accounts'])) {
            $sql = "
                SELECT
                       a.AccountID AS ID,
                       a.UserID,
                       a.SavePassword,
                       t.*,
                       p.ProviderID,
                       p.CanCheck,
                       p.CanCheckItinerary,
                       p.CheckInBrowser,
                       p.AutoLogin,
                       p.AutologinV3,
                       p.State as ProviderState,
			           a.ErrorCode,
			           a.DisableClientPasswordAccess
                FROM   Account a
                       LEFT OUTER JOIN
                              ( SELECT sh.AccountID,
                                      ua.*
                              FROM    AccountShare sh
                                      JOIN UserAgent ua
                                      ON      sh.UserAgentID    = ua.UserAgentID
                                              AND ua.AgentID    = :agentId
                                              AND ua.IsApproved = 1
                              WHERE   sh.AccountID             IN (:accountIds)
                              )
                              t
                       ON     t.AccountID = a.AccountID
                       LEFT OUTER JOIN Provider p
                       ON 	  p.ProviderID = a.ProviderID
                WHERE  a.AccountID       IN (:accountIds)
            ";
            $fields = $this->connection->executeQuery(
                $sql,
                [
                    ':agentId' => $user->getUserid(),
                    ':accountIds' => \array_values($ids['accounts']),
                ],
                [
                    ':agentId' => \PDO::PARAM_INT,
                    ':accountIds' => Connection::PARAM_INT_ARRAY,
                ]
            )->fetchAll(\PDO::FETCH_ASSOC);

            $isImpersonatedAdminHelper = function () {
                if (empty(getSymfonyContainer()->get("request_stack")->getMasterRequest())) {
                    return false;
                }

                return getSymfonyContainer()->get("security.authorization_checker")->isGranted("ROLE_IMPERSONATED_FULLY");
            };

            $isImpersonatedHelper = function () {
                if (empty(getSymfonyContainer()->get("request_stack")->getMasterRequest())) {
                    return false;
                }

                return getSymfonyContainer()->get("security.authorization_checker")->isGranted("ROLE_IMPERSONATED");
            };

            $userImpersonated = $isImpersonatedHelper() && !$isImpersonatedAdminHelper();

            if ($userImpersonated) {
                $pvData = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Passwordvault::class)->getAccountsUsers(array_map('intval', $ids['accounts']));

                foreach ($fields as &$accountData) {
                    $accountId = $accountData['ID'];
                    $accountData['PasswordVault'] = $pvData[$accountId] ?? ['Login' => [], 'UserID' => []];
                }
                unset($accountData);
            }

            $fullRights = function ($fields) use ($user) {
                if ($user->getUserid() == $fields['UserID']) {
                    return true;
                }

                if (!isset($fields['AccessLevel'])) {
                    return false;
                }

                return in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]);
            };
            // synthetic right to update immediately or to obtain update ability in future by requesting password from vault
            $Updates = [
                'eligible' => [],
                'one' => [],
                'group' => [],
                'edit' => [],
                'trips' => [],
            ];

            foreach ([
                'read_password', 'read_number', 'read_balance', 'read_extproperties',
                'edit', 'delete', 'autologin',
                'update', 'eligibleUpdate', 'oneUpdate', 'groupUpdate', 'editUpdate', 'tripsUpdate',
                'autologinExtension', 'autologinExtensionV3',
            ] as $permission) {
                $f = &$mapperContext->rights['account'][$permission];
                $f = $this->getDefaultPermissionValues($ids['accounts']);

                switch ($permission) {
                    case 'read_password':
                    case 'edit':
                    case 'delete':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = $fullRights($fs);
                        }

                        break;

                    case 'autologin':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = $fullRights($fs) && !empty($fs['ProviderID']) && !empty($fs['AutoLogin']) && $fs['AutoLogin'] !== '0';
                        }

                        break;

                    case 'read_number':
                        foreach ($fields as $fs) {
                            $r = false;

                            if ($user->getUserid() == $fs['UserID']) {
                                $r = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $r = false;
                                } else {
                                    $r = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL, ACCESS_READ_NUMBER]);
                                }
                            }

                            $f[$fs['ID']] = $r;
                        }

                        break;

                    case 'read_balance':
                        foreach ($fields as $fs) {
                            $r = false;

                            if ($user->getUserid() == $fs['UserID']) {
                                $r = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $r = false;
                                } else {
                                    $r = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL, ACCESS_READ_BALANCE_AND_STATUS]);
                                }
                            }

                            $f[$fs['ID']] = $r;
                        }

                        break;

                    case 'read_extproperties':
                        foreach ($fields as $fs) {
                            $r = false;

                            if ($user->getUserid() == $fs['UserID']) {
                                $r = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $r = false;
                                } else {
                                    $r = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL]);
                                }
                            }

                            $f[$fs['ID']] = $r;
                        }

                        break;

                    case 'update':
                        foreach ($fields as $fs) {
                            if ($user->getUserid() == $fs['UserID']) {
                                $userCanCheckByAccess = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $userCanCheckByAccess = false;
                                } else {
                                    $userCanCheckByAccess = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER]);
                                }
                            }

                            $providerCanCheck = $fs['CanCheck'] == '1';
                            $providerCanCheckItinerary = $fs['CanCheckItinerary'] == 1;
                            $providerCanCheckByExtensionOnly = PROVIDER_CHECKING_EXTENSION_ONLY == $fs['ProviderState'];

                            $userCanCheckByExtensionHelper = function () use ($fs) {
                                return $this->sessionVoter && isset($fs['ProviderID']) ?
                                    (
                                        $this->sessionVoter->canCheckByBrowserExt(null, $this->provRep->find($fs['ProviderID']))
                                    ) :
                                    false;
                            };

                            // disable update for extension-only accounts, if extension disabled
                            $canCheck = $userCanCheckByAccess && !($providerCanCheckByExtensionOnly && !$userCanCheckByExtensionHelper());

                            $oneUpdate = $canCheck && $providerCanCheck;
                            $editUpdate = $canCheck && $providerCanCheck;
                            $groupUpdate = $canCheck && $providerCanCheck;
                            $tripsUpdate = $canCheck && $providerCanCheckItinerary;

                            $impersonator = Utils::getImpersonator(getSymfonyContainer()->get("security.token_storage")->getToken());
                            $canCheck = $groupUpdate
                                && ($userImpersonated && $providerCanCheckByExtensionOnly && $impersonator) ?
                                (
                                    in_array($impersonator, $fs['PasswordVault']['Login'], true)
                                    || in_array($impersonator, $fs['PasswordVault']['UserID'], true)
                                ) : true;

                            $f[$fs['ID']] = $canCheck;
                            $Updates['eligible'][$fs['ID']] = $oneUpdate;
                            $Updates['one'][$fs['ID']] = $oneUpdate;
                            $Updates['edit'][$fs['ID']] = $editUpdate;
                            $Updates['group'][$fs['ID']] = $groupUpdate;
                            $Updates['trips'][$fs['ID']] = $tripsUpdate;
                        }

                        break;

                        // this access right means "Show update button"
                        // for impersonated users, to request passwords
                    case 'eligibleUpdate':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = isset($Updates['eligible'][$fs['ID']]) && $Updates['eligible'][$fs['ID']];
                        }

                        break;

                    case 'oneUpdate':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = isset($Updates['one'][$fs['ID']]) && $Updates['one'][$fs['ID']];
                        }

                        break;

                    case 'groupUpdate':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = isset($Updates['group'][$fs['ID']]) && $Updates['group'][$fs['ID']];
                        }

                        break;

                    case 'editUpdate':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = isset($Updates['edit'][$fs['ID']]) && $Updates['edit'][$fs['ID']];
                        }

                        break;

                    case 'tripsUpdate':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = isset($Updates['trips'][$fs['ID']]) && $Updates['trips'][$fs['ID']];
                        }

                        break;

                    case "autologinExtension":
                        foreach ($fields as $fs) {
                            $r = $fullRights($fs);
                            $f[$fs['ID']] = $r
                                && in_array($fs['AutoLogin'], [AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED])
                                && !$fs['DisableClientPasswordAccess']
                            ;
                        }

                        break;

                    case "autologinExtensionV3":
                        foreach ($fields as $fs) {
                            $r = $fullRights($fs);
                            $f[$fs['ID']] = $r
                                && in_array($fs['AutoLogin'], [AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED])
                                && !$fs['DisableClientPasswordAccess']
                                && $fs['AutologinV3'];
                        }

                        break;
                }
            }
        }

        // coupons
        if (isset($ids['coupons']) && sizeof($ids['coupons'])) {
            $sql = "
                SELECT
                       a.ProviderCouponID AS ID,
                       a.*,
                       t.*
                FROM   ProviderCoupon a
                       LEFT OUTER JOIN
                              ( SELECT sh.ProviderCouponID,
                                      ua.*
                              FROM    ProviderCouponShare sh
                                      JOIN UserAgent ua
                                      ON      sh.UserAgentID    = ua.UserAgentID
                                              AND ua.AgentID    = :agentId
                                              AND ua.IsApproved = 1
                              WHERE   sh.ProviderCouponID             IN (:couponIds)
                              )
                              t
                       ON     t.ProviderCouponID = a.ProviderCouponID
                WHERE  a.ProviderCouponID       IN (:couponIds)
            ";
            $fields = $this->connection->executeQuery(
                $sql,
                [
                    ':agentId' => $user->getUserid(),
                    ':couponIds' => \array_values($ids['coupons']),
                ],
                [
                    ':agentId' => \PDO::PARAM_INT,
                    ':couponIds' => Connection::PARAM_INT_ARRAY,
                ]
            )->fetchAll(\PDO::FETCH_ASSOC);
            $fullRights = function ($fields) use ($user) {
                if ($user->getUserid() == $fields['UserID']) {
                    return true;
                }

                if (!isset($fields['AccessLevel'])) {
                    return false;
                }

                return in_array($fields['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY]);
            };

            foreach (['read', 'read_expiration', 'read_value', 'edit', 'delete'] as $permission) {
                $f = &$mapperContext->rights['coupon'][$permission];
                $f = $this->getDefaultPermissionValues($ids['coupons']);

                switch ($permission) {
                    case 'read':
                        foreach ($fields as $fs) {
                            $r = false;

                            if ($user->getUserid() == $fs['UserID']) {
                                $r = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $r = false;
                                } else {
                                    $r = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_BOOKING_VIEW_ONLY, ACCESS_READ_ALL, ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS]);
                                }
                            }

                            $f[$fs['ID']] = $r;
                        }

                        break;

                    case 'read_expiration':
                        foreach ($fields as $fs) {
                            $r = false;

                            if ($user->getUserid() == $fs['UserID']) {
                                $r = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $r = false;
                                } else {
                                    $r = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_READ_ALL]);
                                }
                            }

                            $f[$fs['ID']] = $r;
                        }

                        break;

                    case 'read_value':
                        foreach ($fields as $fs) {
                            $r = false;

                            if ($user->getUserid() == $fs['UserID']) {
                                $r = true;
                            } else {
                                if (!isset($fs['AccessLevel'])) {
                                    $r = false;
                                } else {
                                    $r = in_array($fs['AccessLevel'], [ACCESS_WRITE, ACCESS_ADMIN, ACCESS_BOOKING_MANAGER, ACCESS_READ_ALL]);
                                }
                            }

                            $f[$fs['ID']] = $r;
                        }

                        break;

                    case 'edit':
                    case 'delete':
                        foreach ($fields as $fs) {
                            $f[$fs['ID']] = $fullRights($fs);
                        }

                        break;
                }
            }
        }
    }

    /**
     * @param $ids array of ids ['accounts' => x, 'coupons' => y]
     */
    protected function initShares(MapperContext $mapperContext, $ids)
    {
        $user = $mapperContext->options->get(Options::OPTION_USER);
        $mapperContext->shares = [
            'account' => [],
            'coupon' => [],
        ];

        // accounts
        if (isset($ids['accounts']) && sizeof($ids['accounts'])) {
            $filter = [];

            foreach ($ids['accounts'] as $k => $id) {
                $filter[$k] = "'" . $id . "'";
            }
            $filter = implode(", ", $filter);
            $sql = "
                SELECT sh.AccountID, sh.UserAgentID
                FROM    AccountShare sh
                JOIN UserAgent ua
                    ON      sh.UserAgentID    = ua.UserAgentID
                    AND ua.ClientID    = :clientId
                    AND ua.IsApproved = 1
                WHERE  sh.AccountID       IN (:accountIds)
            ";
            $fields = $this->connection->executeQuery(
                $sql,
                [
                    ':clientId' => $user->getUserid(),
                    ':accountIds' => \array_values($ids['accounts']),
                ],
                [
                    ':clientId' => \PDO::PARAM_INT,
                    ':accountIds' => Connection::PARAM_INT_ARRAY,
                ]
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($fields as $field) {
                if (!array_key_exists($field['AccountID'], $mapperContext->shares['account'])) {
                    $mapperContext->shares['account'][$field['AccountID']] = [];
                }
                $mapperContext->shares['account'][$field['AccountID']][] = intval($field['UserAgentID']);
            }
        }

        // coupons
        if (isset($ids['coupons']) && sizeof($ids['coupons'])) {
            $sql = "
                SELECT  sh.ProviderCouponID, sh.UserAgentID
                FROM    ProviderCouponShare sh
                JOIN UserAgent ua
                    ON      sh.UserAgentID    = ua.UserAgentID
                    AND ua.ClientID    = :clientId
                    AND ua.IsApproved = 1
                WHERE  sh.ProviderCouponID       IN (:couponIds)
            ";
            $fields = $this->connection->executeQuery(
                $sql,
                [
                    ':clientId' => $user->getUserid(),
                    ':couponIds' => \array_values($ids['coupons']),
                ],
                [
                    ':clientId' => \PDO::PARAM_INT,
                    ':couponIds' => Connection::PARAM_INT_ARRAY,
                ]
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($fields as $field) {
                if (!array_key_exists($field['ProviderCouponID'], $mapperContext->shares['coupon'])) {
                    $mapperContext->shares['coupon'][$field['ProviderCouponID']] = [];
                }
                $mapperContext->shares['coupon'][$field['ProviderCouponID']][] = intval($field['UserAgentID']);
            }
        }
    }

    protected function array_intersect_key_recursive(array $array1, array $array2)
    {
        $array1 = array_intersect_key($array1, $array2);

        foreach ($array1 as $key => &$value) {
            if (is_array($value) && is_array($subTemplate = $array2[$key])) {
                if (isset($subTemplate[0]) && self::MAP_COLLECTION === $subTemplate[0]) {
                    unset($subTemplate[0]);

                    foreach ($value as $collectionKey => &$collectionItem) {
                        $collectionItem = $this->array_intersect_key_recursive($collectionItem, $subTemplate);
                    }
                    unset($collectionItem);
                } else {
                    $value = $this->array_intersect_key_recursive($value, $array2[$key]);
                }
            }
        }

        return $array1;
    }

    protected function normalizeBalance($balance)
    {
        if (is_null($balance)) {
            return $balance;
        }

        return html_entity_decode(preg_replace("/,+/", ",", $balance));
    }

    protected function normalizeComment($comment)
    {
        return str_replace("\n", "<br />", $comment);
    }

    private function getDefaultPermissionValues($ids)
    {
        return array_fill_keys($ids, false);
    }
}
