<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Message;
use AwardWallet\MainBundle\Service\ResponseTimeMonitor;
use AwardWallet\Tests\Unit\BaseTest;
use Monolog\Logger;

/**
 * @group frontend-unit
 */
class ResponseTimeMonitorTest extends BaseTest
{
    public const ROUTE = 'aw_users_logincheck';

    public const SAMPLE_TIME = 14;

    public function testThresholdExceeded()
    {
        $logger = $this->createMock(Logger::class);
        $http = $this->createMock(\HttpDriverInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->willReturn(new \HttpDriverResponse(file_get_contents(__DIR__ . '/Fixtures/ResponceTimeMonitor/thresholdExceeded.json'), 200));

        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->once())
            ->method('getMessage')
            ->willReturn(new Message());
        $mailer->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $rtm = new ResponseTimeMonitor(
            'ip:9200',
            $http,
            $mailer,
            'test@test.com',
            $logger
        );

        $result = $rtm->search(self::ROUTE, self::SAMPLE_TIME, null);
        $this->assertEquals(true, $result);
    }

    public function testThresholdNotExceeded()
    {
        $logger = $this->createMock(Logger::class);
        $http = $this->createMock(\HttpDriverInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->willReturn(new \HttpDriverResponse(file_get_contents(__DIR__ . '/Fixtures/ResponceTimeMonitor/thresholdNotExceeded.json'), 200));

        $mailer = $this->createMock(Mailer::class);

        $rtm = new ResponseTimeMonitor(
            'ip:9200',
            $http,
            $mailer,
            'test@test.com',
            $logger
        );

        $result = $rtm->search(self::ROUTE, self::SAMPLE_TIME, null);
        $this->assertEquals(false, $result);
    }

    public function testTimeout()
    {
        $logger = $this->createMock(Logger::class);
        $http = $this->createMock(\HttpDriverInterface::class);

        $httpResponse = new \HttpDriverResponse(null, 408);
        $httpResponse->errorMessage = 'Operation timed out';

        $http->expects($this->once())
            ->method('request')
            ->willReturn($httpResponse);

        $mailer = $this->createMock(Mailer::class);

        $rtm = new ResponseTimeMonitor(
            'ip',
            $http,
            $mailer,
            'test@test.com',
            $logger
        );

        $result = $rtm->search(self::ROUTE, self::SAMPLE_TIME, null);
        $this->assertEquals('Operation timed out', $result);
    }
}
