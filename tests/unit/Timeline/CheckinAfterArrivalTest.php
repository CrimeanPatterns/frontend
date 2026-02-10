<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MainBundle\Timeline\Item\Checkin;
use AwardWallet\MainBundle\Timeline\Item\Date;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class CheckinAfterArrivalTest extends BaseTimelineTest
{
    /**
     * @dataProvider dataProvider
     */
    public function testArrivalBeforeCheckin($checkinDate, $arrivalDate, $expectedCheckinDate, $checkinAfterTrip)
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Timeline.Checkin");
        $this->db->haveInDatabase("Answer", ["AccountID" => $accountId, "Question" => "CheckinDate", "Answer" => $checkinDate]);
        $this->db->haveInDatabase("Answer", ["AccountID" => $accountId, "Question" => "ArrivalDate", "Answer" => $arrivalDate]);
        $this->aw->checkAccount($accountId);
        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user));
        /** @var ItemInterface[] $items */
        $items = array_values(array_filter($items, function (ItemInterface $item) { return !($item instanceof Date); }));
        $this->assertEquals(3, count($items));

        if ($checkinAfterTrip) {
            $tripIndex = 0;
            $checkinIndex = 1;
        } else {
            $tripIndex = 1;
            $checkinIndex = 0;
        }
        $this->assertInstanceOf(AirTrip::class, $items[$tripIndex]);
        $this->assertInstanceOf(Checkin::class, $items[$checkinIndex]);
        $this->assertEquals($expectedCheckinDate, $items[$checkinIndex]->getStartDate()->format("Y-m-d H:i"), "checkin was: {$checkinDate}, arrival: {$arrivalDate}");
    }

    public function dataProvider()
    {
        return [
            // checkIn              // arrival                  // expected checkIn     // checkInAfterTrip
            // checkin same day
            ["2030-01-01 11:00", 	"2030-01-01 8:00", 			"2030-01-01 11:00", 	true],
            ["2030-01-01 11:00", 	"2030-01-01 10:00", 		"2030-01-01 11:00", 	true],
            ["2030-01-01 13:00", 	"2030-01-01 10:00", 		"2030-01-01 13:00", 	true],
            ["2030-01-01 13:00", 	"2030-01-01 14:00", 		"2030-01-01 15:00", 	true],
            ["2030-01-01", 			"2030-01-01 13:00", 		"2030-01-01 16:00", 	true],
            ["2030-01-01 16:00", 	"2030-01-01 15:00", 		"2030-01-01 16:00", 	true],
            ["2030-01-01 16:00", 	"2030-01-01 22:03", 		"2030-01-01 23:03", 	true],
            ["2030-01-01 16:00", 	"2030-01-01 21:59", 		"2030-01-01 22:59", 	true],
            ["2030-01-01 23:00", 	"2030-01-01 22:03", 		"2030-01-01 23:03", 	true],

            // checkin previous day
            ["2030-01-01 11:00",    "2030-01-02 7:45", 			"2030-01-01 11:00", 	false],
            ["2030-01-01 23:00", 	"2030-01-02 8:00", 			"2030-01-01 23:00", 	false],
            ["2030-01-01", 			"2030-01-02 9:00", 			"2030-01-01 16:00", 	false],
        ];
    }
}
