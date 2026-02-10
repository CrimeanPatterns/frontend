<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 * @group security
 */
class AntiBruteforceLockerServiceTest extends BaseTest
{
    public const PREFIX = 'test_prefix';
    public const ERROR_MESSAGE = 'error message';

    /**
     * @dataProvider checkForLockoutDataProvider
     */
    public function testCheckForLockout($key, $readOnly, $readOnlyExpected, $throttleReturn, $serviceReturnExpected)
    {
        $service = $this->getService($throttler = $this->getThrottlerMock());

        $throttler->expects($this->once())
            ->method('getDelay')
            ->with(
                self::PREFIX . $key,
                $readOnlyExpected
            )->willReturn($throttleReturn);

        if (null === $readOnly) {
            $serviceReturn = $service->checkForLockout($key);
        } else {
            $serviceReturn = $service->checkForLockout($key, $readOnly);
        }

        $this->assertEquals($serviceReturnExpected, $serviceReturn);
    }

    public function checkForLockoutDataProvider()
    {
        // [key, readOnly, readOnlyExpected, throttleReturn, servicceReturnExpected]
        return [
            ['key', null, false, 10, self::ERROR_MESSAGE],
            ['key', false, false, 10, self::ERROR_MESSAGE],
            ['key', true, true, 0, null],
            ['key', false, false, 0, null],
        ];
    }

    /**
     * @dataProvider invalidKeyDataProvider
     */
    public function testInvalidKey($key)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('key should be string');
        $service = $this->getService($this->getThrottlerMock(), '', '', '', '', '');
        $service->checkForLockout($key);
    }

    public function invalidKeyDataProvider()
    {
        return [
            [''],
            [1],
            [[]],
        ];
    }

    public function testUnlock()
    {
        $service = $this->getService($throttler = $this->getThrottlerMock());

        $value = 'test_value_132234';

        $throttler->expects($this->once())
            ->method('clear')
            ->with(self::PREFIX . $value);

        $service->unlock($value);
    }

    /**
     * @return AntiBruteforceLockerService
     */
    protected function getService(\Throttler $throttler, $prefix = self::PREFIX, $periodSeconds = 100, $periods = 100, $maxAttempts = 100, $errorMessage = self::ERROR_MESSAGE)
    {
        $service = new AntiBruteforceLockerService(
            $this->getMockBuilder(\Memcached::class)->disableOriginalConstructor()->getMock(),
            $prefix,
            $periodSeconds,
            $periods,
            $maxAttempts,
            $errorMessage
        );
        $refl = new \ReflectionProperty(AntiBruteforceLockerService::class, 'throttler');
        $refl->setAccessible(true);
        $refl->setValue($service, $throttler);
        $refl->setAccessible(false);

        return $service;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_Builder_MethodNameMatch|\PHPUnit_Framework_MockObject_MockObject|\Throttler
     */
    protected function getThrottlerMock()
    {
        return $this->getMockBuilder(\Throttler::class)->disableOriginalConstructor()->getMock();
    }
}
