<?php

namespace AwardWallet\Tests\Unit\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyEnvironmentExecutor;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\MainBundle\Updater\EventsChannelMigrator;
use AwardWallet\MainBundle\Updater\RequestSerializer;
use AwardWallet\MainBundle\Updater\SessionData;
use AwardWallet\MainBundle\Updater\StartResponse;
use AwardWallet\MainBundle\Updater\UpdaterSession;
use AwardWallet\MainBundle\Updater\UpdaterSessionFactory;
use AwardWallet\MainBundle\Updater\UpdaterSessionManager;
use AwardWallet\MainBundle\Updater\UpdaterSessionStorage;
use AwardWallet\MainBundle\Updater\UserMessagesHandlerResult;
use AwardWallet\Tests\Unit\BaseTest;
use Clock\ClockNative;
use Clock\ClockTest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function Duration\hours;
use function Duration\minutes;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Updater\UpdaterSessionManager
 */
class UpdaterSessionManagerTest extends BaseTest
{
    /**
     * @covers ::startSession
     */
    public function testStartSessionSuccess(): void
    {
        $sessionType = UpdaterSessionFactory::TYPE_DESKTOP;
        $accountIdsList = [2, 3];
        $eventsList = [
            ['eventid' => 0],
            ['eventid' => 1],
        ];
        $capturedSessionKey = null;
        $request = new Request();
        $serializedRequest = '{"serialized_from_test": true}';

        $updaterSessionMock = $this->prophesize(UpdaterSession::class);
        $updaterSessionMock
            ->setOption(Argument::cetera())
            ->shouldBeCalled();

        $updaterSessionMock
            ->startWithKey(
                Argument::that(function ($key) use (&$capturedSessionKey) {
                    $capturedSessionKey = $key;

                    return true;
                }),
                $accountIdsList
            )
            ->will(function () use (&$capturedSessionKey, $eventsList) {
                return new StartResponse($capturedSessionKey, $eventsList);
            })
            ->shouldBeCalledOnce();

        $userMock = $this->prophesize(Usr::class);
        $userMock
            ->getId()
            ->willReturn(2);
        $userMock = $userMock->reveal();

        $tokenStorageMock = $this->prophesize(AwTokenStorageInterface::class);
        $tokenStorageMock
            ->getUser()
            ->willReturn($userMock);

        $tokenStorageMock
            ->getToken()
            ->willReturn(new UsernamePasswordToken($userMock, 'CREDENDIALS', 'secured_area'));

        $lockWrapperMock = $this->createSucceedingLockWrapperProphecy();

        $updaterSessionFactoryMock = $this->prophesize(UpdaterSessionFactory::class);
        $updaterSessionFactoryMock
            ->createSessionByType($sessionType)
            ->willReturn($updaterSessionMock->reveal())
            ->shouldBeCalledOnce();

        $updaterSessionStorage = $this->prophesize(UpdaterSessionStorage::class);
        $updaterSessionStorage
            ->linkUpdaterSessionToAccounts(
                $accountIdsList,
                Argument::that(function (string $key) use (&$capturedSessionKey) {
                    return $capturedSessionKey === $key;
                })
            )
            ->shouldBeCalled();
        $updaterSessionStorage
            ->updateSessionData(
                Argument::that(function (string $cacheKey) use (&$capturedSessionKey) {
                    return $capturedSessionKey === $cacheKey;
                }),
                Argument::that(fn (SessionData $sessionData) =>
                    $sessionData->getSessionType() === $sessionType
                    && $sessionData->getEventIndex() === 1
                    && $sessionData->getUserId() === 2
                    && $sessionData->getSerializedRequest() === $serializedRequest
                )
            )
            ->shouldBeCalled();
        $updaterSessionStorage
            ->linkUpdaterSessionToUser(
                2,
                Argument::that(function (string $cacheKey) use (&$capturedSessionKey) {
                    return $capturedSessionKey === $cacheKey;
                })
            );

        $requestSerializerMock = $this->prophesize(RequestSerializer::class);
        $requestSerializerMock
            ->serializeRequest(Argument::exact($request))
            ->willReturn($serializedRequest)
            ->shouldBeCalledOnce();

        $centrifugeClientMock = $this->prophesize(ClientInterface::class);
        $centrifugeClientMock
            ->getClientData()
            ->shouldBeCalledOnce();
        $centrifugeClientMock
            ->generateChannelSign(Argument::any(), Argument::any())
            ->shouldBeCalledOnce();

        $eventCounter = 0;

        foreach ($accountIdsList as $accountIdList) {
            $centrifugeClientMock
                ->publish(
                    Argument::that(function ($cacheKey) use (&$capturedSessionKey) {
                        return ('$update_session_' . $capturedSessionKey) === $cacheKey;
                    }),
                    [[0, $eventsList[0]], [1, $eventsList[1]]]
                )
                ->shouldBeCalledOnce();
            $eventCounter++;
        }

        $updaterSessionManager = new UpdaterSessionManager(
            $updaterSessionFactoryMock->reveal(),
            $this->prophesize(CacheManager::class)->reveal(),
            $lockWrapperMock->reveal(),
            $centrifugeClientMock->reveal(),
            $this->prophesize(UsrRepository::class)->reveal(),
            $this->prophesize(SymfonyEnvironmentExecutor::class)->reveal(),
            $tokenStorageMock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $requestSerializerMock->reveal(),
            new ClockNative(),
            $updaterSessionStorage->reveal(),
            $this->createMock(AuthorizationCheckerInterface::class),
        );
        $updaterSessionManager->startSession('some-client-id', $sessionType, ['option1' => 1], $accountIdsList, $request);
    }

