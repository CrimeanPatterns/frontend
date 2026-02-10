<?php

namespace Booking;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbSegment;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

/**
 * @group booking
 * @group frontend-functional
 */
class ViewRequestCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;
    use LoggedIn;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var AbRequest
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService("router");

        /** @var EntityManager $em */
        $this->em = $I->grabService("doctrine")->getManager();
        $this->request = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($I->createAbRequest([
            "UserID" => $this->user->getUserid(),
        ]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $this->request = null;
        $this->em = null;
        parent::_after($I);
    }

    public function testAccessDenied(\TestSymfonyGuy $I)
    {
        $newUser = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)
            ->find($I->createAwUser());
        $I->executeQuery("UPDATE AbRequest SET UserID = " . $newUser->getUserid() . " WHERE AbRequestID = " . $this->request->getAbRequestID());

        $I->amOnRoute("aw_booking_view_index", ["id" => $this->request->getAbRequestID()]);
        $I->seeResponseCodeIs(403);
    }

    public function testRequestDetails(\TestSymfonyGuy $I)
    {
        $requestId = $this->request->getAbRequestID();
        $I->amOnRoute("aw_booking_view_index", ["id" => $requestId]);
        $I->seeResponseCodeIs(200);
        $I->seeInTitle("Booking Request #" . $requestId);
        $I->see("Opened", "#request-status");

        $refIcon = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getRefIcon($this->request, 'medium');
        $I->seeInSource($refIcon);
        $I->seeLink("Cancel this request");
        $I->seeLink("Edit");
        $I->seeNumberOfElements("//*[@id=\"table-travelers\"]/tr", $this->request->getPassengers()->count());
        $segments = $this->request->getSegments();
        /** @var AbSegment $firstSegment */
        $firstSegment = $segments->first();
        $I->seeNumberOfElements("//*[@id=\"table-destinations\"]/tr",
            $segments->count() === 1 && $firstSegment->isRoundTrip() ? 2 : $segments->count());

        $miles = sizeof($this->request->getAccounts()) + sizeof($this->request->getCustomPrograms());
        $I->seeNumberOfElements("//*[@id=\"lp-table\"]/tr", $miles);
        $I->see($this->request->getBooker()->getCompany(), "#commonMessages");
    }

    public function testRedirectFromList(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_list_requests");
        $I->seeResponseCodeIs(200);
        $I->seeInTitle("Booking Request #");
    }

    public function testSendMessage(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $I->saveCsrfToken();
        $route = $this->router->generate("aw_booking_message_ajaxaddmessage", ["id" => $id]);

        $I->sendPOST($route, [
            "booking_request_message" => ["Post" => ""],
        ]);
        $I->seeResponseContainsJson(["errors" => ["Post" => "This value should not be blank."]]);

        $I->sendPOST($route, [
            "booking_request_message" => ["Post" => str_repeat("x", 30000)],
        ]);
        $I->seeResponseContainsJson(["errors" => ["Post" => "This value is too long. It should have 20000 characters or less."]]);

        $I->sendPOST($route, [
            "booking_request_message" => ["Post" => "Test message #1"],
        ]);
        $I->seeResponseContainsJson(["success" => true]);
        $I->assertEquals(1, $I->grabCountFromDatabase("AbMessage", ["RequestID" => $id]));
    }

    public function testEditMessage(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $I->saveCsrfToken();

        $messageId = $I->haveInDatabase("AbMessage", [
            "RequestID" => $id,
            "UserID" => $this->user->getUserid(),
            "Post" => "Abc",
            "CreateDate" => date("Y-m-d"),
        ]);
        $route = $this->router->generate("aw_booking_message_ajaxeditmessage", ["id" => $id, "messageId" => $messageId]);
        $I->sendPOST($route, [
            "booking_request_edit_message" => ["Post" => $newText = "Test Edit"],
        ]);
        $I->seeResponseContainsJson(["success" => true]);
        $I->seeInDatabase("AbMessage", ["AbMessageID" => $messageId, "Post" => $newText]);
    }

    public function testDeleteMessage(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $I->saveCsrfToken();

        $messageId = $I->haveInDatabase("AbMessage", [
            "RequestID" => $id,
            "UserID" => $this->user->getUserid(),
            "Post" => "Abc",
            "CreateDate" => date("Y-m-d"),
        ]);
        $route = $this->router->generate("aw_booking_message_ajaxdeletemessage", ["id" => $id, "messageId" => $messageId]);
        $I->sendPOST($route);
        $I->seeResponseContainsJson(["success" => true]);
        $I->dontSeeInDatabase("AbMessage", ["AbMessageID" => $messageId]);
    }
}
