<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\AccountHistory\Formatter;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileHistoryFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\HistoryQuery;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class MobileHistoryFormatterTest extends BaseContainerTest
{
    /**
     * @var MobileHistoryFormatter
     */
    private $formatter;

    public function _before()
    {
        parent::_before();

        $this->formatter = $this->container->get(MobileHistoryFormatter::class);
    }

    /**
     * @dataProvider historyProvider
     */
    public function testFormatter(string $file, ?HistoryQuery $historyQuery = null)
    {
        $in = \json_decode(\file_get_contents(__DIR__ . "/Fixtures/MobileHistoryFormatter/{$file}In.json"), true);
        $expectedFileName = __DIR__ . "/Fixtures/MobileHistoryFormatter/{$file}Out.json";
        $expected = \json_decode(\file_get_contents($expectedFileName), true);
        $actual = $this->formatter->format($in, $historyQuery ?? new HistoryQuery(new Account()));

        if (\getenv('DUMP_JSON') === '1') {
            \file_put_contents($expectedFileName, \json_encode($actual, \JSON_PRETTY_PRINT));
        }

        $this->assertEquals($expected, \json_decode(\json_encode($actual), true));
    }

    public function historyProvider()
    {
        return [
            'simple' => ['simpleAccount'],
            'credit card' => ['creditCardAccount'],
        ];
    }
}