    public function testSynchronizedTickRemovesExpiredSession(): void
    {
        $sessionId = 'session1';
        $updaterSessionStorage = $this->prophesize(UpdaterSessionStorage::class);
        $updaterSessionStorage
            ->loadSessionData($sessionId)
            ->will(function () use ($updaterSessionStorage, $sessionId) {
                $updaterSessionStorage
                    ->removeSession($sessionId)
                    ->shouldBeCalled();

                return new SessionData(
                    'mobile',
                    0,
                    minutes(20),
                    1,
                    1,
                    'some'
                );
            })
            ->shouldBeCalled();

        /** @var UpdaterSessionManager $updaterSessionManager */
        $updaterSessionManager = $this->makeProphesized(
            UpdaterSessionManager::class,
            [
                '$lockWrapper' => $this->createSucceedingLockWrapperProphecy()->reveal(),
                '$clock' => new ClockTest(hours(1)),
                '$sessionStorage' => $updaterSessionStorage->reveal(),
            ]
        );
        $updaterSessionManager->synchronizedTick($sessionId);
    }

    public function testSynchronizedTickRemovesSessionWithExceededTotalTickCount(): void
    {
        $sessionId = 'session1';
        $logger = $this->prophesize(LoggerInterface::class);
        $updaterSessionStorage = $this->prophesize(UpdaterSessionStorage::class);
        $updaterSessionStorage
            ->loadSessionData($sessionId)
            ->will(function () use ($updaterSessionStorage, $sessionId, $logger) {
                $logger
                    ->log(LogLevel::CRITICAL, Argument::cetera())
                    ->shouldBeCalled();
                $updaterSessionStorage
                    ->removeSession($sessionId)
                    ->shouldBeCalled();

                return new SessionData(
                    'mobile',
                    0,
                    minutes(20),
                    1500,
                    1,
                    'some'
                );
            })
            ->shouldBeCalled();

        /** @var UpdaterSessionManager $updaterSessionManager */
        $updaterSessionManager = $this->makeProphesized(
            UpdaterSessionManager::class,
            [
                '$lockWrapper' => $this->createSucceedingLockWrapperProphecy()->reveal(),
                '$clock' => new ClockTest(hours(1)),
                '$sessionStorage' => $updaterSessionStorage->reveal(),
                '$logger' => $logger->reveal(),
            ]
        );
        $updaterSessionManager->synchronizedTick($sessionId);
    }

