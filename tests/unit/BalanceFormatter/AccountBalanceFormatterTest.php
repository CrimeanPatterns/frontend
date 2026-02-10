<?php

namespace AwardWallet\Tests\Unit\BalanceFormatter;

use AwardWallet\MainBundle\Entity\Account;

/**
 * @group frontend-unit
 */
class AccountBalanceFormatterTest extends AbstractBalanceFormatterTest
{
    public function testNA()
    {
        $account = $this->createAccountWithBalance(null);
        $this->assertEquals('n/a', $this->formatter->formatAccount($account, false, 'n/a'));
        $this->assertNull($this->formatter->formatAccount($account, false, null));
        $this->assertEquals('n/a', $this->formatter->formatAccount($account, null, 'n/a'));
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
        $account = $this->createAccountWithBalance($balance);
        $this->assertEquals('n/a', $this->formatter->formatAccount($account, false, 'n/a'));
        $this->assertEquals('n/a', $this->formatter->formatAccount($account, $balance, 'n/a'));
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
        $account = $this->createAccountWithBalance(100);
        $this->assertEquals('5,000', $this->formatter->formatAccount($account, 5000));
        $this->assertEquals('5,000', $this->formatter->formatAccount($account, '5,000'));
    }

    public function testFormatBalanceAsNumber()
    {
        $account = $this->createAccountWithBalance(3000);
        $this->assertEquals('3,000', $this->formatter->formatAccount($account));
        $this->assertEquals('8,000', $this->formatter->formatAccount($account, 8000));
        $this->assertEquals('100', $this->formatter->formatAccount($account, 100.00));
        $this->assertEquals('2,500', $this->formatter->formatAccount($account, 2500.00));
        $this->assertEquals('100.20', $this->formatter->formatAccount($account, 100.20));

        $this->db->updateInDatabase('Provider', [
            'BalanceFormat' => '$%0.2f',
        ], ['ProviderID' => $this->providerId]);
        $this->em->refresh($account->getProviderid());

        $this->assertEquals('$3,000', $this->formatter->formatAccount($account));
        $this->assertEquals('$1,520.30', $this->formatter->formatAccount($account, 1520.30));

        $this->db->updateInDatabase('Provider', [
            'BalanceFormat' => '&pound;%d',
        ], ['ProviderID' => $this->providerId]);
        $this->em->refresh($account->getProviderid());

        $this->assertEquals('£3,000', $this->formatter->formatAccount($account));
        $this->assertEquals('£1,520', $this->formatter->formatAccount($account, 1520.30));
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
        $account = $this->createAccountWithBalance(3000, $providerId);
        $this->assertEquals($expected, $this->formatter->formatAccount($account, $balance, 'n/a'));
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
        $account->setCurrency($this->getCurrency('miles'));
        $this->assertEquals('2,500 miles', $this->formatter->formatAccount($account));

        $account->setCurrency($this->getCurrency('points'));
        $this->assertEquals('2,500 points', $this->formatter->formatAccount($account));

        $account->setCurrency($this->getCurrency('Dollars', 'USD'));
        $this->assertEquals('$2,500', $this->formatter->formatAccount($account));

        $account->setCurrency($this->getCurrency('Dollars', null, '$'));
        $this->assertEquals('$2,500', $this->formatter->formatAccount($account));

        $account->setCurrency($this->getCurrency('Dollars', 'RUB'));
        $this->assertEquals('RUB 2,500', $this->formatter->formatAccount($account));

        $account->setCurrency($this->getCurrency('Dollars', 'RUB'));
        $this->assertEquals('2 500 ₽', $this->formatter->formatAccount($account, false, null, 'ru_RU'));
    }
}
