<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\ProviderMileValue;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\UserPointValueService;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @group mobile
 * @group functional-symfony
 */
class MileValueControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;

    private ?UrlGeneratorInterface $router;
    private ?MileValueService $mileValueService;
    private ?UserPointValueService $userPointValueService;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->mileValueService = $I->grabService(MileValueService::class);
        $this->userPointValueService = $I->grabService(UserPointValueService::class);
    }

    public function dataList(\TestSymfonyGuy $I): void
    {
        $I->saveCsrfToken();
        $providerCode = 'code' . StringUtils::getRandomCode(12);
        $providerId = $I->createAwProvider(
            $providerName = 'provider' . StringUtils::getRandomCode(12),
            $providerCode,
            ['Kind' => PROVIDER_KIND_AIRLINE]
        );
        $I->haveInDatabase('ProviderMileValue', [
            'ProviderID' => $providerId,
            'RegionalEconomyMileValue' => '1.23',
            'GlobalEconomyMileValue' => '3.21',
            'RegionalBusinessMileValue' => '2.34',
            'GlobalBusinessMileValue' => '4.32',
            'AvgPointValue' => '3.45',
            'Status' => ProviderMileValue::STATUS_ENABLED,
            'CertifiedByUserID' => $this->user->getId(),
        ]);
        $I->fillMileValueData([$providerId]);
        $this->userPointValueService->setProviderUserPointValue($this->user->getId(), $providerId, 6.78);
        $I->sendPost($this->router->generate('awm_mile_value_data'));
        $data = $I->grabDataFromResponseByJsonPath('$..[?(@.id = ' . $providerId . ')]')[0];
        $expected = [
            'id' => $providerId,
            'name' => $providerName,
            'value' => [
                'primary' => [
                    'value' => '6.78¢',
                    'raw' => '6.78',
                ],
                'secondary' => [
                    'value' => '3.45¢',
                    'raw' => '3.45',
                ],
            ],
            'custom' => false,
            'flightClass' => [
                'economy' => [
                    'global' => '3.21¢',
                    'regional' => '1.23¢',
                ],
                'business' => [
                    'global' => '4.32¢',
                    'regional' => '2.34¢',
                ],
            ],
        ];
        $I->assertArrayContainsArray($expected, $data);

        // + Provider Code
        $I->setMobileVersion('4.49.0');
        $I->sendPost($this->router->generate('awm_mile_value_data'));
        $data = $I->grabDataFromResponseByJsonPath('$..[?(@.id = ' . $providerId . ')]')[0];
        $expected['code'] = $providerCode;
        $I->assertArrayContainsArray($expected, $data);
    }
}
