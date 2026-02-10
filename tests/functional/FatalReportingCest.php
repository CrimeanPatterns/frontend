<?php

namespace AwardWallet\Tests\Functional;

use AwardWallet\MainBundle\Globals\StringHandler;

/**
 * Class ErrorReportingCest.
 *
 * @group functional
 * @group frontend-functional
 * @group testmail1
 */
class FatalReportingCest
{
    public const URL = '/test/error_reporting';

    public function _before(\TestGuy $I)
    {
    }

    public function testFatal(\TestGuy $I)
    {
        $mailTo = getSymfonyContainer()->getParameter("aw.email.address.error");
        $random = StringHandler::getRandomCode(20);
        $I->sendGET(self::URL, ['_switch_user' => 'SiteAdmin']);
        //        $I->setCookie("XDEBUG_SESSION", "aw_idekey_awardwallet.docker");
        $I->sendGET(self::URL, ['case' => 'undefinedFunction', 'random' => $random]);
        $I->seeEmailTo($mailTo, "An Error Occurred!", "someMissingFunction{$random}", 20);
    }

    public function testDieTrace(\TestGuy $I)
    {
        $mailTo = getSymfonyContainer()->getParameter("aw.email.address.error");
        $random = StringHandler::getRandomCode(20);
        $I->sendGET('/admin/test/dietrace.php?Code=' . $random);
        $I->seeEmailTo($mailTo, $random, "{$random}", 20);
        $I->dontSeeEmailTo($mailTo, $random, "some password", 1);
    }

    public function testOutOfMemory(\TestGuy $I)
    {
        $mailTo = getSymfonyContainer()->getParameter("aw.email.address.error");
        $random = StringHandler::getRandomCode(20);
        $I->sendGET(self::URL, ['_switch_user' => 'SiteAdmin']);
        $I->sendGET(self::URL, ['case' => 'outOfMemory', 'random' => $random]);
        $I->seeEmailTo($mailTo, "An Error Occurred!", "outOfMemory&random={$random}", 20);
    }
}
