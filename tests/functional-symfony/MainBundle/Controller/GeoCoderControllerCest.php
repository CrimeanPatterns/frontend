<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ReverseGeoCoder;
use Codeception\Util\Stub;

/**
 * @group frontend-functional
 */
class GeoCoderControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var string
     */
    private $login;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = 'user_' . StringUtils::getRandomCode(10);
        $I->createAwUser($this->login);
    }

    public function queryTest(\TestSymfonyGuy $I)
    {
        $I->executeQuery("delete from GeoTag where FoundAddress = 'New York'");
        $I->amOnRoute('geo_coder_query', ['query' => 'New York', '_switch_user' => $this->login]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'foundaddress' => 'New York',
            'timeZoneLocation' => 'America/New_York',
        ]);
    }

    public function reverseQueryTest(\TestSymfonyGuy $I)
    {
        $geoTag = (new Geotag())
            ->setFoundaddress('157 N Main St, Valentine, NE 69201, USA')
            ->setTimeZoneLocation('America/Chicago')
            ->setLat(42.87278329999999)
            ->setLng(-100.5509669);
        $I->mockService(ReverseGeoCoder::class, $I->stubMake(ReverseGeoCoder::class, [
            'reverseQuery' => Stub::once(function ($lat, $lng) use ($I, $geoTag) {
                $I->assertEquals($lat, $geoTag->getLat());
                $I->assertEquals($lng, $geoTag->getLng());

                return [$geoTag];
            }),
        ]));
        $I->amOnRoute('geo_coder_reverse_query', [
            'lat' => $geoTag->getLat(),
            'lng' => $geoTag->getLng(),
            '_switch_user' => $this->login,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->assertArrayContainsArray([
            'foundaddress' => '157 N Main St, Valentine, NE 69201, USA',
            'timeZoneLocation' => 'America/Chicago',
        ], $I->grabDataFromResponseByJsonPath('$[0]'));
    }
}
