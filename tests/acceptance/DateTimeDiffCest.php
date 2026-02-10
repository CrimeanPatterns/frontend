<?php

/**
 * @group frontend-acceptance
 */
class DateTimeDiffCest
{
    public function index(WebGuy $I)
    {
        $I->amOnPage("/test/date-time-diff");
        $I->waitForText("all tests complete", 5);
        $I->see("passed");
        $I->dontSee("failed");
    }
}
