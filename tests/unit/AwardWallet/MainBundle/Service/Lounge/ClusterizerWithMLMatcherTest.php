<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\Clusterizer;
use AwardWallet\MainBundle\Service\Lounge\MLMatcher;

/**
 * @group frontend-unit
 */
class ClusterizerWithMLMatcherTest extends AbstractClustererTest
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
        $this->markTestSkipped();
        $clusters = $this->clusterizer->clusterize($sources, MLMatcher::NAME, $allowSameParserMatching);
        $this->assertCount(\count($expectedClusters), $clusters);
        $this->assertEquals($this->mapLounges($expectedClusters), $this->mapLounges($clusters));
    }

    public function clusterizeDataProvider(): array
    {
        return [
            // unknown sources (no location in the form of terminals and gates). We assume the airport has one terminal and one gate
            'no terminals, no gates' => [
                [
                    $s1 = $this->source('VIP Business Lounge', 'priorityPass', 'PEE'),
                    $s2 = $this->source('Business Lounge', 'skyTeam', 'PEE'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // incomplete location (missing terminal or both gates)
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

            // incomplete location (missing terminal or both gates)
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

            // one source, 2 lounges
            'one source, 2 lounges' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', 2, 1),
                    $s2 = $this->source('Business Lounge', 'priorityPass', 'PEE', 2, 2),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // 2 sources for different lounges
            'two sources, 1 lounge' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', 2, 1),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 2),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // incomplete source merges
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

            // incomplete source matching
            'incomplete source 2' => [
                [
                    $s1 = $this->source('Business', 'priorityPass', 'PEE'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s2, $s1],
                ],
            ],

            // matching at least Gate or Gate2
            'gate 1' => [
                [
                    $s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 2, null, 1),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // matching at least Gate or Gate2 even with different names
            'gate 2' => [
                [
                    $s1 = $this->source('Business VIP Lounge', 'priorityPass', 'PEE', 2, null, 1),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // matching nearby gate
            'nearby gates' => [
                [
                    $s1 = $this->source('VIP Lounge', 'priorityPass', 'PEE', null, null, 2),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 2, 1),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // matching nearby gate
            'nearby gates 2' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 2),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 3),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // matching nearby gate
            'nearby gates 3' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 'A2'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 'A3'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // different terminals
            'different terminals' => [
                [
                    $s1 = $this->source('Star Alliance Lounge', 'priorityPass', 'PEE', 2, 'A2'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 'A3'),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // presence and absence of prefix
            'prefix gate' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, null, 'A3'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 3),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // different Gate prefixes
            'prefix gate 2' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, null, 'A3'),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 'B4'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // absence of gate prefix
            'prefix gate 3' => [
                [
                    $s1 = $this->source('Delta Sky Club T4', 'skyTeam', 'JFK', 4, 'B30', 'B32'),
                    $s2 = $this->source('Delta Sky Club', 'delta', 'JFK', 4, 31),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // absence of gate prefix
            'prefix gate 4' => [
                [
                    $s1 = $this->source('XXX Sky Club', 'skyTeam', 'JFK', 4, 'B31'),
                    $s2 = $this->source('Delta Sky Club', 'delta', 'JFK', 4, 31),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // gates are not adjacent
            'nearby gates 4' => [
                [
                    $s1 = $this->source('Delta Sky Club', 'priorityPass', 'PEE', 1, 2),
                    $s2 = $this->source('Business Lounge', 'delta', 'PEE', 1, 4),
                ],
                [
                    [$s2],
                    [$s1],
                ],
            ],

            // one source listed the same lounge on 2 pages. Should have similar name and matching location (terminal + gates)
            'one source, one lounge' => [
                [
                    $s1 = $this->source('Business Lo', 'priorityPass', 'PEE', 1, 4),
                    $s2 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            // one source and one lounge. Only the airport is common
            'one merged, one lounge' => [
                [
                    $s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4),
                    $l1 = $this->lounge('Star Alliance Lounge', 'PEE'),
                ],
                [
                    [$s1],
                    [$l1],
                ],
            ],

            // one source and one lounge linked to this source. Not much in common
            'one merged, one lounge 2' => [
                [
                    $l1 = $this->lounge('Star Alliance Lounge', 'PEE'),
                    ($s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4))
                        ->setLounge($l1),
                ],
                [
                    [$s1],
                    [$l1],
                ],
            ],

            // two lounges with two sources. Not much in common.
            'two merged, 3 lounges' => [
                [
                    $l1 = $this->lounge('Star Alliance Lounge', 'PEE'),
                    ($s1 = $this->source('Business Lounge', 'priorityPass', 'PEE', 1, 4))
                        ->setLounge($l1),
                    $l2 = $this->lounge('Delta Sky Club', 'JFK'),
                    ($s2 = $this->source('Delta Lounge', 'loungereview', 'JFK', 2, 10))
                        ->setLounge($l2),
                    $s3 = $this->source('Delta Sky Club', 'priorityPass', 'JFK', 2, 10),
                ],
                [
                    [$s2, $s3],
                    [$s1],
                    [$l2],
                    [$l1],
                ],
            ],

            // two saved lounges cannot be similar in theory, but in practice they can be
            'multiple lounges' => [
                [
                    $l1 = $this->lounge('American Admirals Club', 'DFW', 1, 4, null, 1000),
                    $l2 = $this->lounge('American Admirals Club', 'DFW', 1, 4, null, 1001),
                    $l3 = $this->lounge('American Admirals Club', 'DFW', 1, 4),
                ],
                [
                    [$l1, $l3],
                    [$l2],
                ],
            ],

            // in merge mode, a cluster should not have more than 1 lounge from the same parser
            'multiple lounges 2' => [
                [
                    $l1 = $this->lounge('American Admirals Club', 'DFW', 1, 4, null, 1000),
                    $l2 = $this->lounge('Virgin Atlantic Clubhouse', 'JFK', 2, null, null, 1001),
                    $s1 = $this->source('American Flagship Lounge', 'priorityPass', 'DFW', 1, 4),
                    $s2 = $this->source('Virgin Atlantic Clubhouse', 'priorityPass', 'JFK', 2),
                    $s3 = $this->source('Virgin Clubhouse', 'priorityPass', 'JFK', 2),
                    $s4 = $this->source('Virgin Atlantic Upper Class', 'loungereview', 'JFK', 2),
                ],
                [
                    [$l2, $s2, $s4],
                    [$l1, $s1],
                    [$s3],
                ],
                false,
            ],

            // several different parsers and three lounges
            'multiple parsers, 3 lounges' => [
                [
                    $s1 = $this->source('Lufthansa Business Lounge', 'priorityPass', 'FRA', 1, 4),
                    $s2 = $this->source('Lufthansa Senator Lounge', 'delta', 'FRA', 1, 3),
                    $s3 = $this->source('Lufthansa First Class Terminal', 'skyTeam', 'FRA', null, 'A3'),
                    $s4 = $this->source('Lufthansa Senator Lounge', 'loungereview', 'FRA', 1, 'A03'),
                    $s5 = $this->source('Star Alliance Gold Lounge', 'loungereview', 'FRA', 1, 'A03'),
                    $s6 = $this->source('Star Alliance Silver Lounge', 'delta', 'FRA', 2, 3),
                ],
                [
                    [$s2, $s4, $s1],
                    [$s6, $s5],
                    [$s3],
                ],
                false,
            ],

            // several different parsers and three lounges
            'multiple parsers, 3 lounges, 2' => [
                [
                    $s1 = $this->source('Singapore Airlines SilverKris Lounge', 'priorityPass', 'SIN', 1, 4),
                    $s2 = $this->source('Singapore Airlines KrisFlyer Gold Lounge', 'delta', 'SIN', 1, 3),
                    $s3 = $this->source('Singapore Airlines The Private Room', 'skyTeam', 'SIN', null, 'A3'),
                    $s4 = $this->source('Singapore Airlines KrisFlyer Gold Lounge', 'loungereview', 'SIN', 1, 'A03'),
                    $s5 = $this->source('Singapore Airlines First Class Lounge', 'loungereview', 'SIN', 1, 'A03'),
                    $s6 = $this->source('Plaza Premium Lounge', 'delta', 'SIN', 2, 3),
                ],
                [
                    [$s2, $s4, $s5, $s1],
                    [$s6],
                    [$s3],
                ],
                true,
            ],

            'different terminals, same gates with prefix' => [
                [
                    $s1 = $this->source('Delta Sky Club', 'loungereview', 'MSP', 1, 'C12'),
                    $s2 = $this->source('Delta Sky Club MSP', 'delta', 'MSP', 'C', 'C12'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'different terminals, same gates with prefix 2' => [
                [
                    $s1 = $this->source('Delta Air Lines', 'loungereview', 'BOS', 'E', 'E13'),
                    $s2 = $this->source('Delta Sky Club', 'delta', 'BOS', 'International', 'E13'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'names are very similar, terminals are indirectly similar' => [
                [
                    $s1 = $this->source('Lien Khuong Business Lounge', 'loungereview', 'DLI', 'Main', 1, 2),
                    $s2 = $this->source('Lien Khuong Business Lounge', 'delta', 'DLI', 'Domestic'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'names are very similar, terminals are indirectly similar, 2' => [
                [
                    $s1 = $this->source('Club Aspire Lounge', 'loungeKey', 'LHR', '5', 'A18'),
                    $s2 = $this->source('Club Aspire Lounge', 'loungereview', 'LHR', '5A', 'A18'),
                ],
                [
                    [$s1, $s2],
                ],
            ],

            'conflict with same location' => [
                [
                    $l1 = $this->lounge('Nomad Lounge', 'GRU', '3', '302', '303', 321),
                    $l2 = $this->lounge('Nomad Lounge', 'GRU', '3', '302', null, 322),
                    $s1 = $this->source('Nomad Lounge', 'loungereview', 'GRU', '3', '302', '303'),
                    $s2 = $this->source('LATAM VIP Lounge', 'dragonPass', 'GRU', '3', '301', '302'),
                    $s3 = $this->source('LATAM VIP Lounge', 'loungereview', 'GRU', '3', '301', '302'),
                ],
                [
                    [$s2, $s3],
                    [$s1, $l1],
                    [$l2],
                ],
                false,
            ],
        ];
    }
}
