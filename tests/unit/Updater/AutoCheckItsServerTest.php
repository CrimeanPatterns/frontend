<?php

namespace AwardWallet\Tests\Unit\Updater;

use AwardWallet\MainBundle\Updater\Event\TripsFoundEvent;
use AwardWallet\MainBundle\Updater\Event\UpdatedEvent;
use AwardWallet\MainBundle\Updater\Option;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class AutoCheckItsServerTest extends UpdaterBase
{
    public function testServer()
    {
        // we always check trips now, this test could be deleted
        // leaving it here just to check that trips are gathered
        // actually this test could be commented out entirely, there is no 24h trip update logic now

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, 'future.trip', 'pass');
        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsFoundEvent($accountId, 1, [])]);
        $this->db->seeInDatabase("Trip", ["AccountID" => $accountId]);

        // within 24h, do check trips
        $this->db->executeQuery("delete from Trip where AccountID = $accountId");
        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsFoundEvent($accountId, 1, [])]);
        $this->db->seeInDatabase("Trip", ["AccountID" => $accountId]);

        // more than 24h, check again
        //        $this->db->executeQuery("update Account set LastCheckItDate = adddate(now(), -2) where AccountID = $accountId");
        //        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsFoundEvent($accountId, 1, [])]);
        //        $this->db->seeInDatabase("Trip", ["AccountID" => $accountId]);

        // check trips = true
        //        $this->db->executeQuery("delete from Trip where AccountID = $accountId");
        //        $this->updater->setOption(Option::CHECK_TRIPS, true);
        //        $this->updateAccount($accountId, [new UpdatedEvent($accountId, null), new TripsFoundEvent($accountId, 1, [])]);
        //        $this->db->seeInDatabase("Trip", ["AccountID" => $accountId]);
    }
}
