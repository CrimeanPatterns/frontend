<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\Clusterizer;
use AwardWallet\MainBundle\Service\Lounge\LegacyMatcher;

/**
 * @group frontend-unit
 */
class ClusterizerWithLegacyMatcherTest extends AbstractClustererTest
{
    private ?Clusterizer $clusterizer;

    public function _before()
    {
        parent::_before();

        $this->clusterizer = $this->container->get(Clusterizer::class);
    }

    public function _after()
    {
        $this->clusterizer = null;

        parent::_after();
    }

    /**
     * @dataProvider clusterizeDataProvider
     */
    public function testClusterize(array $sources, array $expectedClusters, bool $allowSameParserMatching = true)
    {
        $clusters = $this->clusterizer->clusterize($sources, LegacyMatcher::NAME, $allowSameParserMatching);
        $this->assertCount(\count($expectedClusters), $clusters);
        $this->assertEquals($this->mapLounges($expectedClusters), $this->mapLounges($clusters));
    }

    public function clusterizeDataProvider(): array
    {
        return [
            // неизвестные источники (нет локации в виде терминалов и гейтов). Считаем, что в аэропорте один терминал и один гейт
            'no terminals, no gates' => [
                [
                    $s1 = $this->source('VIP Business Lounge', 'priorityPass', 'PEE'),
                    $s2 = $this->source('Business Lounge', 'skyTeam', 'PEE'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // неполная локация (отсутствует терминал или оба гейта сразу)
            'no terminals or no gates' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', 2),
                    $s2 = $this->source('Business Lounge', 'skyTeam', 'PEE', null, 5),
                ],
                [
                    [$s1],
                    [$s2],
                ],
            ],

            // неполная локация (отсутствует терминал или оба гейта сразу)
            'no terminals or no gates 2' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', 2),
                    $s2 = $this->source('Business Lounge', 'skyTeam', 'PEE', null, 5),
                    $s3 = $this->source('Business Lounge', 'delta', 'PEE', null, null, 5),
                ],
                [
                    [$s2, $s3],
                    [$s1],
                ],
            ],

            // один источник, 2 лаунджа
            'one source, 2 lounges' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', 2, 1),
                    $s2 = $this->source('Business Lounge', 'priorityPass', 'PEE', 2, 2),
                ],
                [
                    [$s1],
                    [$s2],
                ],
            ],

            // 2 источника по одному отличному лаунджу
            'two sources, 1 lounge' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', 2, 1),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 2),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // неполный источник мержится только при совпадении Name от 75%, если длина имени свыше 3 символов. Либо при полном совпадении
            'incomplete source 1' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // совпадение неполного источника
            'incomplete source 2' => [
                [
                    $s1 = $this->source(' Business Lo', 'priorityPass', 'PEE'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // совпадение хотя бы Gate или Gate2
            'gate 1' => [
                [
                    $s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 2, null, 1),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // совпадение хотя бы Gate или Gate2 даже при разных именах
            'gate 2' => [
                [
                    $s1 = $this->source('Business VIP Lounge', 'priorityPass', 'PEE', 2, null, 1),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // совпадение рядом расположенного гейта
            'nearby gates' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', null, null, 2),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // совпадение рядом расположенного гейта
            'nearby gates 2' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 2),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 3),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // совпадение рядом расположенного гейта
            'nearby gates 3' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 'A2'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 'A3'),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // разные терминалы
            'different terminals' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 2, 'A2'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 'A3'),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // наличие и отсутствие префикса
            'prefix gate' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, null, 'A3'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 3),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // разные префиксы Gate
            'prefix gate 2' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, null, 'A3'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 'B4'),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // отсутствие префикса гейта
            'prefix gate 3' => [
                [
                    $s1 = $this->source('Delta Sky Club T4', 'skyTeam', 'JFK', 4, 'B30', 'B32'),
                    $s2 = $this->source('Delta Sky Club', 'delta', 'JFK', 4, 31),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // отсутствие префикса гейта
            'prefix gate 4' => [
                [
                    $s1 = $this->source('XXX Sky Club', 'skyTeam', 'JFK', 4, 'B31'),
                    $s2 = $this->source('Delta Sky Club', 'delta', 'JFK', 4, 31),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // гейты расположены не рядом
            'nearby gates 4' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 2),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 4),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // один источник выдал один и тот же лаундж на 2 страницах. Должно быть похожее имя и сходится локация (терминал + гейты)
            'one source, one lounge' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 4),
                    $s2 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // один источник и один лаундж. Общее только аэропорт
            'one merged, one lounge' => [
                [
                    $s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4),
                    $l1 = $this->lounge('Test Lounge', 'PEE'),
                ],
                [
                    [$s1],
                    [$l1],
                ],
            ],

            // один источник и один лаундж, который связан с этим источником. Общего мало
            'one merged, one lounge 2' => [
                [
                    $l1 = $this->lounge('Test Lounge', 'PEE'),
                    ($s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4))
                        ->setLounge($l1),
                ],
                [
                    [$s1],
                    [$l1],
                ],
            ],

            // два лаунджа с двумя источниками. Общего мало.
            'two merged, 3 lounges' => [
                [
                    $l1 = $this->lounge('Test Lounge', 'PEE'),
                    ($s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4))
                        ->setLounge($l1),
                    $l2 = $this->lounge('Test Lounge 2', 'JFK'),
                    ($s2 = $this->source('Business Lounge', 'loungebuddy', 'JFK', 2, 10))
                        ->setLounge($l2),
                    $s3 = $this->source('Business Lounge', 'priorityPass', 'JFK', 2, 10),
                ],
                [
                    [$s2, $s3],
                    [$s1],
                    [$l2],
                    [$l1],
                ],
            ],

            // два сохраненных лаунджа не могут быть похожими
            'multiple lounges' => [
                [
                    $l1 = $this->lounge('Test Lounge', 'PEE', 1, 4, null, 1000),
                    $l2 = $this->lounge('Test Lounge', 'PEE', 1, 4, null, 1001),
                    $l3 = $this->lounge('Test Lounge', 'PEE', 1, 4),
                ],
                [
                    [$l1, $l3],
                    [$l2],
                ],
            ],

            // несколько разных парсеров и три лаунджа
            'multiple parsers, 3 lounges' => [
                [
                    $s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4),
                    $s2 = $this->source('Business Lounge Perm', 'delta', 'PEE', 1, 3),
                    $s3 = $this->source('Business Lounge #1', 'skyTeam', 'PEE', null, 'A3'),
                    $s4 = $this->source('Business Lounge #1', 'loungebuddy', 'PEE', 1, 'A03'),
                    $s5 = $this->source('Lounge #2', 'loungebuddy', 'PEE', 1, 'A03'),
                    $s6 = $this->source('Lounge #2', 'delta', 'PEE', 2, 3),
                ],
                [
                    [$s2, $s4],
                    [$s6],
                    [$s5],
                    [$s1],
                    [$s3],
                ],
                false,
            ],

            // несколько разных парсеров и три лаунджа
            'multiple parsers, 3 lounges, 2' => [
                [
                    $s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4),
                    $s2 = $this->source('Business Lounge Perm', 'delta', 'PEE', 1, 3),
                    $s3 = $this->source('Business Lounge #1', 'skyTeam', 'PEE', null, 'A3'),
                    $s4 = $this->source('Business Lounge #1', 'loungebuddy', 'PEE', 1, 'A03'),
                    $s5 = $this->source('Business Lounge #2', 'loungebuddy', 'PEE', 1, 'A03'),
                    $s6 = $this->source('Lounge #2', 'delta', 'PEE', 2, 3),
                ],
                [
                    [$s2, $s4, $s5],
                    [$s6],
                    [$s1],
                    [$s3],
                ],
                true,
            ],

            'different terminals, same gates with prefix' => [
                [
                    $s1 = $this->source('Business Lounge', 'loungebuddy', 'MSP', 1, 'C12'),
                    $s2 = $this->source('Business Lounge Perm', 'delta', 'MSP', 'C', 'C12'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'different terminals, same gates with prefix 2' => [
                [
                    $s1 = $this->source('Delta Air Lines', 'loungebuddy', 'BOS', 'E', 'E13'),
                    $s2 = $this->source('Delta Sky Club', 'delta', 'BOS', 'International', 'E13'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'names are very similar, terminals are indirectly similar' => [
                [
                    $s1 = $this->source('Lien Khuong Business Lounge', 'loungebuddy', 'DLI', 'Main', 1, 2),
                    $s2 = $this->source('Lien Khuong Business Lounge', 'delta', 'DLI', 'Domestic'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'names are very similar, terminals are indirectly similar, 2' => [
                [
                    $s1 = $this->source('Club Aspire Lounge', 'loungeKey', 'LHR', '5', 'A18'),
                    $s2 = $this->source('Club Aspire Lounge', 'loungebuddy', 'LHR', '5A', 'A18'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'conflict with same location' => [
                [
                    $l1 = $this->lounge('Nomad Lounge', 'GRU', '3', '302', '303', 321),
                    $l2 = $this->lounge('Nomad Lounge', 'GRU', '3', '302', null, 322),
                    $s1 = $this->source('Nomad Lounge', 'loungebuddy', 'GRU', '3', '302', '303'),
                    $s2 = $this->source('LATAM VIP Lounge', 'dragonPass', 'GRU', '3', '301', '302'),
                    $s3 = $this->source('LATAM VIP Lounge', 'loungebuddy', 'GRU', '3', '301', '302'),
                ],
                [
                    [$s2, $s3],
                    [$s1, $l1],
                    [$l2],
                ],
                false,
            ],

            'lounges with matching names should be clustered together' => [
                [
                    $l1 = $this->lounge('Kyra Lounge', 'HKG', '1', '24', null, 1),
                    $l2 = $this->lounge('Intervals Sky Bar and Restaurant', 'HKG', '1', '12', '24', 2),
                    $s1 = $this->source('Intervals Sky Bar & Restaurant', 'priorityPass', 'HKG', '1', '12', '24'),
                    $s2 = $this->source('Intervals Sky Bar and Restaurant', 'loungereview', 'HKG', '1', '12', '24'),
                    $s3 = $this->source('Intervals', 'dragonPass', 'HKG', '1', '24'),
                    $s4 = $this->source('Kyra Lounge', 'loungereview', 'HKG', '1', '24'),
                ],
                [
                    [$s3, $s2, $s1, $l2],
                    [$s4, $l1],
                ],
                false,
            ],
        ];
    }
}
