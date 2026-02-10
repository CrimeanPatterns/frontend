<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\ProviderHandler;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 */
class UserPointValueCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Router $router;

    private int $providerId;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->createUserAndLogin($I, 'account-', 'userpass-');
        $this->providerId = $I->createAwProvider(StringUtils::getRandomCode(12), null, [
            'Kind' => PROVIDER_KIND_HOTEL,
        ]);
    }

    public function sendProviderUserSet(\TestSymfonyGuy $I)
    {
        $value = 12.34;
        $I->sendPost(
            $this->router->generate('aw_points_miles_userset'),
            [
                'providerId' => $this->providerId,
                'value' => $value,
            ]
        );

        $I->seeInDatabase('UserPointValue', [
            'UserID' => $this->userId,
            'ProviderID' => $this->providerId,
        ]);

        $provider = null;
        $datas = $I->grabDataFromJsonResponse('datas.' . ProviderHandler::KIND_KEYS[PROVIDER_KIND_HOTEL] . '.data');

        foreach ($datas as $item) {
            if ($item['ProviderID'] == $this->providerId) {
                $provider = $item;
            }
        }

        $I->assertArrayHasKey(MileValueService::PRIMARY_CALC_FIELD, $provider['user']);
        $I->assertEquals($value, $provider['user'][MileValueService::PRIMARY_CALC_FIELD]);
    }

    public function sendAccountUserSet(\TestSymfonyGuy $I)
    {
        $customAccountId = $I->haveInDatabase('Account', [
            'UserID' => $this->userId,
            'Login' => StringUtils::getRandomCode(8),
            'Balance' => null,
            'ProgramName' => 'testCustomProgram',
        ]);

        $value = 34.56;
        $I->sendPost(
            $this->router->generate('aw_points_miles_userset'), [
                'accountId' => $customAccountId,
                'value' => $value,
                'source' => 'cashEquivalent',
            ]);

        $I->seeInDatabase('Account', [
            'UserID' => $this->userId,
            'AccountID' => $customAccountId,
            'PointValue' => $value,
        ]);

        $item = $I->grabDataFromJsonResponse('MileValue.awEstimate');

        $I->assertEquals($value . '¢', $item['value']);

        return $customAccountId;
    }

    public function removeProviderUserSet(\TestSymfonyGuy $I)
    {
        $this->sendProviderUserSet($I);

        $I->sendPost(
            $this->router->generate('aw_points_miles_userset', ['providerId' => $this->providerId]), [
                'value' => null,
            ]);

        $I->dontSeeInDatabase('UserPointValue', [
            'UserID' => $this->userId,
            'ProviderID' => $this->providerId,
        ]);
    }

    public function removeAccountUserSet(\TestSymfonyGuy $I)
    {
        $customAccountId = $this->sendAccountUserSet($I);

        $I->sendPost(
            $this->router->generate('aw_points_miles_userset', ['accountId' => $customAccountId]), [
                'value' => null,
            ]);

        $I->seeInDatabase('Account', [
            'UserID' => $this->userId,
            'AccountID' => $customAccountId,
            'PointValue' => null,
        ]);
    }
}
