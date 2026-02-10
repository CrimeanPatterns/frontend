<?php

namespace AwardWallet\MainBundle\Globals\JsonSerialize;

use GuzzleHttp\Promise\PromiseInterface;

trait FilterNull
{
    public function jsonSerialize()
    {
        self::filterNull($this, true);

        return $this;
    }

    private static function filterNull(&$container, $containerIsObject)
    {
        $skipped = 0;
        $needRecursion =
            is_object($container)
            && !in_array(NonRecursive::class, class_uses($container), true);

        foreach ($container as $property => &$value) {
            if (null === $value) {
                if ($containerIsObject) {
                    unset($container->$property);
                } else {
                    unset($container[$property]);
                }
            } elseif (\is_object($value) && ($value instanceof PromiseInterface)) {
                try {
                    $value = $value->wait(true);
                } catch (\Throwable $exception) {
                    $value = null;
                }
            } elseif (
                $needRecursion
                && (
                    (
                        ($valueIsObject = is_object($value))
                        && !$value instanceof \JsonSerializable
                    )
                    || is_array($value)
                )
            ) {
                if ((0 === self::filterNull($value, $valueIsObject)) && $valueIsObject && $containerIsObject) {
                    unset($container->$property);
                } else {
                    $skipped++;
                }
            } else {
                $skipped++;
            }
        }

        return $skipped;
    }
}
