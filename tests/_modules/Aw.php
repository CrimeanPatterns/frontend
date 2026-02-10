<?php

namespace Codeception\Module;

use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use AwardWallet\Common\Tests\HttpDriverMock;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Updater\Engine\Local;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\TwoFactorAuthenticationService;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Updater\AccountProgress;
use Codeception\Actor;
use Codeception\Example;
use Codeception\Exception\InjectionException;
use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\Di;
use Codeception\Module;
use Codeception\Scenario;
use Codeception\Test\Cest;
use Codeception\Test\Metadata;
use Codeception\TestInterface;
use Codeception\Util\ArrayContainsComparator;
use Doctrine\DBAL\Connection;
use Google\Authenticator\GoogleAuthenticator;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ArrayComparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use SebastianBergmann\GlobalState\ExcludeList;
use SebastianBergmann\GlobalState\Restorer;
use SebastianBergmann\GlobalState\Snapshot;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Aw extends Module
{
    public const TEST_PROVIDER_ID = 636;
    public const SITEDMIN_ID = 7;
    public const BOOKER_ID = 678336;
    public const CAME_FROM_BOOKER = 125;
    public const DEFAULT_PASSWORD = 'testuserpassword';
    public const GMT_AIRPORT = 'OUA'; // Ouagadougou Airport, Burkina Faso, GMT all over the year
    public const GMT_AIRPORT_2 = 'XLU'; // Leo Airport, Burkina Faso, GMT all over the year
    public const GMT_AIRPORT_3 = 'XZA'; // Zabre Airport, Burkina Faso, GMT all over the year

    private const MEMORY_LEAK_PREVENTION = ['MEMORY_LEAK_PREVENTION'];

    private static $utilSymfonyContainer;

    private $fakeDataPath;

    /**
     * @var Snapshot
     */
    private $globalsSnapshot;
    /**
     * @var Restorer
     */
    private $restorer;
    /**
     * @var Example
     */
    private $exampleLeakPrevention;

    /**
     * @var \ReflectionProperty
     */
    private $reflScenarioSteps;

    /**
     * @var \ReflectionProperty
     */
    private $reflMetadataCurrent;

    public function __construct($moduleContainer)
    {
        $this->fakeDataPath = __DIR__ . '/../_data';
        parent::__construct($moduleContainer);

        $this->exampleLeakPrevention = new Example(self::MEMORY_LEAK_PREVENTION);
        $this->reflMetadataCurrent = new \ReflectionProperty(Metadata::class, 'current');
        $this->reflScenarioSteps = new \ReflectionProperty(Scenario::class, 'steps');
    }

    /**
     * **HOOK** triggered after module is created and configuration is loaded.
     */
    public function _initialize()
    {
        // codecepion do not backup / restore globals between Cest-s, so we will do it on our own
        // https://github.com/Codeception/Codeception/issues/4357
        $blacklist = new ExcludeList();
        $blacklist->addGlobalVariable('Connection');
        $blacklist->addGlobalVariable('symfonyContainer');
        $this->globalsSnapshot = new Snapshot($blacklist, true, false);
        $this->restorer = new Restorer();
    }

    public function _before(TestInterface $test)
    {
        \Locale::setDefault('en_US');
        set_time_limit(180);
    }

    public function _after(TestInterface $test)
    {
        $this->restoreGlobals();
        $this->clearTestProperties($test);

        if ($this->hasModule('Symfony')) {
            /** @var Symfony $symfony2 */
            $symfony2 = $this->getModule('Symfony');
            // how to unpersist all services after a test ?
            $symfony2->unpersistService("session");
        }
    }

    /**
     * @param string $login
     * @param string $password
     * @param bool $staff use this for impersonate test
     * @return int UserID
     */
    public function createAwUser($login = null, $password = null, array $userFields = [], $staff = false, $beta = true)
    {
        if (empty($login)) {
            $login = 'test' . $this->grabRandomString();
        }

        if ($password === null) {
            $password = self::DEFAULT_PASSWORD;
        }

        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $userData = include $this->fakeDataPath . '/fakeUser.php';
        $userData['RegistrationIP'] = $this->getClientIp();
        $userData['LastLogonIP'] = $userData['RegistrationIP'];
        unset($userData['Password']);
        $userData['Login'] = $login;
        /** @var Aw $awModule */
        $awModule = $this->getModule('Aw');
        $awModule->resetLockout('password', self::DEFAULT_PASSWORD);

        if (self::DEFAULT_PASSWORD === $password) {
            $userData['Pass'] = '$2y$04$EQWRhtUvziQvTAbE6GV5EeC60fiUe1fZk.rkS.vi0yycaDo983T7W';
        } else {
            $encoder = new NativePasswordEncoder(null, null, 4, \PASSWORD_BCRYPT);
            $userData['Pass'] = $encoder->encodePassword($password, null);
        }

        // prevent email OTC, register user with same ip as we will use in tests
        if (empty($userData['RegistrationIP']) && empty($userData['LastLogonIP'])) {
            $userData['RegistrationIP'] = $this->getClientIp();
        }

        if ($staff) {
            // turn on 2FA for staff to access /manager
            $userData['GoogleAuthSecret'] = (new GoogleAuthenticator())->generateSecret();
            $userData['GoogleAuthRecoveryCode'] = 'some';
        }

        $userData = array_merge($userData, ['AccountLevel' => ACCOUNT_LEVEL_FREE], $userFields);
        $businessInfo = @$userFields['BusinessInfo'] ?: [];
        unset($userData['BusinessInfo']);
        $userId = (int) $I->haveInDatabase('Usr', $userData);

        if ($userData['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
            $I->haveInDatabase('BusinessInfo', array_merge(
                [
                    'UserID' => $userId,
                    'Balance' => 1000,
                    'Discount' => 100,
                ],
                $businessInfo
            ));
        }

        if ($staff) {
            // give ROLE_STAFF group
            $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => Sitegroup::STAFF_ID]);
            $I->haveInDatabase('GroupUserLink', ['UserID' => $userId, 'SiteGroupID' => Sitegroup::STAFF_DEVELOPER_ID]);
        }

        if ($beta) {
            $I->executeQuery("update Usr set InBeta = 1, BetaApproved = 1 where UserId = " . $userId);
        }

        return $userId;
    }

    public function getClientIp()
    {
        if ($this->hasModule('PhpBrowser')) {
            static $ip;

            if (!empty($ip)) {
                return $ip;
            }

            /** @var PhpBrowser $browser */
            $browser = $this->getModule('PhpBrowser');
            $url = $browser->_getConfig('url') . '/test/client-info';
            $infoJson = curlRequest($url, 60, [CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false]);
            $info = @json_decode($infoJson, true);

            if (empty($info['client_ip'])) {
                throw new ModuleConfigException(__CLASS__, "Can't detect my ip, url: {$url}, response: " . $infoJson);
            }
            $ip = $info['client_ip'];

            return $ip;
        }

        if ($this->hasModule('WebHelper')) {
            /** @var WebHelper $browser */
            $browser = $this->getModule('WebHelper');

            return gethostbyname($browser->getHost());
        }

        return '127.0.0.1';

        //        if(empty($infoUrl))
        //            throw new ModuleConfig(__CLASS__, "Can't detect my ip, no supported browser modules");
        //
        //
        //        $infoJson = curlRequest($infoUrl . '/test/client-info');
        //        $info = @json_decode($infoJson, true);
        //        if(empty($info['host_ip']))
        //            throw new ModuleConfig(__CLASS__, "Can't detect my ip, response: " . $infoJson);
        //
        //        return $info['host_ip'];
    }

    /**
     * @param int $userId
     * @param int|string|null $providerIdOrCode
     * @param string $login
     * @param string|null $password
     * @return int account ID
     */
    public function createAwAccount($userId, $providerIdOrCode, $login, $password = '', array $fields = [])
    {
        if ($password === null) {
            $password = '';
        }

        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');

        if (is_numeric($providerIdOrCode)) {
            $providerId = $providerIdOrCode;
        } elseif (null === $providerIdOrCode) {
            $providerId = null;
        } else {
            $providerId = $I->grabFromDatabase("Provider", "ProviderID", ["Code" => $providerIdOrCode]);
        }

        if (!isset($login)) {
            $login = StringUtils::getRandomCode(20);
        }

        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var PasswordEncryptor $encryptor */
        $encryptor = $symfony->grabService(PasswordEncryptor::class);

        $fakeData = [
            'ProviderID' => $providerId,
            'UserID' => $userId,
            'Login' => $login,
            'Pass' => $encryptor->encrypt($password),
            'State' => ACCOUNT_CHECKED,
            'ErrorCode' => ACCOUNT_ENGINE_ERROR,
            'ErrorMessage' => 'Unknown error',
            'CreationDate' => $now = (new \DateTime())->format('Y-m-d H:i:s'),
            'UpdateDate' => $now,
            'PassChangeDate' => $now,
            'ModifyDate' => $now,
            'NotRelated' => 1,
        ];

        $fakeData = array_merge($fakeData, $fields);
        $accountID = $I->haveInDatabase('Account', $fakeData);
        $I->haveInsertedInDatabase('Usr', ['UserID' => $userId]);

        return $accountID;
    }

    public function createAwSubAccount($accountId, array $fields = [])
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');

        $fakeData = array_merge(['AccountID' => $accountId, 'Code' => bin2hex(random_bytes(5))], $fields);
        $subAccId = $I->haveInDatabase('SubAccount', $fakeData);
        $I->haveInsertedInDatabase('Account', ['AccountID' => $accountId]);

        return $subAccId;
    }

    public function shareAwAccount($accountId, $userId)
    {
        $fakeUaData = include $this->fakeDataPath . '/fakeUserAgent.php';
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $fakeUaData['AgentID'] = (int) $userId;
        $fakeUaData['ClientID'] = $I->grabFromDatabase('Account', 'UserID', ['AccountID' => $accountId]);
        $userAgentId = $I->shouldHaveInDatabase('UserAgent', $fakeUaData);

        return $this->shareAwAccountByConnection($accountId, $userAgentId);
    }

    public function shareAwAccountByConnection($accountId, $connectionId)
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');

        return $I->haveInDatabase('AccountShare', ['AccountID' => $accountId, 'UserAgentID' => $connectionId]);
    }

    public function shareAwCouponByConnection(int $couponId, int $connectionId)
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');

        return $I->haveInDatabase('ProviderCouponShare', ['ProviderCouponID' => $couponId, 'UserAgentID' => $connectionId]);
    }

    /**
     * @param null $familyMember
     * @return int
     */
    public function shareAwTimeline($owner, $familyMember = null, $viewer)
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $userAgentId = $I->grabFromDatabase('UserAgent', 'UserAgentID', ['ClientID' => $owner, 'AgentID' => $viewer]);

        return $I->haveInDatabase('TimelineShare', [
            'UserAgentID' => $userAgentId,
            'TimelineOwnerID' => $owner,
            'FamilyMemberID' => $familyMember,
            'RecipientUserID' => $viewer,
        ]);
    }

    public function createAwBookerStaff($login = null, $password = null, array $userFields = [], $staff = false)
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $bookerId = $this->createAwUser($login, $password, $userFields, $staff);
        $businessId = $this->createBusinessUserWithBookerInfo();
        $this->connectUserWithBusiness($bookerId, $businessId, \ACCESS_BOOKING_MANAGER);

        return $bookerId;
    }

    public function createBusinessUserWithBookerInfo($login = null, array $userFields = [], array $bookerInfoFields = [])
    {
        $businessUserId = $this->createAwUser($login, self::DEFAULT_PASSWORD, array_merge($userFields, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]));
        $this->createBookerInfo($businessUserId, $bookerInfoFields);

        return $businessUserId;
    }

    public function createStaffUserForBusinessUser($businessUserId, $accessLevel = \ACCESS_ADMIN)
    {
        $staffUser = $this->createAwUser($this->grabRandomString(), self::DEFAULT_PASSWORD, [], true);
        $this->connectUserWithBusiness($staffUser, $businessUserId, $accessLevel);

        return $staffUser;
    }

    public function connectUserWithBusiness($userId, $businessId, $accessLevel, array $connectionFields = [])
    {
        return $this->createConnection($businessId, $userId, 1, true, array_merge(['AccessLevel' => $accessLevel], $connectionFields));
    }

    public function createBookerInfo($businessUserId, array $bookerInfoFields = [])
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $fakeData = include $this->fakeDataPath . '/Booking/fakeAbBookerInfo.php';
        $fakeData['UserID'] = $businessUserId;

        return $I->haveInDatabase('AbBookerInfo', array_merge($fakeData, $bookerInfoFields));
    }

    public function createAwCoupon($userId, $name, $value, $description = '', array $fields = [])
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $fakeData = call_user_func(include $this->fakeDataPath . '/fakeCoupon.php', $name, $value, $description, $userId);

        $fakeData = array_merge($fakeData, $fields);
        $couponID = $I->haveInDatabase('ProviderCoupon', $fakeData);
        $I->haveInsertedInDatabase('Usr', ['UserID' => $userId]);

        return $couponID;
    }

    /**
     * @return int
     */
    public function createAwProvider($name = null, $code = null, array $fields = [], array $instanceMethods = [], array $staticMethods = [], $useTraits = [])
    {
        if (empty($code)) {
            $code = 'provider' . StringUtils::getRandomCode(12);
        }

        if (empty($name)) {
            $name = "Provider $code";
        }

        if (strlen($code) > 20) {
            throw new \Exception("Provider code should be no longer than 20 chars");
        }

        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $fakeData = call_user_func(include $this->fakeDataPath . '/fakeProvider.php', $name, $code);

        $methods = [
            'static' => [],
            'instance' => [],
        ];

        if ($instanceMethods) {
            $defaultInstanceMethodsImpl = [
                'LoadLoginForm' => function () {
                    return true;
                },
                'Login' => function () {
                    return true;
                },
                'Parse' => function () {
                    /** @var $this \TAccountChecker */
                    $this->setBalanceNA();
                },
                'ParseItineraries' => function () {
                    return [];
                },
                'ParseHistory' => function ($startDate = null) {
                    return [];
                },
            ];

            $methods['instance'] = array_merge($defaultInstanceMethodsImpl, $instanceMethods);
        }

        if ($staticMethods) {
            $methods['static'] = $staticMethods;
        }

        $className = 'TAccountChecker' . ucfirst($code);
        $methodsCode = '';

        foreach ($methods as $scope => $scopeMethods) {
            foreach ($scopeMethods as $methodName => $closure) {
                $hash = spl_object_hash($closure);
                ClosureStorage::set($hash, $closure);
                $reflection = new \ReflectionFunction($closure);
                $params = $reflection->getParameters();
                $closureStorageClass = ClosureStorage::class;

                $paramsDefinitionCode = it($params)
                    ->map(function (\ReflectionParameter $param) {
                        $type = (method_exists($param, 'getType') && $param->getType() ? '\\' . $param->getType() . ' ' : '');
                        $name = $param->getName();
                        $defaultValue = ($param->isOptional() ? " = " . var_export($param->getDefaultValue(), true) : "");

                        return "{$type} \${$name}{$defaultValue}";
                    })
                    ->joinToString(', ');

                $paramsCallSiteCode = it($params)
                    ->propertyPath('name')
                    ->format('$%s')
                    ->joinToString(', ');

                $staticMethodScope = ($scope === 'static') ? $scope : '';
                $bindObject = ($scope === 'static') ? 'null' : '$this';

                $methodsCode .= "
                    public {$staticMethodScope} function {$methodName}({$paramsDefinitionCode})
                    {
                        \$closure = {$closureStorageClass}::get(\"{$hash}\")->bindTo({$bindObject}, \"{$className}\");
                        
                        return \$closure({$paramsCallSiteCode});
                    }
                    ";
            }
        }

        if ('' !== $methodsCode) {
            $checkerCode = sprintf(
                '
                namespace 
                {
                    class %1$s extends TAccountChecker {
                        %3$s
                        %2$s
                    }
                }
                ',
                $className,
                $methodsCode,
                empty($useTraits) ? "" : "use " . implode(", ", $useTraits) . ";\n"
            );
            eval($checkerCode);
            $className = '\\' . $className;
            new $className(); // prevent autoloading
        }

        $providerCountries = $fields['Countries'] ?? [];
        unset($fields['Countries']);

        $fakeData = array_merge($fakeData, $fields);
        $providerId = $I->haveInDatabase('Provider', $fakeData);

        foreach ($providerCountries as $countryCode => $providerCountryData) {
            $providerCountryData['ProviderID'] = $providerId;
            $providerCountryData['CountryID'] = $I->grabFromDatabase('Country', 'CountryID', ['Code' => $countryCode, 'Name' => $providerCountryData['Name']]);
            unset($providerCountryData['Name']);
            $I->haveInDatabase('ProviderCountry', $providerCountryData);
        }

        return $providerId;
    }

    public function grabRandomString($length = 10)
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    public function finishAccountCheck(int $accountId, ?string $loyaltyRequestId = null, int $errorCode = \ACCOUNT_CHECKED): void
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $connection = $symfony->grabService(Connection::class);
        $connection->executeStatement("update Account set ErrorCode = " . $errorCode . " where AccountID = $accountId");
        /** @var AccountProgress $progress */
        $progress = $symfony->grabService(AccountProgress::class);
        $progress->finishAccount($accountId, null, $errorCode);

        if ($loyaltyRequestId !== null) {
            $progress->finishLoyaltyRequest($loyaltyRequestId, null, $errorCode);
        }
    }

    public function checkAccount(
        int $accountId,
        bool $parseItineraries = true,
        ?int $source = null
    ) {
        if ($this->hasModule('Symfony')) {
            /** @var Module\Symfony $symfony */
            $symfony = $this->getModule('Symfony');
            $container = $symfony->_getContainer();
        } else {
            $container = getSymfonyContainer();
        }
        /** @var Account $account */
        $account = $container->get('doctrine')->getRepository(Account::class)->find($accountId);

        if (null === $account) {
            return;
        }
        /** @var Local $engine */
        $engine = $container->get(Local::class);
        $engine->sendAccounts(
            [
                ['AccountID' => $account->getAccountid(), 'AutoGatherPlans' => false, 'ParseItineraries' => $parseItineraries],
            ],
            0,
            $source
        );
    }

    public function createAbRequest($requestParams = [])
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $request = require $this->fakeDataPath . '/Booking/fakeAbRequest.php';
        $request = array_merge($request, $requestParams);
        $requestId = $I->haveInDatabase('AbRequest', $request);

        $I->seeInDatabase('AbRequest', ['AbRequestID' => $requestId]);

        $passenger = require $this->fakeDataPath . '/Booking/fakeAbPassenger.php';
        $passenger['RequestID'] = $requestId;
        $I->haveInDatabase('AbPassenger', $passenger);

        $segment = require $this->fakeDataPath . '/Booking/fakeAbSegment.php';
        $segment['RequestID'] = $requestId;
        $I->haveInDatabase('AbSegment', $segment);

        return $requestId;
    }

    public function markUserCartPaid($userId)
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $cartId = $I->grabFromDatabase("Cart", "CartID", ["UserID" => $userId, "PayDate" => null]);
        $this->assertNotEmpty($cartId);
        $I->executeQuery("update Cart set PayDate = now() where CartID = " . intval($cartId));

        return $cartId;
    }

    public function getBookingRequestAdmin($requestId, $field = "Login")
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $bookingId = $I->grabFromDatabase("AbRequest", "BookerUserID", ["AbRequestID" => $requestId]);
        $bookerId = $I->grabFromDatabase("UserAgent", "AgentID", ["ClientID" => $bookingId, "AccessLevel" => ACCESS_ADMIN, "IsApproved" => 1]);

        return $I->grabFromDatabase("Usr", $field, ["UserID" => $bookerId]);
    }

    public function retrieveByConfNo(Usr $user, ?Useragent $familyMember = null, Provider $provider, $confFields)
    {
        if ($this->hasModule('Symfony')) {
            /** @var Module\Symfony $symfony */
            $symfony = $this->getModule('Symfony');
            $container = $symfony->_getContainer();
        } else {
            $container = getSymfonyContainer();
        }
        /** @var Local $engine */
        $engine = $container->get(Local::class);
        $trips = [];

        return $engine->retrieveConfirmation($confFields, $provider, $trips, $user, $familyMember);
    }

    /**
     * add family member to this user.
     *
     * @return int userAgentId
     */
    public function createFamilyMember($userId, $firstName, $lastName, $midName = null, $email = null)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase("UserAgent", [
            "AgentID" => $userId,
            "FirstName" => $firstName,
            "LastName" => $lastName,
            "MidName" => $midName,
            "Email" => $email,
            "IsApproved" => 1,
            "TripAccessLevel" => 1,
        ]);
    }

    /**
     * add connection to this user - clientId shares something to agentId.
     *
     * @param $clientId - from
     * @param $agentId - to
     * @return int userAgentId
     */
    public function createConnection($clientId, $agentId, $approved = null, $shareTripsByDefault = null, $fields = [])
    {
        if ($approved === null) {
            $approved = true;
        }

        if ($shareTripsByDefault === null) {
            $shareTripsByDefault = true;
        }
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase("UserAgent", array_merge([
            "AgentID" => $agentId,
            "ClientID" => $clientId,
            "IsApproved" => $approved ? 1 : 0,
            "TripShareByDefault" => $shareTripsByDefault ? 1 : 0,
            "AccessLevel" => ACCESS_READ_NUMBER,
        ], $fields));
    }

    public function createCustomLoyaltyProperty($name, $value, array $loyaltyProgramCriteria)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase('CustomLoyaltyProperty', array_merge(
            [
                'Name' => $name,
                'Value' => $value,
            ],
            $loyaltyProgramCriteria
        ));
    }

    public function createAccountProperty($code, $value, array $accountCriteria, $providerId = null)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase('AccountProperty', array_merge(
            [
                'ProviderPropertyID' => $db->grabFromDatabase('ProviderProperty', 'ProviderPropertyID', ['Code' => $code, 'ProviderID' => $providerId]),
                'Val' => $value,
            ],
            $accountCriteria
        ));
    }

    /**
     * @return int InvitesID
     */
    public function inviteUser(int $inviter, int $invitee, bool $approved = true)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase('Invites', [
            'InviterID' => $inviter,
            'InviteeID' => $invitee,
            'InviteDate' => (new \DateTime())->format('Y-m-d H:i:s'),
            'Approved' => $approved,
        ]);
    }

    public function showMyVar($v)
    {
        $this->debug($v);
    }

    public function resetLockout($lockerId, $key = '192.168.10.10')
    {
        if ($this->hasModule('Symfony')) {
            /** @var Module\Symfony $symfony */
            $symfony = $this->getModule('Symfony');
            $container = $symfony->_getContainer();
        } else {
            $container = getSymfonyContainer();
        }

        /** @var AntiBruteforceLockerService $locker */
        $locker = $container->get('aw.security.antibruteforce.' . $lockerId);
        $locker->unlock($key);
    }

    public function resetLocker($prefix, $key)
    {
        if ($this->hasModule('Symfony')) {
            /** @var Module\Symfony $symfony */
            $symfony = $this->getModule('Symfony');
            $container = $symfony->_getContainer();
        } else {
            $container = getSymfonyContainer();
        }

        $locker = new \AwardWallet\MainBundle\Security\AntiBruteforceLockerService($container->get(\Memcached::class), $prefix, 60, 60, 5, "no matter");
        $locker->unlock($key);
    }

    /**
     * @param int $paymentType
     * @return \AwardWallet\MainBundle\Entity\Cart
     * @throws \Codeception\Exception\Module
     */
    public function addUserPayment($userId, $paymentType = Cart::PAYMENTTYPE_APPSTORE, ?CartItem $cartItem = null, ?array $extraItems = null, ?\DateTime $payDate = null, ?Coupon $coupon = null)
    {
        if (empty($cartItem)) {
            $cartItem = new AwPlus();
        }

        if ($extraItems === null) {
            $extraItems = [];
        }

        /** @var Module\Symfony2 $symfony */
        $symfony = $this->getModule('Symfony');
        $container = $symfony->_getContainer();
        $cartManager = $container->get("aw.manager.cart");

        $user = $container->get('doctrine')->getRepository(Usr::class)->find($userId);
        /** @var Usr $user */
        $cartManager->setUser($user);
        $cart = $cartManager->createNewCart();
        $cart->setPaymenttype($paymentType);

        if ($paymentType == Cart::PAYMENTTYPE_CREDITCARD) {
            $cart->setCreditcardtype('VISA');
            $cart->setCreditcardnumber('XXXXXXXXXXXX5678');
        }
        $cart->addItem($cartItem);

        foreach ($extraItems as $item) {
            $cart->addItem($item);
        }

        if ($coupon !== null) {
            $discount = new Discount();
            $discount->setName("Coupon " . $coupon->getName());
            $discount->setPrice(-1 * $cart->getTotalPrice() * ($coupon->getDiscount() / 100));
            $discount->setId(Discount::ID_COUPON);
            $cart->addItem($discount);
            $cart->setCoupon($coupon);
        }
        $cartManager->markAsPayed($cart, null, $payDate);

        return $cart;
    }

    public function isLoggedIn()
    {
        /** @var REST $rest */
        $rest = $this->getModule('REST');
        $rest->sendGET("/m/api/login_status");

        /** @var TestHelper $helper */
        $helper = $this->getModule('TestHelper');
        $helper->saveCsrfToken();

        return $rest->grabDataFromResponseByJsonPath('$.authorized')[0];
    }

    /**
     * @param bool $rememberMe
     * @param null $otc
     */
    public function login($login, $password, $rememberMe = false, $otc = null)
    {
        /** @var REST $rest */
        $rest = $this->getModule('REST');
        $rest->client->getCookieJar()->expire("MOCKSESSID");
        $this->assertFalse($this->isLoggedIn());

        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');

        $rest->sendPOST("/user/check", []);
        $csrf = $rest->grabDataFromResponseByJsonPath("csrf_token");

        $clientCheck = $symfony->grabService('session')->get('client_check');
        $rest->haveHttpHeader("X-Scripted", $clientCheck['result']);
        $params = ["login" => $login, "password" => $password, "_csrf_token" => $csrf[0]];

        if (!empty($rememberMe)) {
            $params['_remember_me'] = 'true';
        }

        if (!empty($otc)) {
            $params['_otc'] = $otc;
        }
        $rest->sendPOST("/login_check", $params);
        $result = $rest->grabDataFromResponseByJsonPath('$.success')[0];

        return $result;
    }

    public function amOnBusiness()
    {
        /** @var Symfony $symfonyModule */
        $symfonyModule = $this->getModule('Symfony');

        if ($this->hasModule('WebHelper')) {
            /** @var WebHelper $I */
            $I = $this->getModule('WebHelper');
            $domain = $symfonyModule->_getContainer()->getParameter('business_host');
            $I->amOnSubdomain(explode('.', $domain)[0]);
        }

        if ($this->hasModule('TestHelper')) {
            /** @var TestHelper $I */
            $I = $this->getModule('TestHelper');
            $I->amOnSubdomain('business');
        }
    }

    public function loadPage($method, $route, $params)
    {
        $this->getModule('Symfony')->_loadPage($method, $route, $params);
    }

    public function followMetaRedirect()
    {
        /** @var Symfony $symfonyModule */
        $symfonyModule = $this->getModule('Symfony');
        $source = $symfonyModule->grabPageSource();

        if (preg_match('#<meta\s+http\-equiv=[\'"]?refresh[\'"]?\s+content=[\'"]?\d+;url=([^\'"]+)[\'"]?>#ims', $source, $matches)) {
            $symfonyModule->amOnPage($matches[1]);
        }
    }

    public function setServerParameter($key, $value)
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $symfony->client->setServerParameter($key, $value);
    }

    public function createAwMobileDevice($userId)
    {
        /** @var CustomDb $I */
        $I = $this->getModule('CustomDb');
        $I->haveInDatabase("MobileDevice", [
            "UserID" => $userId,
            "DeviceType" => MobileDevice::TYPE_IOS,
            "DeviceKey" => StringUtils::getRandomCode(40),
            "Lang" => "en",
        ]);
    }

    public function assertArrayContainsArray(array $expected, array $actual)
    {
        if ((new ArrayContainsComparator($actual))->containsArray($expected)) {
            return;
        }

        $comparator = new ArrayComparator();
        $comparator->setFactory(new Factory());

        try {
            $comparator->assertEquals($expected, $actual);
        } catch (ComparisonFailure $failure) {
            throw new ExpectationFailedException("Array does not contain the provided array\n", $failure);
        }
    }

    public function assertArrayNotContainsArray(array $needle, array $haystack)
    {
        $this->assertFalse(
            (new ArrayContainsComparator($haystack))->containsArray($needle),
            "Array contains provided array\n"
            . "- <info>" . var_export($needle, true) . "</info>\n"
            . "+ " . var_export($haystack, true)
        );
    }

    public function createTwoFactorAuthCode($userId)
    {
        $authenticator = new GoogleAuthenticator();
        $secret = $authenticator->generateSecret();
        $generator = (new \RandomLib\Factory())->getMediumStrengthGenerator();
        $key = implode('-', str_split($generator->generateString(32, str_replace(str_split(TwoFactorAuthenticationService::LOOK_ALIKE_CHARSET), '', TwoFactorAuthenticationService::RECOVERY_KEY_CHARSET)), 4));

        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var PasswordEncryptor $encryptor */
        $encryptor = $symfony->grabService(PasswordEncryptor::class);
        $db->executeQuery('UPDATE Usr SET GoogleAuthSecret = \'' . $encryptor->encrypt($secret) . '\', GoogleAuthRecoveryCode = \'' . $encryptor->encrypt($key) . '\' WHERE UserID = ' . $userId);

        return [$secret, $key, $authenticator];
    }

    public function createTripSegment(array $fields)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        foreach ([
            "DepCode" => "DepName",
            "ArrCode" => "ArrName",
            "DepDate" => "ScheduledDepDate",
            "ArrDate" => "ScheduledArrDate",
        ] as $sourceField => $targetField) {
            if (!isset($fields[$targetField]) && isset($fields[$sourceField])) {
                $fields[$targetField] = $fields[$sourceField];
            }
        }

        return $db->haveInDatabase("TripSegment", $fields);
    }

    public function createInstance(string $className, array $arguments = [])
    {
        $ref = new \ReflectionClass($className);

        $params = [];

        foreach ($ref->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getType() === null) {
                throw new \Exception("Could not mock {$className}, unknown type for constructor argument '{$parameter->name}'");
            }

            $paramValue = null;

            if (isset($arguments[$parameter->getName()])) {
                $paramValue = $arguments[$parameter->getName()];
            } else {
                switch ($parameter->getType()) {
                    case "bool":
                        $paramValue = false;

                        break;

                    default:
                        /** @var SymfonyTestHelper $symfonyTestHelper */
                        $symfonyTestHelper = $this->getModule('SymfonyTestHelper');
                        $paramValue = $symfonyTestHelper->stubMakeEmpty($parameter->getType()->getName());
                }
            }

            $params[] = $paramValue;
        }

        return new $className(...$params);
    }

    public function createAbMessage(int $requestId, int $userId, string $text, ?int $createDate = null, $fromBooker = true): int
    {
        if ($createDate === null) {
            $createDate = time();
        }
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase("AbMessage", [
            "UserID" => $userId,
            "RequestID" => $requestId,
            "CreateDate" => date("Y-m-d H:i:s", $createDate),
            "Post" => $text,
            "Type" => AbMessage::TYPE_COMMON,
            "FromBooker" => $fromBooker ? 1 : 0,
        ]);
    }

    public function markAbMessageRead(int $requestId, int $userId, ?int $readDate = null): int
    {
        if ($readDate === null) {
            $readDate = time();
        }
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        return $db->haveInDatabase("AbRequestMark", [
            "ReadDate" => date("Y-m-d H:i:s", $readDate),
            "UserID" => $userId,
            "RequestID" => $requestId,
        ]);
    }

    public function switchToUser(string $loginOrUserId): void
    {
        $rest = $this->getModule('REST');

        if (is_numeric($loginOrUserId)) {
            /** @var CustomDb $db */
            $db = $this->getModule('CustomDb');
            $loginOrUserId = $db->grabFromDatabase("Usr", "Login", ["UserID" => $loginOrUserId]);
        }

        $rest->sendGET("/m/api/login_status?_switch_user=" . $loginOrUserId);
    }

    public function fillMileValueData(array $providersId = [2, 7, 190], bool $isRandomCost = true)
    {
        /** @var CustomDb $db */
        $db = $this->getModule('CustomDb');

        foreach ($providersId as $providerId) {
            $certifyDate = $db->grabFromDatabase('ProviderMileValue', 'CertificationDate', ['ProviderID' => $providerId]);

            foreach (array_merge(MileValueService::CLASSOFSERVICE_ECONOMY, MileValueService::CLASSOFSERVICE_BUSINESS) as $classOfService) {
                foreach ([0, 1] as $international) {
                    for ($i = 0; $i < 6; $i++) {
                        if (null === $certifyDate) {
                            $createDate = '2021-01-01 00:00:00';
                            $depDate = '2021-01-03 00:00:00';
                        } else {
                            $time = strtotime($certifyDate);
                            $createDate = date('Y-m-d H:i:s', $time - 86400);
                            $depDate = date('Y-m-d H:i:s', $time);
                        }

                        if ($isRandomCost) {
                            $totalMilesSpent = rand(1000, 5000);
                            $totalTaxesSpent = rand(500, 1000);
                            $alternativeCost = rand(5000, 10000);
                        } else {
                            $totalMilesSpent = 3000;
                            $totalTaxesSpent = 700;
                            $alternativeCost = 5000;
                        }

                        $db->haveInDatabase(
                            'MileValue',
                            [
                                'ProviderID' => $providerId,
                                'ClassOfService' => $classOfService,
                                'CreateDate' => $createDate,
                                'TotalMilesSpent' => $totalMilesSpent,
                                'TotalTaxesSpent' => $totalTaxesSpent,
                                'AlternativeCost' => $alternativeCost,
                                'Route' => 'SSA-BPS',
                                'International' => $international,
                                'MileRoute' => 'SSA-BPS',
                                'CashRoute' => 'SSA-BPS',
                                'BookingClasses' => '',
                                'CabinClass' => 'Economy',
                                'DepDate' => $depDate,
                                'MileDuration' => 1,
                                'CashDuration' => 1,
                                'Hash' => md5(mt_rand()),
                                'UpdateDate' => $createDate,
                                'MileValue' => 0.5,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * next request to aw.curl_driver->request will return this response
     * you could add multiple next responses multiple times, FIFO.
     */
    public function mockNextHttpResponse(\HttpDriverResponse $response)
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        /** @var HttpDriverMock $http */
        $http = $symfony->grabService('aw.curl_driver');
        $http->mockNextResponse($response);
    }

    public function createAwReservation(int $userId, array $reservationFields = []): int
    {
        $db = $this->getModule('CustomDb');
        $geoTagId = $db->haveInDatabase("GeoTag", [
            "Address" => "Some address " . bin2hex(random_bytes(10)),
            "Lat" => 0,
            "Lng" => 0,
            "TimeZoneLocation" => "UTC",
        ]);

        return $db->haveInDatabase("Reservation", array_merge([
            "UserID" => $userId,
            "ConfirmationNumber" => "CONFN1",
            "HotelName" => "Santa Barbara",
            "Address" => "1100 Palomino Road, Santa Barbara, CA 93105, United States",
            "CheckInDate" => date("Y-m-d", strtotime("+1 week")),
            "CheckOutDate" => date("Y-m-d", strtotime("+2 week")),
            'RoomCount' => 1,
            'GuestCount' => 1,
            'GeoTagID' => $geoTagId,
            'Total' => 100,
            'CurrencyCode' => 'USD',
        ], $reservationFields));
    }

    public function _failed(TestInterface $test, $fail)
    {
        $test->__aw_failed_test = true;
    }

    private function clearTestProperties(object $test)
    {
        ClosureStorage::clear();
        $this->doClearTestProperties($test);

        parent::_after($test);
    }

    private function restoreGlobals()
    {
        $this->restorer->restoreGlobalVariables($this->globalsSnapshot);
        //        $this->restorer->restoreStaticAttributes($this->globalsSnapshot);
    }

    /**
     * @throws \ReflectionException
     */
    private function doClearTestProperties(object $test): void
    {
        /** @var \ReflectionProperty $reflectionProperty */
        foreach ((new \ReflectionClass($test))->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            foreach ([
                'testClassInstance',
                'dispatcher',
            ] as $prefix) {
                if (stripos($name, $prefix) === 0) {
                    continue 2;
                }
            }

            try {
                $reflectionProperty->setAccessible(true);
                // ignore already unset (in _after) properties
                @$value = $reflectionProperty->getValue($test);

                if (!is_object($value) || !isset($value)) {
                    continue;
                }

                if ($value instanceof Actor) {
                    continue;
                }

                $class = get_class($value);

                foreach ([
                    '\PHPUnit_Framework_',
                    'PHPUnit_Framework_',
                    '\Codeception',
                    'Codeception',
                    '\SebastianBergmann',
                    'SebastianBergmann',
                ] as $prefix) {
                    if (stripos($class, $prefix) === 0) {
                        continue 2;
                    }
                }

                $reflectionProperty->setValue($test, null);
                codecept_debug(sprintf('[Aw] Freeing "%s" property with type "%s"', $name, $class));
            } finally {
                $reflectionProperty->setAccessible(false);
            }
        }

        if (
            $test instanceof TestInterface
            && !($test->__aw_failed_test ?? false)
        ) {
            if ($test instanceof Cest) {
                if ($scenario = $test->getScenario()) {
                    $this->reflScenarioSteps->setAccessible(true);
                    $this->reflScenarioSteps->setValue($scenario, []);
                    $this->reflScenarioSteps->setAccessible(false);
                }

                if ($testClassInstance = $test->getTestClass()) {
                    $this->doClearTestProperties($testClassInstance);
                }
            }

            if (
                ($metadata = $test->getMetadata())
                && $metadata->getCurrent('example')
            ) {
                $this->reflMetadataCurrent->setAccessible(true);
                $currentMetadata = $this->reflMetadataCurrent->getValue($metadata);
                $currentMetadata['example'] = self::MEMORY_LEAK_PREVENTION;
                $currentMetadata['modules'] = [];
                $this->reflMetadataCurrent->setValue($metadata, $currentMetadata);
                $this->reflMetadataCurrent->setAccessible(false);

                try {
                    /** @var Di $di */
                    $di = $metadata->getService('di');

                    if ($di && $di->get(Example::class)) {
                        $di->set($this->exampleLeakPrevention);
                    }
                } catch (InjectionException $e) {
                }
            }
        }
    }
}

class ClosureStorage
{
    /**
     * @var \Closure[]
     */
    private static $storage = [];

    /**
     * @return \Closure
     */
    public static function get($name)
    {
        return self::$storage[$name];
    }

    public static function set($name, \Closure $closure)
    {
        self::$storage[$name] = $closure;
    }

    public static function clear()
    {
        self::$storage = [];
    }
}
