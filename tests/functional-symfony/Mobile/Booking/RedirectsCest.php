<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Booking;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Codeception\Module\Aw;
use Codeception\Scenario;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-functional
 * @group mobile
 */
class RedirectsCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    public const MOBILE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3';

    public function redirectOnPersonalInterface(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $requestId = $I->createAbRequest(['UserID' => $this->user->getUserid()]);
        $I->haveHttpHeader('User-Agent', self::MOBILE_USER_AGENT);
        $I->followRedirects(false);

        $I->sendGET($this->getFullRoute($I, 'host', 'aw_booking_view_index', ['id' => $requestId]));

        $I->seeResponseCodeIs(302);
        assertEquals("/m/booking/{$requestId}/details", $I->grabHttpHeader('Location'));
    }

    public function noRedirectOnBusinessInterface(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $requestId = $I->createAbRequest(['UserID' => $this->user->getUserid()]);
        /** @var UsrRepository $usrRep */
        $usrRep = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $bookerId = $I->createAwBookerStaff('bkr' . StringHandler::getRandomCode(10), Aw::DEFAULT_PASSWORD);
        /** @var Usr $booker */
        $booker = $usrRep->find($bookerId);
        $requestId = $I->createAbRequest([
            'UserID' => $this->user->getUserid(),
            'BookerUserID' => $usrRep->getBusinessByUser($booker)->getUserid(),
        ]);
        $I->sendGET($this->getFullRoute($I, 'business_host', 'aw_profile_overview') . '?_switch_user=' . $booker->getLogin());

        $I->haveHttpHeader('User-Agent', self::MOBILE_USER_AGENT);
        $I->followRedirects(false);

        $I->sendGET($this->getFullRoute($I, 'business_host', 'aw_booking_view_index', ['id' => $requestId]));

        $I->seeResponseCodeIs(200);
        $I->dontSeeHttpHeader('Location');
    }

    /**
     * @param array $routeParams
     * @return string
     */
    protected function getFullRoute(\TestSymfonyGuy $I, $hostParam, $route, $routeParams = [])
    {
        $container = $I->grabService('service_container');

        return
            $container->getParameter('requires_channel') .
            '://' .
            $container->getParameter($hostParam) .
            $container->get('router')->generate($route, $routeParams)
        ;
    }
}
