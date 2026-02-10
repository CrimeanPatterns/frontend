<?php

namespace AwardWallet\MainBundle\Globals;

class ProcessUtils
{
    public static function runInFork(callable $callable)
    {
        $pid = \pcntl_fork();

        if ($pid == -1) {
            throw new \RuntimeException('Unable to fork process');
        } elseif ($pid) {
            \pcntl_wait($status);

            return $status;
        } else {
            $callable();

            \register_shutdown_function(function () {
                exit(0);
            });

            exit(0);
        }
    }
}
