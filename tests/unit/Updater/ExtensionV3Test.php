<?php

namespace AwardWallet\Tests\Unit\Updater {
    use AwardWallet\MainBundle\Globals\StringUtils;
    use AwardWallet\MainBundle\Globals\Updater\Engine\CheckAccountResponse;
    use AwardWallet\MainBundle\Globals\Updater\Engine\Local;
    use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
    use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
    use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageResponse;
    use AwardWallet\MainBundle\Updater\Event\ExtensionRequiredEvent;
    use AwardWallet\MainBundle\Updater\Event\ExtensionV3Event;
    use AwardWallet\MainBundle\Updater\Event\FailEvent;
    use AwardWallet\MainBundle\Updater\Event\StartProgressEvent;
    use AwardWallet\MainBundle\Updater\Event\UpdatedEvent;
    use AwardWallet\MainBundle\Updater\Option;
    use AwardWallet\Tests\Unit\Updater\ExtensionV3TestFixtures\TestFixture;
    use Codeception\Module\Aw;
    use Codeception\Module\Symfony;
    use Doctrine\DBAL\Connection;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\RequestStack;

    use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

    /**
     * @group frontend-unit
     */
    class ExtensionV3Test extends UpdaterBase
    {
        /**
         * @dataProvider dataProvider
         */
        public function test(TestFixture $fixture)
        {
            $userId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD);

            $this->loginUser($userId);

            /** @var Symfony $symfony */
            $symfony = $this->getModule('Symfony');
            $providerId = $this->aw->createAwProvider(null, null, [
                'Code' => $providerCode = StringUtils::getRandomCode(10),
                "CheckInBrowser" => $fixture->checkInBrowser,
                "IsExtensionV3ParserEnabled" => $fixture->isExtensionV3ParserEnabled,
                "ExtensionV3ParserReady" => $fixture->extensionV3ParserReady ? 1 : 0,
            ]);
            $accountId = $this->aw->createAwAccount($this->user->getId(), $providerId, 'login', 'pass');

            /** @var RequestStack $requestStack */
            $requestStack = $symfony->grabService(RequestStack::class);

            if ($fixture->isMobile) {
                // we need /m/api prefix to trigger mobile check
                $request = Request::create('http://some.host/m/api/some', 'GET', [], [], [], [
                    'HTTP_X_AW_PLATFORM' => 'ios',
                ]);
            } else {
                $request = new Request();
            }
            $requestStack->push($request);

            $localEngine = $this->createMock(Local::class);
            $oldThis = $this;
            $localEngine
                ->method('sendAccounts')
                ->willReturnCallback(function (array $accounts) use ($accountId, $fixture, $oldThis) {
                    $loyaltyRequestId = \bin2hex(\random_bytes(16));
                    /** @var Connection $connection */
                    $this->assertEquals(
                        $fixture->expectedBrowserExtensionAllowed,
                        $accounts[$accountId]["browserExtensionAllowed"]
                    );
                    $oldThis->aw->finishAccountCheck($accountId, $loyaltyRequestId);

                    return [
                        $accounts[$accountId]["browserExtensionAllowed"] && $fixture->extensionSessionReturned ?
                            new CheckAccountResponse($loyaltyRequestId, $accountId, "sess123", "token123") :
                            new CheckAccountResponse($loyaltyRequestId, $accountId, null, null),
                    ];
                })
            ;
            $localEngine
                ->method('getUpdateSlots')
                ->willReturn(1)
            ;

            $this->mockService(UpdaterEngineInterface::class, $localEngine);
            $loyaltyApi = $this->createMock(ApiCommunicator::class);
            $loyaltyApi
                ->method('CheckExtensionSupport')
                ->willReturn(new CheckExtensionSupportPackageResponse([$accountId => $fixture->checkExtensionSupportResult]));
            $this->mockService(ApiCommunicator::class, $loyaltyApi);

            $updater = $this->getUpdater();

            foreach ([
                Option::BROWSER_SUPPORTED => fn (TestFixture $f) => $f->browserSupported,
                Option::EXTENSION_INSTALLED => fn (TestFixture $f) => $f->extensionInstalled,

                Option::EXTENSION_V3_SUPPORTED => fn (TestFixture $f) => $f->extensionV3Supported,
                Option::EXTENSION_V3_INSTALLED => fn (TestFixture $f) => $f->extensionV3Installed,
            ] as $option => $getter) {
                $updater->setOption($option, $getter($fixture));
            }

            $updaterStartResult = $updater->start([$accountId]);
            $this->waitEvents($updaterStartResult, ($fixture->expectedEventsGen)($accountId, $providerCode));
        }

