<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

class DieTraceUtils
{
    public static function DieTraceOnWarning($message, $moreInfo)
    {
        $container = getSymfonyContainer();

        if (!empty($container)) {
            $container->get("logger")->critical($message, ["EmailTitle" => "Warning", "info" => $moreInfo]);
        }
    }
}
