<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 */
class QueryOptionsTest extends BaseTest
{
    public function testDefaultValues()
    {
        $queryOptions = new QueryOptions();
        $queryOptions->lock();

        $this->assertFalse($queryOptions->isWithDetails());

        $this->assertNull($queryOptions->getStartDate());
        $this->assertNull($queryOptions->getEndDate());
        $this->assertNull($queryOptions->getFilterCallback());
        $this->assertNull($queryOptions->getFormat());
        $this->assertNull($queryOptions->getUserAgent());

        $this->assertFalse($queryOptions->hasFilterCallback());
        $this->assertFalse($queryOptions->hasEndDate());
        $this->assertFalse($queryOptions->hasFormat());
        $this->assertFalse($queryOptions->hasStartDate());
        $this->assertFalse($queryOptions->hasUserAgent());

        $queryOptions = QueryOptions::createDesktop();
        $queryOptions->lock();
        $this->assertEquals(ItemFormatterInterface::DESKTOP, $queryOptions->getFormat());

        $queryOptions = QueryOptions::createMobile();
        $queryOptions->lock();
        $this->assertEquals(ItemFormatterInterface::MOBILE, $queryOptions->getFormat());
    }

    public function testUninitilizedBoolean()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access to undefined property "someBool"');
        $queryOptions = new QueryOptions();
        $queryOptions->lock();
        $queryOptions->isSomeBool();
    }

    public function testUndefinedMethod()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Call to undefined method "someMethod"');
        $queryOptions = new QueryOptions();
        $queryOptions->lock();
        $queryOptions->someMethod();
    }

    public function testSneakyBastardProgrammer()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access violation on property "locked"');
        $queryOptions = new QueryOptions();
        $queryOptions->lock();
        $queryOptions->setLocked(false);
    }

    public function testDatesImmutability()
    {
        $queryOptions = new QueryOptions();

        $dateTime = new \DateTime();
        $queryOptions->setEndDate($dateTime);
        $this->assertEquals($dateTime, $queryOptions->getEndDate()); // same attributes
        $this->assertFalse($queryOptions->getEndDate() === $dateTime); // not same instances

        $reflectionProperty = new \ReflectionProperty('\AwardWallet\MainBundle\Timeline\QueryOptions', 'endDate');
        $reflectionProperty->setAccessible(true);
        $dateTime = $reflectionProperty->getValue($queryOptions);
        $reflectionProperty->setAccessible(false);
        $this->assertFalse($dateTime === $queryOptions->getEndDate());

        $dateTime = new \DateTime();
        $queryOptions->setStartDate($dateTime);
        $this->assertEquals($dateTime, $queryOptions->getStartDate()); // same attributes
        $this->assertFalse($queryOptions->getStartDate() === $dateTime); // not same instances

        $reflectionProperty = new \ReflectionProperty('\AwardWallet\MainBundle\Timeline\QueryOptions', 'startDate');
        $reflectionProperty->setAccessible(true);
        $dateTime = $reflectionProperty->getValue($queryOptions);
        $reflectionProperty->setAccessible(false);
        $this->assertFalse($dateTime === $queryOptions->getStartDate());
    }

    public function testRestrictToFluentWriteAccessOnly()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Undefined property "value" or non-fluent access');
        $queryOptions = new QueryOptions();
        $queryOptions->value = 1;
    }

    public function testRestrictToFluentReadAccessOnly()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Undefined property "value" or non-fluent access');
        $queryOptions = new QueryOptions();
        $queryOptions->value;
    }

    public function testUndefinedPropertyExistence()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access to undefined property "someVar"');
        $queryOptions = new QueryOptions();
        $queryOptions->lock();
        $queryOptions->hasSomeVar();
    }

    public function testInvalidArgumentsCount()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid arguments count 0 for method "setWithDetails", must be 1');
        $queryOptions = new QueryOptions();
        $queryOptions->setWithDetails();
    }

    public function testAccessOnUnlockedHas()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invoking method "hasUserAgent" on non-locked object');
        $queryOptions = new QueryOptions();
        $queryOptions->hasUserAgent();
    }

    public function testAccessOnUnlockedGet()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invoking method "getUserAgent" on non-locked object');
        $queryOptions = new QueryOptions();
        $queryOptions->getUserAgent();
    }

    public function testUnlocked()
    {
        $queryOptions = new QueryOptions();
        $jar = $queryOptions
            ->setFormat(ItemFormatterInterface::MOBILE);

        $this->assertTrue($queryOptions === $jar);
    }

    public function testLocked()
    {
        $queryOptions = new QueryOptions();
        $jar = $queryOptions
            ->setFormat(ItemFormatterInterface::MOBILE)
            ->lock();

        $jar3 = $jar
            ->setFormat(1)
            ->setWithDetails(true);

        $this->assertFalse($jar->isWithDetails());

        $this->assertTrue($jar3->isWithDetails());
        $this->assertEquals(1, $jar3->getFormat());

        $this->assertFalse($jar === $jar3);
    }

    public function testExpandDateIntervalUnlocked()
    {
        $date = new \DateTime();
        $queryOptions = (new QueryOptions())
            ->setStartDate($date)
            ->setEndDate($date);

        $queryOptions1 = $queryOptions->expandDateInterval('3 day');
        $this->assertTrue($queryOptions === $queryOptions1);

        $date->modify('-3 day');
        $this->assertTrue($date->getTimestamp() === $queryOptions->getStartDate()->getTimestamp());

        $date->modify('+6 day');
        $this->assertTrue($date->getTimestamp() === $queryOptions->getEndDate()->getTimestamp());
    }

    public function testExpandDateIntervalLocked()
    {
        $date = new \DateTime();
        $queryOptions = (new QueryOptions())
            ->setStartDate($date)
            ->setEndDate($date)
            ->lock();

        $queryOptions1 = $queryOptions->expandDateInterval('3 day');
        $this->assertFalse($queryOptions === $queryOptions1);
        $this->assertFalse($queryOptions->getStartDate()->getTimestamp() === $queryOptions1->getStartDate()->getTimestamp());
        $this->assertFalse($queryOptions->getEndDate()->getTimestamp() === $queryOptions1->getEndDate()->getTimestamp());
    }
}
