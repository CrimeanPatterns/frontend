<?php

namespace AwardWallet\Tests\Unit\Updater;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\MainBundle\Updater\AddAccount;
use AwardWallet\MainBundle\Updater\Event\ErrorEvent;
use AwardWallet\MainBundle\Updater\Event\FailEvent;
use AwardWallet\MainBundle\Updater\Event\QuestionEvent;
use AwardWallet\MainBundle\Updater\Event\StartProgressEvent;
use AwardWallet\MainBundle\Updater\Event\TripsFoundEvent;
use AwardWallet\MainBundle\Updater\Event\TripsNotFoundEvent;
use AwardWallet\MainBundle\Updater\Event\UpdatedEvent;
use AwardWallet\MainBundle\Updater\ExtensionV3LocalPasswordWaitMapOps;
use AwardWallet\MainBundle\Updater\Formatter\FormatterInterface;
use AwardWallet\MainBundle\Updater\UpdaterSession;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

/**
 * @group frontend-unit
 */
class UpdaterSessionTest extends UpdaterBase
{
    public function testInvalidAccountId()
    {
        $result = $this->getUpdater()->start([0, -1]);
        $this->assertEquals([
            new FailEvent(0, 'Account not found'),
            new FailEvent(-1, 'Account not found'),
        ], self::filterDebug(array_values($result->events)));
    }

    public function testAccessDenied()
    {
        $otherUserId = $this->aw->createAwUser();
        $accountId = $this->aw->createAwAccount($otherUserId, "testprovider", "balance.random");

        $result = $this->getUpdater()->start([$accountId]);
        $this->assertEquals([
            new FailEvent($accountId, 'Account is not accessible'),
        ], self::filterDebug(array_values($result->events)));
    }

