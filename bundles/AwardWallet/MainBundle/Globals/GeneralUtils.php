<?php

namespace AwardWallet\MainBundle\Globals;

class GeneralUtils
{
    public static function coalesce()
    {
        foreach (func_get_args() as $arg) {
            if (null !== $arg) {
                return $arg;
            }
        }

        return null;
    }
}
