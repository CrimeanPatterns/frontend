<?php

/**
 * JSONPath implementation for PHP.
 *
 * @license https://github.com/SoftCreatR/JSONPath/blob/main/LICENSE  MIT License
 */

declare(strict_types=1);

namespace AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\Filters;

use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\AccessHelper;
use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\JSONPathException;

class RecursiveFilter extends AbstractFilter
{
    /**
     * @throws JSONPathException
     */
    public function filter($collection): array
    {
        $result = [];

        $this->recurse($result, $collection);

        return $result;
    }

    /**
     * @param array|\ArrayAccess $data
     * @throws JSONPathException
     */
    private function recurse(array &$result, $data): void
    {
        $result[] = $data;

        if (AccessHelper::isCollectionType($data)) {
            foreach (AccessHelper::arrayValues($data) as $key => $value) {
                $results[] = $value;

                if (AccessHelper::isCollectionType($value)) {
                    $this->recurse($result, $value);
                }
            }
        }
    }
}
