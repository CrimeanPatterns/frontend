<?php

namespace AwardWallet\Tests\Unit\AsyncProcess\Callback\Fixtures;

class Dependency2
{
    public function testSomeVar($var)
    {
        return $var;
    }
}