        /**
         * @return list<array{TestFixture}>
         */
        public function dataProvider(): array
        {
            $v3SupportedEventsGen = fn (int $accountId) => [
                new ExtensionV3Event($accountId, "sess123", "token123", 30),
                new UpdatedEvent($accountId, null),
            ];
            $serverCheckEventsGen = fn (int $accountId) => [
                new StartProgressEvent($accountId, 30, null),
                new UpdatedEvent($accountId, null),
            ];
            $v3RequiredEventsGen = fn (int $accountId) => [
                new ExtensionRequiredEvent($accountId, 3, '/extension-install?v3=true', 'Install'),
                new FailEvent(
                    $accountId,
                    'Browser extension is missing'
                ),
            ];
            $v3UpgradeRequiredEventsGen = fn (int $accountId) => [
                new ExtensionRequiredEvent($accountId, 3, '/extension-install?v3=true', 'Upgrade'),
                new FailEvent(
                    $accountId,
                    'Browser extension upgrade required'
                ),
            ];

            return [
                // parser ready, but not enabled, run server check
                [create(function (TestFixture $f) use ($serverCheckEventsGen) {
                    $f->isExtensionV3ParserEnabled = false;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionInstalled = true;
                    $f->extensionV3Installed = true;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $serverCheckEventsGen;
                })],
                // same, mobile
                [create(function (TestFixture $f) use ($serverCheckEventsGen) {
                    $f->isExtensionV3ParserEnabled = false;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionInstalled = true;
                    $f->extensionV3Installed = true;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $serverCheckEventsGen;
                    $f->isMobile = true;
                })],
                // run server check for users
                [create(function (TestFixture $f) use ($serverCheckEventsGen) {
                    $f->isExtensionV3ParserEnabled = false;
                    $f->extensionV3ParserReady = false;
                    $f->checkExtensionSupportResult = false;
                    $f->extensionSessionReturned = false;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionInstalled = false;
                    $f->extensionV3Installed = false;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $serverCheckEventsGen;
                })],
                [create(function (TestFixture $f) use ($serverCheckEventsGen) {
                    // server check fired when v3 and v2 is not enabled
                    $f->isExtensionV3ParserEnabled = false;
                    $f->checkInBrowser = CHECK_IN_SERVER;
                    $f->extensionV3Installed = true;
                    $f->extensionV3Supported = true;
                    $f->extensionSessionReturned = false;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $serverCheckEventsGen;
                })],
                [create(function (TestFixture $f) use ($v3SupportedEventsGen) {
                    // v3 check selected over v2 when v3 is enable
                    $f->isExtensionV3ParserEnabled = true;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionV3Installed = true;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = true;
                    $f->expectedEventsGen = $v3SupportedEventsGen;
                })],
                [create(function (TestFixture $f) use ($v3RequiredEventsGen) {
                    // require v3 when v3 and v2 enabled and v3 not installed
                    $f->isExtensionV3ParserEnabled = true;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionV3Installed = false;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $v3RequiredEventsGen;
                })],
                [create(function (TestFixture $f) use ($v3RequiredEventsGen) {
                    // require v3 when v3 enabled and v2 not enabled and v3 not installed
                    $f->isExtensionV3ParserEnabled = true;
                    $f->checkInBrowser = CHECK_IN_SERVER;
                    $f->extensionV3Installed = false;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $v3RequiredEventsGen;
                })],
                [create(function (TestFixture $f) use ($v3UpgradeRequiredEventsGen) {
                    // show v3 upgrade when v3 enabled and v2 not enabled and v2 installed and v3 not installed
                    $f->isExtensionV3ParserEnabled = true;
                    $f->checkInBrowser = CHECK_IN_SERVER;
                    $f->extensionInstalled = true;
                    $f->extensionV3Installed = false;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $v3UpgradeRequiredEventsGen;
                })],
                [create(function (TestFixture $f) use ($v3UpgradeRequiredEventsGen) {
                    // show v3 upgrade when v3 enabled and v2 enabled and v2 installed and v3 not installed
                    $f->isExtensionV3ParserEnabled = true;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionV3Installed = false;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $v3UpgradeRequiredEventsGen;
                    $f->extensionInstalled = true;
                    $f->browserSupported = true;
                })],
                [create(function (TestFixture $f) use ($serverCheckEventsGen) {
                    // v2 used when v2 enabled and v3 not enabled
                    $f->isExtensionV3ParserEnabled = false;
                    $f->checkInBrowser = CHECK_IN_CLIENT;
                    $f->extensionV3Installed = false;
                    $f->extensionV3Supported = true;
                    $f->expectedBrowserExtensionAllowed = false;
                    $f->expectedEventsGen = $serverCheckEventsGen;
                    $f->extensionInstalled = true;
                    $f->browserSupported = true;
                })],
            ];
        }
    }
}

namespace AwardWallet\Tests\Unit\Updater\ExtensionV3TestFixtures {
    use AwardWallet\MainBundle\Updater\Event\AbstractEvent;

    class TestFixture
    {
        public bool $isExtensionV3ParserEnabled;
        public int $checkInBrowser;
        public bool $extensionV3Installed;
        public bool $extensionV3Supported;
        public bool $expectedBrowserExtensionAllowed;
        /**
         * @var callable(int): list<AbstractEvent>
         */
        public $expectedEventsGen;
        public bool $extensionInstalled = false;
        public bool $browserSupported = false;
        public bool $extensionV3ParserReady = true;
        public bool $checkExtensionSupportResult = true;
        public bool $extensionSessionReturned = true;
        public bool $isMobile = false;
    }
}
