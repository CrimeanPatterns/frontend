<?php

namespace AwardWallet\Tests\Unit\BalanceFormatter;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class SubAccountBalanceFormatterTest extends AbstractBalanceFormatterTest
{
    public function testNA()
    {
        $subaccount = $this->createSubAccountWithBalance(null);
        $this->assertEquals('n/a', $this->formatter->formatSubAccount($subaccount, false, 'n/a'));
        $this->assertNull($this->formatter->formatSubAccount($subaccount, false, null));
        $this->assertEquals('n/a', $this->formatter->formatSubAccount($subaccount, null, 'n/a'));
    }

    /**
     * @dataProvider naProviderSettingsProvider
     */
    public function testNAProviderSettings($canCheck, $canCheckBalance, $balance)
    {
        $this->db->updateInDatabase('Provider', [
            'CanCheck' => $canCheck,
            'CanCheckBalance' => $canCheckBalance,
            'BalanceFormat' => null,
        ], ['ProviderID' => $this->providerId]);
        $subaccount = $this->createSubAccountWithBalance($balance);
        $this->assertEquals('n/a', $this->formatter->formatSubAccount($subaccount, false, 'n/a'));
        $this->assertEquals('n/a', $this->formatter->formatSubAccount($subaccount, $balance, 'n/a'));
    }

    public function naProviderSettingsProvider()
    {
        return [
            [0, 1, 100],
            [1, 0, 100],
            [0, 0, 100],
            [0, 0, null],
            [0, 0, -500],
        ];
    }

    public function testFormatCustomBalance()
    {
        $subaccount = $this->createSubAccountWithBalance(100);
        $this->assertEquals('5,000', $this->formatter->formatSubAccount($subaccount, 5000));
        $this->assertEquals('5,000', $this->formatter->formatSubAccount($subaccount, '5,000'));
    }

    public function testFormatBalanceAsNumber()
    {
        $subaccount = $this->createSubAccountWithBalance(3000);
        $account = $subaccount->getAccountid();
        $this->assertEquals('3,000', $this->formatter->formatSubAccount($subaccount));
        $this->assertEquals('8,000', $this->formatter->formatSubAccount($subaccount, 8000));
        $this->assertEquals('100', $this->formatter->formatSubAccount($subaccount, 100.00));
        $this->assertEquals('2,500', $this->formatter->formatSubAccount($subaccount, 2500.00));
        $this->assertEquals('100.20', $this->formatter->formatSubAccount($subaccount, 100.20));

        $this->db->updateInDatabase('Provider', [
            'BalanceFormat' => '$%0.2f',
        ], ['ProviderID' => $this->providerId]);
        $this->em->refresh($account->getProviderid());

        $this->assertEquals('$3,000', $this->formatter->formatSubAccount($subaccount));
        $this->assertEquals('$1,520.30', $this->formatter->formatSubAccount($subaccount, 1520.30));

        $this->db->updateInDatabase('Provider', [
            'BalanceFormat' => '&pound;%d',
        ], ['ProviderID' => $this->providerId]);
        $this->em->refresh($account->getProviderid());

        $this->assertEquals('£3,000', $this->formatter->formatSubAccount($subaccount));
        $this->assertEquals('£1,520', $this->formatter->formatSubAccount($subaccount, 1520.30));
    }

    /**
     * @dataProvider formatBalanceViaCheckerProvider
     */
    public function testFormatBalanceViaChecker($balance, callable $callback, $expected)
    {
        $providerId = $this->aw->createAwProvider(
            $name = 'formbal' . $this->aw->grabRandomString(5),
            $name,
            [
                'BalanceFormat' => 'function',
            ],
            [],
            [
                'FormatBalance' => $callback,
            ]
        );
        $subaccount = $this->createSubAccountWithBalance(3000, $providerId);
        $this->assertEquals($expected, $this->formatter->formatSubAccount($subaccount, $balance, 'n/a'));
    }

    public function formatBalanceViaCheckerProvider()
    {
        return [
            [
                1000,
                function ($fields, $properties) {
                    return $fields['Balance'];
                },
                1000,
            ],
            [
                500.20,
                function ($fields, $properties) {
                    return $fields['Balance'];
                },
                500.20,
            ],
            [
                null,
                function ($fields, $properties) {
                    return $fields['Balance'];
                },
                'n/a',
            ],
        ];
    }

    public function testSubAccountCodeProperty()
    {
        $code = 'testSubAccCode';
        $called = false;
        $providerId = $this->aw->createAwProvider(
            $name = 'formbal' . $this->aw->grabRandomString(5),
            $name,
            [
                'BalanceFormat' => 'function',
            ],
            [],
            [
                'FormatBalance' => function ($fields, $properties) use ($code, &$called) {
                    $called = true;
                    assertEquals($code, $properties['SubAccountCode']);

                    return $fields['Balance'];
                },
            ]
        );

        $subaccount = $this->createSubAccountWithBalance(3000, $providerId, $code);
        $this->assertEquals(3000, $this->formatter->formatSubAccount($subaccount, false, 'n/a'));
        $this->assertTrue($called);
    }
}
