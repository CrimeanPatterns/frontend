<?php

namespace AwardWallet\Tests\FunctionalSymfony;

/**
 * @group frontend-functional
 */
class IataCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var string
     */
    private $login;
    /**
     * @var int
     */
    private $user;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->login = 'test' . $I->grabRandomString();
        $this->user = $I->createAwUser($this->login);
    }

    public function _after(\TestSymfonyGuy $I)
    {
    }

    public function testProviderId(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase('Trip', [
            'UserID' => $this->user,
            'ProviderID' => 1, // AA
        ]);

        $segmentId = $I->createTripSegment([
            'TripID' => $tripId,
            'MarketingAirlineConfirmationNumber' => 'TESTCN',
            'DepCode' => 'TFR',
            'DepName' => 'Test from',
            'ArrCode' => 'TTO',
            'ArrName' => 'Test to',
            'DepDate' => date("Y-m-d H:i:s"),
            'ScheduledDepDate' => date("Y-m-d H:i:s"),
            'ArrDate' => date("Y-m-d H:i:s"),
            'ScheduledArrDate' => date("Y-m-d H:i:s"),
        ]);

        $I->sendGET("/timeline/data?_switch_user=$this->login");
        $data = $I->grabDataFromResponseByJsonPath("");
        $I->assertEquals('<span>AA</span> American Airlines', $data[0]['segments'][1]['title']);
    }

    public function testProviderName(\TestSymfonyGuy $I)
    {
        $tripId = $I->haveInDatabase('Trip', [
            'UserID' => $this->user,
            'ProviderID' => 1, // AA
        ]);

        $I->createTripSegment([
            'TripID' => $tripId,
            'MarketingAirlineConfirmationNumber' => 'TESTCN',
            'DepCode' => 'TFR',
            'DepName' => 'Test from',
            'ArrCode' => 'TTO',
            'ArrName' => 'Test to',
            'DepDate' => date("Y-m-d H:i:s"),
            'ScheduledDepDate' => date("Y-m-d H:i:s"),
            'ArrDate' => date("Y-m-d H:i:s"),
            'ScheduledArrDate' => date("Y-m-d H:i:s"),
            'AirlineName' => 'alaska', // Check provider resolve
        ]);

        $I->sendGET("/timeline/data?_switch_user=$this->login");
        $data = $I->grabDataFromResponseByJsonPath("");
        $I->assertEquals('<span>AS</span> alaska', $data[0]['segments'][1]['title']);
    }
}
