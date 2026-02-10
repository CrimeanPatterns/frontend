<?php

namespace AwardWallet\MainBundle\Service\ItineraryComparator\Property;

class TravelerNames extends SmartCollectionProperty
{
    protected function split(string $value): array
    {
        return \array_filter(
            \array_map(
                function ($v) {
                    $v = \preg_replace('/\s{2,}/', ' ', \trim(\str_replace("/", " ", $v)));

                    if (empty($v)) {
                        return null;
                    }

                    return $v;
                },
                \explode(',', $value)
            )
        );
    }

    protected function match(string $value1, string $value2): bool
    {
        $value1 = \trim(\strtolower($value1));
        $value2 = \trim(\strtolower($value2));

        \similar_text($this->sortWords($value1), $this->sortWords($value2), $percent);

        if ($percent >= 80) {
            return true;
        }

        if (
            (
                strpos($value1, ' ') !== false
                && preg_match($this->getPattern($value1), $value2)
            ) || (
                strpos($value2, ' ') !== false
                && preg_match($this->getPattern($value2), $value1)
            )
        ) {
            return true;
        }

        if (
            (
                strpos($value1, ' ') === false
                && strpos($value2, ' ') !== false
                && strpos($value2, $value1) !== false
            ) || (
                strpos($value2, ' ') === false
                && strpos($value1, ' ') !== false
                && strpos($value1, $value2) !== false
            )
        ) {
            return true;
        }

        return false;
    }

    private function getPattern(string $value): string
    {
        return \sprintf('/^%s$/', \preg_replace('/\s{1,}/', '\s+\w+\s+', preg_quote($value)));
    }

    private function sortWords(string $value): string
    {
        $words = \explode(' ', $value);
        \sort($words);

        return \implode(' ', $words);
    }
}
