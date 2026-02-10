<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Entity\Providerproperty as EntityProviderProperty;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopInfoMapper;
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\AccountProperty;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderProperty;
use AwardWallet\Tests\Modules\DbBuilder\SubAccount;
use AwardWallet\Tests\Modules\DbBuilder\User;

/**
 * @group frontend-unit
 */
class MapperFormatAccountPropertyTest extends AbstractMapperTest
{
    /**
     * @dataProvider dataProvider
     */
    public function test(Account $account, array $mappers)
    {
        $this->testMappers($mappers, $account);
    }

    public function dataProvider(): array
    {
        return [
            'number' => [
                new Account(
                    new User(null, false, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]),
                    new Provider(),
                    [
                        new AccountProperty(ProviderProperty::createTyped('test1', EntityProviderProperty::TYPE_NUMBER), '740.00'),
                    ],
                    [],
                    [
                        new SubAccount(
                            'testsub',
                            null,
                            [
                                new AccountProperty(ProviderProperty::createTyped('testsub1', EntityProviderProperty::TYPE_NUMBER), '5 nights'),
                            ]
                        ),
                    ]
                ),
                [
                    self::mapperSet([
                        '[Properties][test1][Val]' => '740',
                        '[SubAccountsArray][0][Properties][testsub1][Val]' => '5',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        "$.Blocks[?(@.Name=test1)].Val" => '740',
                        "$.SubAccountsArray[0]Blocks[?(@.Name=testsub1)].Val" => '5',
                    ]),
                ],
            ],

            'date' => [
                new Account(
                    new User(null, false, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]),
                    new Provider(),
                    [
                        new AccountProperty(ProviderProperty::createTyped('test1', EntityProviderProperty::TYPE_DATE), '1668506883'),
                    ],
                    [],
                    [
                        new SubAccount(
                            'testsub',
                            null,
                            [
                                new AccountProperty(ProviderProperty::createTyped('testsub1', EntityProviderProperty::TYPE_DATE), '2022-10-11 10:13:00'),
                            ]
                        ),
                    ]
                ),
                [
                    self::mapperSet([
                        '[Properties][test1][Val]' => 'Nov 15, 2022',
                        '[SubAccountsArray][0][Properties][testsub1][Val]' => 'Oct 11, 2022',
                    ]),
                    self::mobileMapperSet('4.37.0', [
                        "$.Blocks[?(@.Name=test1)].Val" => 'Nov 15, 2022',
                        "$.SubAccountsArray[0]Blocks[?(@.Name=testsub1)].Val" => 'Oct 11, 2022',
                    ]),
                ],
            ],

            'last activity without type' => [
                new Account(
                    new User(null, false, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]),
                    new Provider(),
                    [
                        new AccountProperty(ProviderProperty::createTyped('LastActivity', null, PROPERTY_KIND_LAST_ACTIVITY), '2022-11-12'),
                    ]
                ),
                [
                    self::mapperSet([
                        '[Properties][LastActivity][Val]' => '2022-11-12',
                    ], [], DesktopInfoMapper::class),
                ],
            ],

            'last activity with type' => [
                new Account(
                    new User(null, false, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]),
                    new Provider(),
                    [
                        new AccountProperty(ProviderProperty::createTyped('LastActivity', EntityProviderProperty::TYPE_DATE, PROPERTY_KIND_LAST_ACTIVITY), '2022-11-12'),
                    ]
                ),
                [
                    self::mapperSet([
                        '[Properties][LastActivity][Val]' => '/ ago$/',
                        '[Properties][LastActivity][Tip]' => 'November 12, 2022',
                    ], [], DesktopInfoMapper::class),
                ],
            ],
        ];
    }
}
