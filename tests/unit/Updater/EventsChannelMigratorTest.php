<?php

namespace AwardWallet\Tests\Unit\Updater;

use AwardWallet\MainBundle\Updater\EventsChannelMigrator;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use function Duration\minutes;

class EventsChannelMigratorTest extends BaseTest
{
    public function testSend()
    {
        $memcached = $this->prophesize(\Memcached::class);
        $memcached
            ->set(
                Argument::that(fn (string $key) => \strpos($key, 'updater_events_channel_migration_') === 0),
                Argument::that(fn ($data) =>
                    \is_array($data)
                    && (\count($data) === 2)
                    && ('$update_session_somesessionkey' === $data[0])
                    && (\strlen($data[1]) === 40)
                ),
                minutes(5)->getAsSecondsInt()
            )
            ->shouldBeCalledOnce();
        $migrator = new EventsChannelMigrator(
            $memcached->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(RequestStack::class)->reveal(),
        );
        $migrator->send('somesessionkey');
    }

    public function testReceive()
    {
        $memcached = $this->prophesize(\Memcached::class);
        $memcached
            ->get('updater_events_channel_migration_sometoken')
            ->willReturn(['$update_session_somesessionkey', 'sometoken'])
            ->shouldBeCalledOnce();
        $memcached
            ->getResultCode()
            ->willReturn(\Memcached::RES_SUCCESS);
        $migrator = new EventsChannelMigrator(
            $memcached->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(RequestStack::class)->reveal(),
        );
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $request = new Request();
        $request->setSession($session);

        $migrator->receive('sometoken', $request);
        $this->assertEquals(
            ['$update_session_somesessionkey', 'sometoken'],
            $session->get('updater_events_channel_migrator')
        );
    }

    /**
     * @dataProvider validateDataProvider
     * @param callable(RequestStack): void $requestStackModifier
     * @param callable(Session): void $sessionModifier
     * @param callable(ObjectProphecy|\Memcached): void $memcachedModifier
     */
    public function testValidate(
        string $userProvidedChannelName,
        bool $isValidExpected,
        callable $requestStackModifier,
        callable $sessionModifier,
        callable $memcachedModifier
    ) {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $sessionModifier($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $requestStackModifier($requestStack);
        $memcachedMock = $this->prophesize(\Memcached::class);
        $memcachedModifier($memcachedMock);

        $migrator = new EventsChannelMigrator(
            $memcachedMock->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $requestStack,
        );
        $this->assertEquals(
            $isValidExpected,
            $migrator->validate($userProvidedChannelName)
        );
        $this->assertNull($session->get('updater_events_channel_migrator'));
    }

    public function validateDataProvider()
    {
        return [
            [
                '$updater_channel_somekey',
                true,
                fn () => null,
                function (SessionInterface $s) {
                    $s->start();
                    $s->set('updater_events_channel_migrator', ['$updater_channel_somekey', 'sometoken']);
                },
                fn (ObjectProphecy $m) => $m->delete('updater_events_channel_migration_sometoken')->shouldBeCalledOnce(),
            ],
            [
                'some_garbage',
                false,
                fn () => null,
                function (SessionInterface $s) {
                    $s->start();
                    $s->set('updater_events_channel_migrator', ['$updater_channel_somekey', 'sometoken']);
                },
                fn (ObjectProphecy $m) => $m->delete('updater_events_channel_migration_sometoken')->shouldBeCalledOnce(),
            ],
            [
                '$updater_channel_somekey',
                false,
                fn () => null,
                function (SessionInterface $s) {
                    $s->start();
                    $s->set('updater_events_channel_migrator', new \stdClass());
                },
                fn () => null,
            ],
            [
                '$updater_channel_somekey',
                false,
                fn () => null,
                function (SessionInterface $s) {
                    $s->start();
                },
                fn () => null,
            ],
            [
                '$updater_channel_somekey',
                false,
                fn () => null,
                fn () => null,
                fn () => null,
            ],
            [
                '$updater_channel_somekey',
                false,
                fn (RequestStack $s) => $s->pop(),
                fn () => null,
                fn () => null,
            ],
        ];
    }
}
