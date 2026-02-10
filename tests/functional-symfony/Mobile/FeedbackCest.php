<?php

namespace AwardWallet\Tests\FunctionalSymfony\Mobile;

use AwardWallet\MainBundle\Entity\Mobilefeedback;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;

/**
 * @group mobile
 * @group frontend-functional
 */
class FeedbackCest extends AbstractCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        parent::createUserAndLogin($I, 'accounts-', 'userpass-', [], true);
    }

    public function testAddFeedback(\TestSymfonyGuy $I)
    {
        $I->haveHttpHeader(MobileHeaders::MOBILE_VERSION, $appVersion = '3.5.100500');
        $I->sendPOST($url = '/m/api/feedback/add', []);
        $I->seeResponseContainsJson(['error' => 'Undefined action']);

        $I->sendPOST($url, ['action' => 100]);
        $I->seeResponseContainsJson(['error' => 'Undefined action']);

        $this->accountSteps->loadData();
        $I->assertEquals([], $I->grabDataFromJsonResponse('profile.feedbacks'));

        $time = time();
        $I->sendPOST($url, ['action' => Mobilefeedback::ACTION_CONTACTUS]);
        sleep(2);
        $I->sendPOST($url, ['action' => Mobilefeedback::ACTION_RATE]);

        $I->seeResponseContainsJson(['success' => true]);

        $this->accountSteps->loadData();
        $feedbacks = $I->grabDataFromJsonResponse('profile.feedbacks');
        $I->assertEquals($feedbacks[0]['action'], Mobilefeedback::ACTION_RATE);
        $I->assertEquals($feedbacks[0]['appVersion'], $appVersion);
        $I->assertLessThanOrEqual(10, abs($time - $feedbacks[0]['date']));

        $I->assertEquals($feedbacks[1]['action'], Mobilefeedback::ACTION_CONTACTUS);
        $I->assertEquals($feedbacks[1]['appVersion'], $appVersion);
        $I->assertLessThanOrEqual(10, abs($time - $feedbacks[1]['date']));
    }
}
