<?php

namespace AwardWallet\MainBundle\Event\PushNotification;

use AwardWallet\MainBundle\Globals\CollectionUtils as C;
use AwardWallet\MainBundle\Globals\FunctionalUtils as F;
use AwardWallet\MainBundle\Globals\StringHandler;

class ListenerUtils
{
    public static function isAnyEmptyParams($params)
    {
        return C::any(
            array_filter($params, F::not('is_object')),
            F::composition('trim', [StringHandler::class, 'isEmpty'])
        );
    }

    /**
     * @param array $params
     * @return array
     */
    public static function decodeStrings($params)
    {
        return array_map(function ($param) {
            if (is_string($param)) {
                return htmlspecialchars_decode($param);
            } else {
                return $param;
            }
        }, $params);
    }
}
