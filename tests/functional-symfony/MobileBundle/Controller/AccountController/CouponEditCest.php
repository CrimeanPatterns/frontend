<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\AccountController;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\JsonHeaders;
use AwardWallet\Tests\FunctionalSymfony\Traits\LatestMobileApp;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;

/**
 * @group frontend-functional
 * @group mobile
 */
class CouponEditCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;
    use JsonHeaders;
    use LatestMobileApp;

    public function changeOwner(\TestSymfonyGuy $I)
    {
        $couponId = $I->createAwCoupon($this->user->getUserid(), 'some', '100');
        $useragentId = $I->createFamilyMember($this->user->getUserid(), 'somename', 'somename');
        $I->sendGET("/m/api/coupon/{$couponId}");
        $I->assertArrayContainsArray(
            [
                ['value' => $this->user->getUserid()],
                ['value' => $this->user->getUserid() . '_' . $useragentId],
            ],
            $I->grabDataFromResponseByJsonPath('$..[?(@.name = "owner")]')[0]
        );

        $form = array_combine(
            $I->grabDataFromJsonResponse('formData.children.*.name'),
            $I->grabDataFromJsonResponse('formData.children.*.value')
        );
        $form['owner'] = $this->user->getUserid() . '_' . $useragentId;
        $I->sendPUT("/m/api/coupon/{$couponId}", $form);
        $I->seeResponseContainsJson([
            'account' => [
                'ID' => $couponId,
            ],
        ]);
        $I->seeInDatabase('ProviderCoupon', [
            'ProviderCouponID' => $couponId,
            'UserAgentID' => $useragentId,
            'UserID' => $this->user->getUserid(),
        ]);
    }

    public function attachToAccount(\TestSymfonyGuy $I)
    {
        $providerId = $I->createAwProvider($providerName = 'atoa' . StringUtils::getPseudoRandomString(8), $providerName, [
            'Kind' => 1,
            'DisplayName' => $providerName,
            'State' => PROVIDER_ENABLED,
            'KeyWords' => $providerName,
        ]);
        $accountId = $I->createAwAccount($this->user->getUserid(), $providerId, 'some', null, ['Balance' => 100500]);
        $couponId = $I->createAwCoupon($this->user->getUserid(), 'some', '100');
        $I->sendGET("/m/api/coupon/{$couponId}");
        $I->assertArrayContainsArray(
            [[
                'value' => '',
                'label' => 'Standalone',
            ]],
            $I->grabDataFromResponseByJsonPath('$..[?(@.name = "account")].choices')[0]
        );
        $form = array_combine(
            $I->grabDataFromJsonResponse('formData.children.*.name'),
            $I->grabDataFromJsonResponse('formData.children.*.value')
        );

        $I->sendPOST("/m/api/providers/completion/1", ['queryString' => $providerName]);
        $I->seeResponseContainsJson([
            [
                'value' => $providerName,
                'label' => $providerName,
                'kind' => '1',
                'additionalData' => [
                    'attachAccounts' => [
                        $this->user->getUserid() => [
                            [
                                'value' => $accountId,
                                'label' => 'some (100,500)',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $a = 1;

        $form['account'] = $accountId;
        $form['programname'] = $providerName;
        $I->sendPUT("/m/api/coupon/{$couponId}", $form);
        $I->seeInDatabase('ProviderCoupon', [
            'ProviderCouponID' => $couponId,
            'AccountID' => $accountId,
        ]);
    }
}
