<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Currency;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountTotalCalculator;
use AwardWallet\Tests\Unit\BaseUserTest;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\Test\TestLogger;

/**
 * @group frontend-unit
 */
class AccountTotalCalculatorTest extends BaseUserTest
{
    private ?Provider $provider;

    private ?Account $account;

    private ?AccountTotalCalculator $calculator;

    private ?TestLogger $logger;

    public function _before()
    {
        parent::_before();

        $this->provider = (new Provider())
            ->setAllowfloat(true);
        $this->account = (new Account())
            ->setProviderid($this->provider)
            ->setSubAccountsEntities([])
        ;
        $this->calculator = new AccountTotalCalculator(
            $this->logger = new TestLogger()
        );
    }

    public function _after()
    {
        $this->calculator = null;
        $this->account = null;
        $this->provider = null;

        parent::_after();
    }

    public function testNullMainBalance()
    {
        $this->account->setBalance(null);
        $this->seeTotalBalance(0);
    }

    public function testNonNullMainBalance()
    {
        $this->account->setBalance(450);
        $this->seeTotalBalance(450);

        $this->account->setBalance(700.40);
        $this->seeTotalBalance(700);

        $this->provider->setAllowfloat(false);
        $this->seeTotalBalance(700);
    }

    public function testFormattedMainBalance()
    {
        $this->account->setBalance('100,560');
        $this->seeTotalBalance(100560);

        $this->account->setBalance('100.500');
        $this->seeTotalBalance(100500);

        $this->provider->setAllowfloat(false);
        $this->account->setBalance('100.57');
        $this->seeTotalBalance(100);

        $this->account->setBalance('230,20');
        $this->seeTotalBalance(230);
    }

    public function testNullSubBalance()
    {
        $this->account->setBalance(null);
        $this->account->setSubAccountsEntities([
            $this->createSubAccount('test', null),
        ]);
        $this->seeTotalBalance(0);
    }

    public function testNonNullSubBalance()
    {
        $this->account->setBalance(null);
        $this->account->setSubAccountsEntities([
            $this->createSubAccount('test', 550),
        ]);
        $this->seeTotalBalance(550);

        $this->account->setSubAccountsEntities([
            $this->createSubAccount('test', 550),
            $this->createSubAccount('test2', 1000),
            $this->createSubAccount('test3', null),
        ]);
        $this->seeTotalBalance(1550);
    }

    public function testBalanceFormat()
    {
        $this->account->setBalance(200);
        $this->provider->setBalanceformat('$%d');
        $this->seeTotalBalance(0);

        $this->provider->setBalanceformat(null);
        $this->seeTotalBalance(200);

        $this->provider->setBalanceformat('function');
        $this->seeTotalBalance(200);
    }

    public function testProviderCurrency()
    {
        $this->account->setBalance(200);
        $this->provider->setCurrency(null);
        $this->seeTotalBalance(200);

        $this->provider->setCurrency((new Currency())->setCode('RUB'));
        $this->seeTotalBalance(200);

        $this->provider->setCurrency((new Currency())->setCode('USD'));
        $this->seeTotalBalance(200);
    }

    /**
     * @dataProvider totalProvider
     */
    public function testSpecificTotals(float $expected, array $account, array $subAccounts = [], ?string $warningText = null)
    {
        $props = [];
        $accountBalance = $account[0] ?? null;
        $currency = $account[1] ?? null;
        $this->account->setBalance($accountBalance);

        if (isset($currency)) {
            $props[] = $this->getAccountProperty(null, 'Currency', $currency);
        }

        if (count($props) > 0) {
            $this->account->setProperties(new ArrayCollection($props));
        }

        $subs = [];

        foreach ($subAccounts as $k => $subAccount) {
            $balance = $subAccount[0] ?? null;
            $inTotal = $subAccount[1] ?? null;
            $hidden = $subAccount[2] ?? false;
            $code = $subAccount[3] ?? sprintf('test_%d', $k);
            $currency = $subAccount[4] ?? null;
            $props = [];

            if (isset($currency)) {
                $props['Currency'] = $currency;
            }

            $subs[] = $this->createSubAccount($code, $balance, $inTotal, $hidden, $props);
        }
        $this->account->setSubAccountsEntities($subs);
        $this->seeTotalBalance($expected);

        if ($warningText) {
            $this->assertTrue($this->logger->hasWarningThatContains($warningText), json_encode($this->logger->records));
        }
    }

