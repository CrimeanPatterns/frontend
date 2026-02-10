<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\Common\FlightStats\Communicator;
use AwardWallet\Common\FlightStats\CommunicatorCallException;
use AwardWallet\Common\FlightStats\Schedule;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use JMS\Serializer\Serializer;

/**
 * @group frontend-functional
 */
class FillFlightCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $I->resetLockout('flight_fill', $I->getClientIp());
    }

    public function fillFlight(\TestSymfonyGuy $I)
    {
        $I->wantTo('Get data to fill out a flight form');

        $I->mockService(Communicator::class, $I->stubMakeEmpty(Communicator::class, ['getScheduleByCarrierFNAndDepartureDate' => $this->fakeSchedule($I)]));
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'departureAirport' => "LGA",
            'departureDate_date' => "2017-06-15",
            'departureDate_time' => "2:45 PM",
            'arrivalAirport' => "YUL",
            'arrivalDate_date' => "2017-06-15",
            'arrivalDate_time' => "4:34 PM",
            'airlineName' => "Delta Air Lines",
            'flightNumber' => "3660",
        ]);
    }

    public function flightNotFoundInFlightStatus(\TestSymfonyGuy $I)
    {
        $I->wantTo('Fail to find the flight in flight stats and get 404');

        $I->mockService(Communicator::class, $I->stubMakeEmpty(Communicator::class, ['getScheduleByCarrierFNAndDepartureDate' => function () {
            throw new CommunicatorCallException("Not found", 404);
        }]));
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(404);
    }

    public function flightNotFoundInSchedule(\TestSymfonyGuy $I)
    {
        $I->wantTo('Fail to find the flight in schedule and get 404');

        $I->mockService(Communicator::class, $I->stubMakeEmpty(Communicator::class, ['getScheduleByCarrierFNAndDepartureDate' => $this->fakeSchedule($I)]));
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'SVO', // Wrong code
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(404);
    }

    public function failedToRetrieveDataFromFlightStats(\TestSymfonyGuy $I)
    {
        $I->wantTo('Fail to retrieve data from flight stats and get 500');

        $I->mockService(Communicator::class, $I->stubMakeEmpty(Communicator::class, ['getScheduleByCarrierFNAndDepartureDate' => function () {
            throw new CommunicatorCallException("", 500);
        }]));
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(500);
    }

    public function MalformedQuery(\TestSymfonyGuy $I)
    {
        $I->wantTo('Send a malformed query and get 400');

        $I->comment('Non existent airline name');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'NON EXISTING AIRLINE NAME',
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('No airline name');
        $I->sendAjaxGetRequest('/flight/fill', [
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('Non existent airport code');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'ZZA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('No airport code');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('Malformed date');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'departureDate_date' => 'NOT A DATE',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('No date');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'flightNumber' => '2331',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('Non-digit flight number');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
            'flightNumber' => 'NOT A DIGIT',
        ]);
        $I->seeResponseCodeIs(400);

        $I->comment('No flight number');
        $I->sendAjaxGetRequest('/flight/fill', [
            'airlineName' => 'Delta Air Lines',
            'departureAirport' => 'LGA',
            'departureDate_date' => '2017-06-15',
        ]);
        $I->seeResponseCodeIs(400);
    }

    public function throttle(\TestSymfonyGuy $I)
    {
        $I->wantTo('Throttle service');

        $I->resetLockout('flight_fill', $I->getClientIp());

        for ($i = 0; $i < 10; $i++) {
            $I->sendAjaxGetRequest('/flight/fill', ['json' => json_encode(['1' => '1'])]);
            $I->seeResponseCodeIs(400);
        }
        $I->comment('We\'re at the limit, should still be a 400 code');
        $I->seeResponseCodeIs(400);
        $I->comment('Now we passed the limit and should get 429');
        $I->sendAjaxGetRequest('/flight/fill', ['json' => json_encode(['1' => '1'])]);
        $I->seeResponseCodeIs(429);
    }

    private function fakeSchedule(\TestSymfonyGuy $I)
    {
        $scheduleJson = file_get_contents(__DIR__ . "/../_data/ScheduleByCarrierFNAndDepartureDate.json");
        /** @var Serializer $serializer */
        $serializer = $I->grabService('jms_serializer');
        $schedule = $serializer->deserialize($scheduleJson, Schedule::class, 'json');

        return $schedule;
    }
}
