<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile\Booking;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Scenario;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group mobile
 */
class EmailCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    /**
     * @var AbRequest
     */
    private $abRequest;

    /**
     * @var RouterInterface
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->abRequest = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)
            ->find($I->createAbRequest(['UserID' => $this->user->getUserid()]));

        $this->router = $I->grabService('router');
    }

    public function testResendEmailVerification(\TestSymfonyGuy $I, Scenario $scenario)
    {
        $I->saveCsrfToken();
        $requestId = $this->abRequest->getAbRequestID();
        // 404
        $I->sendPOST($this->router->generate('awm_newapp_booking_resend', [
            'abRequest' => time(),
        ]));
        $I->seeResponseCodeIs(404);

        // POST Method
        $I->sendGET($this->router->generate('awm_newapp_booking_resend', [
            'abRequest' => $requestId,
        ]));
        $I->seeResponseCodeIs(405);

        $I->sendPOST($this->router->generate('awm_newapp_booking_resend', [
            'abRequest' => $requestId,
        ]));

        $I->dontSeeResponseJsonMatchesJsonPath("$.error");
        $I->seeResponseContainsJson(["success" => true]);
    }
}