    public function testSynchronizedTick(): void
    {
        $sessionId = 'session1';
        $updaterSessionStorage = $this->prophesize(UpdaterSessionStorage::class);
        $updaterSessionStorage
            ->loadSessionData($sessionId)
            ->will(fn () => new SessionData(
                'mobile',
                0,
                minutes(20),
                1,
                1,
                'some'
            ))
            ->shouldBeCalled();
        $updaterSessionStorage
            ->linkUpdaterSessionToAccounts([1, 2], $sessionId)
            ->shouldBeCalled();
        $updaterSessionStorage
            ->unlinkUpdaterSessionFromAccounts([2], $sessionId)
            ->shouldBeCalled();
        $updaterSessionStorage
            ->updateSessionData(
                $sessionId,
                Argument::that(fn (SessionData $sessionData) =>
                    $sessionData->getSessionType() === 'mobile'
                    && $sessionData->getEventIndex() === 1
                    && $sessionData->getUserId() === 1
                    && $sessionData->getSerializedRequest() === 'some'
                    && $sessionData->getLastTickTime()->equals(minutes(21))
                )
            )
            ->shouldBeCalled();
        $userRepository = $this->prophesize(UsrRepository::class);
        $userRepository
            ->find(1)
            ->willReturn(
                new class() extends Usr {
                    public function getId(): ?int
                    {
                        return 1;
                    }
                }
            );
        $requestDeserializer = $this->prophesize(RequestSerializer::class);
        $requestDeserializer
            ->deserializeRequest('some')
            ->willReturn(new Request());
        $symfonyEnvExecutor = $this->prophesize(SymfonyEnvironmentExecutor::class);
        $symfonyEnvExecutor
            ->process(Argument::cetera())
            ->will(fn (array $arguments) => ($arguments[1])())
            ->shouldBeCalled();
        $fugeClient = $this->prophesize(ClientInterface::class);
        $fugeClient
            ->publish(
                '$update_session_' . $sessionId,
                [
                    [0, ['event body1']],
                    [1, ['event body2']],
                ]
            )
            ->shouldBeCalled();
        $clock = new ClockTest(minutes(21));
        $updaterSession = $this->prophesize(UpdaterSession::class);
        $updaterSession
            ->tick($sessionId, 0, [1, 2], [], [])
            ->will(function () use ($clock) {
                $clock->sleep(minutes(9));

                return [['event body1'], ['event body2']];
            })
            ->shouldBeCalled();
        $updaterSession
            ->getRemovedAccounts()
            ->willReturn([2])
            ->shouldBeCalled();
        $updaterSessionFactory = $this->prophesize(UpdaterSessionFactory::class);
        $updaterSessionFactory
            ->createSessionByType('mobile')
            ->willReturn($updaterSession->reveal())
            ->shouldBeCalled();

        /** @var UpdaterSessionManager $updaterSessionManager */
        $updaterSessionManager = new UpdaterSessionManager(
            $updaterSessionFactory->reveal(),
            $this->prophesize(CacheManager::class)->reveal(),
            $this->createSucceedingLockWrapperProphecy()->reveal(),
            $fugeClient->reveal(),
            $userRepository->reveal(),
            $symfonyEnvExecutor->reveal(),
            $this->prophesize(AwTokenStorageInterface::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $requestDeserializer->reveal(),
            $clock,
            $updaterSessionStorage->reveal(),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->prophesize(EventsChannelMigrator::class)->reveal(),
        );
        $userMessagesRes = new UserMessagesHandlerResult();
        $userMessagesRes->addAccounts = [1, 2];
        $updaterSessionManager->synchronizedTick($sessionId, $userMessagesRes);
    }

    protected function createSucceedingLockWrapperProphecy(): ObjectProphecy
    {
        $lockWrapperMock = $this->prophesize(LockWrapper::class);
        $lockWrapperMock
            ->wrap(Argument::cetera())
            ->will(fn ($args) => ($args[1])());

        return $lockWrapperMock;
    }
}
