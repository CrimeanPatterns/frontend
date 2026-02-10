<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\Common\Geo\GoogleGeo;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\Bus as SchemaBusRide;
use AwardWallet\Schema\Itineraries\CarRental as SchemaCarRental;
use AwardWallet\Schema\Itineraries\Cruise as SchemaCruise;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use AwardWallet\Schema\Itineraries\Flight as SchemaFlight;
use AwardWallet\Schema\Itineraries\HotelReservation as SchemaHotelReservation;
use AwardWallet\Schema\Itineraries\Train as SchemaTrainRide;
use AwardWallet\Schema\Itineraries\Transfer as SchemaTransfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group moscow
 */
class TimelineControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;

    public function _before(\TestSymfonyGuy $I)
    {
        $userId = $I->createAwUser('test' . $I->grabRandomString(), $I->grabRandomString(10));
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($userId);
        $I->sendGET('/m/api/login_status?_switch_user=' . $this->user->getLogin());
        // Have to pull user again because we have booted a new kernel after the switch
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($userId);
    }

    public function taxiRideFormat(\TestSymfonyGuy $I)
    {
        /** @var GoogleGeo $geoCoder */
        $geoCoder = $I->grabService('aw.geo.google_geo');
        /** @var LocalizeService $localizer */
        $localizer = $I->grabService(LocalizeService::class);
        $startDate = new \DateTime('+1 day 11:00');
        $endDate = new \DateTime('+2 days 12:00');
        /** @var Geotag $pickUpGeoTag */
        $pickUpGeoTag = $geoCoder->findGeoTagEntity('Moscow, Russia');
        /** @var Geotag $pickUpGeoTag */
        $dropOffGeoTag = $geoCoder->findGeoTagEntity('Perm, Russia');
        $I->haveInDatabase('Rental', [
            'UserID' => $this->user->getUserid(),
            'PickupLocation' => 'Moscow, Russia',
            'DropoffLocation' => 'Perm, Russia',
            'Number' => 'TEST_NUMBER',
            'PickupPhone' => 'TEST_PICK_UP_PHONE',
            'DropoffPhone' => 'TEST_DROP_OFF_PHONE',
            'PickupDatetime' => $startDate->format('Y-m-d H:i:s'),
            'DropoffDatetime' => $endDate->format('Y-m-d H:i:s'),
            'PickupGeoTagID' => $pickUpGeoTag->getGeotagid(),
            'DropoffGeoTagID' => $dropOffGeoTag->getGeotagid(),
            'RentalCompanyName' => 'TEST_PROVIDER',
            'ProviderID' => null,
            'Notes' => 'TEST_NOTES',
            'Cancelled' => false,
            'ChangeDate' => null,
            'Type' => Rental::TYPE_TAXI,
        ]);
        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $response = json_decode($I->grabResponse(), true);
        $segment = $response['segments'][1];
        $expectedStartTimeStamp = $startDate->getTimestamp() - $pickUpGeoTag->getDateTimeZone()->getOffset(new \DateTime());
        $I->assertEquals($expectedStartTimeStamp, $segment['startDate'], 'Start time stamp');
        $expectedEndTimeStamp = $endDate->getTimestamp() - $dropOffGeoTag->getDateTimeZone()->getOffset(new \DateTime());
        $I->assertEquals($expectedEndTimeStamp, $segment['endDate'], 'End time stamp');
        $I->assertEquals('MSK', $segment['startTimezone'], 'Start timezone');
        $I->assertEquals('11:00 AM', $segment['localTime'], 'Local time');
        $I->assertEquals('taxi', $segment['icon'], 'Icon');
        $I->assertEquals('TEST_NUMBER', $segment['confNo'], 'Confirmation number');
        $I->assertEquals(false, $segment['deleted'], 'deleted');
        $I->assertEquals('TEST_PROVIDER', $segment['title'], 'Title');

        $details = $segment['details'];
        $I->assertEquals('TEST_NOTES', $details['notes'], 'Notes');
        $I->assertEquals('TEST_PICK_UP_PHONE', $details['phones']['account']['phone'], 'Phone');

        $pickUpColumn = $details['columns'][0];
        $I->assertEquals('info', $pickUpColumn['type']);
        $I->assertEquals('pickup.taxi', $pickUpColumn['rows'][0]['type']);
        $I->assertEquals($startDate->format('M j, Y, h:i A'), $pickUpColumn['rows'][0]['date']);
        $I->assertEquals('text', $pickUpColumn['rows'][1]['type']);
        $I->assertEquals('Moscow, Russia', $pickUpColumn['rows'][1]['text']);

        $dropOffColumn = $details['columns'][2];
        $I->assertEquals('info', $dropOffColumn['type']);
        $I->assertEquals('dropoff', $dropOffColumn['rows'][0]['type']);
        $I->assertEquals($endDate->format('M j, Y'), $dropOffColumn['rows'][0]['date']);
        $I->assertEquals('text', $dropOffColumn['rows'][1]['type']);
        $I->assertEquals('Perm, Russia', $dropOffColumn['rows'][1]['text']);
    }

    public function flightDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaFlight.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaFlight::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($I->createAwAccount($this->user->getUserid(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));
        $I->assertCount(1, $report->getAdded());
        /** @var Trip $flight */
        $flight = $report->getAdded()[0];
        $flight->getSegments()[0]->setDepartureGate('DG1');
        $flight->getSegments()[0]->setDepartureTerminal('DT1');
        $flight->getSegments()[0]->setArrivalGate('AG1');
        $flight->getSegments()[0]->setArrivalTerminal('AT1');
        $flight->getSegments()[0]->setBaggageClaim('BC1');
        $flight->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $flight->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $flight->setPricingInfo($flight->getPricingInfo()->withTravelAgencyEarnedAwards('100 miles'));
        $flight->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'accountId' => $account->getId(),
            'pricing' => [
                'Base Fare' => '$100',
                'Tax' => '$30',
                'Seat selection' => '$5.50',
                'Baggage fee' => '$14.50',
                'Spent Awards' => '3 segments',
                'Discount' => '$28.34',
                'Total Charge' => '$150',
            ],
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'Los Angeles, CA',
                                'code' => 'LAX',
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '1:30 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893533400,
                            'timezone' => 'PST',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Departure Terminal',
                            'value' => 'DT1',
                            'icon' => 'departure-terminal',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Gate',
                            'value' => 'DG1',
                            'icon' => 'gate',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Seats',
                            'value' => '3E, 3F',
                            'icon' => 'seats',
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'San Francisco, CA',
                                'code' => 'SFO',
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '3:00 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893538800,
                            'timezone' => 'PST',
                            'arrivalDay' => 'Jan 1, 2030',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Arrival Terminal',
                            'value' => 'AT1',
                            'icon' => 'arrival-terminal',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Arrival Gate',
                            'value' => 'AG1',
                            'icon' => 'gate',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Baggage Claim',
                            'value' => 'BC1',
                            'icon' => 'baggage',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Duration',
                            'value' => '1h 30m',
                        ],
                    ],
                ],
            ],
            'bookingLink' => [
                'formFields' => [
                    'destination' => 'San Francisco, CA',
                    'checkinDate' => '2030-01-01',
                    'checkoutDate' => '2030-01-05',
                    'url' => "https://awardwallet.com/blog/link/booking?aid=1473858&label=dskTimelineForm_{$this->user->getRefcode()}",
                ],
            ],
            'phones' => [
                'operating_airline' => [
                    'phone' => '+1-718-335-7070',
                    'provider' => 'British Airways',
                    'section' => 'Operating Airline',
                ],
                'account' => [
                    'phone' => '+1-404-714-2300',
                    'provider' => 'Delta',
                ],
            ],
            'refreshLink' => "/trips/update?accounts%5B0%5D={$account->getId()}",
            'notes' => 'some notes',
            'Files' => [],
            'shareCode' => $flight->getEncodedShareCode(),
            'autoLoginLink' => "/account/redirect?itID={$flight->getId()}&table=T",
            'canEdit' => true,
            'Confirmation Numbers' => 'CARR23, ISSD12, J3HND-8776',
            'Ticket Numbers' => '006 123321, 006 456654',
            'Passengers' => 'John Doe, Jane Doe',
            'Reservation Date' => 'January 1, 2000',
            'Status' => 'Confirmed',
            'Booking class' => 'CL',
            'Cabin' => 'Coach',
            'Meal' => 'Snacks',
            'Aircraft' => 'Boeing 737MAX 7 Passenger',
            'Stops' => '0',
            'Smoking' => 'No',
            'Account #' => '1234****, 4321****',
            'Travel Agency Account #' => 'EXP-11298',
            'Travelled Miles' => '300mi',
            'Earned Awards' => '300 award miles',
            'Travel Agency Earned Awards' => '100 miles',
        ];
        $I->assertEquals($expectedDetails, $details);
        $expectedDetailsKeys = array_keys($expectedDetails);
        $actualDetailsKeys = array_keys($details);
        sort($expectedDetailsKeys);
        sort($actualDetailsKeys);
        $I->assertSame($expectedDetailsKeys, $actualDetailsKeys);
    }

    public function cruiseDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaCruise.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaCruise::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($I->createAwAccount($this->user->getUserid(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));
        $I->assertCount(1, $report->getAdded());
        /** @var Trip $cruise */
        $cruise = $report->getAdded()[0];
        $cruise->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $cruise->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $cruise->setPricingInfo($cruise->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $cruise->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'accountId' => $account->getId(),
            'pricing' => [
                'Cost' => '$193.75',
                'Tax' => '$34.56',
                'Insurance' => '$23.10',
                'Spent Awards' => '10000 points',
                'Discount' => '$40',
                'Total Charge' => '$251.41',
            ],
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'PORT CANAVERAL',
                                'code' => null,
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '1:30 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893522600,
                            'timezone' => 'EST',
                            'formattedDate' => '2030-01-01',
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'NASSAU',
                                'code' => null,
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '8:00 AM',
                            'date' => 'Jan 2, 2030',
                            'timestamp' => 1893589200,
                            'timezone' => 'EST',
                            'arrivalDay' => 'Jan 2, 2030',
                            'formattedDate' => '2030-01-02',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Duration',
                            'value' => '18h 30m',
                        ],
                    ],
                ],
            ],
            'phones' => [
                'account' => [
                    'phone' => '+1800 951-3532',
                    'provider' => 'Disney Cruise Line',
                ],
            ],
            'shareCode' => $cruise->getEncodedShareCode(),
            'refreshLink' => "/trips/update?accounts%5B0%5D={$account->getId()}",
            'notes' => 'some notes',
            'Files' => [],
            'autoLoginLink' => "/account/redirect?itID={$cruise->getId()}&table=T",
            'canEdit' => true,
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Cruise Name' => 'Long cruise',
            'Deck' => '3',
            'Room Number' => '342',
            'Room Class' => 'Regular',
            'Ship Code' => 'SHCD',
            'Ship Name' => 'Disney Dream',
            'Passengers' => 'John Doe, Jane Doe',
            'Reservation Date' => 'January 1, 2000',
            'Status' => 'Confirmed',
            'Account #' => 'AM3398',
            'Travel Agency Account #' => 'EXP-11298',
            'Earned Awards' => '50 points',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $expectedDetailsKeys = array_keys($expectedDetails);
        $actualDetailsKeys = array_keys($details);
        sort($expectedDetailsKeys);
        sort($actualDetailsKeys);
        $I->assertSame($expectedDetailsKeys, $actualDetailsKeys);
    }

    public function busRideDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaBusRide.json');
        $busProviderCode = "testbus" . bin2hex(random_bytes(4));
        $providerId = $I->createAwProvider(null, $busProviderCode, ["Kind" => PROVIDER_KIND_OTHER, 'CanCheck' => 1, 'Autologin' => 1, 'CanCheckConfirmation' => 1, 'ItineraryAutoLogin' => 3]);
        $schemaRaw = str_replace("boltbus", $busProviderCode, $schemaRaw);
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaBusRide::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", []));
        $I->assertCount(1, $report->getAdded());
        /** @var Trip $busRide */
        $busRide = $report->getAdded()[0];
        $busRide->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $busRide->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $busRide->setPricingInfo($busRide->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $busRide->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'refreshLink' => "/trips/retrieve/confirmation/{$providerId}?itKind=T&itId={$busRide->getId()}",
            'canEdit' => true,
            'pricing' => [
                'Cost' => '$193.75',
                'Tax' => '$34.56',
                'Insurance' => '$23.10',
                'Spent Awards' => '10000 points',
                'Discount' => '$40',
                'Total Charge' => '$251.41',
            ],
            'autoLoginLink' => "/account/redirect?itID={$busRide->getId()}&table=T",
            'notes' => 'some notes',
            'Files' => [],
            'shareCode' => $busRide->getEncodedShareCode(),
            'phones' => [
            ],
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'Boston South Station - Gate 9 NYC-Gate 10 NWK\/PHL',
                                'code' => null,
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '1:30 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893522600,
                            'timezone' => 'EST',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Seats',
                            'value' => '11, 12',
                            'icon' => 'seats',
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)',
                                'code' => null,
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '8:34 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893548040,
                            'timezone' => 'EST',
                            'arrivalDay' => 'Jan 1, 2030',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Duration',
                            'value' => '7h 4m',
                        ],
                    ],
                ],
            ],
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Ticket Numbers' => '345667, 345668',
            'Passengers' => 'John Doe, Jane Doe',
            'Reservation Date' => 'January 1, 2000',
            'Status' => 'Confirmed',
            'Bus' => 'Mercedes',
            'Account #' => 'BB3398',
            'Travel Agency Account #' => 'EXP-11298',
            'Travelled Miles' => '43mi',
            'Earned Awards' => '50 points',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $expectedDetailsKeys = array_keys($expectedDetails);
        $actualDetailsKeys = array_keys($details);
        sort($expectedDetailsKeys);
        sort($actualDetailsKeys);
        $I->assertSame($expectedDetailsKeys, $actualDetailsKeys);
    }

    public function trainRideDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaTrainRide.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaTrainRide::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", []));
        $I->assertCount(1, $report->getAdded());
        /** @var Trip $trainRide */
        $trainRide = $report->getAdded()[0];
        $trainRide->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $trainRide->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $trainRide->setPricingInfo($trainRide->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $trainRide->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'refreshLink' => "/trips/retrieve/confirmation/28?itKind=T&itId={$trainRide->getId()}",
            'notes' => 'some notes',
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'Boston South Station - Gate 9 NYC-Gate 10 NWK\/PHL',
                                'code' => 'BBSS',
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '1:30 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893522600,
                            'timezone' => 'EST',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Seats',
                            'value' => '11, 12',
                            'icon' => 'seats',
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)',
                                'code' => 'NNYW',
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '8:34 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893548040,
                            'timezone' => 'EST',
                            'arrivalDay' => 'Jan 1, 2030',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Duration',
                            'value' => '7h 4m',
                        ],
                    ],
                ],
            ],
            'phones' => [
                'account' => [
                    'phone' => '+1-800-307-5000',
                    'provider' => 'Amtrak',
                ],
            ],
            'shareCode' => $trainRide->getEncodedShareCode(),
            'autoLoginLink' => "/account/redirect?itID={$trainRide->getId()}&table=T",
            'Files' => [],
            'pricing' => [
                'Cost' => '$193.75',
                'Tax' => '$34.56',
                'Insurance' => '$23.10',
                'Spent Awards' => '10000 points',
                'Discount' => '$40',
                'Total Charge' => '$251.41',
            ],
            'canEdit' => true,
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Ticket Numbers' => '345667, 345668',
            'Service Name' => 'Amtrak Express',
            'Car Number' => '4',
            'Passengers' => 'John Doe, Jane Doe',
            'Reservation Date' => 'January 1, 2000',
            'Status' => 'Confirmed',
            'Cabin' => 'coach',
            'Account #' => 'AM3398',
            'Travel Agency Account #' => 'EXP-11298',
            'Travelled Miles' => '43mi',
            'Earned Awards' => '50 points',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $I->assertSame(array_keys($expectedDetails), array_keys($details));
    }

    public function transferDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaTransfer.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaTransfer::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", []));
        $I->assertCount(1, $report->getAdded());
        /** @var Trip $transfer */
        $transfer = $report->getAdded()[0];
        $transfer->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $transfer->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $transfer->setPricingInfo($transfer->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $transfer->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'canEdit' => true,
            'notes' => 'some notes',
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'San Francisco International Airport',
                                'code' => 'SFO',
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '1:30 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893533400,
                            'timezone' => 'PST',
                            'formattedDate' => '2030-01-01',
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'airport',
                            'text' => [
                                'place' => 'some place',
                                'code' => null,
                            ],
                        ],
                        [
                            'type' => 'datetime',
                            'time' => '2:34 PM',
                            'date' => 'Jan 1, 2030',
                            'timestamp' => 1893537240,
                            'timezone' => 'PST',
                            'arrivalDay' => 'Jan 1, 2030',
                            'formattedDate' => '2030-01-01',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Duration',
                            'value' => '1h 4m',
                        ],
                    ],
                ],
            ],
            'phones' => [],
            'shareCode' => $transfer->getEncodedShareCode(),
            'Files' => [],
            'pricing' => [
                'Cost' => '$193.75',
                'Tax' => '$34.56',
                'Insurance' => '$23.10',
                'Spent Awards' => '10000 points',
                'Discount' => '$40',
                'Total Charge' => '$251.41',
            ],
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Passengers' => 'John Doe, Jane Doe',
            'Reservation Date' => 'January 1, 2000',
            'Status' => 'Confirmed',
            'Aircraft' => 'Ford Focus',
            'Adults' => '1',
            'Kids' => '0',
            'Account #' => 'AM3398',
            'Travel Agency Account #' => 'EXP-11298',
            'Travelled Miles' => '4.3mi',
            'Earned Awards' => '50 points',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $I->assertSame(array_keys($expectedDetails), array_keys($details));
    }

    public function hotelReservationDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaReservation.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaHotelReservation::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", []));
        $I->assertCount(1, $report->getAdded());
        /** @var Reservation $reservation */
        $reservation = $report->getAdded()[0];
        $reservation->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $reservation->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $reservation->setPricingInfo($reservation->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $reservation->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'canEdit' => true,
            'notes' => 'some notes',
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'checkin',
                            'date' => 'January 1, 2030',
                            'nights' => 4,
                        ],
                        [
                            'type' => 'text',
                            'text' => '201 North 17th Street, Philadelphia, Pennsylvania 19103 United States',
                            'geo' => [
                                'country' => 'United States',
                                'state' => 'Pennsylvania',
                                'city' => 'Philadelphia',
                            ],
                        ],
                    ],
                ],
            ],
            'shareCode' => $reservation->getEncodedShareCode(),
            'Files' => [],
            'phones' => [
                'account' => [
                    'phone' => '+1-22-3333',
                    'provider' => 'Starwood Hotels',
                ],
            ],
            'pricing' => [
                'Cost' => '$200',
                'Tax' => '$100',
                'Discount' => '$40',
                'Spent Awards' => '10000 points',
                'Total Charge' => '$300',
            ],
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Status' => 'Confirmed',
            'Guests' => '2',
            'Guest Names' => 'John D., Jane D.',
            'Kids' => '3',
            'Room Count' => '1',
            'Description' => 'Traditional, TV, free wi-fi',
            'Room Type' => 'King bed',
            'Rate' => '30$/night',
            'Rate Type' => 'King bed',
            'Account #' => 'xxxxxx345',
            'Travel Agency Account #' => 'EXP-11298',
            'Fax' => '+1-66-77899',
            'Cancellation Policy' => 'Cancellation is free 24 hours prior to check-in',
            'Reservation Date' => 'January 1, 2000',
            'Earned Awards' => '4 nights',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $I->assertSame(array_keys($expectedDetails), array_keys($details));
    }

    public function rentalDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaRental.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaCarRental::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", []));
        $I->assertCount(1, $report->getAdded());
        /** @var Rental $rental */
        $rental = $report->getAdded()[0];
        $rental->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $rental->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $rental->setPricingInfo($rental->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $rental->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'refreshLink' => "/trips/retrieve/confirmation/42?itKind=L&itId={$rental->getId()}",
            'autoLoginLink' => "/account/redirect?itID={$rental->getId()}&table=L",
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'pickup',
                            'date' => 'January 1, 2030 at 1:30 PM',
                            'days' => 4,
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Palm Beach Intl Airport,PBI, 2500 Turnage Boulevard, West Palm Beach, FL 33406 US',
                        ],
                        [
                            'type' => 'pairs',
                            'pairs' => [
                                'Pick-Up Hours' => 'Sun - Sat open 24 hrs',
                            ],
                        ],
                    ],
                ],
            ],
            'phones' => [
                'account' => [
                    'phone' => '+1-13-PICKUP',
                    'provider' => 'Avis',
                ],
            ],
            'shareCode' => $rental->getEncodedShareCode(),
            'Files' => [],
            'notes' => 'some notes',
            'canEdit' => true,
            'pricing' => [
                'Cost' => '$193.75',
                'Tax' => '$34.56',
                'Insurance' => '$23.10',
                'Spent Awards' => '10000 points',
                'Discount' => '$40',
                'Total Charge' => '$251.41',
            ],
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Pick-Up Fax' => '+1-14-FAX',
            'Drop-Off Fax' => '+1-14-FAX',
            'Status' => 'Confirmed',
            'Renter name' => 'John Doe',
            'Car Type' => 'Regular',
            'Car Model' => 'Ford Edge or similar',
            'Account #' => 'AVS454545',
            'Travel Agency Account #' => 'EXP-11298',
            'Reservation Date' => 'January 1, 2000',
            'Earned Awards' => '50 points',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $I->assertSame(array_keys($expectedDetails), array_keys($details));
    }

    public function eventDetails(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaEvent.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaEvent::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($I->createAwAccount($this->user->getUserid(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));
        $I->assertCount(1, $report->getAdded());
        /** @var Restaurant $event */
        $event = $report->getAdded()[0];
        $event->setTravelAgencyConfirmationNumbers(['J3HND-8776']);
        $event->setTravelAgencyParsedAccountNumbers(['EXP-11298']);
        $event->setPricingInfo($event->getPricingInfo()->withTravelAgencyEarnedAwards('100 points'));
        $event->setNotes('some notes');
        $I->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $I->sendAjaxGetRequest('/timeline/data');
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $details = $I->grabDataFromResponseByJsonPath('$.segments[?(@.details)]')[0]['details'];
        $expectedDetails = [
            'accountId' => $account->getId(),
            'Files' => [],
            'autoLoginLink' => "/account/redirect?itID={$event->getId()}&table=E",
            'canEdit' => true,
            'columns' => [
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'datetime',
                            'date' => 'Jan 1, 2030',
                            'time' => '6:00 PM',
                        ],
                        [
                            'type' => 'text',
                            'text' => '132 West 58th Street New York, NY 10019',
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Guests',
                            'value' => 2,
                        ],
                        [
                            'type' => 'pair',
                            'name' => 'Seats',
                            'value' => 'table 13',
                            'prevValue' => null,
                        ],
                    ],
                ],
                [
                    'type' => 'arrow',
                ],
                [
                    'type' => 'info',
                    'rows' => [
                        [
                            'type' => 'datetime',
                            'time' => '11:00 PM',
                            'date' => 'Jan 1, 2030',
                        ],
                    ],
                ],
            ],
            'phones' => [
                'account' => [
                    'phone' => '+1-23-44556',
                    'provider' => 'OpenTable',
                ],
            ],
            'shareCode' => $event->getEncodedShareCode(),
            'pricing' => [
                'Cost' => '$193.75',
                'Tax' => '$34.56',
                'Insurance' => '$23.10',
                'Spent Awards' => '10000 points',
                'Discount' => '$40',
                'Total Charge' => '$251.41',
            ],
            'refreshLink' => "/trips/update?accounts%5B0%5D={$account->getId()}",
            'notes' => 'some notes',
            'Confirmation Numbers' => '887756, J3HND-8776',
            'Status' => 'Confirmed',
            'Guests' => '2',
            'Guest Names' => 'John Doe, Jane Doe',
            'Account #' => 'AM3398',
            'Travel Agency Account #' => 'EXP-11298',
            'Reservation Date' => 'January 1, 2000',
            'Earned Awards' => '50 points',
            'Travel Agency Earned Awards' => '100 points',
        ];
        $I->assertEquals($expectedDetails, $details);
        $I->assertSame(array_keys($expectedDetails), array_keys($details));
    }

    public function testUploadAndRemoveFile(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(codecept_data_dir('itineraries/schemaEvent.json'));
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaEvent::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)
            ->find($I->createAwAccount($this->user->getId(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor
            ->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));

        $event = $report->getAdded()[0];
        $router = $I->grabService('router');

        $path = codecept_data_dir('cardImages/front.png');
        $file = [
            'name' => 'imageTest' . StringUtils::getRandomCode(8) . '.png',
            'type' => 'image/png',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
            'tmp_name' => $path,
        ];
        $I->sendPost(
            $router->generate('upload_note_file', ['type' => 'event', 'itineraryId' => $event->getId()]),
            [],
            ['file' => $file]
        );
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson([
            'status' => true,
        ]);
        $I->seeResponseContains($file['name']);
        $criteria = ['FileName' => $file['name']];
        $I->seeInDatabase('ItineraryFile', $criteria);

        // Test remove file
        $itineraryFileId = $I->grabFromDatabase('ItineraryFile', 'ItineraryFileID', $criteria);
        $I->sendPost($router->generate('aw_timeline_itinerary_remove_file', ['itineraryFileId' => $itineraryFileId]));

        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson(['status' => true]);

        $I->dontSeeInDatabase('ItineraryFile', $criteria);

        // Test invalid format file
        $file['name'] = 'missingFormat' . StringUtils::getRandomCode(8) . '.png';
        $file['type'] = 'image/jpeg';
        $I->sendPost(
            $router->generate('upload_note_file', ['type' => 'event', 'itineraryId' => $event->getId()]),
            [],
            ['file' => $file]
        );

        $I->seeResponseContainsJson(['status' => false]);
        $I->assertStringContainsString('Invalid file type', $I->grabResponse());
    }

    public function testStripTagsNotes(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(codecept_data_dir('itineraries/schemaEvent.json'));
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaEvent::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)
            ->find($I->createAwAccount($this->user->getId(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor
            ->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));

        $event = $report->getAdded()[0];
        $router = $I->grabService('router');

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

        $I->amOnPage($router->generate('itinerary_edit', ['type' => 'event', 'itineraryId' => $event->getId()]));
        $I->fillField('event[notes]', $note);
        $I->submitForm('form', []);

        $dbNotes = $I->grabFromDatabase('Restaurant', 'Notes', ['RestaurantID' => $event->getId()]);

        $I->assertEquals($clean, $dbNotes);
    }

    public function testFileDescription(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(codecept_data_dir('itineraries/schemaEvent.json'));
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaEvent::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)
            ->find($I->createAwAccount($this->user->getId(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor
            ->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));

        $event = $report->getAdded()[0];
        $router = $I->grabService('router');

        $path = codecept_data_dir('cardImages/front.png');
        $file = [
            'name' => 'imageTest' . StringUtils::getRandomCode(8) . '.png',
            'type' => 'image/png',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($path),
            'tmp_name' => $path,
        ];
        $I->sendPost(
            $router->generate('upload_note_file', ['type' => 'event', 'itineraryId' => $event->getId()]),
            [],
            ['file' => $file]
        );
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseContainsJson([
            'status' => true,
        ]);
        $I->seeResponseContains($file['name']);
        $criteria = ['FileName' => $file['name']];
        $I->seeInDatabase('ItineraryFile', $criteria);

        $itineraryFileId = $I->grabFromDatabase('ItineraryFile', 'ItineraryFileID', $criteria);

        $I->amOnPage($router->generate('itinerary_edit', ['type' => 'event', 'itineraryId' => $event->getId()]));
        $fileDescription = StringUtils::getRandomCode(16);
        $I->submitForm('form', [
            'fileDescription' => [
                $itineraryFileId => $fileDescription,
            ],
        ]);

        $criteria['Description'] = $fileDescription;
        $I->seeInDatabase('ItineraryFile', $criteria);
    }

    public function deleteTripSegment(\TestSymfonyGuy $I)
    {
        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaFlight.json');
        $schema = $I->getContainer()->get('jms_serializer')->deserialize($schemaRaw, SchemaFlight::class, 'json');
        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)->find($I->createAwAccount($this->user->getUserid(), 'aeroplan', 'nothing'));
        $report = $itinerariesProcessor->save([$schema], SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER));
        $I->assertCount(1, $report->getAdded());

        /** @var Trip $trip */
        $trip = $report->getAdded()[0];
        $segment = $trip->getSegments()[0];

        $I->saveCsrfToken();

        $I->sendAjaxPostRequest('/timeline/delete/T.' . $segment->getId());
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("TripSegment", ["TripSegmentID" => $segment->getId(), "Hidden" => 2]);

        /** @var EntityManagerInterface $em */
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");
        // reload entities, previous version were detached from EM somewhere
        $segment = $em->getRepository(\AwardWallet\MainBundle\Entity\Tripsegment::class)->find($segment->getId());
        $trip = $em->getRepository(\AwardWallet\MainBundle\Entity\Trip::class)->find($trip->getId());
        $I->assertTrue($segment->isHiddenByUser());
        $I->assertTrue($trip->isHiddenByUser());

        $I->sendAjaxPostRequest('/timeline/delete/T.' . $segment->getId() . "?undelete=true");
        $I->seeResponseCodeIs(200);
        $I->seeInDatabase("TripSegment", ["TripSegmentID" => $segment->getId(), "Hidden" => 0]);
        $em->refresh($segment);
        $I->assertFalse($segment->isHiddenByUser());
        $I->assertFalse($trip->isHiddenByUser());
    }

    public function testNoForeignFeesCards(\TestSymfonyGuy $I)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $I->getContainer()->get("doctrine.orm.entity_manager");
        /** @var RouterInterface $router */
        $router = $I->getContainer()->get('router');

        $cards = $entityManager->getConnection()->fetchAllAssociative("
            SELECT CreditCardID FROM CreditCard WHERE ForeignTransactionFee = '0.0' LIMIT 4
        ");

        foreach ($cards as $card) {
            $I->haveInDatabase('UserCreditCard', [
                'UserID' => $this->user->getId(),
                'CreditCardID' => $card['CreditCardID'],
            ]);
        }

        $schemaRaw = file_get_contents(__DIR__ . '/../../../_data/itineraries/schemaFlight.json');
        /** @var SchemaFlight $schema */
        $schema = $I->getContainer()->get('jms_serializer')
            ->deserialize($schemaRaw, SchemaFlight::class, 'json');

        $itinerariesProcessor = $I->getContainer()->get(ItinerariesProcessor::class);
        /** @var Account $account */
        $account = $I->grabService('doctrine')->getRepository(Account::class)
            ->find($I->createAwAccount($this->user->getId(), 'aeroplan', 'nothing'));

        $schema->segments[0]->arrival->airportCode = 'PEE';
        $schema->segments[0]->arrival->name = 'Perm International Airport';
        $schema->segments[0]->arrival->address->text = 'PEE';
        $schema->segments[0]->arrival->address->city = 'Moscow';
        $schema->segments[0]->arrival->address->addressLine = 'Sheremetyevo International Airport';
        $schema->segments[0]->arrival->address->countryName = 'Russia';
        $schema->segments[0]->arrival->address->countryCode = 'RU';
        $schema->segments[0]->arrival->address->stateName = '';
        $schema->segments[0]->arrival->address->lat = '55.966324';
        $schema->segments[0]->arrival->address->lng = '37.416574';

        unset($schema->segments[1]);

        $report = $itinerariesProcessor->save(
            [$schema],
            SavingOptions::savingByAccount($account, SavingOptions::INITIALIZED_BY_USER)
        );

        $I->assertCount(1, $report->getAdded());
        $I->sendAjaxGetRequest($router->generate('aw_timeline_data'));
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseIsJson();

        $noForeignFeesCards = $I->grabDataFromResponseByJsonPath('noForeignFeesCards');
        $I->assertNotEmpty($noForeignFeesCards);
    }
}
