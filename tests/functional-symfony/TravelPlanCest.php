<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Globals\DateUtils;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Codeception\Module\Aw;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-functional
 */
class TravelPlanCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $login;
    private $userId;

    /** @var RouterInterface */
    private $router;
    /** @var CacheManager */
    private $cacheManager;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');

        $this->login = 'test' . $I->grabRandomString();
        $this->userId = $I->createAwUser($this->login, null, [], false, true);
        $this->cacheManager = $I->grabService(CacheManager::class);
        $I->setCookie("NDEnabled", "1");
        $I->sendGET("/page/about?_switch_user={$this->login}");
        $I->saveCsrfToken();
    }

    public function testSharedPlan(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser();
        $I->haveInDatabase("Rental", [
            "Number" => "RENT1",
            "PickupLocation" => Aw::GMT_AIRPORT,
            "DropoffLocation" => Aw::GMT_AIRPORT,
            "PickupDatetime" => "2036-01-01",
            "DropoffDatetime" => "2036-01-03",
            "UserID" => $userId,
        ]);

        $creationDate = strtotime("2000-01-01");
        $shareCode = StringHandler::getRandomCode(32, true);
        $planId = $I->haveInDatabase("Plan", [
            "UserID" => $userId,
            "Name" => "Rental plan",
            "CreationDate" => date("Y-m-d H:i:s", $creationDate),
            "StartDate" => "2035-01-01",
            "EndDate" => "2037-01-01",
            "ShareCode" => $shareCode,
        ]);

        /** @var RouterInterface $router */
        $router = $I->grabService("router");

        $I->sendGET($router->generate("aw_travelplan_data_shared", ["shareCode" => base64_encode('Travelplan.' . $planId . '.' . $shareCode)]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['segments' => ['type' => 'planStart']]);
    }

    public function testSuccessfulCreate(\TestSymfonyGuy $I)
    {
        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->userId,
            "ConfNo" => 'Rest1',
            "Address" => "London",
            "Name" => 'Meeting with John',
            "StartDate" => date("Y-m-d H:i:s", strtotime("tomorrow")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 5),
        ]);
        $this->cacheManager->invalidateTags([Tags::getTimelineKey($this->userId)]);
        $I->sendGET("/timeline/data");
        $timeline = $I->grabDataFromJsonResponse();
        $I->assertEquals(2, count($timeline['segments']));

        $I->sendPOST("/plan/create", ["startTime" => strtotime("tomorrow")]);
        $plan = $I->grabDataFromJsonResponse();
        $I->assertNotNull($plan);
        $I->seeInDatabase("Plan", ["PlanID" => $plan['id'], "UserID" => $this->userId]);

        $I->sendGET("/timeline/data");
        $timeline = $I->grabDataFromJsonResponse();
        $I->assertEquals(4, count($timeline['segments']));
    }

    public function testFailedCreate(\TestSymfonyGuy $I)
    {
        $I->haveInDatabase("Plan", [
            "UserID" => $this->userId,
            "Name" => "Intersection1",
            "StartDate" => date("Y-m-d H:i:s", strtotime("yesterday")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("+3 day")),
            "ShareCode" => StringUtils::getRandomCode(20),
        ]);
        $this->cacheManager->invalidateTags([Tags::getTimelineKey($this->userId)]);
        $I->sendPOST("/plan/create", ["startTime" => strtotime("tomorrow")]);
        $planId = $I->grabDataFromJsonResponse();
        $I->assertNull($planId);
    }

    public function testDisplay(\TestSymfonyGuy $I)
    {
        $I->haveInDatabase("Plan", [
            "UserID" => $this->userId,
            "Name" => "Intersection1",
            "StartDate" => date("Y-m-d H:i:s", strtotime("yesterday")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("+3 day")),
            "ShareCode" => StringUtils::getRandomCode(20),
        ]);
        $seg1Date = strtotime("-1 day");
        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->userId,
            "ConfNo" => 'Rest1',
            "Address" => "London",
            "Name" => 'Meeting with John',
            "CreateDate" => date("Y-m-d H:i:s", $seg1Date),
            "StartDate" => date("Y-m-d H:i:s", strtotime("tomorrow")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 5),
        ]);
        $updateDate = strtotime("-1 hour");
        $tripId = $I->haveInDatabase("Trip", [
            "UserID" => $this->userId,
            "RecordLocator" => 'FLIGHT1',
            "CreateDate" => date("Y-m-d H:i:s", $updateDate),
            "UpdateDate" => date("Y-m-d H:i:s", $updateDate),
        ]);
        $I->createTripSegment([
            "TripID" => $tripId,
            "DepCode" => "JFK",
            "DepName" => "JFK",
            "DepDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR),
            "ScheduledDepDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR),
            "ArrCode" => "LAX",
            "ArrName" => "LAX",
            "ArrDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 2),
            "ScheduledArrDate" => date("Y-m-d H:i:s", strtotime("tomorrow") + SECONDS_PER_HOUR * 2),
        ]);
        $seg2Date = strtotime("-8 hour");
        $I->haveInDatabase("Restaurant", [
            "UserID" => $this->userId,
            "ConfNo" => 'Rest1',
            "Name" => 'Meeting with John',
            "Address" => "London",
            "CreateDate" => date("Y-m-d H:i:s", $seg2Date),
            "StartDate" => date("Y-m-d H:i:s", strtotime("+5 day")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("+5 day") + SECONDS_PER_HOUR * 5),
        ]);
        $this->cacheManager->invalidateTags([Tags::getTimelineKey($this->userId)]);
        $I->sendGET("/timeline/data");
        $timeline = $I->grabDataFromJsonResponse();
        $types = array_map(function (array $item) {
            return $item['type'];
        }, $timeline['segments']);

        $I->assertEquals(['planStart', 'date', 'segment', 'segment', 'planEnd', 'date', 'segment'], $types);
        $I->assertEquals($timeline['segments'][1]['localDate'], $timeline['segments'][0]['localDate']);
        $I->assertEquals($timeline['segments'][1]['localDate'], $timeline['segments'][4]['localDate']);
        $I->assertEquals('Intersection1', $timeline['segments'][0]['name']);
        $I->assertEquals(false, $timeline['segments'][1]['createPlan']);
        $I->assertEquals(true, $timeline['segments'][0]['canEdit']);
        $I->assertEquals(true, $timeline['segments'][5]['createPlan']);
        $I->assertEquals($timeline['segments'][0]['lastUpdated'], $updateDate);
        $I->assertEquals($timeline['segments'][2]['lastUpdated'], $seg1Date);
        $I->assertEquals($timeline['segments'][3]['lastUpdated'], $updateDate);
        $I->assertEquals($timeline['segments'][6]['lastUpdated'], $seg2Date);
    }

    public function testDelete(\TestSymfonyGuy $I)
    {
        $planId = $I->haveInDatabase("Plan", [
            "UserID" => $this->userId,
            "Name" => "Delete1",
            "StartDate" => date("Y-m-d H:i:s", strtotime("yesterday")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("+3 day")),
            "ShareCode" => StringUtils::getRandomCode(20),
        ]);

        $I->sendPOST("/plan/delete/{$planId}");
        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase("Plan", ["PlanID" => $planId]);
    }

    public function testRename(\TestSymfonyGuy $I)
    {
        $planId = $I->haveInDatabase("Plan", [
            "UserID" => $this->userId,
            "Name" => "Rename my",
            "StartDate" => date("Y-m-d H:i:s", strtotime("yesterday")),
            "EndDate" => date("Y-m-d H:i:s", strtotime("+3 day")),
            "ShareCode" => StringUtils::getRandomCode(20),
        ]);

        // Check required
        $I->sendPOST("/plan/rename/{$planId}", ['name' => ""]);
        $I->seeResponseCodeIs(500);

        // Check maxlength
        $I->sendPOST("/plan/rename/{$planId}", ['name' => $I->grabRandomString(255)]);
        $I->seeResponseCodeIs(500);

        // Check normal rename
        $newName = "New super name";
        $I->sendPOST("/plan/rename/{$planId}", ['name' => $newName]);
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("Plan", ["PlanID" => $planId, "Name" => $newName]);
    }

    public function testMoveShared(\TestSymfonyGuy $I)
    {
        $friendUserId = $I->createAwUser();
        $connectionFromFriendToUser = $I->createConnection($friendUserId, $this->userId, true, true, ['AccessLevel' => ACCESS_READ_ALL, 'TripAccessLevel' => 1]);
        $connectionFromUserToFriend = $I->createConnection($this->userId, $friendUserId, true, true, ['AccessLevel' => ACCESS_READ_ALL, 'TripAccessLevel' => 1]);
        $I->shareAwTimeline($friendUserId, null, $this->userId);
        $segmentsTypesOrderingAssert = $this->createSegmentTypesOrderingAsserter($I, "/timeline/data/{$connectionFromFriendToUser}");
        $this->assertPlanMove($I, $segmentsTypesOrderingAssert, $friendUserId);
    }

    public function testMove(\TestSymfonyGuy $I)
    {
        $this->assertPlanMove($I, $this->createSegmentTypesOrderingAsserter($I, "/timeline/data"), $this->userId);
    }

    public function testSetNotes(\TestSymfonyGuy $I): void
    {
        $planId = $this->createPlan($I);
        $note = StringUtils::getRandomCode(16) . '<b>bold</b>';

        $I->sendPost($this->router->generate('aw_timeline_plan_update_note', ['planId' => $planId]),
            json_encode([
                'note' => $note,
                'fileDescription' => [],
            ])
        );
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson([
            'status' => true,
            'note' => $note,
        ]);

        $I->seeInDatabase('Plan', ['UserID' => $this->userId, 'Notes' => $note]);
    }

    public function testStripTagsNotes(\TestSymfonyGuy $I): void
    {
        $planId = $this->createPlan($I);
        $note = 'test<b>bold</b>
            <a href="">link</a>
            <u style="">underline</u>
            <script>window();</script>
            <i title="test">attribute</i>';
        $clean = 'test<b>bold</b>
            link
            <u>underline</u>
            window();
            <i>attribute</i>';

        $I->sendPost($this->router->generate('aw_timeline_plan_update_note', ['planId' => $planId]),
            json_encode([
                'note' => $note,
                'fileDescription' => [],
            ])
        );
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson([
            'status' => true,
            'note' => $clean,
        ]);

        $I->seeInDatabase('Plan', ['UserID' => $this->userId, 'Notes' => $clean]);
    }

    public function testUploadFileNotes(\TestSymfonyGuy $I)
    {
        $planId = $this->createPlan($I);
        $path = codecept_data_dir('cardImages/front.png');
        $file = [
            'name' => 'imageTest' . StringUtils::getRandomCode(8) . '.png',
            'type' => 'image/png',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
            'tmp_name' => $path,
        ];
        $I->sendPost(
            $this->router->generate('aw_timeline_plan_upload_file', ['planId' => $planId]),
            [],
            ['file' => $file]
        );
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson([
            'status' => true,
        ]);
        $I->seeResponseContains($file['name']);
        $criteria = ['FileName' => $file['name']];
        $I->seeInDatabase('PlanFile', $criteria);

        // remove test
        $planFileId = $I->grabFromDatabase('PlanFile', 'PlanFileID', $criteria);
        $I->sendPost($this->router->generate('aw_timeline_plan_remove_file', ['planFileId' => $planFileId]));

        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson(['status' => true]);

        $I->dontSeeInDatabase('PlanFile', $criteria);
    }

    public function testUploadFileBadMimeTypeNotes(\TestSymfonyGuy $I)
    {
        $planId = $this->createPlan($I);
        $path = codecept_data_dir('cardImages/invalid.png');
        $file = [
            'name' => 'imageTest' . StringUtils::getRandomCode(8) . '.png',
            'type' => 'image/png',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
            'tmp_name' => $path,
        ];
        $I->sendPost(
            $this->router->generate('aw_timeline_plan_upload_file', ['planId' => $planId]),
            [],
            ['file' => $file]
        );

        $I->seeResponseContainsJson([
            'status' => false,
        ]);
        $I->seeResponseContains('Invalid file type');
    }

    protected function createSegmentTypesOrderingAsserter(\TestSymfonyGuy $I, string $url): \Closure
    {
        return function (array $expectedTypes) use ($I, $url) {
            $I->sendGET($url);
            $timeline = $I->grabDataFromJsonResponse();
            $actual = it($timeline['segments'])->column('type')->toArray();

            $I->assertEquals($expectedTypes, $actual);
        };
    }

    protected function assertPlanMove(\TestSymfonyGuy $I, \Closure $segmentsTypesOrderingAssert, int $timelineOwner)
    {
        $I->haveInDatabase("Restaurant", [
            "UserID" => $timelineOwner,
            "ConfNo" => 'Rest1',
            "Address" => "Moscow",
            "Name" => 'Meeting with John', // TODO: fix DST distortion
            "CreateDate" => $firstSegmentStartDate = DateUtils::toSQLDateTime(new \DateTime('tomorrow')),
            "StartDate" => $firstSegmentStartDate,
            'EndDate' => $firstSegmentEndDate = DateUtils::toSQLDateTime(new \DateTime('tomorrow 5:00')),
        ]);

        $middleEventId = $I->haveInDatabase("Restaurant", [
            "UserID" => $timelineOwner,
            "ConfNo" => 'Rest2',
            "Address" => "Moscow", // TODO: fix DST distortion
            "Name" => 'Meeting with John',
            "CreateDate" => $middleSegmentStartDate = DateUtils::toSQLDateTime(new \DateTime('+3 day')),
            "StartDate" => $middleSegmentStartDate,
            'EndDate' => $middleSegmentEndDate = DateUtils::toSQLDateTime(new \DateTime('+3 day 5:00')),
        ]);

        $I->haveInDatabase("Restaurant", [
            "UserID" => $timelineOwner,
            "ConfNo" => 'Rest2',
            "Address" => "Moscow", // TODO: fix DST distortion
            "Name" => 'Meeting with John',
            "CreateDate" => $lastSegmentStartDate = DateUtils::toSQLDateTime(new \DateTime('+5 day')),
            "StartDate" => $lastSegmentStartDate,
            'EndDate' => $lastSegmentEndDate = DateUtils::toSQLDateTime(new \DateTime('+5 day, 5:00')),
        ]);

        $planId = $I->haveInDatabase("Plan", [
            "UserID" => $timelineOwner,
            "Name" => "Plan1",
            "StartDate" => DateUtils::toSQLDateTime(new \DateTime('-1 day')),
            "EndDate" => DateUtils::toSQLDateTime(new \DateTime('+6 day')),
            "ShareCode" => StringUtils::getRandomCode(20),
        ]);

        $this->cacheManager->invalidateTags([Tags::getTimelineKey($this->userId)]);
        $segmentsTypesOrderingAssert(['planStart', 'date', 'segment', 'date', 'segment', 'date', 'segment', 'planEnd']);
        // move to start of middle segment
        $I->sendPOST("/plan/move", [
            'planId' => $planId,
            'nextSegmentId' => "E.{$middleEventId}",
            'nextSegmentTs' => (new \DateTime($middleSegmentStartDate))->getTimestamp(),
            'type' => 'planStart',
        ]);
        $I->seeResponseCodeIs(200);
        $segmentsTypesOrderingAssert(['date', 'segment', 'planStart', 'date', 'segment', 'date', 'segment', 'planEnd']);
    }

    private function createPlan(\TestSymfonyGuy $I, ?int $userId = null): int
    {
        return $I->haveInDatabase('Plan', [
            'UserID' => $userId ?? $this->userId,
            'Name' => 'That is PlanName',
            'StartDate' => date('Y-m-d H:i:s', strtotime('yesterday')),
            'EndDate' => date('Y-m-d H:i:s', strtotime('+3 day')),
            'ShareCode' => StringUtils::getRandomCode(20),
        ]);
    }
}
