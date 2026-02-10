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
use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\JSONPathException;

class IndexFilter extends AbstractFilter
{
    /**
     * @throws JSONPathException
     */
    public function filter($collection): array
    {
        if (is_array($this->token->value)) {
            $result = [];

            foreach ($this->token->value as $value) {
                if (AccessHelper::keyExists($collection, $value, $this->magicIsAllowed)) {
                    $result[] = new FoundValue(
                        AccessHelper::getValue($collection, $value, $this->magicIsAllowed),
                        [$value]
                    );
                }
            }

            return $result;
        }

        if (AccessHelper::keyExists($collection, $this->token->value, $this->magicIsAllowed)) {
            return [
                new FoundValue(
                    AccessHelper::getValue($collection, $this->token->value, $this->magicIsAllowed),
                    [$this->token->value]
                ),
            ];
        }

        if ($this->token->value === '*') {
            return AccessHelper::arrayValues($collection);
        }

        if ($this->token->value === 'length') {
            return [
                count($collection),
            ];
        }

        return [];
    }
}
