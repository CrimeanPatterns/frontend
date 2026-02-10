<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

/**
 * @group mobile
 * @group frontend-functional
 */
class LocaleCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const ABOUT_US_EN = 'is a service that can help you track any type of loyalty account and manage your travel plans';
    public const ABOUT_US_RU = 'балансы различных программ лояльности';

    public function testAboutUsDefault(\TestSymfonyGuy $I)
    {
        $this->mobileAboutUsFor($I, self::ABOUT_US_EN, 'en');
    }

    /*
    public function testAboutUsRussianHeader(\TestSymfonyGuy $I, $scenario)
    {
        if(time() < strtotime("2017-04-20"))
            $scenario->skip();
        $this->mobileAboutUsFor($I, self::ABOUT_US_RU, 'ru');
    }

    public function testAboutUsRussianQueryParam(\TestSymfonyGuy $I, $scenario)
    {
        if(time() < strtotime("2017-04-20"))
            $scenario->skip();
        $this->mobileAboutUsFor($I, self::ABOUT_US_RU, 'en', 'ru');
    }
    */

    protected function mobileAboutUsFor(\TestSymfonyGuy $I, $expectedText, $acceptHeader = null, $queryParam = null)
    {
        $I->assertFalse(null === $acceptHeader && null === $queryParam, 'provide locale options');

        $route = '/m/api/about';

        if (null !== $queryParam) {
            $route .= '?locale=' . $queryParam;
        }

        if (null !== $acceptHeader) {
            $I->haveHttpHeader('Accept-Language', $acceptHeader);
        }

        $I->sendGET($route);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains($expectedText);
    }
}
