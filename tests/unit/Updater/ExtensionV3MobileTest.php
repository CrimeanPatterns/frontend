<?php

namespace AwardWallet\Tests\Unit\Updater;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Updater\Engine\CheckAccountResponse;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportRequest;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Updater\AddAccount;
use AwardWallet\MainBundle\Updater\Event\AbstractEvent;
use AwardWallet\MainBundle\Updater\Event\ExtensionV3Event;
use AwardWallet\MainBundle\Updater\Event\LocalPasswordEvent;
use AwardWallet\MainBundle\Updater\Event\StartProgressEvent;
use AwardWallet\MainBundle\Updater\Event\SwitchFromBrowserEvent;
use AwardWallet\MainBundle\Updater\Event\SwitchToBrowserEvent;
use AwardWallet\MainBundle\Updater\Event\UpdatedEvent;
use AwardWallet\MainBundle\Updater\EventsChannelMigrator;
use AwardWallet\MainBundle\Updater\Formatter\MobileEvents\ExtensionV3MobileEvent;
use AwardWallet\MainBundle\Updater\Option;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;
use Codeception\Module\Symfony;
use Herrera\Version\Parser;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\chain;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class ExtensionV3MobileTest extends UpdaterBase
{
    /**
     * @var array<class-string<AbstractEvent>, callable(AbstractEvent): AbstractEvent>
     */
    private static array $formattersMap;

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        if (!isset(self::$formattersMap)) {
            self::$formattersMap = [
                AbstractEvent::class => function (AbstractEvent $e) {
                    $class = \get_class($e);
                    $refl = new \ReflectionClass($e);
                    $constructor = $refl->getConstructor();
                    $args = [];

                    if ($constructor) {
                        foreach ($constructor->getParameters() as $parameter) {
                            $type = $parameter->getType();

                            if ($type) {
                                if ($type->allowsNull()) {
                                    $args[] = null;
                                } else {
                                    if ($type->isBuiltin()) {
                                        switch ((string) $type) {
                                            case 'string':
                                                $args[] = '';

                                                break;

                                            case 'int':
                                                $args[] = 0;

                                                break;

                                            default:
                                                throw new \LogicException('Unsupported type: ' . $type);
                                        }
                                    } else {
                                        throw new \LogicException('Unsupported type: ' . $type);
                                    }
                                }
                            } else {
                                $args[] = null;
                            }
                        }
                    }

                    $newEvent = new $class(...$args);
                    $newEvent->accountId = $e->accountId;

                    return $newEvent;
                },
            ];
        }
    }

    public function afterUserCreated(int $userId)
    {
        parent::afterUserCreated($userId);

        $this->db->haveInDatabase('GroupUserLink', [
            'SiteGroupID' => $this->db->grabFromDatabase(
                'SiteGroup',
                'SiteGroupID',
                ['GroupName' => 'staff:extension_v3_tester']
            ),
            'UserID' => $userId,
        ]);
    }

    public function _before()
    {
        parent::_before();

        $this->UPDATER_SERVICE = 'aw.updater_session.mobile';
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');
        $reqStack = $symfony->grabService(RequestStack::class);
        $reqStack->push(new Request());
        /** @var ApiVersioningService $apiVersioning */
        $apiVersioning = $symfony->grabService(ApiVersioningService::class);
        $apiVersioning->setVersion(Parser::toVersion('4.47.0'));
        $this->db->updateInDatabase('Usr', ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS], ['UserID' => $this->user->getId()]);
        $this->em->refresh($this->user);
    }

    public function testAccountsWithLocalPasswordsShouldSetAllV3AccountsOnHoldWhileOthersAreChecked()
    {
        $lpmMock = $this->prophesize(LocalPasswordsManager::class);
        /** @var array<int, Account> $accountsMap */
        $accountsMap =
            it([
                // two v3 accounts with server passwords
                /*  0 */ $this->createAccount($lpmMock, true, false, true),
                /*  1 */ $this->createAccount($lpmMock, true, false, true),
                // two v3 accounts with local passwords and passwords present
                /*  2 */ $this->createAccount($lpmMock, true, true, true),
                /*  3 */ $this->createAccount($lpmMock, true, true, true),
                // two v3 accounts with local passwords and passwords absent
                /*  4 */ $this->createAccount($lpmMock, true, true, false),
                /*  5 */ $this->createAccount($lpmMock, true, true, false),
                // two non-v3 accounts with local passwords and passwords present
                /*  6 */ $this->createAccount($lpmMock, false, true, true),
                /*  7 */ $this->createAccount($lpmMock, false, true, true),
                // two non-v3 accounts with local passwords and passwords absent
                /*  8 */ $this->createAccount($lpmMock, false, true, false),
                /*  9 */ $this->createAccount($lpmMock, false, true, false),
                // two non-v3 accounts with server passwords
                /* 10 */ $this->createAccount($lpmMock, false, false, true),
                /* 11 */ $this->createAccount($lpmMock, false, false, true),
            ])
            ->reindex(fn (Account $a) => $a->getId())
            ->toArrayWithKeys();
        // Make a handy named maps for further usage
        /** @var array<int, Account> $accountsPassedToServerCheckMap */
        $accountsPassedToServerCheckMap = \array_merge(
            \array_slice($accountsMap, 6, 2, true),
            \array_slice($accountsMap, 10, 2, true),
        );
        /** @var array<int, Account> $accountV3 */
        $accountsV3Map = \array_slice($accountsMap, 0, 6, true);
        /** @var array<int, Account> $accountsWithAbsentLocalPasswordsMap */
        $accountsWithAbsentLocalPasswordsMap = \array_merge(
            \array_slice($accountsMap, 4, 2, true),
            \array_slice($accountsMap, 8, 2, true)
        );
        /** @var list<Account> $accountsV3WithAbsentLocalPasswordsList */
        $accountsV3WithAbsentLocalPasswordsList = \array_values(\array_slice($accountsMap, 4, 2, true));

        $this->mockService(LocalPasswordsManager::class, $lpmMock->reveal());
        $this->mockService(
            ApiCommunicator::class,
            $this->prophesize(ApiCommunicator::class)
            ->CheckExtensionSupport(Argument::that(fn (CheckExtensionSupportPackageRequest $req) =>
                it($req->getPackage())
                ->map(fn (CheckExtensionSupportRequest $subReq) => $subReq->getId())
                ->sort()
                ->toArray()
                ===
                it($accountsV3Map)
                ->map(fn (Account $a) => (string) $a->getId())
                ->sort()
                ->toArray()
            ))
            ->willReturn(
                (new CheckExtensionSupportPackageResponse())
                ->setPackage(
                    it($accountsV3Map) // only v3 accounts
                    ->flatMap(fn (Account $a) => yield (string) $a->getId() => true)
                    ->toArrayWithKeys()
                )
            )
            ->shouldBeCalledOnce()
            ->getObjectProphecy()
            ->reveal()
        );

        $engineMock = $this->prophesize(UpdaterEngineInterface::class);

        $serverCheckFinisher = function (int $accountId, string $loyaltyRequestId) {
            $this->aw->finishAccountCheck($accountId, $loyaltyRequestId);
        };
        $loyaltyRequestMap = [];

        $makeSendAccountsCallMock = function (array $accountsEntities, bool $v3SessionStart, bool $autoFinish) use ($engineMock, &$loyaltyRequestMap, $serverCheckFinisher) {
            return $engineMock
                ->sendAccounts(
                    Argument::that(fn (array $accounts) => it($accounts)
                            ->map(fn (array $a) => $a['ID'])
                            ->sort()
                            ->toArray()
                        ===
                        it($accountsEntities)
                            ->map(fn (Account $a) => $a->getId())
                            ->sort()
                            ->toArray()
                    ),
                    Argument::cetera()
                )
                ->will(function (array $args) use (&$loyaltyRequestMap, $autoFinish, $v3SessionStart, $serverCheckFinisher): array {
                    [$preparedAccountsMap] = $args;
                    /** @var list<CheckAccountResponse> $responsesList */
                    $responsesList = [];

                    foreach ($preparedAccountsMap as $accountId => $_) {
                        $loyaltyRequestId = \bin2hex(\random_bytes(16));
                        $loyaltyRequestMap[$accountId] = $loyaltyRequestId;

                        if ($autoFinish) {
                            $serverCheckFinisher($accountId, $loyaltyRequestId);
                        }

                        $responsesList[] = $v3SessionStart ?
                            new CheckAccountResponse(
                                $loyaltyRequestId,
                                $accountId,
                                "{$accountId}_sessionid",
                                "{$accountId}_token"
                            ) :
                            new CheckAccountResponse(
                                $loyaltyRequestId,
                                $accountId,
                                null,
                                null
                            );
                    }

                    return $responsesList;
                })
                ->shouldBeCalledOnce();
        };
        $engineMock
            ->getUpdateSlots(Argument::cetera())
            ->willReturn(5)
        ;
        $makeSendAccountsCallMock(
            $accountsPassedToServerCheckMap,
            false,
            true
        );

        $this->mockService(
            UpdaterEngineInterface::class,
            $engineMock->reveal(),
        );

        $this->mockService(
            EventsChannelMigrator::class,
            $this->prophesize(EventsChannelMigrator::class)
            ->send(Argument::type('string'))
            ->willReturn('somelonglongtoken')
            ->shouldBeCalledOnce()
            ->getObjectProphecy()
            ->reveal()
        );

        $updater = $this->getUpdater();
        $updater->setOption(Option::CLIENT_PLATFORM, ClientPlatform::MOBILE);
        $updater->setOption(Option::EXTENSION_V3_SUPPORTED, true);
        $updater->setOption(Option::EXTENSION_V3_INSTALLED, true);

        $updaterStartResponse = $updater->start(
            it($accountsMap)
            ->map(fn (Account $a) => $a->getId())
            ->toArray()
        );
        $this->waitEvents(
            $updaterStartResponse,
            chain(
                it($accountsWithAbsentLocalPasswordsMap)
                ->map(fn (Account $a) => new LocalPasswordEvent($a->getId(), null, null, null)),

                it($accountsPassedToServerCheckMap)
                ->map(fn (Account $a) => new StartProgressEvent($a->getId(), null, null)),

                it($accountsPassedToServerCheckMap)
                ->map(fn (Account $a) => new UpdatedEvent($a->getId(), null))
            )
            ->toArray(),
            true,
            self::$formattersMap
        );

        [$providedV3LocalPassword, $refusedV3LocalPassword] = $accountsV3WithAbsentLocalPasswordsList;
        $lpmMock
            ->hasPassword($providedV3LocalPassword->getId())
            ->willReturn(true);
        $lpmMock
            ->getPassword($providedV3LocalPassword->getId())
            ->willReturn('some');

        $v3CheckedAccountsMap = $accountsV3Map;
        unset($v3CheckedAccountsMap[$refusedV3LocalPassword->getId()]);
        /** @var list<AbstractEvent> $finalEvents */
        $finalEvents =
            it($v3CheckedAccountsMap)
            ->prepend(null) // reduce callback can check for first tick
            ->append(null) // reduce callback can check for last tick
            ->values()
            ->sliding(2)
            ->reduce(
                /**
                 * @param list<AbstractEvent> $previousEvents
                 * @param array{0: ?Account, 1: ?Account} $windowList
                 */
                function (array $previousEvents, array $windowList, int $idx) use (&$loyaltyRequestMap, $makeSendAccountsCallMock, $refusedV3LocalPassword, $providedV3LocalPassword, $updaterStartResponse, $updater, $serverCheckFinisher) {
                    [$firstAccount, $secondAccount] = $windowList;
                    $isFirstTick = (null === $firstAccount);
                    $isLastTick = (null === $secondAccount);

                    if (!$isFirstTick) {
                        // finish check from the previous iteration
                        $serverCheckFinisher($firstAccount->getId(), $loyaltyRequestMap[$firstAccount->getId()]);
                    }

                    if (!$isLastTick) {
                        // send next account to check
                        $makeSendAccountsCallMock(
                            [$secondAccount],
                            true,
                            false
                        );
                    }

                    $tickEvents = $updater->tick(
                        $updaterStartResponse->key,
                        \count($updaterStartResponse->events) + \count($previousEvents),
                        $isFirstTick ?
                            [AddAccount::createLowPriority($providedV3LocalPassword->getId())] :
                            [],
                        [],
                        $isFirstTick ?
                            [$refusedV3LocalPassword->getId()] :
                            []
                    );

                    if (!$isLastTick) {
                        $expectedEvent = new ExtensionV3MobileEvent(
                            $secondAccount->getId(),
                            "{$secondAccount->getId()}_sessionid",
                            "{$secondAccount->getId()}_token",
                            30
                        );
                        $expectedEvent->login = $secondAccount->getLogin();
                        $secondProvider = $secondAccount->getProviderid();
                        $expectedEvent->displayName = $secondProvider->getDisplayname();
                        $expectedEvent->providerCode = $secondProvider->getCode();
                        $this->assertEquals(
                            [$expectedEvent],
                            it(self::filterDebug($tickEvents))
                                ->filter(fn (AbstractEvent $e) => $e instanceof ExtensionV3Event)
                                ->toArray(),
                            "V3 Accounts should be checked on by one, no other events should be generated in the meantime. Idx: {$idx}"
                        );
                    }

                    $noNewEventsList = [];

                    foreach (\range(1, 10) as $_) {
                        $noNewEventsList = \array_merge(
                            $noNewEventsList,
                            $updater->tick(
                                $updaterStartResponse->key,
                                \count($updaterStartResponse->events) + \count($previousEvents) + \count($tickEvents) + \count($noNewEventsList),
                            )
                        );
                    }

                    $this->assertEmpty(
                        self::filterDebug($noNewEventsList),
                        "No new events should be generated until account is checked. Idx: {$idx}"
                    );

                    return \array_merge($previousEvents, $tickEvents, $noNewEventsList);
                },
                []
            );

        $actualV3EventsWithoutDebug = $this->formatEvents(
            self::filterDebug($finalEvents),
            \array_merge(
                self::$formattersMap,
                [SwitchToBrowserEvent::class => fn ($obj) => $obj]
            )
        );
        // due to plugins nature of popping accounts ExtensionV3Events and
        // UpdatedEvents are interleaved in irregular way, so we test them separately
        $this->assertEquals(
            chain(
                [new SwitchToBrowserEvent('somelonglongtoken')],

                it($v3CheckedAccountsMap)
                ->map(fn (Account $a) => new ExtensionV3MobileEvent($a->getId(), '', '', 0)),

                [new SwitchFromBrowserEvent()]
            )
            ->toArray(),
            it($actualV3EventsWithoutDebug)
            ->filter(fn (AbstractEvent $e) =>
                $e instanceof SwitchToBrowserEvent
                || $e instanceof ExtensionV3Event
                || $e instanceof SwitchFromBrowserEvent
            )
            ->toArray()
        );
        $this->assertEquals(
            it($v3CheckedAccountsMap)
            ->map(fn (Account $a) => new UpdatedEvent($a->getId(), null))
            ->toArray(),
            it($actualV3EventsWithoutDebug)
            ->filter(fn (AbstractEvent $e) => $e instanceof UpdatedEvent)
            ->toArray()
        );
    }

    /**
     * @param ObjectProphecy|LocalPasswordsManager $localPasswordsManagerMock
     */
    protected function createAccount(ObjectProphecy $localPasswordsManagerMock, bool $v3, bool $localPassword, bool $passwordPresents): Account
    {
        $providerId = $this->aw->createAwProvider(null, null, [
            'Code' => StringUtils::getRandomCode(10),
            "IsExtensionV3ParserEnabled" => $v3,
            'CanCheck' => true,
            'CheckInBrowser' => \CHECK_IN_SERVER,
        ]);

        $accountId = $this->aw->createAwAccount(
            $this->user->getId(),
            $providerId,
            'login',
            'pass',
            [
                'SavePassword' => $localPassword ? \SAVE_PASSWORD_LOCALLY : \SAVE_PASSWORD_DATABASE,
            ]
        );

        if ($localPassword) {
            $localPasswordsManagerMock
                ->hasPassword($accountId)
                ->willReturn($passwordPresents);

            if ($passwordPresents) {
                $localPasswordsManagerMock
                    ->getPassword($accountId)
                    ->willReturn('some');
            }
        }

        return $this->em->getRepository(Account::class)->find($accountId);
    }
}
