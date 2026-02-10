<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class TrimeLeftZerosTest extends BaseUserTest
{
    public function testLeftTrim()
    {
        return;
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), "testprovider", "Itineraries.LeadingZeros");
        $this->aw->checkAccount($accountId);

        $this->db->seeInDatabase("Trip", ["UserID" => $this->user->getUserid(), "RecordLocator" => "1203450"]);
        $this->db->seeInDatabase("Reservation", ["UserID" => $this->user->getUserid(), "ConfirmationNumber" => "1203460"]);
        $this->db->seeInDatabase("Rental", ["UserID" => $this->user->getUserid(), "Number" => "123400"]);
    }
}
