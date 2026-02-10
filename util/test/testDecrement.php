<?php
require "vendor/autoload.php";

error_reporting(E_ALL);

define('MEMCACHED_HOST', 'memcached');

        $cache = new \Memcached('proxylist_bin_' . MEMCACHED_HOST . getmypid());
        if(count($cache->getServerList()) == 0){
            $cache->addServer(MEMCACHED_HOST, 11211);
            $cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $cache->setOption(\Memcached::OPT_RECV_TIMEOUT, 500);
            $cache->setOption(\Memcached::OPT_SEND_TIMEOUT, 500);
            $cache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 500);
            $cache->setOption(\Memcached::OPT_TCP_NODELAY, true);
        }


$ttl = 600;
$throttler = new \Throttler($cache, 60, (int)ceil($ttl / 60), 1);

$key = "some_test_1";
$cache->increment($key, 1, 1, $ttl);
echo $cache->get($key) . "\n";
echo $throttler->getThrottledRequestsCount($key) . "\n";
$throttler->increment($key, 1);
echo $cache->getResultMessage() . "\n";
echo $throttler->getThrottledRequestsCount($key) . "\n";

