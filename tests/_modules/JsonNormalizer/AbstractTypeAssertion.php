<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use AwardWallet\MainBundle\Globals\Singleton;

abstract class AbstractTypeAssertion implements PlaceholderProcessorInterface
{
    use Singleton;
    use PlaceholderProcessorHelperTrait;

    public function process($value, string $propertyPath, Context $context)
    {
        return $this->is($value) ?
            $this->makeStub() :
            $this->makeError(
                'type mismatch',
                $propertyPath,
                [
                    'value' => $value,
                ]
            );
    }

    abstract protected function is($value): bool;
}
