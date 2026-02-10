<?php

namespace AwardWallet\Tests\Unit\BalanceFormatter;

/**
 * @group frontend-unit
 */
class NumberBalanceFormatterTest extends AbstractBalanceFormatterTest
{
    /**
     * @dataProvider naProvider
     */
    public function testNA($value, $allowFloat)
    {
        $this->assertEquals('n/a', $this->formatter->formatNumber($value, $allowFloat, null, 'n/a'));
    }

    public function naProvider()
    {
        return [
            [null, true],
            [null, false],
            ['', true],
            ['', false],
        ];
    }

    /**
     * @dataProvider formatBalanceAsNumberProvider
     */
    public function testFormatBalanceAsNumber($value, $allowFloat, $valueFormat, $expected, $fraction)
    {
        $this->assertEquals($expected, $this->formatter->formatNumber($value, $allowFloat, $valueFormat, null, null, $fraction));
    }

    public function formatBalanceAsNumberProvider()
    {
        return [
            [3000, true, null, '3,000', 2],
            [3000, false, null, '3,000', 2],
            [100.00, true, null, '100', 2],
            [100.00, false, null, '100', 2],
            [2500.00, true, null, '2,500', 2],
            [1520.30, true, '&pound;%.2f', '£1,520.30', 2],
            [1520.30, false, '&pound;%.2f', '£1,520', 2],
            [1520.30, true, '&pound;%.1f', '£1,520.3', 2],
            [1520.30, true, '&pound;%.2f', '£1,520.30', 3],
            [1520.30, true, '&pound;%.3f', '£1,520.300', 2],
            [1520.30, true, '%d', '1,520', 2],
            [1520.30, false, '%d', '1,520', 2],
        ];
    }
}
