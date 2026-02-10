<?php

namespace AwardWallet\MainBundle\Service;

class MxRecordChecker
{
    public function check(string $host): bool
    {
        $mxRecords = [];
        \getmxrr($host, $mxRecords);

        return (bool) $mxRecords;
    }
}
