<?php

namespace AwardWallet\Tests\FunctionalSymfony;

/**
 * @group frontend-functional
 */
class PageNotFoundCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function test404(\TestSymfonyGuy $I)
    {
        $I->wantTo("check 404 page");
        $I->amOnPage("/dsdfsf");
        $I->see("404");
        $I->see("Not Found");
    }
}
