<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountbalance;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\BusinessInfo;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\Globals\Logger\StubLogger;
use AwardWallet\MainBundle\Globals\Updater\Engine\Local;
use AwardWallet\MainBundle\Globals\Updater\Engine\Wsdl;
use AwardWallet\MainBundle\Globals\Utils\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

use function AwardWallet\MainBundle\Globals\Utils\Criteria\Expression\andX;
use function AwardWallet\MainBundle\Globals\Utils\Criteria\Expression\eq;
use function AwardWallet\MainBundle\Globals\Utils\Criteria\Expression\isNull;
use function AwardWallet\MainBundle\Globals\Utils\Criteria\Expression\orX;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AwDataGenerator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Connection
     */
    private $dbConnection;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var Local
     */
    private $updaterEngineLocal;
    /**
     * @var Wsdl
     */
    private $updaterEngineWsdl;

    private AccountFactory $accountFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $dbConnection,
        PropertyAccessorInterface $propertyAccessor,
        Local $updaterEngineLocal,
        Wsdl $updaterEngineWsdl,
        AccountFactory $accountFactory
    ) {
        $this->entityManager = $entityManager;
        $this->dbConnection = $dbConnection;
        $this->propertyAccessor = $propertyAccessor;
        $this->updaterEngineLocal = $updaterEngineLocal;
        $this->updaterEngineWsdl = $updaterEngineWsdl;
        $this->accountFactory = $accountFactory;
    }

    public function createAwUser(?string $login, string $password, array $userFields = []): Usr
    {
        $userData = $this->getFakeUserData();
        unset($userData['Password']);

        if (isset($login)) {
            $userData['Login'] = $login;
        }

        $encoder = new NativePasswordEncoder(null, null, 4, \PASSWORD_BCRYPT);
        $userData['Pass'] = $encoder->encodePassword($password, null);

        $userData = array_merge($userData, ['AccountLevel' => ACCOUNT_LEVEL_FREE], $userFields);

        $businessInfo = $userFields['BusinessInfo'] ?? [];
        unset($userData['BusinessInfo']);

        $betaData = (($userData['Beta'] ?? false) ? ['InBeta' => 1, 'BetaApproved' => 1] : []);
        unset($userData['Beta']);
        $groupData = $userData['Groups'] ?? [];
        unset($userData['Groups']);

        $userData = array_merge($betaData, $userData);

        /** @var Usr $usr */
        $usr = $this->applyDataToEntity(new Usr(), $userData);
        $this->saveEntity($usr);
        $userId = $usr->getUserid();

        if ($userData['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
            $businessInfo = $this->applyDataToEntity(new BusinessInfo($usr, 1000, 100), $businessInfo);
            $this->saveEntity($businessInfo);
        }

        $groupRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Sitegroup::class);

        foreach ($groupData as $groupName) {
            $usr->getGroups()->add($groupRep->findOneBy(['groupname' => $groupName]));
        }

        $this->saveEntity($usr);

        return $usr;
    }

    /**
     * @param string|int|Usr $user
     * @param string|int|Provider $provider
     */
    public function createAwAccount($user, $provider, string $login, string $password = '', array $fields = []): Account
    {
        $providerRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
        $userRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $accountData = \array_merge($this->getFakeAccountData(), $fields);
        $accountData['UserID'] = $this->findUser($user);

        if (is_scalar($provider)) {
            $provider = $providerRep->matching(Criteria::create()
                ->where(orX(
                    eq('code', $provider),
                    eq('providerid', $provider)
                ))
                ->setMaxResults(1)
            )->first();
            $accountData['ProviderID'] = $provider;
        } else {
            $accountData['ProviderID'] = $provider;
        }

        $accountData['Login'] = $login;
        $accountData['Pass'] = $password;

        $properties = ($accountData['Properties'] ?? []);
        unset($accountData['Properties']);

        $balanceData = $accountData['BalanceData'] ?? [];
        unset($accountData['BalanceData']);

        $subAccounts = $accountData['SubAccounts'] ?? [];
        $accountData['SubAccounts'] = \count($subAccounts);

        /** @var Account $account */
        $account = $this->applyDataToEntity($this->accountFactory->create(), $accountData);
        $this->saveEntity($account);

        $this->saveLoyaltyProperties($account, $properties);

        foreach ($subAccounts as $subAccount) {
            $this->createAwSubAccount($account, $subAccount['DisplayName'], $subAccount['Code'] ?? null, $subAccount);
        }

        $this->saveBalanceData($account, $balanceData);

        return $account;
    }

    public function createAwSubAccount(Account $account, string $name, ?string $code, array $fields = []): Subaccount
    {
        $subAccountData = [
            'IsHidden' => false,
            'DisplayName' => $name,
            'Code' => $code ?? preg_replace('/[^a-z_]/', '', $name),
            'AccountID' => $account,
        ];

        $subAccountData = array_merge($subAccountData, $fields);
        $properties = $subAccountData['Properties'] ?? [];
        unset($subAccountData['Properties']);
        $balanceData = $subAccountData['BalanceData'] ?? [];
        unset($subAccountData['BalanceData']);

        /** @var Subaccount $subAccount */
        $subAccount = $this->applyDataToEntity(new Subaccount(), $subAccountData);
        $this->saveEntity($subAccount);
        $this->saveLoyaltyProperties($subAccount, $properties);

        $this->saveBalanceData($subAccount, $balanceData);

        return $subAccount;
    }

    public function createAwFamilyMember($user, string $firstName, string $lastName, ?string $midName = null, ?string $email = null): Useragent
    {
        $user = $this->findUser($user);
        /** @var Useragent $useragent */
        $useragent = $this->applyDataToEntity(new Useragent(), [
            "AgentID" => $user,
            "FirstName" => $firstName,
            "LastName" => $lastName,
            "MidName" => $midName,
            "Email" => $email,
            "IsApproved" => 1,
            "TripAccessLevel" => 1,
            'AccessLevel' => 0,
        ]);

        $this->saveEntity($useragent);

        return $useragent;
    }

    public function mockAwProvider(string $code, array $instanceMethods, array $staticMethods = []): void
    {
        if ($instanceMethods) {
            $defaultInstanceMethodsImpl = [
                '__construct' => function () {
                    parent::__construct();
                    $this->logger = new StubLogger();
                },
                'InitBrowser' => function () {
                    $this->http = new class() extends \HttpBrowser {
                        public function __construct()
                        {
                        }

                        public function start()
                        {
                        }

                        public function Log($s, $level = null, $htmlEncode = true)
                        {
                        }
                    };
                    // Prevent from http-browser logging attempts
                },
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
                    return true;
                },
                'ParseHistory' => function ($startDate = null) {
                    return [];
                },
                'Cleanup' => function () {},
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
            $traitsCode = empty($useTraits) ? "" : "use " . implode(", ", $useTraits) . ";\n";

            $checkerCode = "
                namespace 
                {
                    class {$className} extends TAccountChecker {
                        {$traitsCode}
                        {$methodsCode}
                    }
                }
                ";
            eval($checkerCode);
            $className = '\\' . $className;
            new $className(); // prevent autoloading
        }
    }

    public function createParseItinerariesMethod(array $itineraries): \Closure
    {
        $checkCount = 1;

        return function () use (&$checkCount, $itineraries) {
            array_walk_recursive($itineraries, function (&$value, $key) use (&$checkCount) {
                if (!is_object($value)) {
                    return;
                }

                if ($value instanceof Diff) {
                    $value = $value->storage[$checkCount - 1] ??
                        $value->storage[count($value->storage) - 1];
                }

                if (!is_object($value)) {
                    return;
                }

                if ($value instanceof \Closure) {
                    $value = $value($checkCount);
                }

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->getTimestamp();
                }
            });

            $checkCount++;

            return $itineraries;
        };
    }

    public function checkAccount(int $accountId, bool $withPlans = true, bool $local = true, $history = null)
    {
        $options = \CommonCheckAccountFactory::getDefaultOptions();
        $options->checkIts = $withPlans;
        $options->checkHistory = $history;

        if ($local) {
            $engine = $this->updaterEngineLocal;
        } else {
            $engine = $this->updaterEngineWsdl;
        }

        $accountRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $account = $accountRep->find($accountId);

        if (null === $account) {
            return;
        }

        $engine->sendAccounts([['AccountID' => $account->getAccountid(), 'AutoGatherPlans' => $withPlans]]);
    }

    private function getFakeUserData(): array
    {
        return [
            'Login' => $login = 'test-user-' . \substr(\bin2hex(\openssl_random_pseudo_bytes(7)), 0, 13),
            'Pass' => '$2y$04$8D8o2s3q7bkSRltaEU89fO9S.D/APIQaF2H7HDAvamzkwyPAbfazO', // awdeveloper
            'Password' => 'awdeveloper', // virtual field, removed before fixture insertion
            'FirstName' => 'FirstName' . StringUtils::getPseudoRandomString(10),
            'LastName' => 'LastName' . StringUtils::getPseudoRandomString(10),
            'Email' => $login . '@aw-test-data.io',
            'City' => 'Las Vegas',
            'RegistrationIP' => '107.158.178.6', // Las Vegas IP
            'LastLogonIP' => '107.158.178.6', // Las Vegas IP
            'CreationDateTime' => new \DateTime(),
            'EmailVerified' => EMAIL_VERIFIED,
            'CountryID' => 230, // USA
            'AccountLevel' => ACCOUNT_LEVEL_FREE,
            'RefCode' => StringUtils::getPseudoRandomString(10),
            'Secret' => StringHandler::getRandomCode(32, true),
        ];
    }

    private function applyDataToEntity(object $entity, array $data): object
    {
        foreach ($data as $key => $value) {
            $this->propertyAccessor->setValue($entity, $key, $value);
        }

        return $entity;
    }

    private function saveEntity(object $entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    private function findUser($user): Usr
    {
        $userRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        if (is_scalar($user)) {
            return $userRep->matching(Criteria::create()
                ->where(orX(
                    eq('userid', $user),
                    eq('login', $user)
                ))
                ->setMaxResults(1)
            )->first();
        } else {
            return $user;
        }
    }

    private function getFakeAccountData(): array
    {
        return [
            'State' => ACCOUNT_CHECKED,
            'ErrorCode' => ACCOUNT_ENGINE_ERROR,
            'ErrorMessage' => 'Unknown error',
            'CreationDate' => $now = new \DateTime(),
            'UpdateDate' => $now,
            'PassChangeDate' => $now,
            'ModifyDate' => $now,
            'NotRelated' => 1,
        ];
    }

    private function saveBalanceData(LoyaltyProgramInterface $loyaltyProgram, array $balanceData)
    {
        /** @var Accountbalance $balanceDatum */
        foreach ($balanceData as $balanceDatum) {
            if ($balanceDatum instanceof Accountbalance) {
                $balanceChange = (new Accountbalance())
                    ->setBalance($balanceDatum->getBalance())
                    ->setUpdatedate($balanceDatum->getUpdatedate());
            } else {
                ['UpdateDate' => $updateDate, 'Balance' => $balance] = $balanceDatum;

                if (!$updateDate instanceof \DateTime) {
                    $updateDate = new \DateTime($updateDate);
                }

                $balanceChange = (new Accountbalance())
                    ->setBalance($balance)
                    ->setUpdatedate($updateDate);
            }

            if ($loyaltyProgram instanceof Account) {
                $balanceChange
                    ->setAccountid($loyaltyProgram)
                    ->setSubaccountid(null);
            } elseif ($loyaltyProgram instanceof Subaccount) {
                $balanceChange
                    ->setAccountid($loyaltyProgram->getAccountid())
                    ->setSubaccountid($loyaltyProgram);
            } else {
                throw new \RuntimeException('Loyalty program should be account or subaccount');
            }

            $this->saveEntity($balanceChange);

            if ($loyaltyProgram instanceof Account) {
                $loyaltyProgram->getBalanceHistory()->add($balanceChange);
                $this->saveEntity($loyaltyProgram);
            }
        }
    }

    private function saveLoyaltyProperties(LoyaltyProgramInterface $loyaltyProgram, array $properties): void
    {
        $providerPropRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Providerproperty::class);

        $provider = $loyaltyProgram instanceof Account ?
            $loyaltyProgram->getproviderid() :
            $loyaltyProgram->getAccountId()->getProviderid();

        foreach ($properties as $key => $value) {
            $property = new Accountproperty();

            if (ctype_digit((string) $key)) {
                $providerProperty = $providerPropRep->matching(Criteria::create()
                    ->where(andX(
                        eq('kind', $key),
                        orX(
                            eq('providerid', $provider),
                            isNull('providerid')
                        )
                    ))
                    ->orderBy(['providerid' => 'DESC', 'visible' => 'DESC'])
                    ->setMaxResults(1)
                )->first();

                $property->setProviderpropertyid($providerProperty);
            } else {
                $providerProperty = $providerPropRep->matching(Criteria::create()
                    ->where(andX(
                        eq('code', $key),
                        orX(
                            eq('providerid', $provider),
                            isNull('providerid')
                        )
                    ))
                    ->orderBy(['providerid' => 'DESC'])
                    ->setMaxResults(1)
                )->first();
                $property->setProviderpropertyid($providerProperty);
            }

            $property->setVal($value);

            if ($loyaltyProgram instanceof Account) {
                $property->setAccountid($loyaltyProgram);
                $this->saveEntity($property);
                $loyaltyProgram->getProperties()->add($property);
                $this->saveEntity($loyaltyProgram);
            } elseif ($loyaltyProgram instanceof Subaccount) {
                $property->setAccountid($loyaltyProgram->getAccountid());
                $property->setSubaccountid($loyaltyProgram);
                $this->saveEntity($property);
                $this->saveEntity($loyaltyProgram);
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

class Diff
{
    public $storage;

    public function __construct(...$diff)
    {
        $this->storage = $diff;
    }
}

function diff(...$values)
{
    return new Diff(...$values);
}

function array_walk_recursive(array &$array, callable $callback)
{
    foreach ($array as $key => &$value) {
        $callback($value, $key);

        if (is_array($value)) {
            array_walk_recursive($value, $callback);
        }
    }
}
