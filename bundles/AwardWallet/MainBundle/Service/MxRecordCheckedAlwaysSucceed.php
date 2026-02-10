<?php

namespace AwardWallet\MainBundle\Service;

class MxRecordCheckedAlwaysSucceed extends MxRecordChecker
{
    public function check(string $host): bool
    {
        return true;
    }
}
