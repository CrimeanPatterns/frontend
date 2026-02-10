<?php

namespace AwardWallet\Tests\Unit\FlightStats;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\FlightStats\AirlineConverter;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\Subscriber;
use AwardWallet\MainBundle\Service\FlightStats\TripAlerts\SubscriptionManager;
use Codeception\Module\Aw;
use Codeception\Util\Stub;

/**
 * @group frontend-unit
 */
class SubscriptionManagerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    protected $userId;
    /** @var Usr */
    protected $user;
    /** @var SubscriptionManager */
    protected $subscriptionManager;

    public function _before(\CodeGuy $I)
    {
        $I->mockService(
            AirlineConverter::class,
            $I->stubMakeEmpty(AirlineConverter::class, [
                'IataToFSCode' => 'AA',
                'FSCodeToIata' => 'AA',
                'FSCodeToName' => 'American Airlines',
            ])
        );
        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($this->userId);

        $testUserId = $this->userId;

        $subscriber = $I->stubMake(Subscriber::class, [
            'subscribe' => Stub::atLeastOnce(function (array $flights, $userId) {
                return false;
            }),
        ]);
        $I->mockService(Subscriber::class, $subscriber);
        $this->subscriptionManager = $I->getContainer()->get(SubscriptionManager::class);
    }

    public function testSetTripAlertsUpdateDate(\CodeGuy $I)
    {
        $I->wantTo('test setting TripAlertsUpdateDate field for invalid/unsubscribed segments');

        $startTime = time() + SECONDS_PER_HOUR;
        $updateDate = (new \DateTime())->format("Y-m-d H:i:s");

        $tripId = $I->haveInDatabase("Trip", [
            "UserID" => $this->user->getUserid(),
            "RecordLocator" => 'FLIGHT1',
            "ProviderID" => 1,
        ]);
        $segmentId = $I->createTripSegment([
            "TripID" => $tripId,
            "FlightNumber" => "TST123",
            "ScheduledDepDate" => date("Y-m-d H:i:s", $startTime),
            "DepCode" => Aw::GMT_AIRPORT_2,
            "DepDate" => date("Y-m-d H:i:s", $startTime),
            "ArrCode" => Aw::GMT_AIRPORT,
            "ArrDate" => date("Y-m-d H:i:s", $startTime + SECONDS_PER_HOUR),
            "TripAlertsUpdateDate" => $updateDate,
            "MarketingAirlineConfirmationNumber" => "FLIGHT1",
        ]);

        $I->seeInDatabase("TripSegment", ["TripSegmentId" => $segmentId, "TripAlertsUpdateDate" => $updateDate]);

        $this->subscriptionManager->update([
            'UserID' => $this->userId,
            'HasMobileDevices' => 1,
            'TripAlertsHash' => StringUtils::uuid(),
        ], false);

        $I->seeInDatabase("TripSegment", ["TripSegmentId" => $segmentId, "TripAlertsUpdateDate" => null]);
    }

    public function _after()
    {
        $this->userId = null;
        $this->user = null;
        $this->subscriptionManager = null;
    }
}
