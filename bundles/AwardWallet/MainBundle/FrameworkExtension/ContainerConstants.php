<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerConstants
{
    public static function define(ContainerInterface $container)
    {
        if (!defined('MEMCACHED_HOST')) {
            define('MEMCACHED_HOST', $container->getParameter("memcached_host"));
        }

        if (!defined('SHARED_MEMCACHED_HOST')) {
            define('SHARED_MEMCACHED_HOST', $container->getParameter("shared_memcached_host"));
        }
    }
}
