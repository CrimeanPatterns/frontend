<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\Predictor\Comparator\BrandRecognitionComparator;

/**
 * @group frontend-unit
 */
class BrandRecognitionComparatorTest extends AbstractComparatorTest
{
    private ?BrandRecognitionComparator $comparator;

    public function _before()
    {
        parent::_before();

        $this->comparator = $this->container->get(BrandRecognitionComparator::class);
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
            'identical brands - full name' => [
                self::lounge('American Airlines Admirals Club', 'T8'),
                self::lounge('American Airlines Flagship Lounge', 'T8'),
                1.0,
            ],
            'identical brands - abbreviated' => [
                self::lounge('AA Admirals Club', 'T8'),
                self::lounge('AA Flagship Lounge', 'T8'),
                1.0,
            ],
            'identical brands - mixed format' => [
                self::lounge('Lufthansa Senator Lounge', 'T1'),
                self::lounge('LH Business Lounge', 'T2'),
                1.0,
            ],
            'brand only in first lounge' => [
                self::lounge('United Club', 'T1'),
                self::lounge('Airport Lounge', 'T1'),
                0.2,
            ],
            'brand only in second lounge' => [
                self::lounge('International Lounge', 'T3'),
                self::lounge('Delta Sky Club', 'T3'),
                0.2,
            ],
            'different brands' => [
                self::lounge('Emirates First Class Lounge', 'T3'),
                self::lounge('Qatar Airways Al Mourjan Lounge', 'T3'),
                0.0,
            ],
            'brand in "by" phrase - first lounge' => [
                self::lounge('Premium Lounge by Cathay Pacific', 'T3'),
                self::lounge('Cathay Pacific The Wing', 'T3'),
                1.0,
            ],
            'brand in "by" phrase - second lounge' => [
                self::lounge('Singapore Airlines SilverKris Lounge', 'T2'),
                self::lounge('VIP Lounge operated by Singapore Airlines', 'T2'),
                1.0,
            ],
            'same lounge name with "by" phrase' => [
                self::lounge('Plaza Premium Lounge by Plaza Premium', 'T1'),
                self::lounge('Plaza Premium Lounge', 'T1'),
                1.0,
            ],
            'same lounge name without "by" phrase' => [
                self::lounge('Aspire Lounge by Swissport', 'T2'),
                self::lounge('Aspire Lounge by Airport Authority', 'T2'),
                1.0,
            ],
            'common brand keywords (1)' => [
                self::lounge('Star Alliance Gold Lounge', 'T1'),
                self::lounge('Star Member Lounge', 'T1'),
                1.0,
            ],
            'common brand keywords (2)' => [
                self::lounge('Oneworld Business Lounge', 'T3'),
                self::lounge('Oneworld First Class Lounge', 'T3'),
                1.0,
            ],
            'multiple common brand keywords' => [
                self::lounge('British Airways First Class Galleries', 'T5'),
                self::lounge('BA Galleries Club', 'T5'),
                1.0,
            ],
            'case insensitive brand recognition' => [
                self::lounge('AMERICAN AIRLINES ADMIRALS CLUB', 'T8'),
                self::lounge('american airlines flagship lounge', 'T8'),
                1.0,
            ],
            'two-letter airline code' => [
                self::lounge('QF First Lounge', 'T1'),
                self::lounge('Qantas First Class Lounge', 'T1'),
                1.0,
            ],
            'empty names' => [
                self::lounge('', 'T1'),
                self::lounge('', 'T1'),
                0.0,
            ],
            'one empty name' => [
                self::lounge('Delta Sky Club', 'T4'),
                self::lounge('', 'T4'),
                0.0,
            ],
            'non-brand matching lounges' => [
                self::lounge('Terminal 3 VIP Lounge', 'T3'),
                self::lounge('International Departure Lounge', 'T3'),
                0.0,
            ],
            'brand with variations' => [
                self::lounge('The Wing First Class Lounge', 'T1'),
                self::lounge('Cathay Pacific Business Lounge', 'T1'),
                1.0,
            ],
            'brand with non-standard spacing' => [
                self::lounge('Star-Alliance Lounge', 'T1'),
                self::lounge('Star Alliance Business Class Lounge', 'T1'),
                0.5,
            ],
        ];
    }
}
