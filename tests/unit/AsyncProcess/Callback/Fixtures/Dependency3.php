<?php

namespace AwardWallet\Tests\Unit\AsyncProcess\Callback\Fixtures;

class Dependency3
{
    public function testSomeVar($var)
    {
        return $var;
    }
}
