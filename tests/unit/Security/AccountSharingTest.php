<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\AccountList\Resolver\ExpirationDateResolver;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\MobileExtensionList;
use AwardWallet\Tests\Unit\BaseUserTest;
use Herrera\Version\Parser;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class AccountSharingTest extends BaseUserTest
{
    public const ABSENT = 'absent';

    /**
     * @var Account
     */
    private $friendAccount;

    /**
     * @var Providercoupon
     */
    private $friendCoupon;

    private $userAgentId;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var AccountListManager
     */
    private $listManager;
    private ?\DateTime $lastChangeDate;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->lastChangeDate = (new \DateTime('-1 day 00:00:00'));
        parent::__construct($name, $data, $dataName);
    }

    public function _before()
    {
        parent::_before();

        $myFriend = $this->aw->createAwUser();

        $providerCode = "p" . bin2hex(random_bytes(7));
        $providerId = $this->aw->createAwProvider(null, $providerCode, ["CanCheckItinerary" => 1, "AutoLogin" => 1, "MobileAutoLogin" => MOBILE_AUTOLOGIN_EXTENSION]);
        $this->db->haveInDatabase("ProviderProperty", ["ProviderID" => $providerId, "Kind" => PROPERTY_KIND_NUMBER, "Code" => "Number", "Name" => "Number", "SortIndex" => 10]);
        $this->db->haveInDatabase("ProviderProperty", ["ProviderID" => $providerId, "Kind" => PROPERTY_KIND_STATUS, "Code" => "Status", "Name" => "Status", "SortIndex" => 10]);
        $this->db->haveInDatabase("ProviderProperty", ["ProviderID" => $providerId, "Kind" => PROPERTY_KIND_NAME, "Code" => "Name", "Name" => "Name", "SortIndex" => 10]);
        $this->db->haveInDatabase("ProviderProperty", ["ProviderID" => $providerId, "Code" => "Property1", "Name" => "Property 1", "SortIndex" => 10]);
        $eliteLevelId = $this->db->haveInDatabase("EliteLevel", ["ProviderID" => $providerId, "Rank" => 1, "ByDefault" => 1, "Name" => "Level 1"]);
        $this->db->haveInDatabase("TextEliteLevel", ["EliteLevelID" => $eliteLevelId, "ValueText" => "Level 1"]);
        $this->db->haveInDatabase('ProviderPhone', [
            'ProviderID' => $providerId,
            'EliteLevelID' => $eliteLevelId,
            'Phone' => '+100500600700',
            'Valid' => 1,
        ]);
        $this->mockService(MobileExtensionList::class, self::makeEmpty(MobileExtensionList::class, [
            'getMobileExtensionsList' => [$providerCode],
        ]));

        // Account
        $accountId = $this->aw->createAwAccount($myFriend, $providerCode, "login1", "password1", ['Balance' => 100, "LastBalance" => 99, "SuccessCheckDate" => "2010-01-01", 'LastChangeDate' => $this->lastChangeDate->format('Y-m-d'), "ExpirationDate" => "2010-01-03", "Disabled" => 1, 'SubAccounts' => 2]);

        $this->addProperty($accountId, ['ProviderID' => $providerId, 'Kind' => PROPERTY_KIND_NUMBER], 'AN223322');
        $this->addProperty($accountId, ['ProviderID' => $providerId, 'Kind' => PROPERTY_KIND_STATUS], 'Level 1');
        $this->addProperty($accountId, ['ProviderID' => $providerId, 'Kind' => PROPERTY_KIND_NAME], 'Some Beautiful Name');
        $this->addProperty($accountId, ['ProviderID' => $providerId, 'Code' => 'Property1'], 'Property1 Value');
        $this->addProperty($accountId, ['ProviderID' => null, 'Code' => 'BarCode'], '100500');
        $this->addProperty($accountId, ['ProviderID' => null, 'Code' => 'BarCodeType'], BAR_CODE_CODE_128);

        // First subaccount
        $this->db->haveInDatabase('SubAccount', ['AccountID' => $accountId, 'Code' => 'one', 'DisplayName' => 'First', 'Balance' => 10, 'LastBalance' => 11, 'LastChangeDate' => $this->lastChangeDate->format('Y-m-d'), 'ExpirationDate' => '2011-01-02']);
        // Second subaccount
        $this->db->haveInDatabase('SubAccount', ['AccountID' => $accountId, 'Code' => 'two', 'DisplayName' => 'Second', 'Balance' => 20, 'LastBalance' => 21, 'LastChangeDate' => $this->lastChangeDate->format('Y-m-d'), 'ExpirationDate' => '2012-01-02']);
        $this->friendAccount = $this->em->getRepository(Account::class)->find($accountId);

        // Coupon
        $couponId = $this->aw->createAwCoupon($myFriend, "CouponName1", "102345", "My Test Coupon", ["ExpirationDate" => "2010-01-03"]);
        $this->friendCoupon = $this->em->getRepository(Providercoupon::class)->find($couponId);

        $this->userAgentId = $this->aw->createConnection($myFriend, $this->user->getId(), true, true);
        $this->aw->createConnection($this->user->getId(), $myFriend, true, true);
        $this->db->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $this->userAgentId]);
        $this->db->haveInDatabase('ProviderCouponShare', ['ProviderCouponID' => $couponId, 'UserAgentID' => $this->userAgentId]);

        $this->authorizationChecker = $this->container->get("security.authorization_checker");
        $this->listManager = $this->container->get(AccountListManager::class);
    }

    public function _after()
    {
        $this->authorizationChecker = null;
        $this->listManager = null;
        $this->friendAccount = null;

        parent::_after();
    }

    /**
     * @dataProvider desktopDataProvider
     */
    public function testDesktopPermissions(
        $accessLevel,
        array $accountRights, array $accountFields,
        array $couponRights, array $couponFields)
    {
        $this->db->executeQuery("update UserAgent set AccessLevel = " . $accessLevel . " where UserAgentID = {$this->userAgentId}");

        $accounts = \array_values(json_decode(json_encode(
            $this->listManager->getAccountList(
                $this->container->get(OptionsFactory::class)
                    ->createDesktopListOptions(
                        (new Options())
                            ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                    )
            )
            ->getAccounts()
        ), true));

        // Account
        foreach ($accountRights as $right => $value) {
            $this->assertEquals($value, $accounts[1]['Access'][$right], "access right {$right}: expected " . json_encode($value));
        }

        foreach ($accountFields as $field => $value) {
            $this->assertEquals($value, $this->filterField($accounts[1], $field, $value), "expected " . var_export($value, true) . " in $field");
        }

        // Coupon
        foreach ($couponRights as $right => $value) {
            $this->assertEquals($value, $accounts[0]['Access'][$right], "coupon access right {$right}: expected " . json_encode($value));
        }

        foreach ($couponFields as $field => $value) {
            $this->assertEquals($value, $this->filterField($accounts[0], $field, $value), "expected " . var_export($value, true) . " in Coupon.$field");
        }
    }

    /**
     * @dataProvider votersDataProvider
     */
    public function testVoters($accessLevel, array $accountVoters, array $couponVoters)
    {
        $this->db->executeQuery("update UserAgent set AccessLevel = " . $accessLevel . " where UserAgentID = {$this->userAgentId}");

        foreach ($accountVoters as $right => $value) {
            assertEquals($value, $this->authorizationChecker->isGranted($right, $this->friendAccount), "ACCESS_LEVEL $accessLevel: account, expected $right = " . var_export($value, true));
        }

        foreach ($couponVoters as $right => $value) {
            assertEquals($value, $this->authorizationChecker->isGranted($right, $this->friendCoupon), "ACCESS_LEVEL $accessLevel: coupon, expected $right = " . var_export($value, true));
        }
    }

    public function votersDataProvider()
    {
        return [
            [
                // Access level
                ACCESS_READ_NUMBER,
                // Account voters
                [
                    'READ_PASSWORD' => false,
                    'READ_NUMBER' => true,
                    'READ_BALANCE' => false,
                    'READ_EXTPROP' => false,
                    'EDIT' => false,
                    'SAVE' => false,
                    'DELETE' => false,
                    'AUTOLOGIN' => false,
                    'UPDATE' => false,
                    'UPDATE_GROUP' => false,
                    'UPDATE_ITINERARY' => false,
                ],

                // Coupon voters
                [
                    'READ' => true,
                    'EDIT' => false,
                    'DELETE' => false,
                ],
            ],
            [
                // Access level
                ACCESS_READ_BALANCE_AND_STATUS,
                // Account voters
                [
                    'READ_PASSWORD' => false,
                    'READ_NUMBER' => true,
                    'READ_BALANCE' => true,
                    'READ_EXTPROP' => false,
                    'EDIT' => false,
                    'SAVE' => false,
                    'DELETE' => false,
                    'AUTOLOGIN' => false,
                    'UPDATE' => false,
                    'UPDATE_GROUP' => false,
                    'UPDATE_ITINERARY' => false,
                ],
                // Coupon voters
                [
                    'READ' => true,
                    'EDIT' => false,
                    'DELETE' => false,
                ],
            ],
            [
                // Access level
                ACCESS_READ_ALL,
                // Account voters
                [
                    'READ_PASSWORD' => false,
                    'READ_NUMBER' => true,
                    'READ_BALANCE' => true,
                    'READ_EXTPROP' => true,
                    'EDIT' => false,
                    'SAVE' => false,
                    'DELETE' => false,
                    'AUTOLOGIN' => false,
                    'UPDATE' => false,
                    'UPDATE_GROUP' => false,
                    'UPDATE_ITINERARY' => false,
                ],
                // Coupon voters
                [
                    'READ' => true,
                    'EDIT' => false,
                    'DELETE' => false,
                ],
            ],
            [
                // Access level
                ACCESS_WRITE,
                // Account voters
                [
                    'READ_PASSWORD' => true,
                    'READ_NUMBER' => true,
                    'READ_BALANCE' => true,
                    'READ_EXTPROP' => true,
                    'EDIT' => true,
                    'SAVE' => true,
                    'DELETE' => true,
                    'AUTOLOGIN' => true,
                    'UPDATE' => true,
                    'UPDATE_GROUP' => true,
                    'UPDATE_ITINERARY' => true,
                ],
                // Coupon voters
                [
                    'READ' => true,
                    'EDIT' => true,
                    'DELETE' => true,
                ],
            ],
        ];
    }

    public function desktopDataProvider()
    {
        return [
            [
                // Access level
                ACCESS_READ_NUMBER,
                // Account rights
                [
                    'update' => true,
                    'oneUpdate' => false,
                    'groupUpdate' => false,
                    'editUpdate' => false,
                    'tripsUpdate' => false,
                    'autologin' => false,
                    'autologinExtension' => false,
                ],
                // Account fields
                [
                    'Balance' => '',
                    'LastBalance' => '',
                    'LastChange' => '',
                    'LastUpdatedDateTs' => null,
                    'LastChangeDate' => null,
                    'LastChangeDateTs' => null,
                    'ExpirationDate' => null,
                    'ExpirationDateTs' => ExpirationDateResolver::EXPIRE_EMPTY_TS,
                    'StateBar' => '',
                    'AccountStatus' => 'Level 1',
                    'LoginFieldFirst' => 'login1',
                    'LoginFieldLast' => 'AN223322',
                    'SubAccountsArray' => null,
                ],
                // Coupon rights
                [
                    'read' => true,
                    'read_expiration' => false,
                    'read_value' => false,
                    'edit' => false,
                    'delete' => false,
                ],

                // Coupon fields
                [
                    'ExpirationDate' => null,
                    'ExpirationDateTs' => ExpirationDateResolver::EXPIRE_EMPTY_TS,
                    'LoginFieldLast' => 'My Test Coupon',
                ],
            ],
            [
                // Access level
                ACCESS_READ_BALANCE_AND_STATUS,
                // Account rights
                [
                    'update' => true,
                    'oneUpdate' => false,
                    'groupUpdate' => false,
                    'editUpdate' => false,
                    'tripsUpdate' => false,
                    'autologin' => false,
                    'autologinExtension' => false,
                ],
                // Account fields
                [
                    'Balance' => '100',
                    'LastChange' => '+1',
                    'LastUpdatedDateTs' => null,
                    'LastChangeDate' => $this->lastChangeDate->format('l, F j, Y \a\t h:i A'),
                    'LastChangeDateTs' => $this->lastChangeDate->getTimestamp(),
                    'ExpirationDate' => '1/3/10',
                    'ExpirationDateTs' => 1262476800,
                    'StateBar' => '',
                    'AccountStatus' => 'Level 1',
                    'LoginFieldFirst' => null,
                    'LoginFieldLast' => null,
                    'SubAccountsArray' => [
                        ['Balance' => '10', 'LastBalance' => '11', 'ExpirationDate' => '1/2/11', 'ExpirationDateTs' => 1293926400],
                        ['Balance' => '20', 'LastBalance' => '21', 'ExpirationDate' => '1/2/12', 'ExpirationDateTs' => 1325462400],
                    ],
                ],
                // Coupon rights
                [
                    'read' => true,
                    'read_expiration' => false,
                    'read_value' => false,
                    'edit' => false,
                    'delete' => false,
                ],
                // Coupon fields
                [
                    'ExpirationDate' => null,
                    'ExpirationDateTs' => ExpirationDateResolver::EXPIRE_EMPTY_TS,
                    'LoginFieldLast' => 'My Test Coupon',
                ],
            ],
            [
                // Access level
                ACCESS_READ_ALL,
                // Account rights
                [
                    'update' => true,
                    'oneUpdate' => false,
                    'groupUpdate' => false,
                    'editUpdate' => false,
                    'tripsUpdate' => false,
                    'autologin' => false,
                    'autologinExtension' => false,
                ],
                // Account fields
                [
                    'Balance' => '100',
                    'LastChange' => '+1',
                    'LastUpdatedDateTs' => $this->lastChangeDate->getTimestamp(),
                    'LastChangeDate' => $this->lastChangeDate->format('l, F j, Y \a\t h:i A'),
                    'LastChangeDateTs' => $this->lastChangeDate->getTimestamp(),
                    'ExpirationDate' => '1/3/10',
                    'ExpirationDateTs' => 1262476800,
                    'StateBar' => 'disabled',
                    'AccountStatus' => 'Level 1',
                    'LoginFieldFirst' => 'login1',
                    'LoginFieldLast' => 'AN223322',
                    'SubAccountsArray' => [
                        ['Balance' => '10', 'LastBalance' => '11', 'ExpirationDate' => '1/2/11', 'ExpirationDateTs' => 1293926400],
                        ['Balance' => '20', 'LastBalance' => '21', 'ExpirationDate' => '1/2/12', 'ExpirationDateTs' => 1325462400],
                    ],
                ],
                // Coupon rights
                [
                    'read' => true,
                    'read_expiration' => true,
                    'read_value' => true,
                    'edit' => false,
                    'delete' => false,
                ],
                // Coupon fields
                [
                    'ExpirationDate' => '1/3/10',
                    'ExpirationDateTs' => 1262476800,
                    'LoginFieldLast' => 'My Test Coupon',
                ],
            ],
            [
                // Access level
                ACCESS_WRITE,
                // Account rights
                [
                    'update' => true,
                    'oneUpdate' => true,
                    'groupUpdate' => true,
                    'editUpdate' => true,
                    'tripsUpdate' => true,
                    'autologin' => true,
                    'autologinExtension' => false,
                ],
                // Account fields
                [
                    'Balance' => '100',
                    'LastUpdatedDateTs' => $this->lastChangeDate->getTimestamp(),
                    'LastChangeDate' => $this->lastChangeDate->format('l, F j, Y \a\t h:i A'),
                    'LastChangeDateTs' => $this->lastChangeDate->getTimestamp(),
                    'ExpirationDate' => '1/3/10',
                    'ExpirationDateTs' => 1262476800,
                    'StateBar' => 'disabled',
                    'AccountStatus' => 'Level 1',
                    'LoginFieldFirst' => 'login1',
                    'LoginFieldLast' => 'AN223322',
                    'SubAccountsArray' => [
                        ['Balance' => '10', 'LastBalance' => '11', 'ExpirationDate' => '1/2/11', 'ExpirationDateTs' => 1293926400],
                        ['Balance' => '20', 'LastBalance' => '21', 'ExpirationDate' => '1/2/12', 'ExpirationDateTs' => 1325462400],
                    ],
                ],
                // Coupon rights
                [
                    'read' => true,
                    'read_expiration' => true,
                    'read_value' => true,
                    'edit' => true,
                    'delete' => true,
                ],
                // Coupon fields
                [
                    'ExpirationDate' => '1/3/10',
                    'ExpirationDateTs' => 1262476800,
                    'LoginFieldLast' => 'My Test Coupon',
                ],
            ],
        ];
    }

    /**
     * @dataProvider mobileDataProvider
     */
    public function testMobilePermissions(
        $accessLevel,
        array $accountRights, array $accountFields,
        array $couponRights, array $couponFields
    ) {
        $this->db->executeQuery("update UserAgent set AccessLevel = " . $accessLevel . " where UserAgentID = {$this->userAgentId}");
        $this->db->executeQuery("update Account set TotalBalance = Balance where AccountID = {$this->friendAccount->getAccountid()}");
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $this->em->flush($this->user);

        $this->container->get('translator_hijacker')->setContext('mobile');
        $this->container->get('aw.api.versioning')
            ->setVersionsProvider(new MobileVersions('web'))
            ->setVersion(Parser::toVersion('3.17.0'));

        $accounts = $this->listManager->getAccountList(
            $this->container->get(OptionsFactory::class)
                ->createMobileOptions()
                ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                ->set(Options::OPTION_FORMATTER, $this->container->get(MobileMapper::class))
        );

        $account = $accounts['a' . $this->friendAccount->getAccountid()];
        $coupon = $accounts['c' . $this->friendCoupon->getProvidercouponid()];

        // Account
        foreach ($accountRights as $right => $value) {
            assertEquals($value, $account['Access'][$right], "account access right {$right}: expected " . json_encode($value));
        }

        foreach ($accountFields['properties'] as $field => $value) {
            $this->assertEquals($value, $this->filterField($account, $field, $value), "expected " . var_export($value, true) . " in Account.$field");
        }

        $this->assertBlocks($accountFields['blocks'], $account['Blocks'], "ACCESS_LEVEL {$accessLevel}, account blocks, ");

        if (isset($accountFields['subaccounts'])) {
            foreach ($accountFields['subaccounts'] as $subAccountFields) {
                $subAccount = $this->findArrayByCriteria($subAccountFields['criteria'], $account['SubAccountsArray']);

                $this->assertBlocks($subAccountFields['blocks'], $subAccount['Blocks'], "ACCESS_LEVEL {$accessLevel}, subaccount" . json_encode($subAccountFields['criteria']) . ", ");

                foreach ($subAccountFields['properties'] as $field => $value) {
                    $this->assertEquals($value, $this->filterField($subAccount, $field, $value), "expected " . var_export($value, true) . " in SubAccount" . json_encode($subAccountFields['criteria']) . ".$field");
                }
            }
        }

        // Coupon
        foreach ($couponRights as $right => $value) {
            assertEquals($value, $account['Access'][$right], "coupon access right {$right}: expected " . json_encode($value));
        }

        foreach ($couponFields['properties'] as $field => $value) {
            assertEquals($value, $this->filterField($coupon, $field, $value), "expected " . var_export($value, true) . " in Coupon.$field");
        }
        //
        //        var_dump(array_shift($accounts));
        //        die();

        $this->assertBlocks($couponFields['blocks'], $coupon['Blocks'], "ACCESS_LEVEL {$accessLevel}, coupon blocks, ");
    }

    public function mobileDataProvider()
    {
        return [
            [
                // Access level
                ACCESS_WRITE,
                // Account rights
                [
                    "edit" => true,
                    "delete" => true,
                    "autologin" => true,
                    "update" => true,
                ],
                // Account fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'Login' => 'login1',
                        'Number' => 'AN223322',
                        'BarCode' => '11010011100110010001001000100110011011001100110011101001100011101011',

                        'Balance' => '100',
                        'LastBalance' => '99',
                        'LastChange' => '+1',
                        'LastChangeRaw' => 1,

                        'ExpirationDate' => 1262476800,
                        'ExpirationState' => 'expired',

                        'LastChangeDate' => $this->lastChangeDate->getTimestamp(),

                        'Disabled' => ['Title' => 'Disabled account. This account is not being updated by AwardWallet.'],
                        'Notice' => ['Title' => 'Error occurred'],
                        'Error' => true,

                        'Autologin' => [
                            'desktopExtension' => false,
                            'mobileExtension' => true,
                            'loginUrl' => new Some(),
                        ],
                        'Phones' => [
                            [
                                'name' => 'Level 1',
                                'phone' => '+100500600700',
                                'region' => null,
                            ],
                        ],
                    ],
                    'blocks' => [
                        [
                            'Kind' => 'balance',
                            'Name' => 'Balance',
                            'Val' => [
                                'LastChange' => '+1',
                                'LastChangeRaw' => 1,
                                'Balance' => '100',
                                'BalanceRaw' => 100,
                            ],
                        ],
                        [
                            'Kind' => 'disabled',
                            'Val' => ['Title' => 'Disabled account. This account is not being updated by AwardWallet.'],
                        ],
                        [
                            'Kind' => 'notice',
                            'Val' => ['Title' => 'Error occurred'],
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Account Owner',
                            'Val' => 'Ragnar Petrovich',
                        ],
                        [
                            'Kind' => 'login',
                            'Name' => 'Login',
                            'Val' => 'login1',
                        ],
                        [
                            'Kind' => 'barcode',
                            'Val' => '11010011100110010001001000100110011011001100110011101001100011101011',
                        ],
                        [
                            'Kind' => 'accountNumber',
                            'Name' => 'Account Number',
                            'Val' => 'AN223322',
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            'Kind' => 'date',
                            'Name' => 'Last updated',
                            'Val' => 1262304000,
                        ],
                        [
                            'Kind' => 'expirationDate',
                            'Name' => 'Expiration',
                            'Val' => [
                                'ExpirationDate' => 1262476800,
                                'ExpirationState' => 'expired',
                            ],
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Name',
                            'Val' => 'Some Beautiful Name',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Elite Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Property 1',
                            'Val' => 'Property1 Value',
                        ],
                    ],
                    'subaccounts' => [
                        [
                            'criteria' => ['DisplayName' => 'First'],
                            'properties' => [
                                'Balance' => '10',
                                'LastBalance' => '11',
                                'ExpirationDate' => 1293926400,
                                'LastChange' => '-1',
                                'LastChangeRaw' => -1,
                                'ExpirationState' => 'expired',
                            ],
                            'blocks' => [
                                [
                                    'Kind' => 'string',
                                    'Name' => 'Account Owner',
                                    'Val' => 'Ragnar Petrovich',
                                ],
                                [
                                    'Kind' => 'balance',
                                    'Name' => 'Balance',
                                    'Val' => [
                                        'LastChange' => '-1',
                                        'LastChangeRaw' => -1,
                                        'Balance' => '10',
                                        'BalanceRaw' => 10,
                                    ],
                                ],
                                [
                                    'Kind' => 'expirationDate',
                                    'Name' => 'Expiration',
                                    'Val' => [
                                        'ExpirationDate' => 1293926400,
                                        'ExpirationState' => 'expired',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'criteria' => ['DisplayName' => 'Second'],
                            'properties' => [
                                'Balance' => '20',
                                'LastBalance' => '21',
                                'ExpirationDate' => 1325462400,
                                'LastChange' => '-1',
                                'LastChangeRaw' => -1,
                                'ExpirationState' => 'expired',
                            ],
                            'blocks' => [
                                [
                                    'Kind' => 'string',
                                    'Name' => 'Account Owner',
                                    'Val' => 'Ragnar Petrovich',
                                ],
                                [
                                    'Kind' => 'balance',
                                    'Name' => 'Balance',
                                    'Val' => [
                                        'LastChange' => '-1',
                                        'LastChangeRaw' => -1,
                                        'Balance' => '20',
                                        'BalanceRaw' => 20,
                                    ],
                                ],
                                [
                                    'Kind' => 'expirationDate',
                                    'Name' => 'Expiration',
                                    'Val' => [
                                        'ExpirationDate' => 1325462400,
                                        'ExpirationState' => 'expired',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // Coupon rights
                [
                    'edit' => true,
                    'delete' => true,
                ],
                // Coupon fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'DisplayName' => 'CouponName1',

                        'Login' => '123456789',
                        'Description' => 'My Test Coupon',

                        'Balance' => '102345',
                        'TotalBalance' => 102345,

                        'ExpirationDate' => 1262476800,
                        'ExpirationState' => 'expired',
                    ],
                    'blocks' => [
                        [
                            "Name" => "Account Owner",
                            "Val" => "Ragnar Petrovich",
                        ],
                        [
                            "Name" => "Coupon Value",
                            "Val" => [
                                "Balance" => "102345",
                            ],
                        ],
                        [
                            "Name" => "Note",
                            "Val" => "My Test Coupon",
                        ],
                        [
                            "Name" => "Expiration",
                            "Val" => [
                                "ExpirationDate" => 1262476800,
                                "ExpirationState" => "expired",
                            ],
                        ],
                    ],
                ],
            ],
            [
                // Access level
                ACCESS_READ_ALL,
                // Account rights
                [
                    "edit" => false,
                    "delete" => false,
                    "autologin" => true,
                    "update" => false,
                ],
                // Account fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'Login' => 'login1',
                        'Number' => 'AN223322',
                        'BarCode' => '11010011100110010001001000100110011011001100110011101001100011101011',

                        'Balance' => '100',
                        'LastBalance' => '99',
                        'LastChange' => '+1',
                        'LastChangeRaw' => 1,

                        'ExpirationDate' => 1262476800,
                        'ExpirationState' => 'expired',

                        'LastChangeDate' => $this->lastChangeDate->getTimestamp(),

                        'Disabled' => ['Title' => 'Disabled account. This account is not being updated by AwardWallet.'],
                        'Notice' => ['Title' => 'Error occurred'],
                        'Error' => true,

                        'Autologin' => [
                            'desktopExtension' => null,
                            'mobileExtension' => null,
                            'loginUrl' => new Some(),
                        ],
                        'Phones' => [
                            [
                                'name' => 'Level 1',
                                'phone' => '+100500600700',
                                'region' => null,
                            ],
                        ],
                    ],
                    'blocks' => [
                        [
                            'Kind' => 'balance',
                            'Name' => 'Balance',
                            'Val' => [
                                'LastChange' => '+1',
                                'LastChangeRaw' => 1,
                                'Balance' => '100',
                                'BalanceRaw' => 100,
                            ],
                        ],
                        [
                            'Kind' => 'disabled',
                            'Val' => ['Title' => 'Disabled account. This account is not being updated by AwardWallet.'],
                        ],
                        [
                            'Kind' => 'notice',
                            'Val' => ['Title' => 'Error occurred'],
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Account Owner',
                            'Val' => 'Ragnar Petrovich',
                        ],
                        [
                            'Kind' => 'login',
                            'Name' => 'Login',
                            'Val' => 'login1',
                        ],
                        [
                            'Kind' => 'barcode',
                            'Val' => '11010011100110010001001000100110011011001100110011101001100011101011',
                        ],
                        [
                            'Kind' => 'accountNumber',
                            'Name' => 'Account Number',
                            'Val' => 'AN223322',
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            'Kind' => 'date',
                            'Name' => 'Last updated',
                            'Val' => 1262304000,
                        ],
                        [
                            'Kind' => 'expirationDate',
                            'Name' => 'Expiration',
                            'Val' => [
                                'ExpirationDate' => 1262476800,
                                'ExpirationState' => 'expired',
                            ],
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Name',
                            'Val' => 'Some Beautiful Name',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Elite Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Property 1',
                            'Val' => 'Property1 Value',
                        ],
                    ],
                    'subaccounts' => [
                        [
                            'criteria' => ['DisplayName' => 'First'],
                            'properties' => [
                                'Balance' => '10',
                                'LastBalance' => '11',
                                'ExpirationDate' => 1293926400,
                                'LastChange' => '-1',
                                'LastChangeRaw' => -1,
                                'ExpirationState' => 'expired',
                            ],
                            'blocks' => [
                                [
                                    'Kind' => 'string',
                                    'Name' => 'Account Owner',
                                    'Val' => 'Ragnar Petrovich',
                                ],
                                [
                                    'Kind' => 'balance',
                                    'Name' => 'Balance',
                                    'Val' => [
                                        'LastChange' => '-1',
                                        'LastChangeRaw' => -1,
                                        'Balance' => '10',
                                        'BalanceRaw' => 10,
                                    ],
                                ],
                                [
                                    'Kind' => 'expirationDate',
                                    'Name' => 'Expiration',
                                    'Val' => [
                                        'ExpirationDate' => 1293926400,
                                        'ExpirationState' => 'expired',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'criteria' => ['DisplayName' => 'Second'],
                            'properties' => [
                                'Balance' => '20',
                                'LastBalance' => '21',
                                'ExpirationDate' => 1325462400,
                                'LastChange' => '-1',
                                'LastChangeRaw' => -1,
                                'ExpirationState' => 'expired',
                            ],
                            'blocks' => [
                                [
                                    'Kind' => 'string',
                                    'Name' => 'Account Owner',
                                    'Val' => 'Ragnar Petrovich',
                                ],
                                [
                                    'Kind' => 'balance',
                                    'Name' => 'Balance',
                                    'Val' => [
                                        'LastChange' => '-1',
                                        'LastChangeRaw' => -1,
                                        'Balance' => '20',
                                        'BalanceRaw' => 20,
                                    ],
                                ],
                                [
                                    'Kind' => 'expirationDate',
                                    'Name' => 'Expiration',
                                    'Val' => [
                                        'ExpirationDate' => 1325462400,
                                        'ExpirationState' => 'expired',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // Coupon rights
                [
                    'edit' => false,
                    'delete' => false,
                ],
                // Coupon fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'DisplayName' => 'CouponName1',

                        'Login' => '123456789',
                        'Description' => 'My Test Coupon',

                        'Balance' => '102345',
                        'TotalBalance' => 102345,

                        'ExpirationDate' => 1262476800,
                        'ExpirationState' => 'expired',
                    ],
                    'blocks' => [
                        [
                            "Name" => "Account Owner",
                            "Val" => "Ragnar Petrovich",
                        ],
                        [
                            "Name" => "Coupon Value",
                            "Val" => [
                                "Balance" => "102345",
                            ],
                        ],
                        [
                            "Name" => "Note",
                            "Val" => "My Test Coupon",
                        ],
                        [
                            "Name" => "Expiration",
                            "Val" => [
                                "ExpirationDate" => 1262476800,
                                "ExpirationState" => "expired",
                            ],
                        ],
                    ],
                ],
            ],
            [
                // Access level
                ACCESS_READ_BALANCE_AND_STATUS,
                // Account rights
                [
                    "edit" => false,
                    "delete" => false,
                    "autologin" => true,
                    "update" => false,
                ],
                // Account fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'Login' => null,
                        'Number' => null,
                        'BarCode' => null,

                        'Balance' => '100',
                        'LastBalance' => '99',
                        'LastChange' => '+1',
                        'LastChangeRaw' => 1,

                        'ExpirationDate' => null,
                        'ExpirationState' => null,

                        'LastChangeDate' => null,

                        'Disabled' => null,
                        'Notice' => null,
                        'Error' => null,

                        'Autologin' => [
                            'desktopExtension' => null,
                            'mobileExtension' => null,
                            'loginUrl' => new Some(),
                        ],
                    ],
                    'blocks' => [
                        [
                            'Kind' => 'balance',
                            'Name' => 'Balance',
                            'Val' => [
                                'LastChange' => '+1',
                                'LastChangeRaw' => 1,
                                'Balance' => '100',
                                'BalanceRaw' => 100,
                            ],
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'disabled',
                            'Val' => ['Title' => 'Disabled account. This account is not being updated by AwardWallet.'],
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'notice',
                            'Val' => ['Title' => 'Error occurred'],
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Account Owner',
                            'Val' => 'Ragnar Petrovich',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'login',
                            'Name' => 'Login',
                            'Val' => 'login1',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'barcode',
                            'Val' => '11010011100110010001001000100110011011001100110011101001100011101011',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'accountNumber',
                            'Name' => 'Account Number',
                            'Val' => 'AN223322',
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'date',
                            'Name' => 'Last updated',
                            'Val' => 1262304000,
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'expirationDate',
                            'Name' => 'Expiration',
                            'Val' => [
                                'ExpirationDate' => 1262476800,
                                'ExpirationState' => 'expired',
                            ],
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Name',
                            'Val' => 'Some Beautiful Name',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Property 1',
                            'Val' => 'Property1 Value',
                        ],
                    ],
                    'subaccounts' => [
                        [
                            'criteria' => ['DisplayName' => 'First'],
                            'properties' => [
                                'Balance' => '10',
                                'LastBalance' => '11',
                                'LastChange' => '-1',
                                'LastChangeRaw' => -1,

                                'ExpirationDate' => null,
                                'ExpirationState' => null,
                            ],
                            'blocks' => [
                                [
                                    'Kind' => 'string',
                                    'Name' => 'Account Owner',
                                    'Val' => 'Ragnar Petrovich',
                                ],
                                [
                                    'Kind' => 'balance',
                                    'Name' => 'Balance',
                                    'Val' => [
                                        'LastChange' => '-1',
                                        'LastChangeRaw' => -1,
                                        'Balance' => '10',
                                        'BalanceRaw' => 10,
                                    ],
                                ],
                                [
                                    self::ABSENT,
                                    'Kind' => 'expirationDate',
                                    'Name' => 'Expiration',
                                    'Val' => [
                                        'ExpirationDate' => 1293926400,
                                        'ExpirationState' => 'expired',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'criteria' => ['DisplayName' => 'Second'],
                            'properties' => [
                                'Balance' => '20',
                                'LastBalance' => '21',
                                'LastChange' => '-1',
                                'LastChangeRaw' => -1,

                                'ExpirationDate' => null,
                                'ExpirationState' => null,
                            ],
                            'blocks' => [
                                [
                                    'Kind' => 'string',
                                    'Name' => 'Account Owner',
                                    'Val' => 'Ragnar Petrovich',
                                ],
                                [
                                    'Kind' => 'balance',
                                    'Name' => 'Balance',
                                    'Val' => [
                                        'LastChange' => '-1',
                                        'LastChangeRaw' => -1,
                                        'Balance' => '20',
                                        'BalanceRaw' => 20,
                                    ],
                                ],
                                [
                                    self::ABSENT,
                                    'Kind' => 'expirationDate',
                                    'Name' => 'Expiration',
                                    'Val' => [
                                        'ExpirationDate' => 1325462400,
                                        'ExpirationState' => 'expired',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // Coupon rights
                [
                    'edit' => false,
                    'delete' => false,
                ],
                // Coupon fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'DisplayName' => 'CouponName1',

                        'Login' => null,
                        'Description' => null,

                        'Balance' => '',
                        'TotalBalance' => 102345,

                        'ExpirationDate' => null,
                        'ExpirationState' => null,
                    ],
                    'blocks' => [
                        [
                            "Name" => "Account Owner",
                            "Val" => "Ragnar Petrovich",
                        ],
                        [
                            "Name" => "Coupon Value",
                            "Val" => [
                                "Balance" => "102345",
                            ],
                        ],
                        [
                            self::ABSENT,
                            "Name" => "Note",
                            "Val" => "My Test Coupon",
                        ],
                        [
                            self::ABSENT,
                            "Name" => "Expiration",
                            "Val" => [
                                "ExpirationDate" => 1262476800,
                                "ExpirationState" => "expired",
                            ],
                        ],
                    ],
                ],
            ],
            [
                // Access level
                ACCESS_READ_NUMBER,
                // Account rights
                [
                    "edit" => false,
                    "delete" => false,
                    "autologin" => true,
                    "update" => false,
                ],
                // Account fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'Login' => 'login1',
                        'Number' => 'AN223322',
                        'BarCode' => '11010011100110010001001000100110011011001100110011101001100011101011',

                        'BalanceRaw' => null,
                        'Balance' => null,
                        'TotalBalance' => null,
                        'LastBalance' => null,
                        'LastChange' => null,
                        'LastChangeRaw' => null,

                        'ExpirationDate' => null,
                        'ExpirationState' => null,

                        'LastChangeDate' => null,

                        'Disabled' => null,
                        'Notice' => null,
                        'Error' => null,

                        'Phones' => null,

                        'Autologin' => [
                            'desktopExtension' => null,
                            'mobileExtension' => null,
                            'loginUrl' => new Some(),
                        ],
                        'SubAccountsArray' => null,
                    ],
                    'blocks' => [
                        [
                            self::ABSENT,
                            'Kind' => 'balance',
                            'Name' => 'Balance',
                            'Val' => [
                                'LastChange' => '+1',
                                'LastChangeRaw' => 1,
                                'Balance' => '100',
                                'BalanceRaw' => 100,
                            ],
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'disabled',
                            'Val' => ['Title' => 'Disabled account. This account is not being updated by AwardWallet.'],
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'notice',
                            'Val' => ['Title' => 'Error occurred'],
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Account Owner',
                            'Val' => 'Ragnar Petrovich',
                        ],
                        [
                            'Kind' => 'login',
                            'Name' => 'Login',
                            'Val' => 'login1',
                        ],
                        [
                            'Kind' => 'barcode',
                            'Val' => '11010011100110010001001000100110011011001100110011101001100011101011',
                        ],
                        [
                            'Kind' => 'accountNumber',
                            'Name' => 'Account Number',
                            'Val' => 'AN223322',
                        ],
                        [
                            'Kind' => 'string',
                            'Name' => 'Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'date',
                            'Name' => 'Last updated',
                            'Val' => 1262304000,
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'expirationDate',
                            'Name' => 'Expiration',
                            'Val' => [
                                'ExpirationDate' => 1262476800,
                                'ExpirationState' => 'expired',
                            ],
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Name',
                            'Val' => 'Some Beautiful Name',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Elite Status',
                            'Val' => 'Level 1',
                        ],
                        [
                            self::ABSENT,
                            'Kind' => 'string',
                            'Name' => 'Property 1',
                            'Val' => 'Property1 Value',
                        ],
                    ],
                ],
                // Coupon rights
                [
                    'edit' => false,
                    'delete' => false,
                ],
                // Coupon fields
                [
                    'properties' => [
                        'UserName' => 'Ragnar Petrovich',

                        'DisplayName' => 'CouponName1',

                        'Login' => '123456789',
                        'Description' => 'My Test Coupon',

                        'Balance' => null,
                        'BalanceRaw' => null,
                        'TotalBalance' => null,

                        'ExpirationDate' => null,
                        'ExpirationState' => null,
                    ],
                    'blocks' => [
                        [
                            "Name" => "Account Owner",
                            "Val" => "Ragnar Petrovich",
                        ],
                        [
                            self::ABSENT,
                            "Name" => "Coupon Value",
                            "Val" => [
                                "Balance" => "102345",
                            ],
                        ],
                        [
                            "Name" => "Note",
                            "Val" => "My Test Coupon",
                        ],
                        [
                            self::ABSENT,
                            "Name" => "Expiration",
                            "Val" => [
                                "ExpirationDate" => 1262476800,
                                "ExpirationState" => "expired",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function assertBlocks(array $expectedBlocks, array $actualBlocks, $message)
    {
        foreach ($expectedBlocks as $expectedBlock) {
            if ($isAbsent = isset($expectedBlock[0]) && (self::ABSENT === $expectedBlock[0])) {
                unset($expectedBlock[0]);
            }

            $foundBlock = $this->findArrayByCriteria($expectedBlock, $actualBlocks);

            if ($foundBlock) {
                if ($isAbsent) {
                    $this->fail($message . sprintf('block %s has been found', json_encode($expectedBlock)));
                }
            } elseif (!$isAbsent) {
                $this->fail($message . sprintf('block %s has not been found', json_encode($expectedBlock)));
            }
        }
    }

    protected function findArrayByCriteria(array $criteria, array $arrays)
    {
        foreach ($arrays as $array) {
            foreach ($criteria as $key => $value) {
                if (!array_key_exists($key, $array)) {
                    continue 2;
                }

                if (is_array($array[$key]) && is_array($value)) {
                    if ($value != $this->filterField($array, $key, $value)) {
                        continue 2;
                    }
                } else {
                    if ($array[$key] !== $value) {
                        continue 2;
                    }
                }
            }

            return $array;
        }

        return null;
    }

    private function addProperty($accountId, array $criteria, $val)
    {
        $this->db->haveInDatabase('AccountProperty', [
            'AccountID' => $accountId,
            'ProviderPropertyID' => $this->db->grabFromDatabase('ProviderProperty', 'ProviderPropertyID', $criteria),
            'Val' => $val,
        ]);
    }

    /**
     * remove extra indexes from array.
     */
    private function filterField($actual, $field, $expected)
    {
        if (!isset($actual[$field])) {
            return null;
        }

        if (is_array($expected) && is_array($actual[$field])) {
            $arr = &$actual[$field];

            foreach ($arr as $key => $value) {
                if (!array_key_exists($key, $expected)) {
                    unset($arr[$key]);
                } else {
                    if (
                        $expected[$key] instanceof Some
                        && (null !== $value)
                    ) {
                        $arr[$key] = $expected[$key]->setValue($value);
                    }

                    $arr[$key] = $this->filterField($arr, $key, $expected[$key]);
                }
            }

            foreach (array_keys($expected) as $key) {
                if (!array_key_exists($key, $arr)) {
                    $arr[$key] = null;
                }
            }
        }

        return $actual[$field];
    }
}

class Some
{
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
