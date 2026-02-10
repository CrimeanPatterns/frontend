<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals;

use AwardWallet\MainBundle\Globals\ArrayUtils;
use AwardWallet\MainBundle\Globals\BinarySearchResult as BSR;
use Codeception\Test\Unit;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Globals\ArrayUtils
 */
class ArrayUtilsTest extends Unit
{
    public function binarySearchRangeDataProvider()
    {
        return
            it($this->binarySearchRangeDataTestCases())
            ->toPairs()
            ->reindex(fn (array $case) => \sprintf("%d: searching %d in %s", $case[0], $case[1][1], \json_encode($case[1][0])))
            ->map(fn (array $case) => $case[1])
            ->toArrayWithKeys();
    }

    /**
     * @dataProvider binarySearchRangeDataProvider
     * @covers ::binarySearchLeftmostRange
     */
    public function testBinarySearchLeftmostRange(array $list, $needle, ?int $left, ?int $right, BSR\BinarySearchResultInterface $expectedResult)
    {
        $result = ArrayUtils::binarySearchLeftmostRange($list, $needle, $left, $right);
        $this->assertEquals($expectedResult, $result);

        $result = ArrayUtils::binarySearchLeftmostRange($list, $needle);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider binarySearchRangeDataProvider
     * @covers ::binarySearchLeftmostRange
     */
    public function testBinarySearchLeftmostRangeWithComparator(array $list, $needle, ?int $left, ?int $right, BSR\BinarySearchResultInterface $expectedResult)
    {
        $result = ArrayUtils::binarySearchLeftmostRangeWithComparator($list, fn (int $el) => $el <=> $needle, $left, $right);
        $this->assertEquals($expectedResult, $result);

        $result = ArrayUtils::binarySearchLeftmostRangeWithComparator($list, fn (int $el) => $el <=> $needle);
        $this->assertEquals($expectedResult, $result);
    }

    protected function binarySearchRangeDataTestCases()
    {
        return [
            // less, greater
            [
                /* $list */ [1, 2],
                /* $needle */ 3,
                /* $low */ 0,
                /* $high */ 1,
                /* $expectedResult */ new BSR\GreaterThan(2),
            ],
            [[1, 2], 4, 0, 1, new BSR\GreaterThan(2)],
            [[1, 2], 5, 0, 1, new BSR\GreaterThan(2)],
            [[1, 2], 0, 0, 1, new BSR\LessThan(2)],
            [[1, 2], -1, 0, 1, new BSR\LessThan(2)],
            [[1, 2], -2, 0, 1, new BSR\LessThan(2)],
            [[1, 2], -3, 0, 1, new BSR\LessThan(2)],
            [[1, 2, 3], 4, 0, 2, new BSR\GreaterThan(3)],
            [[1, 2, 3], 5, 0, 2, new BSR\GreaterThan(3)],
            [[1, 2, 3], 6, 0, 2, new BSR\GreaterThan(3)],
            [[1, 2, 3], 0, 0, 2, new BSR\LessThan(3)],
            [[1, 2, 3], -1, 0, 2, new BSR\LessThan(3)],
            [[1, 2, 3], -2, 0, 2, new BSR\LessThan(3)],
            [[1, 2, 3], -3, 0, 2, new BSR\LessThan(3)],
            // simple exact
            [[1], 1, 0, 0, new BSR\Exact(0, 0, 1)],
            [[1, 2], 1, 0, 1, new BSR\Exact(0, 0, 2)],
            [[1, 2], 2, 0, 1, new BSR\Exact(1, 1, 1)],
            [[1, 2, 3], 1, 0, 2, new BSR\Exact(0, 0, 3)],
            [[1, 2, 3], 2, 0, 2, new BSR\Exact(1, 1, 2)],
            [[1, 2, 3], 3, 0, 2, new BSR\Exact(2, 2, 1)],
            // leftmost exact
            [[1, 1], 1, 0, 1, new BSR\Exact(0, 0, 2)],
            [[1, 1, 2], 1, 0, 2, new BSR\Exact(0, 0, 3)],
            [[1, 1, 1, 2], 1, 0, 3, new BSR\Exact(0, 0, 4)],
            [[1, 1, 1, 1, 2], 1, 0, 4, new BSR\Exact(0, 0, 5)],

            [[1, 2, 2], 2, 0, 2, new BSR\Exact(1, 1, 2)],
            [[1, 2, 2, 2], 2, 0, 3, new BSR\Exact(1, 1, 3)],
            [[1, 2, 2, 2, 2], 2, 0, 4, new BSR\Exact(1, 1, 4)],

            [[1, 2, 3, 3], 3, 0, 3, new BSR\Exact(2, 2, 2)],
            [[1, 2, 3, 3, 3], 3, 0, 4, new BSR\Exact(2, 2, 3)],
            [[1, 2, 3, 3, 3, 3], 3, 0, 5, new BSR\Exact(2, 2, 4)],
            [[1, 2, 3, 3, 3, 3, 3], 3, 0, 6, new BSR\Exact(2, 2, 5)],
            // between
            [[1, 3], 2, 0, 1, new BSR\Between(0, 1, 1, 1)],
            [[1, 3, 5], 2, 0, 2, new BSR\Between(0, 1, 1, 2)],
            [[1, 3, 5, 7], 2, 0, 3, new BSR\Between(0, 1, 1, 3)],

            [[1, 3, 5], 4, 0, 2, new BSR\Between(1, 2, 2, 1)],
            [[1, 3, 5, 7], 4, 0, 3, new BSR\Between(1, 2, 2, 2)],

            [[1, 3, 5, 7], 6, 0, 3, new BSR\Between(2, 3, 3, 1)],
            [[1, 3, 5, 7, 9], 6, 0, 4, new BSR\Between(2, 3, 3, 2)],

            [[1, 3, 5, 7, 9], 8, 0, 4, new BSR\Between(3, 4, 4, 1)],
            [[1, 3, 5, 7, 9, 11], 10, 0, 5, new BSR\Between(4, 5, 5, 1)],
            // one element list
            [[1], 2, 0, 0, new BSR\GreaterThan(1)],
            [[1], 0, 0, 0, new BSR\LessThan(1)],
            [[1], 1, 0, 0, new BSR\Exact(0, 0, 1)],
        ];
    }
}
