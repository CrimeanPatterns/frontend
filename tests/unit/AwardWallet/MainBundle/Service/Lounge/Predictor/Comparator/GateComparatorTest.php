<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator\GateComparator;

/**
 * @group frontend-unit
 */
class GateComparatorTest extends AbstractComparatorTest
{
    private ?GateComparator $comparator;

    public function _before()
    {
        parent::_before();

        $this->comparator = $this->container->get(GateComparator::class);
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
            'both lounges without gates' => [
                self::lounge('Star Alliance Lounge', 'T1'),
                self::lounge('Star Alliance Lounge', 'T1'),
                0.8,
            ],
            'first lounge with gate, second without' => [
                self::lounge('Emirates Lounge', 'T3', 'A12'),
                self::lounge('Emirates Lounge', 'T3'),
                0.7,
            ],
            'second lounge with gate, first without' => [
                self::lounge('Qatar Airways Lounge', 'T5'),
                self::lounge('Qatar Airways Lounge', 'T5', 'D45'),
                0.7,
            ],
            'first lounge with two gates, second without' => [
                self::lounge('British Airways Lounge', 'T5', 'B10', 'B12'),
                self::lounge('British Airways Lounge', 'T5'),
                0.7,
            ],
            'identical gates' => [
                self::lounge('Plaza Premium Lounge', 'T1', 'C22'),
                self::lounge('Plaza Premium Lounge', 'T1', 'C22'),
                1.0,
            ],
            'identical gates with different prefixes' => [
                self::lounge('Air France Lounge', 'T2', 'A22'),
                self::lounge('Air France Lounge', 'T2', 'B22'),
                0.8,
            ],
            'identical two gates' => [
                self::lounge('Lufthansa Lounge', 'T2', 'C15', 'D20'),
                self::lounge('Lufthansa Lounge', 'T2', 'C15', 'D20'),
                1.0,
            ],
            'two gates swapped' => [
                self::lounge('Cathay Pacific Lounge', 'T3', 'G42', 'G50'),
                self::lounge('Cathay Pacific Lounge', 'T3', 'G50', 'G42'),
                1.0,
            ],
            'adjacent gates (+1)' => [
                self::lounge('United Club', 'T1', 'B5'),
                self::lounge('United Club', 'T1', 'B6'),
                0.9,
            ],
            'adjacent gates (-1)' => [
                self::lounge('Delta SkyClub', 'T4', 'D12'),
                self::lounge('Delta SkyClub', 'T4', 'D11'),
                0.9,
            ],
            'adjacent gates with different prefixes' => [
                self::lounge('American Admirals Club', 'T8', 'A15'),
                self::lounge('American Admirals Club', 'T8', 'B16'),
                0.7,
            ],
            'near gates (+2)' => [
                self::lounge('Singapore Airlines Lounge', 'T3', 'C10'),
                self::lounge('Singapore Airlines Lounge', 'T3', 'C12'),
                0.0,
            ],
            'near gates (-2)' => [
                self::lounge('JAL Lounge', 'T2', 'D25'),
                self::lounge('JAL Lounge', 'T2', 'D23'),
                0.0,
            ],
            'medium distance gates (+5)' => [
                self::lounge('Etihad Lounge', 'T4', 'B10'),
                self::lounge('Etihad Lounge', 'T4', 'B15'),
                0.0,
            ],
            'medium distance gates (-5)' => [
                self::lounge('Virgin Atlantic Clubhouse', 'T6', 'A20'),
                self::lounge('Virgin Atlantic Clubhouse', 'T6', 'A15'),
                0.0,
            ],
            'distant gates (+10)' => [
                self::lounge('Air Canada Maple Leaf Lounge', 'T1', 'E5'),
                self::lounge('Air Canada Maple Leaf Lounge', 'T1', 'E15'),
                0.0,
            ],
            'distant gates with different prefixes' => [
                self::lounge('KLM Crown Lounge', 'T2', 'C25'),
                self::lounge('KLM Crown Lounge', 'T2', 'D35'),
                0.0,
            ],
            'very distant gates (+20)' => [
                self::lounge('Turkish Airlines Lounge', 'T3', 'B5'),
                self::lounge('Turkish Airlines Lounge', 'T3', 'B25'),
                0.0,
            ],
            'one gate in range of other gates' => [
                self::lounge('Qantas Lounge', 'T4', 'C10', 'C20'),
                self::lounge('Qantas Lounge', 'T4', 'C15'),
                0.9,
            ],
            'one gate in range of other gates, ordered' => [
                self::lounge('Swiss Lounge', 'International', 'A10', 'A5'),
                self::lounge('Swiss Lounge', 'International', 'A7'),
                0.9,
            ],
            'mixed gate formats' => [
                self::lounge('Air New Zealand Lounge', 'International', 'Gate 5'),
                self::lounge('Air New Zealand Lounge', 'International', '5'),
                0.8,
            ],
        ];
    }
}
