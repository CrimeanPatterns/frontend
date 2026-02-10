<?php

namespace AwardWallet\Tests\Unit;

class IsolationCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function writeGlobal(\CodeGuy $I)
    {
        global $myNiceGlobal;
        $myNiceGlobal = "hello";
    }

    public function readGlobal(\CodeGuy $I)
    {
        global $myNiceGlobal;
        $I->assertEmpty($myNiceGlobal);
    }
}
