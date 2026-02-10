<?php

namespace AwardWallet\Tests\Modules;

trait AutoVerifyMocksTrait
{
    public function _passed(\TestSymfonyGuy $I)
    {
        $I->verifyMocks();
    }
}
