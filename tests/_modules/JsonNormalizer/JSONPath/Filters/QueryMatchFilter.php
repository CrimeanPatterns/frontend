<?php

/**
 * JSONPath implementation for PHP.
 *
 * @license https://github.com/SoftCreatR/JSONPath/blob/main/LICENSE  MIT License
 */

declare(strict_types=1);

namespace AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\Filters;

use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\AccessHelper;
use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\FoundValue;

class QueryMatchFilter extends AbstractFilter
{
    protected const MATCH_QUERY_OPERATORS = '
      @(\.(?<key>[^\s<>!=]+)|\[["\']?(?<keySquare>.*?)["\']?\])
      (\s*(?<operator>==|=~|=|<>|!==|!=|>=|<=|>|<|in|!in|nin)\s*(?<comparisonValue>.+))?
    ';

    public function filter($collection): array
    {
        \preg_match('/^' . static::MATCH_QUERY_OPERATORS . '$/x', $this->token->value, $matches);

        if (!isset($matches[1])) {
            throw new \RuntimeException('Malformed filter query');
        }

        $key = $matches['key'] ?: $matches['keySquare'];

        if ($key === '') {
            throw new \RuntimeException('Malformed filter query: key was not set');
        }

        $operator = $matches['operator'] ?? null;
        $comparisonValue = $matches['comparisonValue'] ?? null;

        if (\is_string($comparisonValue)) {
            if (\strpos($comparisonValue, "[") === 0 && \substr($comparisonValue, -1) === "]") {
                $comparisonValue = \substr($comparisonValue, 1, -1);
                $comparisonValue = \preg_replace('/^[\'"]/', '', $comparisonValue);
                $comparisonValue = \preg_replace('/[\'"]$/', '', $comparisonValue);
                $comparisonValue = \preg_replace('/[\'"],[ ]*[\'"]/', ',', $comparisonValue);
                $comparisonValue = array_map('trim', \explode(",", $comparisonValue));
            } else {
                $comparisonValue = \preg_replace('/^[\'"]/', '', $comparisonValue);
                $comparisonValue = \preg_replace('/[\'"]$/', '', $comparisonValue);

                if (\strtolower($comparisonValue) === 'false') {
                    $comparisonValue = false;
                } elseif (\strtolower($comparisonValue) === 'true') {
                    $comparisonValue = true;
                } elseif (\strtolower($comparisonValue) === 'null') {
                    $comparisonValue = null;
                }
            }
        }

        $return = [];

        foreach ($collection as $collKey => $value) {
            if (AccessHelper::keyExists($value, $key, $this->magicIsAllowed)) {
                $value1 = AccessHelper::getValue($value, $key, $this->magicIsAllowed);

                if ($operator === null && $value1) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                /** @noinspection TypeUnsafeComparisonInspection */
                // phpcs:ignore -- This is a loose comparison by design.
                if (($operator === '=' || $operator === '==') && $value1 == $comparisonValue) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                /** @noinspection TypeUnsafeComparisonInspection */
                // phpcs:ignore -- This is a loose comparison by design.
                if (($operator === '!=' || $operator === '!==' || $operator === '<>') && $value1 != $comparisonValue) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if ($operator === '=~' && @\preg_match($comparisonValue, $value1)) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if ($operator === '>' && $value1 > $comparisonValue) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if ($operator === '>=' && $value1 >= $comparisonValue) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if ($operator === '<' && $value1 < $comparisonValue) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if ($operator === '<=' && $value1 <= $comparisonValue) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if ($operator === 'in' && \is_array($comparisonValue) && \in_array($value1, $comparisonValue)) {
                    $return[] = new FoundValue($value, [$collKey]);
                }

                if (
                    ($operator === 'nin' || $operator === '!in')
                    && \is_array($comparisonValue)
                    && !\in_array($value1, $comparisonValue)
                ) {
                    $return[] = new FoundValue($value, [$collKey]);
                }
            }
        }

        return $return;
    }
}
