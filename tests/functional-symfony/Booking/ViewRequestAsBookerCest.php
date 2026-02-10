<?php

namespace Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\AbSegment;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

/**
 * @group booking
 * @group frontend-functional
 */
class ViewRequestAsBookerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
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

    /**
     * @var int
     */
    protected $bookerId;

    /**
     * @var int
     */
    protected $businessId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService("router");

        /** @var EntityManager $em */
        $this->em = $I->grabService("doctrine")->getManager();
        $login = "bkr_" . StringUtils::getRandomCode(20);
        $this->bookerId = $I->createAwUser($login);
        $code = "bb_" . StringUtils::getRandomCode(10);
        $this->businessId = $I->createBusinessUserWithBookerInfo(null, [], ['AutoReplyMessage' => 'Hello from ' . $code, "ServiceName" => $code]);
        $I->connectUserWithBusiness($this->bookerId, $this->businessId, ACCESS_BOOKING_MANAGER);
        $this->request = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($I->createAbRequest([
            "UserID" => $I->createAwUser(),
            "BookerUserID" => $this->businessId,
        ]));
        $I->amOnBusiness();
        $I->amOnPage($this->router->generate("aw_booking_list_queue", ['_switch_user' => $login]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = null;
        $this->request = null;
        $this->em = null;
    }

    public function testAccessDenied(\TestSymfonyGuy $I)
    {
        $otherBusinessId = $I->createBusinessUserWithBookerInfo();
        $newRequestId = $I->createAbRequest([
            "UserID" => $this->request->getUser()->getUserid(),
            "BookerUserID" => $otherBusinessId,
        ]);

        $I->amOnPage($this->router->generate("aw_booking_view_index", ['id' => $newRequestId]));
        $I->seeResponseCodeIs(403);
    }

    public function testRequestDetails(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate("aw_booking_view_index", ['id' => $id = $this->request->getAbRequestID()]));
        $I->seeResponseCodeIs(200);

        $I->seeInTitle("Booking Request #" . $id);
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
            "booking_request_message" => ["Post" => "Test message #1"],
        ]);
        $I->seeResponseContainsJson(["success" => true]);
        $I->assertEquals(1, $I->grabCountFromDatabase("AbMessage", ["RequestID" => $id]));
    }

    public function testSendInternalMessage(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $I->saveCsrfToken();
        $route = $this->router->generate("aw_booking_message_ajaxaddmessage", ["id" => $id]);

        $I->sendPOST($route, [
            "booking_request_message" => ["Post" => "Test message #1", "Internal" => 1],
        ]);
        $I->seeResponseContainsJson(["success" => true]);
        $I->assertEquals(1, $I->grabCountFromDatabase("AbMessage", ["RequestID" => $id, "Type" => AbMessage::TYPE_INTERNAL]));
    }

    public function testEditMessage(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $I->saveCsrfToken();

        $messageId = $I->haveInDatabase("AbMessage", [
            "RequestID" => $id,
            "UserID" => $this->bookerId,
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
            "UserID" => $this->bookerId,
            "Post" => "Abc",
            "CreateDate" => date("Y-m-d"),
        ]);
        $route = $this->router->generate("aw_booking_message_ajaxdeletemessage", ["id" => $id, "messageId" => $messageId]);
        $I->sendPOST($route);
        $I->seeResponseContainsJson(["success" => true]);
        $I->dontSeeInDatabase("AbMessage", ["AbMessageID" => $messageId]);
    }

    public function testAddInvoice(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $route = $this->router->generate("aw_booking_message_createinvoice", ["id" => $id]);

        $I->sendPOST($route, [
            "booking_request_invoice" => [
                "Items" => [
                    ["description" => "Award Booking Service Fee", "quantity" => 1, "price" => 100, "discount" => 0],
                ],
                "Miles" => [
                    ["CustomName" => "Xxx", "Owner" => "Billy Villy", "Balance" => 100],
                ],
                "_token" => $I->grabTextFrom("//*[@name='booking_request_invoice[_token]']/@value"),
            ],
        ]);
        $I->seeResponseContainsJson(["status" => "success"]);
    }

    public function testSeatAssignments(\TestSymfonyGuy $I)
    {
        $I->amOnRoute("aw_booking_view_index", ["id" => $id = $this->request->getAbRequestID()]);
        $route = $this->router->generate("aw_booking_message_seatassignments", ["id" => $id]);

        $I->sendPOST($route, [
            "form" => [
                "PhoneNumbers" => [
                    ["Provider" => "Test Provider", "Phone" => "12345"],
                    ["Provider" => "Test Provider", "Phone" => "54321"],
                ],
                "_token" => $I->grabTextFrom("//*[@name='form[_token]']/@value"),
            ],
        ]);
        $I->seeResponseContainsJson(["status" => "success"]);
    }
}
