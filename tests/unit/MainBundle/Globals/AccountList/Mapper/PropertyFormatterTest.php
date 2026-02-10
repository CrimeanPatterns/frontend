<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Providerproperty as EntityProviderProperty;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\PropertyFormatter;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class PropertyFormatterTest extends BaseContainerTest
{
    private ?PropertyFormatter $propertyFormatter;

    public function _before()
    {
        parent::_before();

        $this->propertyFormatter = $this->container->get(PropertyFormatter::class);
    }

    public function _after()
    {
        $this->propertyFormatter = null;

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(string $expected, int $type, $value, ?string $locale = null)
    {
        $this->assertEquals($expected, $this->propertyFormatter->format($value, $type, $locale));
    }

    public function dataProvider(): array
    {
        return [
            ['100,200', EntityProviderProperty::TYPE_NUMBER, 100200],
            ['200,500', EntityProviderProperty::TYPE_NUMBER, '200,500'],
            ['200.12', EntityProviderProperty::TYPE_NUMBER, '200.12'],
            ['200,123', EntityProviderProperty::TYPE_NUMBER, '200.123'],
            ['0', EntityProviderProperty::TYPE_NUMBER, 'test'],
            ['5', EntityProviderProperty::TYPE_NUMBER, '5 nights'],
            ['3', EntityProviderProperty::TYPE_NUMBER, '$3'],
            ['test', EntityProviderProperty::TYPE_DATE, 'test'],
            ['Oct 11, 2022', EntityProviderProperty::TYPE_DATE, '2022-10-11'],
            ['Oct 11, 2022', EntityProviderProperty::TYPE_DATE, '2022-10-11 10:13:00'],
            ['Nov 15, 2022', EntityProviderProperty::TYPE_DATE, '1668506883'],
            ['12345', EntityProviderProperty::TYPE_DATE, '12345'],
        ];
    }
}
