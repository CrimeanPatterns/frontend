<?php

namespace AwardWallet\Tests\Unit\Diff;

use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;

/**
 * @group frontend-unit
 */
class WhiteListTest extends BaseUserTest
{
    /**
     * #@dataProvider getDepDates.
     *
     * @param int $startInMinutesBefore
     * @param int $startInMinutesAfter
     * @param bool $expectingEmail
     */
    public function testEmailChangedDepDate($startInMinutesBefore, $startInMinutesAfter, $expectingEmail)
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "Itineraries.TripDateFromPass", date("Y-m-d H:i", strtotime("+{$startInMinutesBefore} minutes")));
        $mockProducer = $this->mockServiceWithBuilder("aw.rabbitmq.delayed_producer.itinerary_notification");
        $mockProducer->expects($this->exactly($expectingEmail ? 2 : 1))->method("delayedPublish");
        $this->aw->checkAccount($accountId, true);
        $this->db->executeQuery("update Account set Pass = '" . date("Y-m-d H:i", strtotime("+{$startInMinutesAfter} minutes")) . "' where AccountID = $accountId");
        $this->aw->checkAccount($accountId, true);
    }

    public function getDepDates()
    {
        return [
            [600, 630, true],
            [600, 605, true],
            [50, 55, false],
            [60, 65, true],
            [55, 60, false],
            [55, 50, false],
            [55, 30, true],
        ];
    }
}
