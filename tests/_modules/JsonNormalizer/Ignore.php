<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use AwardWallet\MainBundle\Globals\Singleton;

class Ignore implements PlaceholderProcessorInterface
{
    use Singleton;
    use PlaceholderProcessorHelperTrait;

    public function process($value, string $propertyPath, Context $context)
    {
        return $this->makeStub();
    }

    public static function getCode(): string
    {
        return 'ignore';
    }
}
