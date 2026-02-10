<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Currency as CurrencyEntity;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\AccountProperty;
use AwardWallet\Tests\Modules\DbBuilder\Currency;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderCoupon;
use AwardWallet\Tests\Modules\DbBuilder\ProviderProperty;
use AwardWallet\Tests\Modules\DbBuilder\SubAccount;
use AwardWallet\Tests\Modules\DbBuilder\User;
use AwardWallet\Tests\Modules\DbBuilder\UserPointValue;

/**
 * @group frontend-unit
 */
class MapperFormatTotalsTest extends AbstractMapperTest
{
    /**
     * @dataProvider couponDataProvider
     */
    public function testCoupon(ProviderCoupon $coupon, array $mappers)
    {
        $this->testMappers($mappers, $coupon);
    }

    public function couponDataProvider()
    {
        return [
            'null balance' => [
                new ProviderCoupon('Test Coupon', null, new User()),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 0], ['[USDCash]', '[USDCashRaw]', '[TotalUSDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0]),
                ],
            ],

            'text balance' => [
                new ProviderCoupon('Test Coupon', 'Text value', new User()),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 0], ['[USDCash]', '[USDCashRaw]', '[TotalUSDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0]),
                ],
            ],

            'total > 0' => [
                new ProviderCoupon('Test Coupon', 100, new User()),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 100], ['[USDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 100]),
                ],
            ],

            'non usd currency' => [
                (new ProviderCoupon('Test Coupon', 100, new User()))
                    ->setCurrency(new Currency('Silver', 'SV', 'SV')),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 100], ['[USDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 100]),
                ],
            ],

            'usd currency' => [
                new ProviderCoupon(
                    'Test Coupon',
                    50,
                    new User(),
                    PROVIDER_KIND_AIRLINE,
                    [
                        'CurrencyID' => CurrencyEntity::USD_ID,
                    ]
                ),
                [
                    self::mapperSet([
                        '[USDCashRaw]' => (float) 50,
                        '[USDCash]' => '$50',
                        '[TotalUSDCashRaw]' => (float) 50,
                        '[TotalUSDCash]' => '$50',
                    ], ['[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.36.0', [], ['[TotalUSDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalUSDCash]' => (float) 50], ['[TotalUSDCashChange]']),
                ],
            ],

            'attached coupon' => [
                (
                    new ProviderCoupon(
                        'Test Coupon',
                        350,
                        $user = new User(),
                        PROVIDER_KIND_AIRLINE,
                        [
                            'CurrencyID' => CurrencyEntity::USD_ID,
                        ]
                    )
                )->setAccount(new Account($user, new Provider())),
                [
                    self::mapperSet([
                        '[USDCashRaw]' => (float) 350,
                        '[USDCash]' => '$350',
                        '[TotalUSDCashRaw]' => (float) 350,
                        '[TotalUSDCash]' => '$350',
                    ], ['[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.36.0', [], ['[TotalUSDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalUSDCash]' => (float) 350], ['[TotalUSDCashChange]']),
                ],
            ],
        ];
    }

    /**
     * @dataProvider accountDataProvider
     */
    public function testAccount(Account $account, array $mappers)
    {
        $this->testMappers($mappers, $account);
    }

    public function accountDataProvider()
    {
        return [
            'null balance' => [
                new Account(
                    new User(),
                    new Provider(),
                    [],
                    ['Balance' => null],
                ),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 0], ['[USDCash]', '[USDCashRaw]', '[TotalUSDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0], ['[USDCash]', '[USDCashRaw]', '[TotalUSDCash]']),
                ],
            ],

            'total balance <> balance' => [
                new Account(
                    new User(),
                    new Provider(),
                    [],
                    ['Balance' => 100, 'TotalBalance' => 200],
                ),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 200], ['[USDCash]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 200], ['[USDCash]']),
                ],
            ],

            'usd balance via balance format, cash eq' => [
                new Account(
                    new User(),
                    new Provider(null, ['BalanceFormat' => '$%0.2f']),
                    [],
                    ['Balance' => 50, 'TotalBalance' => 0],
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 0,
                        '[USDCashRaw]' => (float) 50,
                        '[USDCash]' => '$50',
                        '[TotalUSDCashRaw]' => (float) 50,
                        '[TotalUSDCash]' => '$50',
                    ], ['[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0, '[TotalUSDCash]' => (float) 50], ['[TotalUSDCashChange]']),
                ],
            ],

            'non usd balance via balance format' => [
                new Account(
                    new User(),
                    new Provider(null, ['BalanceFormat' => '£%0.2f']),
                    [],
                    ['Balance' => 50, 'TotalBalance' => 50],
                ),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 50], ['[USDCash]', '[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 50], ['[TotalUSDCashChange]']),
                ],
            ],

            'usd balance via currency property' => [
                new Account(
                    new User(),
                    new Provider(),
                    [AccountProperty::createByCode('Currency', '$')],
                    ['Balance' => 50, 'TotalBalance' => 0],
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 0,
                        '[USDCashRaw]' => (float) 50,
                        '[USDCash]' => '$50',
                        '[TotalUSDCashRaw]' => (float) 50,
                        '[TotalUSDCash]' => '$50',
                    ], ['[TotalUSDCashChange]', '[USDCashMileValue]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0, '[TotalUSDCash]' => (float) 50], ['[TotalUSDCashChange]']),
                ],
            ],

            'non usd balance via currency property' => [
                new Account(
                    new User(),
                    new Provider(),
                    [AccountProperty::createByCode('Currency', '£')],
                    ['Balance' => 50, 'TotalBalance' => 50],
                ),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 50], ['[USDCash]', '[TotalUSDCashChange]', '[USDCashMileValue]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 50], ['[TotalUSDCash]', '[TotalUSDCashChange]']),
                ],
            ],

            'usd balance via manual currency, custom account' => [
                new Account(
                    new User(),
                    null,
                    [],
                    ['Balance' => 50, 'TotalBalance' => 0, 'CurrencyID' => CurrencyEntity::USD_ID],
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 0,
                        '[USDCashRaw]' => (float) 50,
                        '[USDCash]' => '$50',
                        '[TotalUSDCashRaw]' => (float) 50,
                        '[TotalUSDCash]' => '$50',
                    ], [
                        '[USDCashMileValue]',
                        '[TotalUSDCashChange]',
                    ]),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0, '[TotalUSDCash]' => (float) 50], ['[TotalUSDCashChange]']),
                ],
            ],

            'non usd balance via manual currency' => [
                (new Account(
                    new User(),
                    null,
                    [],
                    ['Balance' => 50, 'TotalBalance' => 50, 'CurrencyID' => CurrencyEntity::USD_ID],
                ))->setCurrency(new Currency('XXX', '£', 'XXX')),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 50], ['[USDCash]', '[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 50], ['[TotalUSDCash]', '[TotalUSDCashChange]']),
                ],
            ],

            'custom account, total' => [
                new Account(
                    new User(),
                    null,
                    [],
                    ['Balance' => 50, 'TotalBalance' => 0],
                ),
                [
                    self::mapperSet(['[TotalBalance]' => (float) 50], ['[USDCash]', '[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 50], ['[TotalUSDCash]', '[TotalUSDCashChange]']),
                ],
            ],

            'BalanceInTotalSum, 1 sub account, without highlighting' => [
                new Account(
                    new User(),
                    new Provider(),
                    [],
                    ['Balance' => 100],
                    [
                        new SubAccount('sub1', 100, [
                            AccountProperty::createByCode('BalanceInTotalSum', 1),
                        ]),
                    ]
                ),
                [
                    self::mapperSet([], ['[Complex]', '[SubAccountsArray][0][Unhideable]']),
                ],
            ],

            'BalanceInTotalSum, 3 sub account, with highlighting' => [
                new Account(
                    new User(),
                    new Provider(),
                    [],
                    ['Balance' => 100, 'TotalBalance' => 250],
                    [
                        new SubAccount('sub1', 100, [
                            new AccountProperty($pp = new ProviderProperty('BalanceInTotalSum'), 1),
                        ]),
                        new SubAccount('sub2', 150, [
                            new AccountProperty($pp, 1),
                        ]),
                        new SubAccount('sub3', 300),
                    ]
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 250,
                        '[Complex]' => true,
                        '[SubAccountsArray][0][Unhideable]' => true,
                        '[SubAccountsArray][1][Unhideable]' => true,
                    ], ['[SubAccountsArray][2][Unhideable]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 250]),
                ],
            ],

            'MileValue' => [
                new Account(
                    $user = new User(),
                    (new Provider())->setUserPointValue(new UserPointValue(25, $user)),
                    [],
                    ['Balance' => 50, 'TotalBalance' => 0],
                ),
                [
                    self::mapperSet([
                        '[USDCashMileValue]' => true,
                        '[TotalBalance]' => (float) 0,
                        '[USDCashRaw]' => (float) 13,
                        '[USDCash]' => '$13',
                        '[TotalUSDCashRaw]' => (float) 13,
                        '[TotalUSDCash]' => '$13',
                    ], ['[TotalUSDCashChange]']),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0, '[TotalUSDCash]' => (float) 13], ['[TotalUSDCashChange]']),
                ],
            ],

            'MileValue change' => [
                new Account(
                    $user = new User(),
                    (new Provider())->setUserPointValue(new UserPointValue(25, $user)),
                    [],
                    ['Balance' => 50, 'TotalBalance' => 0, 'LastChangeDate' => date('Y-m-d'), 'LastBalance' => 25],
                ),
                [
                    self::mapperSet([
                        '[USDCashMileValue]' => true,
                        '[TotalBalance]' => (float) 0,
                        '[USDCashRaw]' => (float) 13,
                        '[USDCash]' => '$13',
                        '[TotalUSDCashRaw]' => (float) 13,
                        '[TotalUSDCash]' => '$13',
                        '[TotalUSDCashChange]' => (float) 6,
                    ]),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 0, '[TotalUSDCash]' => (float) 13, '[TotalUSDCashChange]' => (float) 6]),
                ],
            ],

            'Total balance change' => [
                new Account(
                    new User(),
                    new Provider(),
                    [],
                    ['Balance' => 70, 'TotalBalance' => 70, 'LastChangeDate' => date('Y-m-d'), 'LastBalance' => 45],
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 70,
                        '[TotalBalanceChange]' => (float) 25,
                    ]),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 70, '[LastChangeRaw]' => (float) 25], ['[TotalUSDCash]', '[TotalUSDCashChange]']),
                ],
            ],

            'Total balance change, 2' => [
                new Account(
                    new User(),
                    new Provider(),
                    [],
                    ['Balance' => 70, 'TotalBalance' => 70, 'LastChangeDate' => date('Y-m-d'), 'LastBalance' => 100],
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 70,
                        '[TotalBalanceChange]' => (float) -30,
                    ]),
                    self::mobileMapperSet('4.37.0', ['[TotalBalance]' => (float) 70, '[LastChangeRaw]' => (float) -30], ['[TotalUSDCash]', '[TotalUSDCashChange]']),
                ],
            ],

            'MileValue in subaccounts' => [
                new Account(
                    $user = new User(),
                    (new Provider())->setUserPointValue(new UserPointValue(25, $user)),
                    [],
                    ['Balance' => 265, 'TotalBalance' => 250],
                    [
                        new SubAccount('sub1', 100, [
                            new AccountProperty($pp = new ProviderProperty('BalanceInTotalSum'), 1),
                        ]),
                        new SubAccount('sub2', 150, [
                            new AccountProperty($pp, 1),
                        ]),
                        new SubAccount('sub3', 15, [
                            new AccountProperty($pp, 1),
                        ]),
                        new SubAccount('sub4', 300),
                    ]
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 250,
                        '[Complex]' => true,
                        '[TotalUSDCashRaw]' => (float) 67,
                        '[SubAccountsArray][0][Unhideable]' => true,
                        '[SubAccountsArray][0][USDCashMileValue]' => true,
                        '[SubAccountsArray][0][USDCashRaw]' => (float) 25,
                        '[SubAccountsArray][0][USDCash]' => '$25',
                        '[SubAccountsArray][1][Unhideable]' => true,
                        '[SubAccountsArray][1][USDCashRaw]' => (float) 38,
                        '[SubAccountsArray][1][USDCash]' => '$38',
                        '[SubAccountsArray][1][USDCashMileValue]' => true,
                        '[SubAccountsArray][2][Unhideable]' => true,
                        '[SubAccountsArray][2][USDCashRaw]' => 4.0,
                        '[SubAccountsArray][2][USDCash]' => '$4',
                        '[SubAccountsArray][2][USDCashMileValue]' => true,
                    ], ['[SubAccountsArray][3][Unhideable]']),
                    self::mobileMapperSet('4.37.0', [
                        '[TotalBalance]' => (float) 250,
                        '[TotalUSDCash]' => (float) 67,
                    ], ['[TotalUSDCashChange]']),
                ],
            ],

            'MileValue in subaccounts, 2' => [
                new Account(
                    $user = new User(),
                    (new Provider())->setUserPointValue(new UserPointValue(25, $user)),
                    [],
                    ['Balance' => 50, 'TotalBalance' => 300],
                    [
                        new SubAccount('sub1', 100),
                        new SubAccount('sub2', 150),
                        new SubAccount('sub3', null),
                    ]
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 300,
                        '[TotalUSDCashRaw]' => (float) 13,
                    ], [
                        '[Complex]',
                        '[SubAccountsArray][0][Unhideable]',
                        '[SubAccountsArray][0][USDCash]',
                        '[SubAccountsArray][1][Unhideable]',
                        '[SubAccountsArray][1][USDCash]',
                        '[SubAccountsArray][2][Unhideable]',
                        '[SubAccountsArray][2][USDCash]',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        '[TotalBalance]' => (float) 300,
                        '[TotalUSDCash]' => (float) 13,
                    ], ['[TotalUSDCashChange]']),
                ],
            ],

            'MileValue in subaccounts, 3' => [
                new Account(
                    $user = new User(),
                    (new Provider())->setUserPointValue(new UserPointValue(25, $user)),
                    [],
                    ['Balance' => 400000, 'TotalBalance' => 400000, 'LastChangeDate' => date('Y-m-d'), 'LastBalance' => 300000],
                    [
                        new SubAccount('sub1', 0, [
                            new AccountProperty($pp = new ProviderProperty('BalanceInTotalSum'), 1),
                        ]),
                        new SubAccount('sub2', 400000, [
                            new AccountProperty($pp, 1),
                        ], ['LastChangeDate' => date('Y-m-d'), 'LastBalance' => 300000]),
                    ]
                ),
                [
                    self::mapperSet([
                        '[USDCashMileValue]' => true,
                        '[TotalBalance]' => (float) 400000,
                        '[TotalBalanceChange]' => (float) 100000,
                        '[USDCash]' => '$100,000',
                        '[TotalUSDCash]' => '$100,000',
                        '[TotalUSDCashRaw]' => (float) 100000,
                        '[TotalUSDCashChange]' => (float) 25000,
                        '[Complex]' => true,
                        '[SubAccountsArray][0][Unhideable]' => true,
                        '[SubAccountsArray][0][USDCashMileValue]' => true,
                        '[SubAccountsArray][0][USDCashRaw]' => (float) 0,
                        '[SubAccountsArray][0][USDCash]' => '$0',
                        '[SubAccountsArray][1][Unhideable]' => true,
                        '[SubAccountsArray][1][USDCashRaw]' => (float) 100000,
                        '[SubAccountsArray][1][USDCash]' => '$100,000',
                        '[SubAccountsArray][1][USDCashMileValue]' => true,
                        '[SubAccountsArray][1][USDCashChange]' => '+$25,000',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        '[TotalBalance]' => (float) 400000,
                        '[TotalUSDCash]' => (float) 100000,
                    ], ['[SubAccountsArray][1][USDCashChange]']),
                ],
            ],

            'USD balance in subaccounts' => [
                new Account(
                    new User(),
                    new Provider(null, ['BalanceFormat' => '$%0.2f']),
                    [],
                    ['Balance' => 250, 'TotalBalance' => 800],
                    [
                        new SubAccount('sub1', 100),
                        new SubAccount('sub2', 150),
                        new SubAccount('sub3', 300),
                    ]
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 800,
                        '[TotalUSDCash]' => '$800',
                        '[SubAccountsArray][0][USDCashRaw]' => (float) 100,
                        '[SubAccountsArray][0][USDCash]' => '$100',
                        '[SubAccountsArray][1][USDCashRaw]' => (float) 150,
                        '[SubAccountsArray][1][USDCash]' => '$150',
                        '[SubAccountsArray][2][USDCashRaw]' => (float) 300,
                        '[SubAccountsArray][2][USDCash]' => '$300',
                    ], [
                        '[SubAccountsArray][0][USDCashMileValue]',
                        '[SubAccountsArray][1][USDCashMileValue]',
                        '[SubAccountsArray][2][USDCashMileValue]',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        '[TotalBalance]' => (float) 800,
                        '[TotalUSDCash]' => (float) 800,
                    ], ['[TotalUSDCashChange]']),
                ],
            ],

            'USD total in subaccounts, MileValue' => [
                new Account(
                    $user = new User(),
                    (new Provider())->setUserPointValue(new UserPointValue(50, $user)),
                    [],
                    ['Balance' => 250, 'TotalBalance' => 0],
                    [
                        new SubAccount('sub1', 100, [
                            new AccountProperty($pp = new ProviderProperty('BalanceInTotalSum'), 1),
                        ]),
                        new SubAccount('sub2', 150, [
                            new AccountProperty($pp, 1),
                        ]),
                        new SubAccount('sub3', 300, [
                            AccountProperty::createByCode('Currency', '$'),
                        ]),
                    ]
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 0,
                        '[USDCashRaw]' => (float) 125,
                        '[USDCash]' => '$125',
                        '[TotalUSDCashRaw]' => (float) 125,
                        '[TotalUSDCash]' => '$125',
                        '[Complex]' => true,
                        '[SubAccountsArray][0][Unhideable]' => true,
                        '[SubAccountsArray][0][USDCashMileValue]' => true,
                        '[SubAccountsArray][0][USDCashRaw]' => (float) 50,
                        '[SubAccountsArray][0][USDCash]' => '$50',
                        '[SubAccountsArray][1][Unhideable]' => true,
                        '[SubAccountsArray][1][USDCashMileValue]' => true,
                        '[SubAccountsArray][1][USDCashRaw]' => (float) 75,
                        '[SubAccountsArray][1][USDCash]' => '$75',
                        '[SubAccountsArray][2][USDCashRaw]' => (float) 300,
                        '[SubAccountsArray][2][USDCash]' => '$300',
                    ], [
                        '[SubAccountsArray][2][Unhideable]',
                        '[SubAccountsArray][2][USDCashMileValue]',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        '[TotalBalance]' => (float) 0,
                        '[TotalUSDCash]' => (float) 125,
                    ], ['[TotalUSDCashChange]']),
                ],
            ],

            'USD total in subaccounts, BalanceInTotalSum' => [
                new Account(
                    new User(),
                    new Provider(null, ['BalanceFormat' => '$%0.2f']),
                    [],
                    ['Balance' => 250, 'TotalBalance' => 0],
                    [
                        new SubAccount('sub1', 100, [
                            new AccountProperty($pp = new ProviderProperty('BalanceInTotalSum'), 1),
                        ]),
                        new SubAccount('sub2', 150, [
                            new AccountProperty($pp, 1),
                        ]),
                        new SubAccount('sub3', 300),
                    ]
                ),
                [
                    self::mapperSet([
                        '[TotalBalance]' => (float) 0,
                        '[TotalUSDCashRaw]' => (float) 550,
                        '[TotalUSDCash]' => '$550',
                        '[Complex]' => true,
                        '[SubAccountsArray][0][Unhideable]' => true,
                        '[SubAccountsArray][0][USDCashRaw]' => (float) 100,
                        '[SubAccountsArray][0][USDCash]' => '$100',
                        '[SubAccountsArray][1][Unhideable]' => true,
                        '[SubAccountsArray][1][USDCashRaw]' => (float) 150,
                        '[SubAccountsArray][1][USDCash]' => '$150',
                        '[SubAccountsArray][2][USDCashRaw]' => (float) 300,
                        '[SubAccountsArray][2][USDCash]' => '$300',
                    ], [
                        '[SubAccountsArray][0][USDCashMileValue]',
                        '[SubAccountsArray][1][USDCashMileValue]',
                        '[SubAccountsArray][2][USDCashMileValue]',
                        '[SubAccountsArray][2][Unhideable]',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        '[TotalBalance]' => (float) 0,
                        '[TotalUSDCash]' => (float) 550,
                    ], ['[TotalUSDCashChange]']),
                ],
            ],
        ];
    }
}
