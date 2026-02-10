<?php

namespace AwardWallet\Tests\FunctionalSymfony\Security;

use AwardWallet\MainBundle\Entity\Useragent;
use Codeception\Module\Aw;

/**
 * @group frontend-functional
 */
class ShareAllAccountsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $login;
    private $userId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = 'test' . $I->grabRandomString();
        $this->userId = $I->createAwUser($this->login);
    }

    public function testUnapproved(\TestSymfonyGuy $I)
    {
        $connectionId = $I->createConnection($this->userId, Aw::BOOKER_ID, false);
        $userAgent = $I->getContainer()->get('doctrine')->getRepository(Useragent::class)->find($connectionId);
        $code = $I->getContainer()->get("aw.manager.program_share_manager")->getShareAllCode($userAgent, "full");
        $I->amOnRoute("aw_share_all", ["code" => $code, "_switch_user" => $this->login]);
        $I->seeResponseCodeIs(403);
    }

    public function testApproved(\TestSymfonyGuy $I)
    {
        $connectionId = $I->createConnection(Aw::BOOKER_ID, $this->userId, true);
        $connectionId = $I->createConnection($this->userId, Aw::BOOKER_ID, true);
        $userAgent = $I->getContainer()->get('doctrine')->getRepository(Useragent::class)->find($connectionId);
        $code = $I->getContainer()->get("aw.manager.program_share_manager")->getShareAllCode($userAgent, "full");
        $I->amOnRoute("aw_share_all", ["code" => $code, "_switch_user" => $this->login]);
        $I->seeResponseCodeIs(200);
    }

    public function testBackLink(\TestSymfonyGuy $I)
    {
        $connectionId = $I->createConnection($this->userId, Aw::BOOKER_ID, true);
        $connectionId = $I->createConnection(Aw::BOOKER_ID, $this->userId, true);
        $userAgent = $I->getContainer()->get('doctrine')->getRepository(Useragent::class)->find($connectionId);
        $code = $I->getContainer()->get("aw.manager.program_share_manager")->getShareAllCode($userAgent, "full");
        $I->amOnRoute("aw_share_all", ["code" => $code, "_switch_user" => $this->login]);
        $I->seeResponseCodeIs(403);
    }

    public function testNoAccountAccess(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "aeroplan", "some");

        $I->amOnSubdomain("business");
        $I->amOnRoute("aw_account_edit", ["accountId" => $accountId, "_switch_user" => "siteadmin"]);
        $I->seeResponseCodeIs(403);
    }

    public function testAccountAccess(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "aeroplan", "some");

        $connectionId = $I->createConnection(Aw::BOOKER_ID, $this->userId, true);
        $connectionId = $I->createConnection($this->userId, Aw::BOOKER_ID, true);

        $userAgent = $I->getContainer()->get('doctrine')->getRepository(Useragent::class)->find($connectionId);
        $code = $I->getContainer()->get("aw.manager.program_share_manager")->getShareAllCode($userAgent, "full");
        $I->amOnRoute("aw_share_all", ["code" => $code, "_switch_user" => $this->login]);
        $I->seeResponseCodeIs(200);

        $I->amOnSubdomain("business");
        $I->amOnRoute("aw_test_client_info", ["_switch_user" => "_exit"]);
        $I->amOnRoute("aw_account_edit", ["accountId" => $accountId, "_switch_user" => "siteadmin"]);
        $I->seeResponseCodeIs(200);
    }
}
