<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\MLMatcher;

/**
 * @group frontend-unit
 */
class MLMatcherTest extends AbstractClustererTest
{
    private ?MLMatcher $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = $this->container->get(MLMatcher::class);
    }

    public function _after()
    {
        $this->matcher = null;

        parent::_after();
    }

    /**
     * @dataProvider calculateSimilarityDataProvider
     */
    public function testCalculateSimilarity(bool $expectedMatch, LoungeInterface $lounge1, LoungeInterface $lounge2)
    {
        $this->markTestSkipped();
        $loungeFormat = function (LoungeInterface $lounge) {
            return sprintf(
                '%s (terminal: %s, gate1: %s, gate2: %s)',
                $lounge->getName(),
                $lounge->getTerminal() ?? 'null',
                $lounge->getGate() ?? 'null',
                $lounge->getGate2() ?? 'null'
            );
        };
        $similarity = round($this->matcher->getSimilarity($lounge1, $lounge2), 2);
        $threshold = MLMatcher::getThreshold();

        if ($expectedMatch) {
            $this->assertGreaterThanOrEqual(
                $threshold,
                $similarity,
                sprintf(
                    'Similarity between "%s" and "%s" is less than expected',
                    $loungeFormat($lounge1),
                    $loungeFormat($lounge2)
                )
            );
        } else {
            $this->assertLessThan(
                $threshold,
                $similarity,
                sprintf(
                    'Similarity between "%s" and "%s" is greater than expected',
                    $loungeFormat($lounge1),
                    $loungeFormat($lounge2)
                )
            );
        }
    }

    public function calculateSimilarityDataProvider(): array
    {
        return [
            [
                false,
                $this->source('Wok on Air', 'loungeKey', 'SYD', '3'),
                $this->source('Qantas Airways Chairman\'s Lounge', 'loungereview', 'SYD', '3'),
            ],
            [
                false,
                $this->source('VIP Lounge', 'priorityPass', 'PEE'),
                $this->source('Business Lounge', 'skyTeam', 'PEE'),
            ],
            [
                false,
                $this->source('Qatar Airways Premium Lounge', 'loungereview', 'SIN', '1'),
                $this->source('SATS Premier Lounge', 'dragonPass', 'SIN', '1'),
            ],
            [
                true,
                $this->source('The House by Aspire Airport Lounges', 'loungereview', 'SYD', '1', '51'),
                $this->source('The House', 'loungeKey', 'SYD', '1', '51', '63'),
            ],
            [
                true,
                $this->source('Super Business Lounge #1', 'loungereview', 'ZZZ', '2', '8', '9'),
                $this->source('Business Lounge #1', 'loungeKey', 'ZZZ', '2'),
            ],
            [
                true,
                $this->source('Lounge V Test', 'priorityPass', 'JFK', 2),
                $this->source('Lounge V Test 2', 'priorityPass', 'JFK', 2),
            ],
            [
                true,
                $this->source('ANA Airport Lounge', 'loungereview', 'LIS', 1, 22),
                $this->source('ANA Lounge', 'dragonPass', 'LIS', 1),
            ],
            [
                true,
                $this->source('ANA Airport Lounge', 'loungereview', 'LIS', 1, 22),
                $this->source('ANA LOUNGE', 'delta', 'LIS'),
            ],
            [
                true,
                $this->source('Chutney Mary', 'dragonPass', 'SIN', 2),
                $this->source('Chutney Mary Indian Fast Food', 'loungeKey', 'SIN', 2),
            ],
            [
                true,
                $this->source('Delta Sky Club', 'delta', 'MSP', 'G', 17, 18),
                $this->source('Delta Air Lines Delta Sky Club', 'loungereview', 'MSP', 1, 17, 18),
            ],
            [
                true,
                $this->source('Library Lounge', 'dragonPass', 'PKX', 'Main'),
                $this->source('Library Lounge by Aerotel Beijing', 'loungeKey', 'PKX', 'Northeast Pier'),
            ],
            [
                true,
                $this->source('Library Lounge by Aerotel Beijing', 'loungeKey', 'PKX', 'Main'),
                $this->source('Library Lounge', 'dragonPass', 'PKX', 'Main'),
            ],
            [
                true,
                $this->source('Capital One Lounge', 'delta', 'LAS', 1, 'D51'),
                $this->source('Capital One Lounge', 'loungereview', 'LAS', 'D', 'D50'),
            ],
            [
                false,
                $this->source('Capital One Lounge', 'delta', 'LAS', 1, 'D52'),
                $this->source('Library Lounge', 'loungereview', 'LAS', 'D', 'D50'),
            ],
            [
                false,
                $this->source('Test Lounge', 'delta', 'LAS', 1, 'D51'),
                $this->source('Capital One Lounge', 'loungereview', 'LAS', 'D', 'D50'),
            ],
        ];
    }
}
