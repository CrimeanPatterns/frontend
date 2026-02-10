<?php
require __DIR__ . "/../../web/kernel/public.php";

$c = getSymfonyContainer();
$memcached = $c->get("aw.memcached");
$throttler = new Throttler($memcached, 1, 2, 100);
$key = bin2hex(random_bytes(10));

for($n = 0; $n < 1000; $n++) {
    $throttler->increment($key);
    usleep(10000);
    $requests = $throttler->getThrottledRequestsCount($key);
    echo "requests: $requests\n";
}
