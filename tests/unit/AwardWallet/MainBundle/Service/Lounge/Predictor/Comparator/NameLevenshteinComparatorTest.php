<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator\NameLevenshteinComparator;

/**
 * @group frontend-unit
 */
class NameLevenshteinComparatorTest extends AbstractComparatorTest
{
    private ?NameLevenshteinComparator $comparator;

    public function _before()
    {
        parent::_before();

        $this->comparator = $this->container->get(NameLevenshteinComparator::class);
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
            'small typo' => [
                self::lounge('Lufthansa Lounge'),
                self::lounge('Lufthunsa Lounge'),
                1.0,
            ],
            'significant difference' => [
                self::lounge('Emirates First Class Lounge'),
                self::lounge('Star Alliance Business Lounge'),
                0.54,
            ],
            'library lounge case' => [
                self::lounge('Library Lounge 123'),
                self::lounge('Library Lounge'),
                0.82,
            ],
            'library lounge case with different terminals' => [
                self::lounge('Library Lounge 123', 'main'),
                self::lounge('Library Lounge', '1'),
                0.82,
            ],
            'same brand different types' => [
                self::lounge('Delta Sky Club'),
                self::lounge('Delta First Class Lounge'),
                0.46,
            ],
            'case difference' => [
                self::lounge('AMERICAN ADMIRALS CLUB'),
                self::lounge('american admirals club'),
                1.0,
            ],
            'words in different order' => [
                self::lounge('International First Class Lounge'),
                self::lounge('First Class International Lounge'),
                0.51,
            ],
            'partial name match' => [
                self::lounge('Plaza Premium Lounge Terminal 2'),
                self::lounge('Plaza Premium Lounge'),
                0.66,
            ],
            'short names' => [
                self::lounge('VIP'),
                self::lounge('V1P'),
                0.76,
            ],
            'special handling for very long names' => [
                self::lounge('International First Class Business Lounge Terminal One Concourse A'),
                self::lounge('International First Class Business Lounge Terminal 1 Concourse A'),
                1.0,
            ],
            'with gate information' => [
                self::lounge('Cathay Pacific Lounge', 'T3', 'G42'),
                self::lounge('Cathay Pacific Lounge', 'T3', 'G43'),
                1.0,
            ],
            'complete lounge data' => [
                self::lounge('Air France Lounge', 'Terminal 2E', 'Gate K', 'Gate L'),
                self::lounge('Air France Lounge', 'T2E', 'K', 'L'),
                1.0,
            ],
        ];
    }
}
