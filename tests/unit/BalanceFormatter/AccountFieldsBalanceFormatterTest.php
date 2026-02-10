<?php

namespace AwardWallet\Tests\Unit\BalanceFormatter;

use AwardWallet\MainBundle\Entity\Account;

/**
 * @group frontend-unit
 */
class AccountFieldsBalanceFormatterTest extends AbstractBalanceFormatterTest
{
    public function testNA()
    {
        [$accountFields, $props] = $this->createAccountFieldsWithBalance(null);
        $this->assertEquals('n/a', $this->formatter->formatFields($accountFields, $props, false, 'n/a'));
        $this->assertNull($this->formatter->formatFields($accountFields, $props, false, null));
        $this->assertEquals('n/a', $this->formatter->formatFields($accountFields, $props, null, 'n/a'));
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
        [$accountFields, $props] = $this->createAccountFieldsWithBalance($balance);
        $this->assertEquals('n/a', $this->formatter->formatFields($accountFields, $props, false, 'n/a'));
        $this->assertEquals('n/a', $this->formatter->formatFields($accountFields, $props, $balance, 'n/a'));
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
        [$accountFields, $props] = $this->createAccountFieldsWithBalance(100);
        $this->assertEquals('5,000', $this->formatter->formatFields($accountFields, $props, 5000));
        $this->assertEquals('5,000', $this->formatter->formatFields($accountFields, $props, '5,000'));
    }

    public function testFormatBalanceAsNumber()
    {
        /** @var Account $account */
        [$accountFields, $props, $account] = $this->createAccountFieldsWithBalance(3000);
        $this->assertEquals('3,000', $this->formatter->formatFields($accountFields, $props));
        $this->assertEquals('8,000', $this->formatter->formatFields($accountFields, $props, 8000));
        $this->assertEquals('100', $this->formatter->formatFields($accountFields, $props, 100.00));
        $this->assertEquals('2,500', $this->formatter->formatFields($accountFields, $props, 2500.00));
        $this->assertEquals('100.20', $this->formatter->formatFields($accountFields, $props, 100.20));

        $this->db->updateInDatabase('Provider', [
            'BalanceFormat' => $accountFields['BalanceFormat'] = '$%0.2f',
        ], ['ProviderID' => $this->providerId]);
        $this->em->refresh($account->getProviderid());

        $this->assertEquals('$3,000', $this->formatter->formatFields($accountFields, $props));
        $this->assertEquals('$1,520.30', $this->formatter->formatFields($accountFields, $props, 1520.30));

        $this->db->updateInDatabase('Provider', [
            'BalanceFormat' => $accountFields['BalanceFormat'] = '&pound;%d',
        ], ['ProviderID' => $this->providerId]);
        $this->em->refresh($account->getProviderid());

        $this->assertEquals('£3,000', $this->formatter->formatFields($accountFields, $props));
        $this->assertEquals('£1,520', $this->formatter->formatFields($accountFields, $props, 1520.30));
    }

    /**
     * @dataProvider formatBalanceViaCheckerProvider
     */
    public function testFormatBalanceViaChecker($balance, $expected)
    {
        $providerId = $this->aw->createAwProvider(
            $name = 'formbal' . $this->aw->grabRandomString(5),
            $name,
            [
                'BalanceFormat' => 'function',
            ],
            [],
            [
                'FormatBalance' => function ($fields, $properties) use ($balance) {
                    return $balance;
                },
            ]
        );
        [$accountFields, $props] = $this->createAccountFieldsWithBalance(3000, $providerId);
        $this->assertEquals($expected, $this->formatter->formatFields($accountFields, $props, $balance, 'n/a'));
    }

    public function formatBalanceViaCheckerProvider()
    {
        return [
            [1000, 1000],
            [500.20, 500.20],
            [null, 'n/a'],
        ];
    }

    public function testCustomAccount()
    {
        /** @var Account $account */
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount(
                $this->user->getId(),
                null,
                'test',
                null,
                [
                    'Balance' => 2500,
                ]
            )
        );

        [$accountFields, $props] = $this->createAccountFields($account);

        $accountFields['ManualCurrencySign'] = 'miles';
        $accountFields['ManualCurrencyCode'] = null;
        $this->assertEquals('2,500 miles', $this->formatter->formatFields($accountFields, $props));

        $accountFields['ManualCurrencySign'] = 'points';
        $accountFields['ManualCurrencyCode'] = null;
        $this->assertEquals('2,500 points', $this->formatter->formatFields($accountFields, $props));

        $accountFields['ManualCurrencySign'] = 'Dollars';
        $accountFields['ManualCurrencyCode'] = 'USD';
        $this->assertEquals('$2,500', $this->formatter->formatFields($accountFields, $props));

        $accountFields['ManualCurrencySign'] = '$';
        $accountFields['ManualCurrencyCode'] = null;
        $this->assertEquals('$2,500', $this->formatter->formatFields($accountFields, $props));

        $accountFields['ManualCurrencySign'] = 'Dollars';
        $accountFields['ManualCurrencyCode'] = 'RUB';
        $this->assertEquals('RUB 2,500', $this->formatter->formatFields($accountFields, $props));

        $accountFields['ManualCurrencySign'] = 'Dollars';
        $accountFields['ManualCurrencyCode'] = 'RUB';
        $this->assertEquals('2 500 ₽', $this->formatter->formatFields($accountFields, $props, false, null, 'ru_RU'));
    }
}
