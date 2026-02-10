<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Country;

/**
 * @group frontend-functional
 */
class SupportedProgramsControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const US_IP = '0:0:0:0:0:ffff:9455:881f';
    public const RU_IP = '2a02:6b8:a::a';

    public function testRegions(\TestSymfonyGuy $I)
    {
        $john = $I->createAwUser(null, null, ["CountryID" => Country::UNITED_STATES, "RegistrationIP" => self::US_IP]);
        $ivan = $I->createAwUser(null, null, ["CountryID" => Country::RUSSIA, "RegistrationIP" => self::RU_IP]);

        $I->haveServerParameter("REMOTE_ADDR", self::US_IP);
        $I->amOnRoute("aw_supported", ["_switch_user" => $I->grabFromDatabase("Usr", "Login", ["UserID" => $john])]);
        $usPrograms = $this->extractProgramsFromBody($I);
        $I->amOnRoute("aw_supported", ["_switch_user" => "_exit"]);

        $I->haveServerParameter("REMOTE_ADDR", self::RU_IP);
        $I->amOnRoute("aw_supported", ["_switch_user" => $I->grabFromDatabase("Usr", "Login", ["UserID" => $ivan])]);
        $ruPrograms = $this->extractProgramsFromBody($I);

        $I->assertNotEquals($usPrograms, $ruPrograms);
    }

    private function extractProgramsFromBody(\TestSymfonyGuy $I)
    {
        $body = $I->grabPageSource();

        if (!preg_match("#window\.providersData = (\[.*\]);#ims", $body, $matches)) {
            $I->fail("failed to find providersData");
        }
        $data = json_decode($matches[1], true);
        $result = array_map(function (array $item) { return $item['ProviderID']; }, $data);
        $result = array_slice($result, 0, 10);
        $I->assertCount(10, $result);

        return $result;
    }
}
