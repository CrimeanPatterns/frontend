<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

use AwardWallet\MainBundle\Service\ItineraryComparator\Property;
use AwardWallet\MainBundle\Service\ItineraryComparator\Util;

class ArrayProperty extends Property
{
    public string $separator = ",";

    public bool $isNumbers = true;

    public float $thresholdPercent = 0;

    public function equals(string $value1, string $value2): bool
    {
        $result = parent::equals($value1, $value2);

        if ($result) {
            return true;
        }
        $value1 = strtolower($value1);
        $value2 = strtolower($value2);

        return $this->compareStringsAsArray($value1, $value2, $this->separator, $this->isNumbers, $this->thresholdPercent);
    }

    protected function compareStringsAsArray(string $value1, string $value2, string $separator = '|', bool $isNumbers = true, float $thresholdPercent = 0): bool
    {
        $arr1 = $this->prepare($value1, $separator, $isNumbers);
        $arr2 = $this->prepare($value2, $separator, $isNumbers);

        $result = \count($arr1) === \count($arr2) && \count(array_diff($arr1, $arr2)) === 0 && \count(array_diff($arr2, $arr1)) === 0;

        if (!$isNumbers && !$result) {
            return $this->similarArrayCompare($arr1, $arr2);
        } elseif ($isNumbers && $thresholdPercent > 0 && !$result) {
            return $this->thresholdArrayCompare($arr1, $arr2, $thresholdPercent);
        }

        return $result;
    }

    protected function thresholdArrayCompare(array $value1, array $value2, float $threshold): bool
    {
        if (\count($value1) !== \count($value2)) {
            return false;
        }

        foreach ($value1 as $v1) {
            foreach ($value2 as $k => $v2) {
                if ($v1 == 0) {
                    if ($v2 != 0) {
                        $percentageChange = 100;
                    } else {
                        $percentageChange = 0;
                    }
                } else {
                    $percentageChange = abs(($v1 - $v2) / $v1) * 100;
                }

                if ($percentageChange < $threshold) {
                    unset($value2[$k]);

                    continue 2;
                }
            }

            return false;
        }

        return true;
    }

    protected function similarArrayCompare(array $value1, array $value2, float $minPercent = 80): bool
    {
        if (\count($value1) != \count($value2)) {
            return false;
        }

        foreach ($value1 as $v1) {
            foreach ($value2 as $k => $v2) {
                similar_text($v1, $v2, $percent);

                if ($percent >= $minPercent) {
                    unset($value2[$k]);

                    continue 2;
                }
            }

            return false;
        }

        return true;
    }

    protected function prepare(string $value, string $separator, bool $isNumbers): array
    {
        $value = explode($separator, $value);

        return array_map(function ($v) use ($isNumbers) {
            if ($isNumbers) {
                return Util::getNumber($v);
            }

            return trim($v);
        }, $value);
    }
}
