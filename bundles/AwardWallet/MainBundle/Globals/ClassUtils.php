<?php

namespace AwardWallet\MainBundle\Globals;

class ClassUtils
{
    /**
     * @param object $object
     * @return string
     */
    public static function getName($object)
    {
        return substr(strrchr('\\' . get_class($object), '\\'), 1);
    }
}
