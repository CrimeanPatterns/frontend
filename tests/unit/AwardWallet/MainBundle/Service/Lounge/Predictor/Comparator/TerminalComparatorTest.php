<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator\TerminalComparator;

/**
 * @group frontend-unit
 */
class TerminalComparatorTest extends AbstractComparatorTest
{
    private ?TerminalComparator $comparator;

    public function _before()
    {
        parent::_before();

        $this->comparator = $this->container->get(TerminalComparator::class);
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
            'identical terminals' => [
                self::lounge('Star Alliance Lounge', 'Terminal 1'),
                self::lounge('Star Alliance Lounge', 'Terminal 1'),
                1.0,
            ],
            'both empty terminals' => [
                self::lounge('Lufthansa Lounge'),
                self::lounge('Lufthansa Lounge'),
                0.8,
            ],
            'one empty terminal' => [
                self::lounge('British Airways Lounge', 'Terminal 5'),
                self::lounge('British Airways Lounge'),
                0.7,
            ],
            'normalized match - different formatting' => [
                self::lounge('Delta Lounge', 'Terminal-2'),
                self::lounge('Delta Lounge', 'T2'),
                0.95,
            ],
            'main terminal equivalence 1' => [
                self::lounge('Plaza Premium', 'Main'),
                self::lounge('Plaza Premium', '1'),
                0.9,
            ],
            'main terminal equivalence 2' => [
                self::lounge('Air France', 'Central'),
                self::lounge('Air France', 'Primary'),
                0.9,
            ],
            'main terminal equivalence 3' => [
                self::lounge('Qatar Airways', 'Domestic'),
                self::lounge('Qatar Airways', 'Main'),
                0.9,
            ],
            'international terminal equivalence' => [
                self::lounge('Emirates', 'International'),
                self::lounge('Emirates', 'INTL'),
                0.9,
            ],
            'numbered terminal equivalence' => [
                self::lounge('JAL', 'Two'),
                self::lounge('JAL', '2'),
                0.9,
            ],
            'letter-number terminal' => [
                self::lounge('United Club', 'Terminal A'),
                self::lounge('United Club', 'Terminal 1'),
                0.9,
            ],
            'geographic direction equivalence' => [
                self::lounge('Cathay Pacific', 'North'),
                self::lounge('Cathay Pacific', 'N'),
                0.8,
            ],
            'geographic direction equivalence complex' => [
                self::lounge('Singapore Airlines', 'Northeast'),
                self::lounge('Singapore Airlines', 'North'),
                0.8,
            ],
            'short terminals with same first character' => [
                self::lounge('American Airlines', '1A'),
                self::lounge('American Airlines', '1B'),
                0.6,
            ],
            'one terminal contains another' => [
                self::lounge('Air Canada', '1A'),
                self::lounge('Air Canada', '1'),
                0.45,
            ],
            'levenshtein distance of 1' => [
                self::lounge('SAS Lounge', 'T3A'),
                self::lounge('SAS Lounge', 'T3B'),
                0.6,
            ],
            'removal of prefixes' => [
                self::lounge('Eva Air', 'Terminal 4'),
                self::lounge('Eva Air', '4'),
                0.95,
            ],
            'partial substring match' => [
                self::lounge('Qantas', 'International Terminal 4'),
                self::lounge('Qantas', 'International'),
                0.49,
            ],
            'adjacent numeric terminals' => [
                self::lounge('Korean Air', '3'),
                self::lounge('Korean Air', '4'),
                0.0,
            ],
            'nearby numeric terminals' => [
                self::lounge('Turkish Airlines', '2'),
                self::lounge('Turkish Airlines', '5'),
                0.0,
            ],
            'different terminals' => [
                self::lounge('Air China', 'Terminal A'),
                self::lounge('Air China', 'Terminal Z'),
                0.0,
            ],
            'domestic vs international' => [
                self::lounge('Malaysia Airlines', 'Domestic'),
                self::lounge('Malaysia Airlines', 'International'),
                0.0,
            ],
        ];
    }
}
