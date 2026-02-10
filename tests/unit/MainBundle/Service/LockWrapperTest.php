<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Service\LockWrapper;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\StoreInterface;

/**
 * @group frontend-unit
 */
class LockWrapperTest extends BaseTest
{
    public function testTryingToAcquireAlreadyAcquiredLockWillThrowException()
    {
        $this->expectException(\Symfony\Component\Lock\Exception\LockConflictedException::class);
        $locker = new LockWrapper(
            $this->prophesize(Factory::class)
                ->createLock(Argument::cetera())
                ->willReturn(new Lock(
                    new Key('key'),
                    $this->prophesize(StoreInterface::class)
                        ->save(Argument::cetera())
                        ->willThrow(new LockConflictedException())
                        ->shouldBeCalledOnce()
                        ->getObjectProphecy()
                        ->reveal()
                ))
                ->shouldBeCalledOnce()
                ->getObjectProphecy()
                ->reveal()
        );
        $locker->wrap('key', function () {});
    }

    public function testCallableWillBeInvokedUnderLock()
    {
        $locker = new LockWrapper(
            $this->prophesize(Factory::class)
                ->createLock(Argument::cetera())
                ->willReturn(new Lock(
                    new Key('key'),
                    $this->prophesize(StoreInterface::class)->reveal()
                ))
                ->shouldBeCalledOnce()
                ->getObjectProphecy()
                ->reveal()
        );
        $this->assertEquals('return', $locker->wrap('key', function () { return 'return'; }));
    }

    public function testLockWillBeReleasedWhenCallableThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Faked!');
        $locker = new LockWrapper(
            $this->prophesize(Factory::class)
                ->createLock(Argument::cetera())
                ->willReturn(new Lock(
                    new Key('key'),
                    $this->prophesize(StoreInterface::class)
                        ->save(Argument::cetera())
                        ->shouldBeCalledOnce()
                        ->getObjectProphecy()

                        ->delete(Argument::type(Key::class))
                        ->shouldBeCalledOnce()
                        ->getObjectProphecy()

                        ->exists(Argument::type(Key::class))
                        ->shouldBeCalledOnce()
                        ->getObjectProphecy()

                        ->reveal()
                ))
                ->shouldBeCalledOnce()
                ->getObjectProphecy()
                ->reveal()
        );
        $callable = function () {
            throw new \RuntimeException('Faked!');
        };
        $this->assertEquals('return', $locker->wrap('key', $callable));
    }
}
