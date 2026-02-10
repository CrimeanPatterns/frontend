<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator\NameContainsComparator;

/**
 * @group frontend-unit
 */
class NameContainsComparatorTest extends AbstractComparatorTest
{
    private ?NameContainsComparator $comparator;

    public function _before()
    {
        parent::_before();

        $this->comparator = $this->container->get(NameContainsComparator::class);
    }

    public function _after()
    {
        $this->comparator = null;

        parent::_after();
    }

    /**
     * @dataProvider compareDataProvider
     */
    public function testCompare(LoungeInterface $lounge1, LoungeInterface $lounge2, float $expectedSimilarity): void
    {
        $this->markTestSkipped();
        $normalized1 = $this->normalize($lounge1);
        $normalized2 = $this->normalize($lounge2);
        $actualSimilarity = $this->comparator->compare($normalized1, $normalized2);

        $this->assertEqualsWithDelta($expectedSimilarity, $actualSimilarity, 0.01);
    }

    public function compareDataProvider(): array
    {
        return [
            'identical names' => [
                self::lounge('Star Alliance Lounge'),
                self::lounge('Star Alliance Lounge'),
                1.0,
            ],
            'empty names' => [
                self::lounge(''),
                self::lounge(''),
                1.0,
            ],
            'one empty name' => [
                self::lounge('Star Alliance Lounge'),
                self::lounge(''),
                0.5,
            ],
            'complete substring' => [
                self::lounge('Star Alliance Lounge'),
                self::lounge('Star Alliance'),
                0.82,
            ],
            'reverse substring' => [
                self::lounge('Star Alliance'),
                self::lounge('Star Alliance Lounge'),
                0.82,
            ],
            'short name in long name' => [
                self::lounge('VIP'),
                self::lounge('VIP Lounge Terminal 5'),
                0.57,
            ],
            'exact prefix match' => [
                self::lounge('Lufthansa'),
                self::lounge('Lufthansa First Class Lounge'),
                0.66,
            ],
            'no containment' => [
                self::lounge('Emirates Lounge'),
                self::lounge('Qatar Airways Lounge'),
                0.0,
            ],
            'no common substrings' => [
                self::lounge('British Airways'),
                self::lounge('Qatar Airways'),
                0.0,
            ],
            'library lounge case' => [
                self::lounge('Library Lounge 123'),
                self::lounge('Library Lounge'),
                0.88,
            ],
            'same words different order' => [
                self::lounge('Lounge Alliance Star'),
                self::lounge('Star Alliance Lounge'),
                0.0,
            ],
            'longer string contains shorter twice' => [
                self::lounge('VIP'),
                self::lounge('VIP Lounge and VIP Services'),
                0.55,
            ],
            'case insensitive match' => [
                self::lounge('STAR ALLIANCE'),
                self::lounge('Star Alliance Lounge'),
                0.82,
            ],
            'with gate information' => [
                self::lounge('Cathay Pacific Lounge', 'T3', 'G42'),
                self::lounge('Cathay Pacific', 'T3', 'G43'),
                0.83,
            ],
            'complete lounge data' => [
                self::lounge('Air France Lounge', 'Terminal 2E', 'Gate K', 'Gate L'),
                self::lounge('Air France', 'T2E', 'K', 'L'),
                0.79,
            ],
        ];
    }
}
