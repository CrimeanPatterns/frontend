<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Entity\Extensionstat;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\_steps\Mobile\AccountSteps;
use Codeception\Scenario;

/**
 * @group mobile
 * @group frontend-functional
 */
class ExtensionCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const MOBILE_API = '/m/api/account/autologin/extension/testprovider';
    public const LOG_API = '/extension/extensionStats.php';

    public function testExtensionStats(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $userSteps = $this->userSteps;
        $I->createAwUser(
            $login = 'mext-' . StringHandler::getRandomCode(10),
            $password = 'password',
            ['Email' => $email = $login . '@fakemail.com']
        );
        $userSteps->login($login, $password);

        $I->sendGET(self::MOBILE_API);
        $I->seeResponseContains('/** VERSION: 1 */');

        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'ios');
        $I->sendGET(self::MOBILE_API);
        $I->seeResponseContains('/** VERSION: 1 */');

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.6.0+abc123');
        $I->sendGET(self::MOBILE_API);
        $I->seeResponseContains('/** VERSION: 1 */');

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.7.0+abc123');
        $I->sendGET(self::MOBILE_API);
        $I->seeResponseContains('/** VERSION: 2 */');

        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, '3.10.0+abc123');
        $I->sendGET(self::MOBILE_API);
        $I->seeResponseContains('/** VERSION: 2.1 */');

        $I->sendPOST(self::LOG_API, [
            'mobileKind' => 'flightstatus',
            'providerCode' => 'testprovider',
            'accountId' => $acc = rand(100, 10000),
            'errorMessage' => $errorMessage = StringHandler::getRandomCode(20),
            'errorCode' => 1,
            'success' => 0,
        ]);

        $I->seeInDatabase('ExtensionStat', [
            'ProviderID' => AccountSteps::TEST_PROVIDER_ID,
            'AccountID' => $acc,
            'Platform' => 'mobile-flightstatus',
            'ErrorText' => $errorMessage,
            'ErrorCode' => '1',
            'Status' => Extensionstat::STATUS_FAIL,
        ]);
    }
}
