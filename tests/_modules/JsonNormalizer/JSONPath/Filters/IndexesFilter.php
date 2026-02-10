<?php

/**
 * JSONPath implementation for PHP.
 *
 * @license https://github.com/SoftCreatR/JSONPath/blob/main/LICENSE  MIT License
 */

declare(strict_types=1);

namespace AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\Filters;

use AwardWallet\Tests\Modules\JsonNormalizer\JSONPath\AccessHelper;

class IndexesFilter extends AbstractFilter
{
    public function filter($collection): array
    {
        $return = [];

        foreach ($this->token->value as $index) {
            if (AccessHelper::keyExists($collection, $index, $this->magicIsAllowed)) {
                $return[] = AccessHelper::getValue($collection, $index, $this->magicIsAllowed);
            }
        }

        return $return;
    }
}
