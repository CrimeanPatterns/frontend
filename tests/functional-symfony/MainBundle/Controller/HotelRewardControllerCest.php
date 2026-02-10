<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class HotelRewardControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /** @var Router|null */
    private $router;

    public function _before(\TestSymfonyGuy $I): void
    {
        $this->router = $I->grabService('router');
        $user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($I->createAwUser(null, null, [], true));
        $I->amOnPage($this->router->generate('aw_hotelreward_index', ['_switch_user' => $user->getLogin()]));
    }

    public function indexActionTest(\TestSymfonyGuy $I): void
    {
        $I->amOnPage($this->router->generate('aw_hotelreward_index'));
        $I->seeInSource('<div id="content" data-primary-list="&#x5B;');
        $I->seeInSource('/hotel-reward.js');
    }

    public function placeActionTest(\TestSymfonyGuy $I): void
    {
        $placeId = 'ChIJOwg_06VPwokRYv534QaPC8g';
        $placeName = 'New York';
        $I->amOnRoute('geo_coder_query', ['query' => $placeName]);

        $I->sendAjaxGetRequest($this->router->generate('aw_hotelreward_place', ['place' => ['place_id' => $placeId, 'value' => $placeName]]));
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(
            [
                'placeId' => 'string',
                'hotels' => [
                    [
                        'hotelId' => 'integer',
                        'name' => 'string',
                        'brandName' => 'string',
                        'pointValue' => 'float',
                        'avgAboveValue' => 'integer',
                        'cashPrice' => 'float',
                        'pointPrice' => 'float',
                        'link' => 'string|null',
                        'location' => 'string',
                        'matchCount' => 'integer',
                    ],
                ],
            ]
        );
    }
}
