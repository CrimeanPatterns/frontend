<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

trait MakeLoggerMockTrait
{
    public function makeLoggerMock(array $calls): LoggerInterface
    {
        return $this->makeLoggerProphecy($calls)->reveal();
    }

    public function makeLoggerProphecy(array $calls): ObjectProphecy
    {
        $logger = $this->prophesize(LoggerInterface::class);

        foreach ($calls as [$method, $args]) {
            $logger->
                {$method}(...$args)
                ->shouldBeCalledOnce();
        }

        return $logger;
    }
}
