<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Service\Account\SearchHintsDynamicFilters;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class SearchHintsDynamicFiltersTest extends BaseContainerTest
{
    private ?SearchHintsDynamicFilters $helper;

    public function _before()
    {
        parent::_before();
        $this->helper = new SearchHintsDynamicFilters(
            $this->container->get(Formatter::class),
            $this->makeEmpty(ElitelevelRepository::class, [
                'getEliteLevelFields' => function ($providerID, $status) {
                    $levels = [
                        10 => [
                            'Member' => ['Rank' => 0, 'Name' => 'Member'],
                            'Silver' => ['Rank' => 1, 'Name' => 'Silver'],
                            'Gold' => ['Rank' => 2, 'Name' => 'Gold'],
                        ],
                        11 => [
                            'Member' => ['Rank' => 0, 'Name' => 'Member'],
                            'Discoverist' => ['Rank' => 1, 'Name' => 'Discoverist'],
                            'Explorist' => ['Rank' => 2, 'Name' => 'Explorist'],
                        ],
                    ];

                    if (isset($levels[$providerID]) && is_array($levels[$providerID])) {
                        foreach ($levels[$providerID] as $key => $level) {
                            if (strcasecmp($key, $status) == 0) {
                                return $level;
                            }
                        }
                    }

                    return null;
                },
            ]),
            $this->container->get('translator')
        );
    }

    public function _after()
    {
        $this->helper = null;
        parent::_after();
    }

    /**
     * Генерирует динамические фильтры по всем категориям.
     */
    public function testGetFilters()
    {
        $result = $this->helper->run(self::getAccountsArray());

        $this->assertEquals([
            'alaska',
            'alliance:oneworld',
            'kind:airlines and balance > 12000',
            'passport mark',
            'expire in 1 month',
            '(delta or british) and (tom or mark or travis) and (kind:airlines)',
            '(marriott or hilton) and (mark or tom) and (kind:hotels)',
            'fico or vantage',
            'gift card',
            'status:gold or status:explorist',
        ], $result);
    }

    /**
     * Массив с тестовыми аккаунтами, на основе которых будут сгенерированы фильтры.
     */
    private static function getAccountsArray(): array
    {
        $today = new \DateTimeImmutable();

        return [
            'rawAccounts' => [
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 75000,
                    'ProviderName' => 'Alaska Air',
                    'TypeID' => null,
                    'ExpirationDate' => $today->add(new \DateInterval('P3Y'))->format('Y-m-d H:i:s'),
                    'ProviderID' => 2,
                    'AllianceID' => 3,
                    'AllianceAlias' => 'oneworld',
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 70000,
                    'ProviderName' => 'United',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 3,
                    'AllianceID' => 2,
                    'AllianceAlias' => 'staralliance',
                    'UserName' => 'Scott Raynor',
                    'UserID' => 4,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => 50,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 65000,
                    'ProviderName' => 'Delta',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 1,
                    'AllianceID' => 1,
                    'AllianceAlias' => 'skyteam',
                    'UserName' => 'Tom DeLonge',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => 2,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 60000,
                    'ProviderName' => 'Delta',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 1,
                    'AllianceID' => 1,
                    'AllianceAlias' => 'skyteam',
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 55000,
                    'ProviderName' => 'British Airways',
                    'TypeID' => null,
                    'ExpirationDate' => $today->add(new \DateInterval('P1M'))->format('Y-m-d H:i:s'),
                    'ProviderID' => 4,
                    'AllianceID' => 3,
                    'AllianceAlias' => 'oneworld',
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 45000,
                    'ProviderName' => 'British Airways',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 4,
                    'AllianceID' => 3,
                    'AllianceAlias' => 'oneworld',
                    'UserName' => 'Travis Barker',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => 3,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 45000,
                    'ProviderName' => 'Delta',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 1,
                    'AllianceID' => 1,
                    'AllianceAlias' => 'skyteam',
                    'UserName' => 'Travis Barker',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_AIRLINE,
                    'UserAgentID' => 3,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 300000,
                    'ProviderName' => 'Marriott',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 14,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 200000,
                    'ProviderName' => 'Radisson Hotels',
                    'TypeID' => null,
                    'ExpirationDate' => $today->add(new \DateInterval('P3M'))->format('Y-m-d H:i:s'),
                    'ProviderID' => 12,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 100000,
                    'ProviderName' => 'Hilton',
                    'TypeID' => null,
                    'ExpirationDate' => $today->add(new \DateInterval('P1Y'))->format('Y-m-d H:i:s'),
                    'ProviderID' => 13,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 150000,
                    'ProviderName' => 'Marriott',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 14,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Tom DeLonge',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => 2,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 50000,
                    'ProviderName' => 'Hilton',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 13,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Tom DeLonge',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => 2,
                ],
                [
                    'TableName' => 'Coupon',
                    'TotalBalance' => 0,
                    'ProviderName' => 'Passport',
                    'TypeID' => Providercoupon::TYPE_PASSPORT,
                    'ExpirationDate' => $today->add(new \DateInterval('P7Y'))->format('Y-m-d H:i:s'),
                    'ProviderID' => null,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_DOCUMENT,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Coupon',
                    'TotalBalance' => 0,
                    'ProviderName' => 'Passport',
                    'TypeID' => Providercoupon::TYPE_PASSPORT,
                    'ExpirationDate' => $today->add(new \DateInterval('P6M'))->format('Y-m-d H:i:s'),
                    'ProviderID' => null,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Tom DeLonge',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_DOCUMENT,
                    'UserAgentID' => 2,
                ],
                [
                    'TableName' => 'Coupon',
                    'TotalBalance' => 0,
                    'ProviderName' => 'Trusted Traveler Number',
                    'TypeID' => Providercoupon::TYPE_TRUSTED_TRAVELER,
                    'ExpirationDate' => $today->add(new \DateInterval('P4Y'))->format('Y-m-d H:i:s'),
                    'ProviderID' => null,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_DOCUMENT,
                    'UserAgentID' => null,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 300000,
                    'ProviderName' => 'Amex',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 20,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_CREDITCARD,
                    'UserAgentID' => null,
                    'SubAccountsArray' => [
                        [
                            'DisplayName' => 'FICO® Score 8 (Experian)',
                            'Balance' => '700',
                            'Properties' => ['FICOScoreUpdatedOn' => []],
                        ],
                    ],
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 400000,
                    'ProviderName' => 'Chase',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 21,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_CREDITCARD,
                    'UserAgentID' => null,
                    'SubAccountsArray' => [
                        [
                            'DisplayName' => 'VantageScore 3.0 (Experian)',
                            'Balance' => '800',
                            'Properties' => ['FICOScoreUpdatedOn' => []],
                        ],
                    ],
                ],
                [
                    'TableName' => 'Coupon',
                    'TotalBalance' => 150,
                    'ProviderName' => 'Amazon',
                    'TypeID' => Providercoupon::TYPE_GIFT_CARD,
                    'ExpirationDate' => null,
                    'ProviderID' => 30,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Scott Raynor',
                    'UserID' => 4,
                    'Kind' => PROVIDER_KIND_SHOPPING,
                    'UserAgentID' => 50,
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 5000,
                    'ProviderName' => 'Hotels.com',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 10,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => null,
                    'MainProperties' => [
                        'Status' => ['Status' => 'Gold'],
                    ],
                ],
                [
                    'TableName' => 'Account',
                    'TotalBalance' => 7500,
                    'ProviderName' => 'Hyatt',
                    'TypeID' => null,
                    'ExpirationDate' => null,
                    'ProviderID' => 11,
                    'AllianceID' => null,
                    'AllianceAlias' => null,
                    'UserName' => 'Mark Hoppus',
                    'UserID' => 1,
                    'Kind' => PROVIDER_KIND_HOTEL,
                    'UserAgentID' => null,
                    'MainProperties' => [
                        'Status' => ['Status' => 'Explorist'],
                    ],
                ],
            ],
            'agents' => [
                [
                    'ID' => 'my',
                    'name' => 'Mark Hoppus',
                ],
                [
                    'ID' => 2,
                    'name' => 'Tom DeLonge',
                ],
                [
                    'ID' => 3,
                    'name' => 'Travis Barker',
                ],
            ],
            'user' => [
                'ID' => 1,
                'name' => 'Mark Hoppus',
            ],
        ];
    }
}
