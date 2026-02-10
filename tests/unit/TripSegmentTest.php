<?php

namespace AwardWallet\Tests\Unit;

use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class TripSegmentTest extends BaseUserTest
{
    public function testFull()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "trip.overnight");
        $this->aw->checkAccount($accountId);
        $old = $this->loadSegments($accountId);
        $this->aw->checkAccount($accountId);
        $new = $this->loadSegments($accountId);
        $this->assertEquals(json_encode($old, JSON_PRETTY_PRINT), json_encode($new, JSON_PRETTY_PRINT));
    }

    public function testChangingTime()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Timeline.RandomTimes");
        $this->aw->checkAccount($accountId);
        $old = $this->loadSegments($accountId);
        $this->aw->checkAccount($accountId);
        $new = $this->loadSegments($accountId);
        $this->assertNotEquals($old, $new);
        $this->assertEquals($old[0]['TripSegmentID'], $new[0]['TripSegmentID']);
    }

    public function testDelete()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "TripSegment.Delete");
        $this->aw->checkAccount($accountId);
        $old = $this->loadSegments($accountId);
        $this->em->getConnection()->executeUpdate("UPDATE Account SET Login2 = 'delete' WHERE AccountID = ?", [$accountId]);
        $this->aw->checkAccount($accountId);
        $new = $this->loadSegments($accountId);
        array_pop($old);
        $this->assertEquals($old, $new);
    }

    private function loadSegments($accountId)
    {
        return $this->em->getConnection()->executeQuery("
		SELECT
			ts.*
		FROM
			TripSegment ts
			JOIN Trip t ON ts.TripID = t.TripID
		WHERE
			t.AccountID = ?", [$accountId])->fetchAll(\PDO::FETCH_ASSOC);
    }
}
