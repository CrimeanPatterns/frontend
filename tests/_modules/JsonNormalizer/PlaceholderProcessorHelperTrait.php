<?php

namespace AwardWallet\Tests\Modules\JsonNormalizer;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

trait PlaceholderProcessorHelperTrait
{
    protected function makeStub(array $mixin = []): array
    {
        $reflClass = new \ReflectionClass(self::class);

        if ($constructor = $reflClass->getConstructor()) {
            $args =
                it($constructor->getParameters())
                ->filter(fn (\ReflectionParameter $param) =>
                    !$param->isOptional()
                    || ($this->{$param->getName()} !== $param->getDefaultValue())
                )
                ->map(fn (\ReflectionParameter $param) => $this->{$param->getName()})
                ->toArray();
        } else {
            $args = [];
        }

        $stub = [
            '_type' => $this::getCode(),
        ];

        if ($args) {
            $stub['_args'] = $args;
        }

        if ($mixin) {
            $stub = \array_merge(
                $stub,
                $mixin,
            );
        }

        return $stub;
    }

    protected function makeError(string $error, string $propertyPath, array $mixin = [])
    {
        return \array_merge(
            $this->makeStub(),
            [
                'error' => $error,
                'property_path' => $propertyPath,
            ],
            $mixin
        );
    }
}
