<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Service\DateTimeResolver;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class DateTimeResolverTest extends BaseContainerTest
{
    private ?DateTimeResolver $resolver;

    private ?\DateTime $dateTime;

    public function _before()
    {
        parent::_before();

        $this->resolver = $this->container->get(DateTimeResolver::class);
        $this->dateTime = new \DateTime();
    }

    public function _after()
    {
        $this->resolver = null;
        $this->dateTime = null;

        parent::_after();
    }

    public function testResolveByFakeTimeZoneIdShouldReturnNull()
    {
        $this->assertNull($this->resolver->resolveByTimeZoneId($this->dateTime, 'xxx'));
    }

    public function testResolveByRealTimeZoneIdShouldReturnDateTime()
    {
        $this->assertInstanceOf(\DateTime::class, $date = $this->resolver->resolveByTimeZoneId($this->dateTime, $tz = 'America/Chicago'));
        $this->assertEquals($tz, $date->getTimezone()->getName());
    }

    public function testResolveByOffset()
    {
        $this->assertEquals(
            '-01:00',
            $this->resolver->resolveByTimeZoneOffset($this->dateTime, -3600)->getTimezone()->getName()
        );
        $this->assertEquals(
            '+05:00',
            $this->resolver->resolveByTimeZoneOffset($this->dateTime, 18000)->getTimezone()->getName()
        );
        $this->assertEquals(
            '+00:00',
            $this->resolver->resolveByTimeZoneOffset($this->dateTime, 0)->getTimezone()->getName()
        );
        $this->assertEquals(
            '+01:30',
            $this->resolver->resolveByTimeZoneOffset($this->dateTime, 5400)->getTimezone()->getName()
        );
        $this->assertEquals(
            '-09:30',
            $this->resolver->resolveByTimeZoneOffset($this->dateTime, -34200)->getTimezone()->getName()
        );
    }

    public function testResolveByFakeAirCodeShouldReturnNull()
    {
        $this->assertNull($this->resolver->resolveByAirCode($this->dateTime, 'xxx'));
    }

    public function testResolveByRealAirCodeShouldReturnDateTime()
    {
        $this->assertInstanceOf(\DateTime::class, $date = $this->resolver->resolveByAirCode($this->dateTime, 'ADX'));
        $this->assertEquals('Europe/London', $date->getTimezone()->getName());
    }

    /**
     * @dataProvider dateProvider
     */
    public function testResolve(?string $expected, ?string $timeZoneId = null, ?int $offset = null, ?string $airCode = null)
    {
        $date = $this->resolver->resolve($this->dateTime, $timeZoneId, $offset, $airCode);

        if (is_null($expected)) {
            $this->assertNull($date);
        } else {
            $this->assertEquals($expected, $date->getTimezone()->getName());
        }
    }

    public function dateProvider(): array
    {
        return [
            ['+01:00', null, 3600],
            ['America/Chicago', 'America/Chicago', 3600],
            ['Europe/London', null, null, 'ADX'],
            ['America/Chicago', 'America/Chicago', 3600, 'ADX'],
            [null, 'xxx', null, 'XXX'],
            [null, null, null, null],
        ];
    }
}
