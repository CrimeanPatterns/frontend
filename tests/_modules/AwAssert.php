<?php

namespace AwardWallet\Tests\Modules;

use AwardWallet\MainBundle\Globals\Utils\ArrayContainsComparator;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ArrayComparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;

class AwAssert
{
    public static function assertArrayContainsArray(array $expected, array $actual)
    {
        if (ArrayContainsComparator::containsArray($expected, $actual)) {
            return;
        }

        $comparator = new ArrayComparator();
        $comparator->setFactory(new Factory());

        try {
            $comparator->assertEquals($expected, $actual);
        } catch (ComparisonFailure $failure) {
            throw new ExpectationFailedException("Array does not contain the provided array\n", $failure);
        }
    }
}
