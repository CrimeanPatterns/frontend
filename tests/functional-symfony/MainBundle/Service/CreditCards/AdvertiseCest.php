<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\CreditCards\Advertise;
use AwardWallet\Tests\FunctionalSymfony\Mobile\AbstractCest;
use Codeception\Example;
use Symfony\Component\Routing\Router;

/**
 * @group frontend-functional
 */
class AdvertiseCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Router */
    private $router;

    /** @var Advertise */
    private $advertiseService;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->advertiseService = $I->grabService(Advertise::class);
    }

    /**
     * @dataProvider adsDataOnAccountListDataProvider
     */
    public function adsDataOnAccountList(\TestSymfonyGuy $I, Example $example)
    {
        $I->haveInDatabase(
            'CreditCard',
            [
                'ProviderID' => Provider::CHASE_ID,
                'Name' => 'Chase' . bin2hex(random_bytes(8)),
                'DisplayNameFormat' => 'Chase',
                'IsBusiness' => 0,
                'Patterns' => 'Chase',
                'MatchingOrder' => 10,
                'ClickURL' => 'https://awardwallet.com',
                'CardFullName' => 'Chase',
                'VisibleInList' => 1,
                'DirectClickUrl' => 'https://awardwallet.com',
                'PictureVer' => time(),
                'PictureExt' => 'png',
                'SortIndex' => 1,
                'Text' => 'info',
            ]);

        $userId = $I->createAwUser(null, null, [
            'CountryID' => Country::UNITED_STATES,
            'AccountLevel' => $example['AccountLevel'],
            'ListAdsDisabled' => $example['ListAdsDisabled'],
        ]);
        $I->createAwAccount($userId, Provider::CHASE_ID, 'chase');
        // $I->createAwAccount($userId, Provider::AMEX_ID, 'amex');
        $I->createAwAccount($userId, Provider::AA_ID, 'aa');
        $I->createAwAccount($userId, Provider::MARRIOTT_ID, 'marriott');

        /** @var Usr $user */
        $user = $I->grabService('doctrine.orm.default_entity_manager')->getRepository(Usr::class)->find($userId);

        $I->amOnPage($this->router->generate(\AccountListPage::$router, ['_switch_user' => $user->getLogin()]));

        $html = $I->grabPageSource();
        $I->assertNotEmpty($html);
        $json = \json_decode($I->grabAttributeFrom('//div[@id="update-all-account-container"]', 'data-adsdata'), true);
        $I->assertEquals($example['expectingAds'], count($json) > 0);

        $ads = $this->advertiseService->getListByUser($user);
        $I->assertEquals($example['expectingAds'], count($ads) > 0);

        $adRequired = ['id', 'priority', 'image', 'title', 'description', 'link', 'visible'];

        foreach ($json as $kind => $ad) {
            foreach ($adRequired as $key) {
                $I->assertEquals($ads[$kind]->{$key}, $json[$kind][$key]);
            }
        }
    }

    private function adsDataOnAccountListDataProvider(): array
    {
        return [
            [
                'ListAdsDisabled' => 0,
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'expectingAds' => true,
            ],
            [
                'ListAdsDisabled' => 1,
                'AccountLevel' => ACCOUNT_LEVEL_FREE,
                'expectingAds' => true,
            ],
            [
                'ListAdsDisabled' => 0,
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'expectingAds' => true,
            ],
            [
                'ListAdsDisabled' => 1,
                'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
                'expectingAds' => false,
            ],
        ];
    }
}