    public function testValidAccount()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'expiration.never');
        $this->UPDATER_SERVICE = 'aw.updater_session.mobile';
        $result = $this->getUpdater()->start([$accountId]);

        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new UpdatedEvent($accountId, 1500.00),
            new TripsNotFoundEvent($accountId),
        ];
        $this->waitEvents($result, $expected);
    }

    public function testEmptyPassword()
    {
        $providerId = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['code' => 'aeroflot'])->getProviderid();
        $this->assertNotEmpty($providerId);

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $providerId, 'testemptypass');

        $result = $this->getUpdater()->start([$accountId]);

        $expected = [
            new FailEvent($accountId, 'Password is missing'),
        ];
        $this->waitEvents($result, $expected);
    }

    public function testQuestion()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'question');
        $result = $this->getUpdater()->start([$accountId]);

        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new ErrorEvent($accountId, 10),
            new QuestionEvent($accountId, $question = "What is your mother's middle name (answer is Petrovna)?", 'Test Provider (Test)'),
        ];
        $this->waitEvents($result, $expected);

        $accountEnt = $this->container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $this->container->get(AccountManager::class)->answerSecurityQuestion($accountEnt, $question, 'Petrovna');

        $this->getUpdater()->tick($result->key, 0, [$accountId]);

        $this->waitEvents(
            $result,
            array_merge($expected, [
                new StartProgressEvent($accountId, 30, null),
                new UpdatedEvent($accountId, null),
                new TripsNotFoundEvent($accountId),
            ])
        );
    }

    public function test3Accounts()
    {
        $createAccount = fn (): int => $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, 'expiration.never', null);
        /** @var AddAccount[] $accounts */
        $accounts = [
            AddAccount::createLowPriority($createAccount()),
            AddAccount::createLowPriority($createAccount()),
            AddAccount::createHighPriority($createAccount()),
        ];

        $expected = [
            new StartProgressEvent($accounts[2]->getAccountId(), 30, null),
            new StartProgressEvent($accounts[0]->getAccountId(), 30, null),
            new UpdatedEvent($accounts[2]->getAccountId(), 1500.00),
            new TripsNotFoundEvent($accounts[2]->getAccountId()),
            new StartProgressEvent($accounts[1]->getAccountId(), 30, null),
            new UpdatedEvent($accounts[0]->getAccountId(), 1500.00),
            new TripsNotFoundEvent($accounts[0]->getAccountId()),
            new UpdatedEvent($accounts[1]->getAccountId(), 1500.00),
            new TripsNotFoundEvent($accounts[1]->getAccountId()),
        ];

        $result = $this->getUpdater()->start($accounts);
        $this->waitEvents($result, $expected);
    }

    public function testGroupSuccess()
    {
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, 'testprovidergroup', 'test', ['ErrorCode' => ACCOUNT_UNCHECKED]);

        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new StartProgressEvent($accountId, 30, null),
            new UpdatedEvent($accountId, 1000.00),
            new TripsNotFoundEvent($accountId),
        ];

        $result = $this->getUpdater()->start([$accountId]);
        $this->waitEvents($result, $expected);
        $groupProviderId = $this->db->grabFromDatabase('Provider', 'ProviderID', ['Code' => 'testprovidergroup']);
        $this->db->seeInDatabase('Account', ['AccountID' => $accountId, 'ProviderID' => $groupProviderId]);

        // repeated check, should not iterate group
        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new UpdatedEvent($accountId, 1000.00),
        ];

        $result = $this->getUpdater()->start([$accountId]);
        $this->waitEvents($result, $expected);
        $this->db->seeInDatabase('Account', ['AccountID' => $accountId, 'ProviderID' => $groupProviderId]);
    }

    public function testGroupInvalid()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'invalid.logon', 'test', ["ErrorCode" => ACCOUNT_UNCHECKED]);

        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new StartProgressEvent($accountId, 30, null),
            new ErrorEvent($accountId, ACCOUNT_INVALID_PASSWORD),
        ];

        $result = $this->getUpdater()->start([$accountId]);
        $this->waitEvents($result, $expected);
        $this->assertEquals(Aw::TEST_PROVIDER_ID, $this->db->grabFromDatabase('Account', 'ProviderID', ['AccountID' => $accountId]));
    }

    public function testGroupProviderError()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'provider.error', 'test', ["ErrorCode" => ACCOUNT_UNCHECKED]);

        $expected = [
            new StartProgressEvent($accountId, 30, null),
            new ErrorEvent($accountId, ACCOUNT_PROVIDER_ERROR),
        ];

        $result = $this->getUpdater()->start([$accountId]);
        $this->waitEvents($result, $expected);
        $this->assertEquals(Aw::TEST_PROVIDER_ID, $this->db->grabFromDatabase('Account', 'ProviderID', ['AccountID' => $accountId]));
    }

    public function testOrder()
    {
        $account1 = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'provider.error', 'test');
        $account2 = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'expiration.never', 'test');

        $result = $this->getUpdater()->start([$account2, $account1]);

        $expected = [
            new StartProgressEvent($account2, 30, null),
            new StartProgressEvent($account1, 30, null),
            new UpdatedEvent($account2, 1500.00),
            new TripsNotFoundEvent($account2),
            new ErrorEvent($account1, ACCOUNT_PROVIDER_ERROR),
        ];

        $this->waitEvents($result, $expected);
    }

    public function testAccountHistory()
    {
        $this->user->setAccountlevel(ACCOUNT_LEVEL_AWPLUS);
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'history', 'test');
        $date = $this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId]);
        $this->assertNull($date);

        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsNotFoundEvent($accountId)]);
        $this->assertNotNull($this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId]));
        $history = $this->getHistory($accountId);
        $this->assertCount(10, $history);

        // could not test the logic below, about incremental history with local check strategy. it always grabs full history
        // we should get rid of Local check strategy. Possible rewrite it to loyalty API emulator
        /*
        // delete oldest row, and make sure it will not be recreated, there should be incremental (by Date) history retrieval
        $first = array_shift($history);
        $deleted = $this->db->executeQuery("delete from AccountHistory where AccountID = $accountId and PostingDate = '" . $first['PostingDate'] . "'");
        $this->assertEquals(1, $deleted);
        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsNotFoundEvent($accountId)]);
        $history = $this->getHistory($accountId);
        $this->assertCount(9, $history);

        // try to reset history cache, expect full history parse, no matter what cache we have
        $providerCacheVersion = $this->db->grabFromDatabase("Provider", "CacheVersion", ["Code" => "testprovider"]);
        $accountCacheVersion = $this->db->grabFromDatabase("Account", "HistoryVersion", ["AccountID" => $accountId]);
        $this->assertEquals($providerCacheVersion, $accountCacheVersion);
        $this->db->executeQuery("update Account set HistoryVersion = HistoryVersion - 1 where AccountID = $accountId");
        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsNotFoundEvent($accountId)]);
        $history = $this->getHistory($accountId);
        $this->assertCount(10, $history);
        $accountCacheVersion = $this->db->grabFromDatabase("Account", "HistoryVersion", ["AccountID" => $accountId]);
        $this->assertEquals($providerCacheVersion, $accountCacheVersion);*/
    }

    public function testEmptyHistory()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'future.rental', 'test');
        $this->db->executeQuery('UPDATE Account SET LastCheckHistoryDate = adddate(now(), -1) WHERE AccountID = ' . $accountId);
        $date = $this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId]);
        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsFoundEvent($accountId, 1, [])]);
        $this->assertTrue($date < strtotime($this->db->grabFromDatabase('Account', 'LastCheckHistoryDate', ['AccountID' => $accountId])));
        $history = $this->getHistory($accountId);
        $this->assertCount(0, $history);
    }

    public function testLastChangeDateWithCombineSubaccount()
    {
        $aw = $this->getModule('Aw');
        $manager = $this->container->get(AccountListManager::class);

        $accountId_yes_lastChange = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'subaccount_expired_combined', 'pass');
        $accountId_no_lastChange = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, '1.subaccount', 'pass');

        $aw->checkAccount($accountId_yes_lastChange);
        $aw->checkAccount($accountId_no_lastChange);

        $this->db->executeQuery('UPDATE Account SET LastChangeDate = "2018-01-01 10:00:00" WHERE AccountID = ' . $accountId_yes_lastChange);
        $this->db->executeQuery('UPDATE Account SET LastChangeDate = "2018-01-01 10:00:00" WHERE AccountID = ' . $accountId_no_lastChange);

        $aw->checkAccount($accountId_yes_lastChange);
        $aw->checkAccount($accountId_no_lastChange);

        $accountData_yes = $manager
            ->getAccountList(
                $this->container->get(OptionsFactory::class)->createDesktopListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                        ->set(Options::OPTION_ACCOUNT_IDS, [$accountId_yes_lastChange])
                )
            )
        ->getAccounts()[0];
        $accountData_no = $manager
            ->getAccountList(
                $this->container->get(OptionsFactory::class)->createDesktopListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $this->container->get(AwTokenStorageInterface::class)->getBusinessUser())
                        ->set(Options::OPTION_ACCOUNT_IDS, [$accountId_no_lastChange])
                )
            )
        ->getAccounts()[0];

        $this->assertEquals(date('Y-m-d'), date('Y-m-d', $accountData_yes->LastChangeDateTs));
        $this->assertEquals($accountData_no->LastChangeDateTs, mktime(10, 0, 0, 1, 1, 2018));
    }

    public function testLoadStateErrors()
    {
        $this->expectException(\AwardWallet\MainBundle\Updater\UpdaterStateException::class);
        $this->expectExceptionMessage('Failed to load updater cache');
        $tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $tokenStorageMock
            ->expects(self::once())
            ->method('getToken')
            ->willReturn(new PostAuthenticationGuardToken($this->getMockUser(100500), 'providerkey', []));

        $memcachedMock = $this->getMockBuilder(\Memcached::class)->disableOriginalConstructor()->getMock();
        $memcachedMock
            ->expects(self::once())
            ->method('getResultCode')
            ->willReturn(\Memcached::RES_FAILURE);

        $key = 'update_100500_abcdefg';

        $memcachedMock
            ->expects(self::once())
            ->method('get')
            ->with($key)
            ->willReturn(false);

        $loggerMock = $this->prophesize(LoggerInterface::class);
        $loggerMock
            ->log(LogLevel::WARNING, Argument::cetera())
            ->shouldBeCalledOnce();

        $updater = new UpdaterSession(
            $tokenStorageMock,
            $this->createMock(EntityManagerInterface::class),
            $memcachedMock,
            $this->createMock(LockWrapper::class),
            $this->createMock(CacheManager::class),
            $loggerMock->reveal(),
            $this->createMock(FormatterInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(ExtensionV3LocalPasswordWaitMapOps::class),
            100,
            []
        );

        $updater->tick('abcdefg', 1, []);

        // TODO: unserialize, json error checks
    }

    private function getMockUser($userId)
    {
        $user = new Usr();
        $reflUser = new \ReflectionProperty(Usr::class, 'userid');
        $reflUser->setAccessible(true);
        $reflUser->setValue($user, $userId);
        $reflUser->setAccessible(false);

        return $user;
    }

    private function getHistory($accountId)
    {
        return $this->db->query("select * from AccountHistory where AccountID = $accountId order by PostingDate")->fetchAll(\PDO::FETCH_ASSOC);
    }
}
