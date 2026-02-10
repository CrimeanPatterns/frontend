<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

trait JsonHeaders
{
    protected function _before_JsonHeaders(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
    }
}
