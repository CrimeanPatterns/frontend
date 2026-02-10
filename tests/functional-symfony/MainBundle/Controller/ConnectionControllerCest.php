<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Globals\StringHandler;
use Codeception\Example;

/**
 * @group frontend-functional
 */
class ConnectionControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider invalidCharsDataProvider
     */
    public function testInvalidChars(\TestSymfonyGuy $I, Example $example)
    {
        $login = "test" . bin2hex(random_bytes(4));
        $userId = $I->createAwUser($login);
        $fmId = $I->createFamilyMember($userId, "Johnie", "Walker", null);

        $I->amOnPage("/user/family/{$fmId}?_switch_user={$login}");
        $I->fillField("family_member[alias]", $example["alias"]);
        $I->click("Update");
        $I->seeResponseCodeIs(200);

        $savedAlias = $I->grabFromDatabase("UserAgent", "Alias", ["UserAgentID" => $fmId]);

        if ($example["valid"]) {
            $I->assertEquals($example["alias"], $savedAlias);
        } else {
            $I->assertEquals(null, $savedAlias);
        }
    }

    /**
     * @dataProvider unsubscribeFamilyRoutesProvider
     */
    public function testUnsubscribeFamilyMember(\TestSymfonyGuy $I, Example $example)
    {
        $login = 'test' . StringHandler::getRandomCode(8);
        $userId = $I->createAwUser($login);
        $fmId = $I->haveInDatabase('UserAgent', [
            'AgentID' => $userId,
            'FirstName' => 'Johnie',
            'LastName' => 'Walker',
            'IsApproved' => 1,
            'Email' => 'fm' . StringHandler::getRandomCode(8) . '@test.com',
            'SendEmails' => 1,
            'ShareCode' => $shareCode = StringHandler::getRandomCode(10),
        ]);

        // invalid code
        $I->amOnRoute($example['route'], $example['route'] === 'aw_user_family_unsubscribe' ? [
            'userAgentId' => $fmId,
            'code' => 'invalidcode',
        ] : [
            'ID' => $fmId,
            'Code' => 'invalidcode',
        ]);
        $I->seeResponseCodeIs(404);

        // valid code
        $I->amOnRoute($example['route'], $example['route'] === 'aw_user_family_unsubscribe' ? [
            'userAgentId' => $fmId,
            'code' => $shareCode,
        ] : [
            'ID' => $fmId,
            'Code' => $shareCode,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase('UserAgent', [
            'UserAgentID' => $fmId,
            'SendEmails' => 0,
        ]);
    }

    protected function unsubscribeFamilyRoutesProvider()
    {
        return [
            ['route' => 'aw_user_family_unsubscribe'],
            ['route' => 'aw_user_family_unsubscribe_old'],
        ];
    }

    protected function invalidCharsDataProvider()
    {
        return [
            ['alias' => 'some.with.char', 'valid' => false],
            ['alias' => 'with+char', 'valid' => false],
            ['alias' => 'justchars', 'valid' => true],
        ];
    }
}
