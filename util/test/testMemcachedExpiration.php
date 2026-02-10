<?php
require __DIR__ . "/../../web/kernel/public.php";

$c = getSymfonyContainer();
$memcached = $c->get("aw.memcached");

for($n = 0; $n < 100; $n++) {
    $key = bin2hex(random_bytes(10));
    $writeTime = microtime(true);
    $memcached->set($key, "exist", 1);
    usleep(200000);
    $readTime = microtime(true);
    $read = $memcached->get($key);
    if ($key !== $read) {
        echo "failed to read, write time: " . round($writeTime, 3) . ", read time: " . round($readTime, 3) . "\n";
    }
}
