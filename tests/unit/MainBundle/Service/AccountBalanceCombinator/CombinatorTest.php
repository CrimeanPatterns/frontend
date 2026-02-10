<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\AccountBalanceCombinator;

use AwardWallet\MainBundle\Service\AccountBalanceCombinator\AccountLoader;
use AwardWallet\MainBundle\Service\AccountBalanceCombinator\BalanceInterface;
use AwardWallet\MainBundle\Service\AccountBalanceCombinator\Combinator;
use AwardWallet\MainBundle\Service\AccountBalanceCombinator\TransferStatLoader;
use AwardWallet\Tests\Unit\BaseUserTest;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class CombinatorTest extends BaseUserTest
{
    /**
     * @dataProvider findCombinationsDataProvider
     */
    public function testFindCombinations(
        array $expected,
        int $targetProviderId,
        float $targetPoints,
        array $userAccounts,
        array $transferStats
    ) {
        foreach ($userAccounts as $providerId => $accounts) {
            foreach ($accounts as $k => $account) {
                $userAccounts[$providerId][$k] = array_merge([
                    'UserAgent' => null,
                    'IsShareable' => true,
                    'DisplayName' => 'test',
                    'AvgPointValue' => 0.2,
                ], $account);
            }
        }

        $stats = [];

        foreach ($transferStats as $stat) {
            $stats[$stat['target']][$stat['source']] = [
                'multiplier' => $stat['multiplier'],
                'minimumTransfer' => $stat['minimumTransfer'],
                'sourceStep' => $stat['sourceStep'],
            ];
        }

        $combinator = new Combinator(
            $this->make(AccountLoader::class, ['load' => $userAccounts]),
            $this->make(TransferStatLoader::class, ['load' => $stats])
        );
        $combinator->bootstrap($this->user, [$targetProviderId]);
        $combinations = it($combinator->findCombinations($this->user, $targetProviderId, $targetPoints))
            ->toArrayWithKeys();
        $convertedCombinations = [];

        foreach ($combinations as $id => $combination) {
            $convertedCombinations[$id] = array_map(fn (BalanceInterface $item) => $item->getId(), $combination);
        }

        $this->assertEquals($expected, $convertedCombinations);
    }

    public function findCombinationsDataProvider(): array
    {
        return [
            'empty' => [
                [], 1, 100, [], [],
            ],

            'exists an account with the required balance' => [
                [
                    '1' => [1],
                ], 1, 100, [
                    1 => [self::account(1, 1, 100)],
                ], [],
            ],

            'not enough balance and no transfer options' => [
                [], 1, 100, [
                    1 => [self::account(1, 1, 50)],
                ], [],
            ],

            'not enough balance and transfer option' => [
                [
                    '2' => [2],
                ], 1, 100, [
                    1 => [self::account(1, 1, 50)],
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 2, 0),
                ],
            ],

            'not enough balance and transfer option with minimum transfer' => [
                [
                    '2' => [2],
                ], 1, 100, [
                    1 => [self::account(1, 1, 50)],
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 2, 100),
                ],
            ],

            'not enough balance and transfer option with minimum transfer 2' => [
                [], 1, 100, [
                    1 => [self::account(1, 1, 50)],
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 2, 101),
                ],
            ],

            'not enough balance and transfer option with multiple sources' => [
                [
                    '1-2' => [1, 2],
                    '1-4' => [1, 4],
                    '2-4' => [2, 4],
                ], 1, 100, [
                    1 => [self::account(1, 1, 50)],
                    2 => [
                        self::account(2, 2, 25),
                        self::account(4, 2, 25),
                    ],
                ], [
                    self::transfer(2, 1, 2, 0),
                ],
            ],

            'not enough balance and transfer option with multiple sources and minimum transfer' => [
                [
                    '1-2' => [1, 2],
                    '2-1' => [2, 1],
                ], 1, 100, [
                    1 => [self::account(1, 1, 50)],
                    2 => [
                        self::account(2, 2, 40),
                        self::account(4, 2, 25),
                    ],
                ], [
                    self::transfer(2, 1, 2, 40),
                ],
            ],

            'enough balance and transfer option' => [
                [
                    1 => [1],
                    2 => [2],
                ], 1, 1000, [
                    1 => [self::account(1, 1, 1200)],
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 10, 5),
                ],
            ],

            'multiple sources' => [
                [
                    '1-2-3-4-5' => [1, 2, 3, 4, 5],
                ], 1, 500, [
                    1 => [self::account(1, 1, 100)],
                    2 => [self::account(2, 2, 100)],
                    3 => [self::account(3, 3, 100)],
                    4 => [self::account(4, 4, 100)],
                    5 => [self::account(5, 5, 100)],
                ], [
                    self::transfer(2, 1, 1, 0),
                    self::transfer(3, 1, 1, 0),
                    self::transfer(4, 1, 1, 0),
                    self::transfer(5, 1, 1, 0),
                ],
            ],

            'step, 1' => [
                [
                    '1-2-3' => [1, 2, 3],
                    '1-3-2' => [1, 3, 2],
                    '2-3-1' => [2, 3, 1],
                ], 1, 250, [
                    1 => [self::account(1, 1, 100)],
                    2 => [self::account(2, 2, 100)],
                    3 => [self::account(3, 3, 100)],
                ], [
                    self::transfer(2, 1, 1, 0, 100),
                    self::transfer(3, 1, 1, 0, 100),
                ],
            ],

            'step, 2' => [
                [
                    '1-2-3' => [1, 2, 3],
                    '1-3-2' => [1, 3, 2],
                    '2-3-1' => [2, 3, 1],
                ], 1, 250, [
                    1 => [self::account(1, 1, 100)],
                    2 => [self::account(2, 2, 50)],
                    3 => [self::account(3, 3, 50)],
                ], [
                    self::transfer(2, 1, 2, 0, 50),
                    self::transfer(3, 1, 2, 0, 50),
                ],
            ],

            'step, 3' => [
                [
                    '3-2' => [3, 2],
                ], 1, 250, [
                    2 => [self::account(2, 2, 60)],
                    3 => [self::account(3, 3, 150)],
                ], [
                    self::transfer(2, 1, 2, 0, 50),
                    self::transfer(3, 1, 1, 0, 50),
                ],
            ],

            'step, 4' => [
                [
                    '2' => [2],
                ], 1, 400, [
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 4, 0, 50),
                ],
            ],

            'step, 5' => [
                [], 1, 400, [
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 4, 0, 60),
                ],
            ],

            'step, 6' => [
                [
                    '2' => [2],
                ], 1, 240, [
                    2 => [self::account(2, 2, 100)],
                ], [
                    self::transfer(2, 1, 4, 0, 60),
                ],
            ],

            'step, 7' => [
                [
                    '1' => [1],
                    '2-5-4' => [2, 5, 4],
                ], 1, 1000, [
                    1 => [self::account(1, 1, 1500)],
                    2 => [
                        self::account(2, 2, 100),
                        self::account(3, 2, 50),
                        self::account(4, 2, 120),
                    ],
                    3 => [self::account(5, 3, 200)],
                ], [
                    self::transfer(2, 1, 4, 100, 100),
                    self::transfer(3, 1, 1, 100),
                ],
            ],
        ];
    }

    private static function transfer(
        int $from,
        int $to,
        float $multiplier,
        int $minimumTransfer,
        int $step = 1
    ): array {
        return [
            'source' => $from,
            'target' => $to,
            'multiplier' => $multiplier,
            'minimumTransfer' => $minimumTransfer,
            'sourceStep' => $step,
        ];
    }

    private static function account(int $id, int $providerId, float $balance): array
    {
        return [
            'ID' => $id,
            'ProviderID' => $providerId,
            'Balance' => $balance,
        ];
    }
}
