<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use Doctrine\ORM\EntityManager;

/**
 * @group booking
 * @group frontend-functional
 */
class YcbScheduleCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $requestData = [];

    public function _before(\TestSymfonyGuy $I)
    {
        $this->requestData['fname'] = 'test' . $I->grabRandomString();
        $this->requestData['lname'] = 'test' . $I->grabRandomString();
        $this->requestData['email'] = 'test@' . $I->grabRandomString();
        $this->requestData['request_id'] = $I->createAbRequest();

        /** @var EntityManager $em */
        $em = $I->grabService('doctrine')->getManager();
        $abRep = $em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);
        $abRequest = $abRep->find($this->requestData['request_id']);
        $this->requestData['request_code'] = $abRequest->getHash();
    }

    public function testAddSchedulingMessage(\TestSymfonyGuy $I)
    {
        $I->wantTo("test adding scheduling message to booking request");

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST("/awardBooking/ycb/schedule", $this->requestData);
        $I->see('Request found: ' . $this->requestData['request_id']);

        $messages = $this->getAbrequest($I)->getMessages();
        $I->assertEquals(2, $messages->count());
    }

    public function testAddReschedulingMessage(\TestSymfonyGuy $I)
    {
        $I->wantTo("test adding rescheduling message to booking request");

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST("/awardBooking/ycb/reschedule", $this->requestData);
        $I->see('Request found: ' . $this->requestData['request_id']);

        $messages = $this->getAbrequest($I)->getMessages();
        $I->assertEquals(2, $messages->count());
    }

    public function testAddCancelMessage(\TestSymfonyGuy $I)
    {
        $I->wantTo("test adding cancel message to booking request");

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST("/awardBooking/ycb/cancel", $this->requestData);
        $I->see('Request found: ' . $this->requestData['request_id']);

        $messages = $this->getAbrequest($I)->getMessages();
        $I->assertEquals(2, $messages->count());
    }

    /**
     * @return AbRequest
     */
    private function getAbrequest(\TestSymfonyGuy $I)
    {
        /** @var AbRequestRepository $abRequestRep */
        $abRequestRep = $I->grabService('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class);

        /** @var AbRequest $abRequest */
        $abRequest = $abRequestRep->find($this->requestData['request_id']);

        return $abRequest;
    }
}