    public function totalProvider()
    {
        return [
            [
                100,
                [null, null],
                [
                    [100, null, false],
                ],
            ],

            [
                400,
                [null, null],
                [
                    [100, null, false],
                    [300, null, false],
                ],
            ],

            [
                0,
                [null, null],
                [
                    [100, null, false],
                    [300, true, false],
                ],
                '"0" <> "300"',
            ],

            [
                300,
                [300, null],
                [
                    [100, null, false],
                    [300, true, false],
                ],
            ],

            [
                400,
                [400, null],
                [
                    [100, true, false],
                    [300, true, false],
                ],
            ],

            [
                400,
                [400, null],
                [
                    [100, true, true],
                    [300, true, true],
                ],
            ],

            [
                300,
                [300, null],
                [
                    [100, true, false],
                    [300, true, true],
                ],
                '"300" <> "400"',
            ],

            [
                100500,
                [100500, null],
                [
                    [100, true, true],
                    [300, true, true],
                ],
                '"100500" <> "400"',
            ],

            [
                425,
                [425.75, null],
                [
                    [125.75, true, false],
                    [300, true, false],
                ],
            ],

            [
                1000,
                [1000, null],
                [
                    [125.75, true, false],
                    [300, true, false],
                ],
                '"1000" <> "425"',
            ],

            [
                125,
                [null, null],
                [
                    [125.75, null, false],
                    [300, null, true],
                ],
            ],

            [
                0,
                [null, null],
                [
                    [125.75, null, true],
                    [300, null, true],
                ],
            ],

            [
                425,
                [1000, 'USD'],
                [
                    [125.75, null, false],
                    [300, null, false],
                ],
            ],

            [
                425,
                [1000, '$'],
                [
                    [125.75, null, false],
                    [300, null, false],
                ],
            ],

            [
                300,
                [1000, '$'],
                [
                    [300, null, false],
                ],
            ],

            [
                0,
                [1000, 'US$'],
                [
                    [250, true, false],
                    [300, true, false, null, 'EUR'],
                    [100, true, true],
                    [900, false, false],
                    [1500, true, false, null, '$'],
                    [5000, true, false],
                ],
                '"0" <> "5650"',
            ],

            [
                5250,
                [5250, null],
                [
                    [250, true, false],
                    [300, true, false, null, 'EUR'],
                    [100, true, true],
                    [900, false, false],
                    [1500, true, false, null, '$'],
                    [5000, true, false],
                ],
            ],

            [
                2300,
                [2000, null],
                [
                    [300, null, false, 'test-1'],
                    [400, null, false, 'xxxFICO'],
                ],
            ],

            [
                2300,
                [2000, null],
                [
                    [300, null, false, 'qwertyfico'],
                    [400, null, false, 'xxxFICO'],
                ],
            ],

            [
                2000,
                [2000, null],
                [
                    [300, null, true, 'qwertyfico'],
                    [400, null, false, 'xxxFICO'],
                ],
            ],

            [
                610,
                [100.56, null],
                [
                    [100.18, null, false],
                    [410.09, null, false],
                ],
            ],
        ];
    }

    private function createSubAccount(string $code, ?float $balance, ?bool $inTotalSum = null, bool $hidden = false, array $props = []): Subaccount
    {
        $subAccount = (new Subaccount())
            ->setCode($code)
            ->setDisplayname($code)
            ->setBalance($balance)
            ->setAccountid($this->account)
            ->setIsHidden($hidden);

        $preparedProps = [];

        if (is_bool($inTotalSum)) {
            $preparedProps[] = $this->getAccountPropertyInTotalSum($subAccount, $inTotalSum);
        }

        foreach ($props as $k => $val) {
            $preparedProps[] = $this->getAccountProperty($subAccount, $k, $val);
        }

        if (count($preparedProps) > 0) {
            $subAccount->setProperties(new ArrayCollection($preparedProps));
        }

        return $subAccount;
    }

    private function getAccountPropertyInTotalSum(?Subaccount $subaccount = null, bool $val)
    {
        return $this->getAccountProperty($subaccount, 'BalanceInTotalSum', (int) $val);
    }

    private function getAccountProperty(?Subaccount $subaccount = null, string $code, $val)
    {
        return (new Accountproperty())
            ->setAccountid($this->account)
            ->setSubaccountid($subaccount)
            ->setProviderpropertyid(
                (new Providerproperty())
                    ->setCode($code)
            )
            ->setVal($val);
    }

    private function seeTotalBalance(float $expected)
    {
        $this->assertEquals($expected, $this->calculator->calculate($this->account));
    }
}
