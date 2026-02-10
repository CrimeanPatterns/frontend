<?php

namespace AwardWallet\Tests\Unit\Itineraries;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class RestaurantTest extends BaseUserTest
{
    /**
     * @dataProvider checkStrategy
     */
    public function testMeeting($local)
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "Itineraries.Meeting");
        $this->aw->checkAccount($accountId, true, $local);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED]);
        $this->assertEquals(EVENT_MEETING, $this->db->grabFromDatabase("Restaurant", "EventType", ["AccountID" => $accountId]));
    }

    /**
     * @dataProvider checkStrategy
     */
    public function testGeneral($local)
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "future.restaurant");
        $this->aw->checkAccount($accountId, true, $local);
        $this->db->seeInDatabase("Account", ["AccountID" => $accountId, "ErrorCode" => ACCOUNT_CHECKED]);
        $this->assertEquals(EVENT_RESTAURANT, $this->db->grabFromDatabase("Restaurant", "EventType", ["AccountID" => $accountId]));
    }

    public function checkStrategy()
    {
        return [
            [true],
        ];
    }
}
