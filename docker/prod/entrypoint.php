#!/usr/bin/env php
<?php

error_reporting(E_ALL);

echo "symfony env: " . getenv("SYMFONY_ENV") . "\n";

// passthru("docker/wait-for-it.sh -s -t 60 postfix:25", $exitCode);
// if($exitCode != 0)
//    exit($exitCode);

if (getenv("SYNC_ENGINE") == "1") {
    echo "waiting for engine folder to be synced\n";
    $file = "engine/sync_marker";
    $synced = false;
    $startTime = time();

    while (($delay = (time() - $startTime)) < 90 && !$synced) {
        clearstatcache();

        if (file_exists($file)) {
            $date = strtotime(file_get_contents($file));
            $curDate = time();

            if ($date < $curDate && $date > strtotime('2000-01-01')) {
                $synced = true;
            } else {
                echo "date in $file: " . date("Y-m-d H:i:s", $date) . ", current date: " . date("Y-m-d H:i:s", $curDate) . "\n";
            }
        } else {
            echo "waiting $delay seconds for $file\n";
        }

        if (!$synced) {
            sleep(1);
        }
    }

    if (!$synced) {
        echo "failed to sync engine\n";

        exit(1);
    }
}

$args = explode(" ", preg_replace("#\s{2,}#ims", "", trim(getenv("COMMAND"))));
$command = array_shift($args);

if (getenv("SSM_WARMUP") == "1") {
    passthru('gosu www-data app/console aw:ssm-warmup-cache -vv', $exitCode);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

var_dump($argv);

pcntl_exec($argv[1], array_slice($argv, 2));
echo "failed to exec\n";

exit(1);
