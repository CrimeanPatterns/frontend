<?php

namespace AwardWallet\Tests\Unit\AsyncProcess\Callback\Fixtures;

class Dependency1
{
    public function testSomeVar($var)
    {
        return $var;
    }
}
