<?php

namespace Booking;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\FreeUser;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;

/**
 * @group booking
 * @group frontend-functional
 */
class AddRequestCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use FreeUser;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var string
     */
    protected $routeAddRequest;

    /**
     * @var array
     */
    protected $requestData = [
        'partner' => 'pointimize',
    ];

    /**
     * @var int
     */
    protected $defaultBooker;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService("router");
        $this->routeAddRequest = $this->router->generate('aw_booking_add_index');
        $this->requestData['json'] = file_get_contents(__DIR__ . '/../../_data/Booking/pointimize.json');
        $I->resetLocker("booking_add", $I->getClientIp());
        $I->executeQuery("UPDATE AbBookerInfo SET SmtpServer = NULL");
        $this->defaultBooker = $I->getContainer()->get(DefaultBookerParameter::class)->get();
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->requestData['json'] = null;
        $this->router = null;
        $this->defaultBooker = null;

        parent::_after($I);
    }

    public function testUnauthUserViaRegister(\TestSymfonyGuy $I)
    {
        $I->wantTo("add request by unauth user via registration");

        $I->amOnPage($this->routeAddRequest);
        $I->saveCsrfToken();
        $I->see("Create Login");
        $I->see("User name");
        $I->see("Password");
        $I->see("Confirm password");

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');

        $I->loadPage('POST', $this->routeAddRequest, self::getFakeAbRequestData($this->user));
        $I->seeResponseCodeIs(200);
        $I->see("This user name is already taken");

        $newUser = (new Usr())
            ->setLogin($newLogin = $I->grabRandomString(5) . time() . "newrequest")
            ->setEmail($newEmail = "$newLogin@mail.com")
            ->setFirstname("Billy")
            ->setLastname("Villy");

        $I->loadPage('POST', $this->routeAddRequest, self::getFakeAbRequestData($newUser));
        $I->seeResponseCodeIs(200);
        $I->see("Booking Request #");
        $I->see("Email Not Verified");
        $request = $this->assertTimelineSharing($I);
        $id = $request->getAbRequestID();
        $I->seeElement("#not_verified_popup");
        $I->seeEmailTo($newEmail, "Award Booking Request #$id");

        $I->loadPage('GET', $this->router->generate('aw_booking_view_index', ["id" => $id, "conf" => $request->getConfirmationCode()]), []);
        $I->seeInDatabase('VerifiedEmail', ['email' => $newEmail]);
        $I->seeResponseCodeIs(200);
        $I->dontSee("Email Not Verified");
        $I->see("Opened", "#request-status");
    }

    public function testAuthUser(\TestSymfonyGuy $I)
    {
        $I->wantTo("add request by auth user");

        $I->amOnPage($this->routeAddRequest . "?_switch_user=" . $this->user->getLogin());
        $I->saveCsrfToken();
        $I->dontSee("Create Login");

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('POST', $this->routeAddRequest, self::getFakeAbRequestData());
        $I->seeResponseCodeIs(200);
        $I->see("Booking Request #");
        $I->see("Email Not Verified", "#request-status");
        $this->assertTimelineSharing($I);
    }

    public function testVerifiedEmail(\TestSymfonyGuy $I)
    {
        $email = self::getFakeAbRequestData()['booking_request']['ContactEmail'];
        $I->createAbRequest([
            'BookerUserID' => $this->defaultBooker,
            'UserID' => $this->user->getUserid(),
            'ContactEmail' => $email,
        ]);
        $datetime = new \DateTime();
        $I->haveInDatabase('VerifiedEmail', ['Email' => $email, 'VerificationDate' => $datetime->format("Y-m-d H:i:s")]);

        $I->amOnPage($this->routeAddRequest . "?_switch_user=" . $this->user->getLogin());
        $I->saveCsrfToken();
        $I->dontSee("Create Login");

        $I->loadPage('POST', $this->routeAddRequest, self::getFakeAbRequestData());
        $id = $this->grabAbRequestId($I);
        $conf = substr(sha1($id . $email), 0, 20);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('GET', $this->router->generate('aw_booking_view_index', ["id" => $id, "conf" => $conf]), []);

        $I->seeResponseCodeIs(200);
        $I->see("Booking Request #");
        $I->dontSee("Email Not Verified", "#request-status");
    }

    public function testNoValidEmail(\TestSymfonyGuy $I)
    {
        $I->createAbRequest([
            'BookerUserID' => $this->defaultBooker,
            'UserID' => $this->user->getUserid(),
            'ContactEmail' => self::getFakeAbRequestData()['booking_request']['ContactEmail'],
            'Status' => AbRequest::BOOKING_STATUS_NOT_VERIFIED,
        ]);

        $I->amOnPage($this->routeAddRequest . "?_switch_user=" . $this->user->getLogin());
        $I->saveCsrfToken();
        $I->dontSee("Create Login");

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('POST', $this->routeAddRequest, self::getFakeAbRequestData());
        $I->seeResponseCodeIs(200);
        $I->see("Booking Request #");
        $I->see("Email Not Verified", "#request-status");
    }

    public function testValidEmail(\TestSymfonyGuy $I)
    {
        $I->createAbRequest([
            'BookerUserID' => $this->defaultBooker,
            'UserID' => $this->user->getUserid(),
            'ContactEmail' => self::getFakeAbRequestData()['booking_request']['ContactEmail'],
            'Status' => AbRequest::BOOKING_STATUS_PENDING,
        ]);

        $I->amOnPage($this->routeAddRequest . "?_switch_user=" . $this->user->getLogin());
        $I->saveCsrfToken();
        $I->dontSee("Create Login");

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('POST', $this->routeAddRequest, self::getFakeAbRequestData());
        $I->seeResponseCodeIs(200);
        $I->see("Booking Request #");
        $I->dontSee("Email Not Verified", "#request-status");
    }

    public function testValidation(\TestSymfonyGuy $I)
    {
        $I->wantTo("test form validation");

        $I->amOnPage($this->routeAddRequest);
        $I->saveCsrfToken();
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $user = (new Usr())
            ->setLogin($newLogin = $I->grabRandomString(5) . time() . "newrequest")
            ->setEmail($newEmail = "$newLogin@mail.com")
            ->setFirstname("Billy")
            ->setLastname("Villy");

        $this->validate($I, $user, function (&$fields) {
            $fields["booking_request"]["User"]["firstname"] = "";
        }, "This value should not be blank", "//*[@data-key='firstname']//*[@data-error-type]");
        $this->validate($I, $user, function (&$fields) {
            $fields["booking_request"]["User"]["lastname"] = "";
        }, "This value should not be blank", "//*[@data-key='lastname']//*[@data-error-type]");
        $this->validate($I, $user, function (&$fields) {
            $fields["booking_request"]["User"]["lastname"] = str_repeat("x", 1000);
        }, "This value is too long", "//*[@data-key='lastname']//*[@data-error-type]");
        $this->validate($I, $user, function (&$fields) {
            $fields["booking_request"]["User"]["email"]["Email"] = "";
        }, "Email addresses do not match", "//*[@data-key='Email']//*[@data-error-type]");
        $this->validate($I, $user, function (&$fields) {
            $fields["booking_request"]["User"]["email"]["Email"] = str_repeat("x", 1000);
        }, "Email addresses do not match", "//*[@data-key='Email']//*[@data-error-type]");
        $this->validate($I, $user, function (&$fields) {
            $fields["booking_request"]["User"]["email"]["Email"] = str_repeat("x", 1000);
            $fields["booking_request"]["User"]["email"]["ConfirmEmail"] = str_repeat("x", 1000);
        }, "This is not a valid email address", "//*[@data-key='Email']//*[@data-error-type]");

        // authorization user
        $formData = self::getFakeAbRequestData($user);
        $formData["booking_request"]["ContactName"] = "";
        $I->loadPage('POST', $this->routeAddRequest, $formData);

        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["ContactName"] = "";
        }, "This value should not be blank", "//*[@data-key='ContactName']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["ContactName"] = str_repeat("x", 1000);
        }, "This value is too long", "//*[@data-key='ContactName']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["ContactEmail"] = "";
        }, "This value should not be blank", "//*[@data-key='ContactEmail']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["ContactEmail"] = str_repeat("x", 1000);
        }, "This value is not a valid email address", "//*[@data-key='ContactEmail']//*[@data-error-type]");

        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Passengers"][0]["FirstName"] = "";
        }, "This value should not be blank", "//*[@data-key='FirstName']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Passengers"][0]["FirstName"] = str_repeat("x", 100);
        }, "This value is too long", "//*[@data-key='FirstName']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Passengers"][0]["LastName"] = "";
        }, "This value should not be blank", "//*[@data-key='LastName']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Passengers"][0]["LastName"] = str_repeat("x", 100);
        }, "This value is too long", "//*[@data-key='LastName']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Passengers"][0]["Birthday"] = 12345;
        }, "This value is not valid", "//*[@data-key='Birthday']//*[@data-error-type]");

        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["Dep"] = "";
        }, "This value should not be blank", "//*[@data-key='Dep']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["Dep"] = "A";
        }, "This value is too short", "//*[@data-key='Dep']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["Dep"] = str_repeat("x", 1000);
        }, "This value is too long", "//*[@data-key='Dep']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["Arr"] = "";
        }, "This value should not be blank", "//*[@data-key='Arr']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["Arr"] = "A";
        }, "This value is too short", "//*[@data-key='Arr']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["Arr"] = str_repeat("x", 1000);
        }, "This value is too long", "//*[@data-key='Arr']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["DepDateIdeal"] = 123;
        }, "This value is not valid", "//*[@data-key='DepDateIdeal']//*[@data-error-type]");
        $this->validate($I, null, function (&$fields) {
            $fields["booking_request"]["Segments"][0]["ReturnDateIdeal"] = 123;
        }, "This value is not valid", "//*[@data-key='ReturnDateIdeal']//*[@data-error-type]");
    }

    public function testPopulateAbRequestFromJson(\TestSymfonyGuy $I)
    {
        $I->wantTo("test populate booking request via json");

        $I->amOnPage($this->routeAddRequest);
        $I->saveCsrfToken();

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('POST', $this->routeAddRequest . "?ref=147#pointimize", $this->requestData);

        $I->seeInField('#booking_request_User_firstname', 'Alexi');
        $I->seeInField('#booking_request_User_lastname', 'Vereschaga');
        $I->seeInField('#booking_request_User_email_Email', 'veresch111@gmail.com');
        $I->seeInField('#booking_request_Notes', 'There are additional notes');

        $I->seeInField('#booking_request_CustomPrograms_0_Name', 'United Airlines (Mileage Plus)');
        $I->seeInField('#booking_request_CustomPrograms_1_Name', 'American Airlines (AAdvantage)');

        $I->seeInField('#booking_request_Passengers_0_FirstName', 'Alexi');
        $I->seeInField('#booking_request_Passengers_0_LastName', 'Vereschaga');
        $I->seeInField('#booking_request_Passengers_1_FirstName', 'John');
        $I->seeInField('#booking_request_Passengers_1_LastName', 'Jameson');
    }

    public function testIgnoreJsonRef(\TestSymfonyGuy $I)
    {
        $I->wantTo("test ignore json with wrong referral");

        $I->amOnPage($this->routeAddRequest);
        $I->saveCsrfToken();

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->loadPage('POST', $this->routeAddRequest, $this->requestData);

        $I->dontSeeInField('#booking_request_User_firstname', 'Alexi');
    }

    public function testDefaultBooker(\TestSymfonyGuy $I)
    {
        $I->wantTo("setting default booker regardless of preferred cabin of service");

        $I->amOnPage($this->routeAddRequest . "?_switch_user=" . $this->user->getLogin());
        $I->saveCsrfToken();

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $formData = self::getFakeAbRequestData();
        $formData['booking_request']['CabinFirst'] = 1;
        $formData['booking_request']['CabinEconomy'] = 1;
        $formData['booking_request']['CabinBusiness'] = 1;
        $I->loadPage('POST', $this->routeAddRequest, $formData);
        $I->see("Booking Request #");
        $id = $this->grabAbRequestId($I);
        $I->seeInDatabase('AbRequest', ['AbRequestID' => $id, 'BookerUserID' => $this->defaultBooker]);

        $formData['booking_request']['CabinFirst'] = 0;
        $formData['booking_request']['CabinEconomy'] = 1;
        $formData['booking_request']['CabinBusiness'] = 0;
        $I->loadPage('POST', $this->routeAddRequest, $formData);
        $I->see("Booking Request #");
        $id = $this->grabAbRequestId($I);
        $I->seeInDatabase('AbRequest', ['AbRequestID' => $id, 'BookerUserID' => $this->defaultBooker]);
    }

    public static function getFakeAbRequestData(?Usr $user = null)
    {
        $data = [
            "booking_request" => [
                "ContactName" => "Billy Villy",
                "ContactPhone" => "123-456",
                "ContactEmail" => "addrequest@gmail.com",
                "PriorSearchResults" => [
                    "choice" => "-",
                ],
                "CabinFirst" => 1,
                "Passengers" => [
                    [
                        "FirstName" => "John",
                        "LastName" => "Popov",
                        "Birthday" => "1980-06-01",
                        "Gender" => "M",
                        "Nationality" => ["choice" => "US"],
                    ],
                    [
                        "FirstName" => "Vassily",
                        "LastName" => "Charlot",
                        "Birthday" => "1986-10-13",
                        "Gender" => "M",
                        "Nationality" => ["choice" => "US"],
                    ],
                ],
                "Segments" => [
                    [
                        "RoundTrip" => 2,
                        "Dep" => "Zombilend",
                        "Arr" => "Perm",
                        "DepDateIdeal" => date("Y-m-d", strtotime("+2 days")),
                        "ReturnDateIdeal" => date("Y-m-d", strtotime("+10 days")),
                        "RoundTripDaysIdeal" => 6,
                    ],
                ],
                "CustomPrograms" => [
                    [
                        "Name" => "Test Provider",
                        "Owner" => "John Popov",
                        "EliteStatus" => "",
                        "Balance" => "12345",
                    ],
                ],
            ],
        ];

        if ($user) {
            $data["booking_request"]["User"] = [
                "firstname" => $user->getFirstname(),
                "lastname" => $user->getLastname(),
                "email" => [
                    "Email" => $user->getEmail(),
                    "ConfirmEmail" => $user->getEmail(),
                ],
                "phone1" => "123-456",
                "login" => $user->getLogin(),
                "pass" => [
                    "Password" => Aw::DEFAULT_PASSWORD,
                    "ConfirmPassword" => Aw::DEFAULT_PASSWORD,
                ],
            ];
            unset($data['booking_request']["ContactName"]);
            unset($data['booking_request']["ContactPhone"]);
            $data["booking_request"]["RememberMe"] = "1";
            $data["booking_request"]["Terms"] = "1";
        }

        return $data;
    }

    private function assertTimelineSharing(\TestSymfonyGuy $I): AbRequest
    {
        $id = $this->grabAbRequestId($I);
        /** @var EntityManager $em */
        $em = $I->grabService("doctrine")->getManager();
        $request = $em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($id);
        $I->assertNotEmpty($request);
        $authorId = $request->getUser()->getUserid();
        $userAgentId = $I->grabFromDatabase('UserAgent', 'UserAgentID', [
            'AgentID' => $request->getBooker()->getUserid(),
            'ClientID' => $authorId,
        ]);
        $I->assertNotEmpty($userAgentId);
        $I->seeInDatabase('TimelineShare', [
            'UserAgentID' => $userAgentId,
            'TimelineOwnerID' => $authorId,
        ]);

        return $request;
    }

    private function grabAbRequestId(\TestSymfonyGuy $I)
    {
        return $I->grabTextFrom(['css' => ".booker-title strong"]);
    }

    private function validate(\TestSymfonyGuy $I, ?Usr $user = null, callable $do, $error, $selector = null)
    {
        $formData = self::getFakeAbRequestData($user);
        $do($formData);
        $I->loadPage('POST', $this->routeAddRequest, $formData);
        $I->seeResponseCodeIs(200);
        $I->see($error, $selector);
    }
}
