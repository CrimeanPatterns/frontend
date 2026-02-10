<?php

namespace Account\EditController;

/**
 * @group frontend-functional
 */
class OAuthAccountCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var int
     */
    private $userId;
    /**
     * @var string
     */
    private $username;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $this->userId]);
    }

    public function bankofamericaValid(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "bankofamerica", "some", null, ["AuthInfo" => "someinfo"]);
        $I->amOnRoute('aw_account_edit', ['accountId' => $accountId, '_switch_user' => $this->username]);
        $I->assertEquals("someinfo", $I->grabAttributeFrom("//input[@id = 'account_authInfo']", "value"));
    }

    public function capitalcardsV1OnOld(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "capitalcards", "some", null, ["AuthInfo" => "someinfo"]);
        $I->amOnRoute('aw_account_edit', ['accountId' => $accountId, '_switch_user' => $this->username]);
        $I->assertEquals('{"rewards":"someinfo","tx":null}', $I->grabAttributeFrom("//input[@id = 'account_authInfo']", "value"));
    }

    public function capitalcardsV1OnNew(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, "capitalcards", "some", null, ["AuthInfo" => 'v1:{"rewards":"tok1", "tx":"tok2"}']);
        $I->amOnRoute('aw_account_edit', ['accountId' => $accountId, '_switch_user' => $this->username]);
        $I->assertEquals('{"rewards":"tok1","tx":"tok2"}', $I->grabAttributeFrom("//input[@id = 'account_authInfo']", "value"));
    }
}
