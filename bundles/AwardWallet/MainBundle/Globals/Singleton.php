<?php

namespace AwardWallet\MainBundle\Globals;

trait Singleton
{
    use PrivateConstructor;

    public static function getInstance(): self
    {
        static $instance = null;

        if (\is_null($instance)) {
            $class = static::class;
            $instance = new $class();
        }

        return $instance;
    }
}
