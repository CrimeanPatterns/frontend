<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Account;

use AwardWallet\MainBundle\Entity\Extensionstat;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Codeception\Module\Aw;

/**
 * @group mobile
 * @group frontend-functional
 */
class ExtensionStatsAutologinCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'accounts-', 'userpass-', [], true);
    }

    public function extensionStatsTest(\TestSymfonyGuy $I)
    {
        $accountId1 = $I->createAwAccount($this->userId, Aw::TEST_PROVIDER_ID, 'secret.login');

        $I->sendGET("/m/api/account/autologin/mobile/{$accountId1}/1/1");
        $I->seeResponseContains("secret.login");

        $I->sendPOST('/extension/extensionStats.php', [
            "providerCode" => "testprovider",
            "success" => 0,
            'accountId' => $accountId1,
            "errorMessage" => $errorMessage = StringHandler::getRandomString(ord('a'), ord('z'), 20),
            "errorCode" => 2,
            "mobileKind" => "autologin",
        ]);

        $I->seeInDatabase('ExtensionStat', $row = [
            'ProviderID' => Aw::TEST_PROVIDER_ID,
            'AccountID' => $accountId1,
            'ErrorText' => $errorMessage,
            'ErrorCode' => 2,
            'Status' => Extensionstat::STATUS_FAIL,
            'Platform' => 'mobile-autologin',
        ]);

        $I->haveInsertedInDatabase('ExtensionStat', ['ExtensionStatID' => $I->grabFromDatabase('ExtensionStat', 'ExtensionStatID', $row)]);
    }
}
