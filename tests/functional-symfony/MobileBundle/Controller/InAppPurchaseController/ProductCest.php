<?php

namespace AwardWallet\Tests\FunctionalSymfony\MobileBundle\Controller\InAppPurchaseController;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleStore;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Codeception\Example;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group mobile
 * @group mobile/billing
 * @group billing
 */
class ProductCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $I->sendGET('/m/api/data' . "?_switch_user=" . $this->user->getLogin());
        $I->haveHttpHeader('Content-Type', 'application/json');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        parent::_after($I);
    }

    /**
     * @dataProvider getSubscriptionDataProvider
     */
    public function getSubscription(\TestSymfonyGuy $I, Example $example)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, $example['platform']);
        $I->sendGET($this->router->generate("aw_mobile_purchase_subscription"));
        $I->seeResponseContainsJson(['productId' => $example['productId']]);
    }

    public function reviewerRole(\TestSymfonyGuy $I)
    {
        $I->executeQuery("update Usr set DiscountedUpgradeBefore = null where UserID = {$this->user->getUserid()}");
        $siteGroupID = $I->haveInDatabase("SiteGroup", ["GroupName" => "Reviewers"]);
        $I->haveInDatabase("GroupUserLink", [
            "SiteGroupID" => $siteGroupID,
            "UserID" => $this->user->getUserid(),
        ]);

        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'ios');
        $I->sendGET($this->router->generate("aw_mobile_purchase_subscription"));
        $I->dontSeeResponseContainsJson(['productId' => AppleStore::PRODUCT_AWPLUS]);
        $I->seeResponseContainsJson(['productId' => AppleStore::PRODUCT_AWPLUS_SUBSCR]);
    }

    public function staffRole(\TestSymfonyGuy $I)
    {
        $I->executeQuery("update Usr set DiscountedUpgradeBefore = null where UserID = {$this->user->getUserid()}");
        $siteGroupID = $I->haveInDatabase("SiteGroup", ["GroupName" => "Staff"]);
        $I->haveInDatabase("GroupUserLink", [
            "SiteGroupID" => $siteGroupID,
            "UserID" => $this->user->getUserid(),
        ]);

        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'ios');
        $I->sendGET($this->router->generate("aw_mobile_purchase_subscription"));
        // no 1 week subscription in iOS
        $I->seeResponseContainsJson(['productId' => AppleStore::PRODUCT_AWPLUS_SUBSCR]);
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, 'android');
        $I->sendGET($this->router->generate("aw_mobile_purchase_subscription"));
        // no 1 week subscription in android
        $I->seeResponseContainsJson(['productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR]);
    }

    /**
     * @dataProvider getConsumablesDataProvider
     */
    public function getConsumables(\TestSymfonyGuy $I, Example $example)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_PLATFORM, $example['platform']);
        $I->sendGET($this->router->generate("aw_mobile_purchase_consumables"));
        $I->seeResponseContainsJson(['consumables' => $example['data'], 'count' => 0]);
    }

    private function getSubscriptionDataProvider()
    {
        return [
            ['platform' => 'android', 'productId' => GooglePlay::PRODUCT_AWPLUS_SUBSCR],
            ['platform' => 'ios', 'productId' => AppleStore::PRODUCT_AWPLUS_SUBSCR],
        ];
    }

    private function getConsumablesDataProvider()
    {
        return [
            ['platform' => 'android', 'data' => [
                [
                    'id' => '1_update_credit',
                ],
                [
                    'id' => '3_update_credit',
                ],
                [
                    'id' => '5_update_credit',
                ],
                [
                    'id' => '10_update_credit',
                ],
            ]],
            ['platform' => 'ios', 'data' => [
                [
                    'id' => '1_update_credit',
                ],
                [
                    'id' => '3_update_credits',
                ],
                [
                    'id' => '5_update_credits',
                ],
                [
                    'id' => '10_update_credits',
                ],
            ]],
        ];
    }
}
