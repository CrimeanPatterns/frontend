<?php

namespace AwardWallet\MainBundle\Globals;

class FunctionalUtils
{
    /**
     * @return \Closure
     */
    public static function composition()
    {
        $callables = func_get_args();

        return function () use ($callables) {
            $initialData = func_get_args();

            $callable = array_shift($callables);
            $ret = call_user_func_array($callable, $initialData);

            foreach ($callables as $callable) {
                $ret = $callable($ret);
            }

            return $ret;
        };
    }

    /**
     * @param callable $callable
     * @return \Closure
     */
    public static function not($callable)
    {
        return function () use ($callable) {
            return !call_user_func_array($callable, func_get_args());
        };
    }
}
