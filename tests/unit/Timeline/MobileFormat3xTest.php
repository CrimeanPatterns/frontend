<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\None;
use Clock\ClockNative;
use Clock\ClockTest;
use Duration\Duration;
use Herrera\Version\Parser;

/**
 * @group frontend-unit
 */
class MobileFormat3xTest extends BaseTimelineTest
{
    public const NOTES = 'Some text before link https://yandex.ru/search/?text=awardwallet&lr=50 and some text after link';

    public function _before()
    {
        global $kernel;
        parent::_before();

        $kernel = new \AppKernel('test', true);
        $this->mockService(ClockNative::class, new ClockTest(Duration::fromDateTime(new \DateTimeImmutable('Jan 1, 2023'))));
        // $this->container->set(ClockNative::class, new ClockTest(Duration::fromDateTime(new \DateTimeImmutable('Jan 1, 2023'))));
        $this->container->get('aw.api.versioning')
            ->setVersionsProvider(new MobileVersions('ios'))
            ->setVersion(Parser::toVersion('3.20.0+abc100500'));
    }

    public function testTrain()
    {
        $this->assertTimelineJson('train.json', $this->trainData(), 'Trip');
    }

    public function testTrip()
    {
        $this->assertTimelineJson('trip.json', $this->tripData(), 'Trip');
    }

    public function testSharedTrip()
    {
        $this->assertSharedTimelineJson('trip_shared.json', $this->tripData(), 'Trip');
    }

    public function testReservation()
    {
        $this->assertTimelineJson('reservation.json', $this->reservationData(), 'Reservation');
    }

    public function testSharedReservation()
    {
        $this->assertSharedTimelineJson('reservation_shared.json', $this->reservationData(), 'Reservation');
    }

    public function testRental()
    {
        $this->assertTimelineJson('rental.json', $this->rentalData(), 'Rental');
    }

    public function testRentalNoCompany()
    {
        $this->assertTimelineJson('rental_no_company.json', $this->rentalDataNoCompany(), 'Rental');
    }

    public function testSharedRental()
    {
        $this->assertSharedTimelineJson('rental_shared.json', $this->rentalData(), 'Rental');
    }

    public function testTaxiRide(string $jsonFile = 'taxiride.json')
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');
        $this->assertTimelineJson(
            $jsonFile,
            [
                [
                    'Kind' => 'L',
                    'Number' => '100500',
                    'PickupDatetime' => diff($baseDate, $baseDate->modify('+1 day')),
                    'PickupLocation' => 'LGA',
                    'DropoffDatetime' => diff($baseDate->modify('+1 day'), $baseDate->modify('+4 day')),
                    'DropoffLocation' => 'EWR',
                    'PickupPhone' => diff('+100 200 300', '+100 200 301'),
                    'RentalCompany' => 'Fasten',
                ],
            ],
            'Rental',
            function () {
                $rental = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Rental::class)->findOneBy(['user' => $this->user->getUserid()]);
                $rental->setType(Rental::TYPE_TAXI);
                $rental->setConfirmationNumber(null);
                $rental->setAccount(null);
                $rental->setRealProvider(null);
            }
        );
    }

    public function testTaxiRideNewVersions()
    {
        $this->container->get('aw.api.versioning')
            ->setVersionsProvider(new MobileVersions('ios'))
            ->setVersion(Parser::toVersion('3.24.14+abc100500'));

        $this->testTaxiRide('taxiride_new.json');
    }

    public function testRestaurant()
    {
        $this->container->get("translator")->setLocale("ru");
        $this->assertTimelineJson('restaurant.json', $this->restaurantData(), 'Restaurant');
    }

    public function testSharedRestaurant()
    {
        $this->assertSharedTimelineJson('restaurant_shared.json', $this->restaurantData(), 'Restaurant');
    }

    public function testMetaSegments()
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');
        $parsedData = [
            [
                'Kind' => 'T',
                'RecordLocator' => '100500',
                'TripSegments' => [
                    [
                        'AirlineName' => 'Air Transat',
                        'FlightNumber' => '10050',
                        'DepCode' => 'LAX',
                        'DepName' => 'LAX',
                        'DepDate' => $baseDate,
                        'ArrCode' => 'JFK',
                        'ArrName' => 'JFK',
                        'ArrDate' => $baseDate->modify('+3 hours'),
                    ],
                    [
                        'AirlineName' => 'Air Transat',
                        'FlightNumber' => '10051',
                        'DepCode' => 'JFK',
                        'DepName' => 'JFK',
                        'DepDate' => $baseDate->modify('+18 hours'),
                        'ArrCode' => 'FLL',
                        'ArrName' => 'FLL',
                        'ArrDate' => $baseDate->modify('+22 hours'),
                    ],
                ],
            ],
        ];

        $plan = (new Plan())
            ->setName('Some Test Plan')
            ->setUser($this->em->find(Usr::class, $this->user->getUserid()))
            ->setStartDate(new \DateTime('@' . $baseDate->modify('-3 hours')->getTimestamp()))
            ->setEndDate(new \DateTime('@' . $baseDate->modify('+30 hours')->getTimestamp()))
            ->setShareCode(StringHandler::getRandomCode(10));
        $this->em->persist($plan);
        $this->em->flush();

        $this->doAssertTimelineJson(
            codecept_data_dir("timeline/mobile/3.x/meta.json"),
            $parsedData,
            1,
            'Trip',
            fn () => $this->container->get('aw.timeline.helper.mobile')->getUserTimelines($this->user),
            [
                'PLAN_ID.RAW' => $plan->getId(),
                'PLAN_ID.0' => 'PS.' . $plan->getId(),
                'PLAN_ID.1' => 'PE.' . $plan->getId(),
                'PLAN_SHARE_ID.0' => $plan->getEncodedShareCode(),
            ]
        );
    }

    protected function tripData(): array
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');

        return
            [
                [
                    'Kind' => 'T',
                    'RecordLocator' => '100500',
                    'TripNumber' => '10501',
                    'Passengers' => diff(
                        ['John', 'Bill', 'Peter'],
                        ['Julia', 'Jessica', 'Lisa']
                    ),
                    'TotalCharge' => diff('100.22', '222.33'),
                    'Currency' => diff('USD', 'EUR'),
                    'SpentAwards' => diff('100532', '4050440'),
                    'Fees' => diff([
                        ['Name' => 'Some Fee 1', 'Charge' => '28.9'],
                        ['Name' => 'Some Fee 2', 'Charge' => '120.9'],
                    ]),
                    'AccountNumbers' => '2AJKD384',
                    'TripSegments' => [
                        [
                            'FlightNumber' => '55960',
                            'DepCode' => 'JFK',
                            'DepName' => 'New York John F. Kennedy International Airport',
                            'DepartureTerminal' => diff('1A', '2B'),
                            'DepDate' => diff($baseDate, $baseDate->modify('+20 minutes')),
                            'ArrCode' => 'LAX',
                            'ArrName' => 'Los Angeles International Airport',
                            'ArrivalTerminal' => diff('3C', '4D'),
                            'ArrDate' => diff($baseDate->modify('+5 hour'), $baseDate->modify('+5 hour 25 minutes')),
                            'AirlineName' => 'Test Airline',
                            'Aircraft' => diff('Su-27', 'Su-33'),
                            'TraveledMiles' => diff('100', '140'),
                            'Cabin' => diff('Economy', 'Business'),
                            'BookingClass' => diff('T', 'Q'),
                            'Seats' => diff('11A', '22B'),
                            'Duration' => '5h',
                            'Meal' => diff('shaverma', 'vodka'),
                            'Smoking' => diff(true, false),
                            'Stops' => diff('0', '1'),
                            'Operator' => diff('S7', 'Aeroflot'),
                            'Gate' => diff('32', '54'),
                            'ArrivalGate' => diff('22', '44'),
                            'BaggageClaim' => diff('12', '20'),
                        ],
                    ],
                ],
            ];
    }

    protected function trainData(): array
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');

        return
            [
                [
                    'Kind' => 'T',
                    'RecordLocator' => '100500',
                    'TripNumber' => '10501',
                    'Passengers' => diff(
                        ['John', 'Bill', 'Peter'],
                        ['Julia', 'Jessica', 'Lisa']
                    ),
                    'TripCategory' => TRIP_CATEGORY_TRAIN,
                    'TotalCharge' => diff('100.22', '222.33'),
                    'Currency' => 'USD',
                    'Fees' => diff([
                        ['Name' => 'Some Fee 1', 'Charge' => '28.9'],
                        ['Name' => 'Some Fee 2', 'Charge' => '120.9'],
                    ]),
                    'TripSegments' => [
                        [
                            'FlightNumber' => '55960',
                            'DepCode' => TRIP_CODE_UNKNOWN,
                            'DepAddress' => $depAddress = 'New York John F. Kennedy International Airport',
                            'DepName' => $depAddress,
                            'DepDate' => diff($baseDate, $baseDate->modify('+20 minutes')),
                            'ArrCode' => TRIP_CODE_UNKNOWN,
                            'ArrName' => $arrAddress = 'Los Angeles International Airport',
                            'ArrAddress' => $arrAddress,
                            'ArrDate' => diff($baseDate->modify('+5 hour'), $baseDate->modify('+5 hour 25 minutes')),
                            'AirlineName' => 'Test Airline',
                            'Vehicle' => 'Parovoz P36',
                            'Type' => 'Train',
                            'TraveledMiles' => diff('100', '140'),
                            'Cabin' => diff('Economy', 'Business'),
                            'BookingClass' => diff('T', 'Q'),
                            'Seats' => diff('11A', '22B'),
                            'Duration' => '5h',
                            'Meal' => diff('shaverma', 'vodka'),
                            'Smoking' => diff(true, false),
                            'Stops' => diff('0', '1'),
                        ],
                    ],
                ],
            ];
    }

    protected function reservationData(): array
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');

        return
            [
                [
                    'Kind' => 'R',
                    'ConfirmationNumber' => '100500',
                    'TripNumber' => '10501',
                    'ConfirmationNumbers' => 'Room1_100500A11, Room2_100500A12',
                    'HotelName' => 'Sheraton Palace Hotel, Moscow',
                    'CheckInDate' => diff($baseDate, $baseDate->modify('+1 day')),
                    'CheckOutDate' => diff($baseDate->modify('+1 day'), $baseDate->modify('+5 day')),
                    'Address' => '1st Tverskaya Yamskaya Street 19, Moscow, 125047',
                    'Phone' => diff('+100 500 700', '+100 500 11'),
                    'Fax' => diff('+200 300 800', '+200 300 801'),
                    'GuestNames' => diff(
                        ['John', 'Bill', 'Peter'],
                        ['Julia', 'Jessica', 'Lisa']
                    ),
                    'Guests' => 3,
                    'Kids' => 3,
                    'Rooms' => 2,
                    'Rate' => diff('109.00 EUR / night', '209.00 EUR / night'),
                    'RoomType' => '2 QUEEN BEDS NONSMOKING',
                    'CancellationPolicy' => 'Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy. Too long cancellation policy.',
                    'RoomTypeDescription' => "Some text with\nnew lines\nnewlines",
                    'Cost' => diff('1005', '1200'),
                    'Total' => diff('1200', '1500'),
                    'Currency' => 'EUR',
                    'AccountNumbers' => diff('100500', '1006001'),
                    'FreeNights' => diff(3, 8),
                ],
            ];
    }

    protected function rentalData(): array
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');

        return
            [
                [
                    'Kind' => 'L',
                    'Number' => '100500',
                    'TripNumber' => '1005001',
                    'PickupDatetime' => diff($baseDate, $baseDate->modify('+1 day')),
                    'PickupLocation' => 'LGA',
                    'DropoffDatetime' => diff($baseDate->modify('+1 day'), $baseDate->modify('+4 day')),
                    'DropoffLocation' => 'EWR',
                    'PickupPhone' => diff('+100 200 300', '+100 200 301'),
                    'PickupFax' => diff('+100 200 400', '+100 200 401'),
                    'PickupHours' => diff('11:00', '12:00'),
                    'DropoffPhone' => diff('+100 200 300', '+100 200 301'),
                    'DropoffHours' => diff('16:00', '17:00'),
                    'DropoffFax' => diff('+100 200 400', '+100 200 401'),
                    'RentalCompany' => 'Test Rental Company',
                    'Discounts' => diff(
                        [['Code' => 'SomeCode1', 'Name' => 'SomeValue1']],
                        [['Code' => 'SomeCode2', 'Name' => 'SomeValue2']]
                    ),
                    'Fees' => diff([
                        ['Name' => 'Some Fee 1', 'Charge' => '28.9'],
                        ['Name' => 'Some Fee 2', 'Charge' => '120.9'],
                    ]),
                    'CarType' => 'Mid-Size Economy',
                    'CarModel' => 'Ford Focus',
                    'RenterName' => 'John Smith',
                    'Total' => diff('1200', '1500'),
                    'BaseFare' => diff('1000', '1100'),
                    'Currency' => 'EUR',
                    'TotalTaxAmount' => diff('200', '300'),
                    'SpentAwards' => '100 rentals',
                    'EarnedAwards' => '20 rentals',
                    'AccountNumbers' => 'RENTAL100500',
                    'ServiceLevel' => 'gold',
                    'PricedEquips' => [['Name' => 'GPS', 'Charge' => '20 EUR']],
                    'PaymentMethod' => 'Pay Now',
                ],
            ];
    }

    protected function doAssertTimelineJson(
        string $expectedTimelineContentFileName,
        array $parserData,
        int $checkTimes,
        string $entityName,
        callable $timelineItemsGenerator,
        array $context = []
    ) {
        $this->generateItineraries($parserData, $checkTimes);
        $entities = $this->loadHomogeneousEntities();
        $ctxMerge = $this->getCtxMergeData($entities);
        $this->jsonNormalizer->expectJsonTemplate(
            $expectedTimelineContentFileName,
            \json_encode($timelineItemsGenerator($entities)),
            \array_merge($context, $ctxMerge)
        );
    }

    protected function rentalDataNoCompany(): array
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');

        return
            [
                [
                    'Kind' => 'L',
                    'Number' => '100500',
                    'TripNumber' => '1005001',
                    'PickupDatetime' => diff($baseDate, $baseDate->modify('+1 day')),
                    'PickupLocation' => 'LGA',
                    'DropoffDatetime' => diff($baseDate->modify('+1 day'), $baseDate->modify('+4 day')),
                    'DropoffLocation' => 'EWR',
                    'PickupPhone' => diff('+100 200 300', '+100 200 301'),
                    'PickupFax' => diff('+100 200 400', '+100 200 401'),
                    'PickupHours' => diff('11:00', '12:00'),
                    'DropoffPhone' => diff('+100 200 300', '+100 200 301'),
                    'DropoffHours' => diff('16:00', '17:00'),
                    'DropoffFax' => diff('+100 200 400', '+100 200 401'),
                    'Discounts' => diff(
                        [['Code' => 'SomeCode1', 'Name' => 'SomeValue1']],
                        [['Code' => 'SomeCode2', 'Name' => 'SomeValue2']]
                    ),
                    'Fees' => diff([
                        ['Name' => 'Some Fee 1', 'Charge' => '28.9'],
                        ['Name' => 'Some Fee 2', 'Charge' => '120.9'],
                    ]),
                    'CarType' => 'Mid-Size Economy',
                    'CarModel' => 'Ford Focus',
                    'RenterName' => 'John Smith',
                    'Total' => diff('1200', '1500'),
                    'BaseFare' => diff('1000', '1100'),
                    'Currency' => 'EUR',
                    'TotalTaxAmount' => diff('200', '300'),
                    'SpentAwards' => '100 rentals',
                    'EarnedAwards' => '20 rentals',
                    'AccountNumbers' => 'RENTAL100500',
                    'ServiceLevel' => 'gold',
                    'PricedEquips' => [['Name' => 'GPS', 'Charge' => '20 EUR']],
                    'PaymentMethod' => 'Pay Now',
                ],
            ];
    }

    protected function restaurantData()
    {
        $baseDate = new \DateTimeImmutable('Jan 16, 2023');

        return
            [
                [
                    'Kind' => 'E',
                    'ConfNo' => 'TEST100500',
                    'TripNumber' => '10050',
                    'Name' => 'McDonalds',
                    'StartDate' => diff($baseDate, $baseDate->modify('+3 hours')),
                    'EndDate' => diff($baseDate->modify('+3 hours'), $baseDate->modify('+6 hours')),
                    'Address' => 'Tverskya 42, Moscow, Russia',
                    'Phone' => '+100500',
                    'DinerName' => 'John Smith',
                    'Guests' => 10,
                    'TotalCharge' => diff('1000', '1200'),
                    'Currency' => 'EUR',
                    'Tax' => diff('100', '200'),
                    'SpentAwards' => '100 burgers',
                    'EarnedAwards' => '200 burgers',
                    'AccountNumbers' => 'TEST100500',
                    'EventType' => EVENT_RESTAURANT,
                ],
            ];
    }

    protected function assertSharedTimelineJson(
        string $expectedTimelineContentFileName,
        array $parserData,
        string $entityName
    ) {
        $this->doAssertTimelineJson(
            codecept_data_dir("timeline/mobile/3.x/{$expectedTimelineContentFileName}"),
            $parserData,
            2,
            $entityName,
            function ($entities) {
                array_walk($entities, [$this, 'notesMofifier']);
                $this->em->flush();
                $entity = $entities[0];
                $timelineItems = $this->container->get('aw.timeline.helper.mobile')->getSharedTimelineItems(StringUtils::base64_encode_url(implode('.', [
                    $entity->getKind(),
                    $entity->getId(),
                    $entity->getShareCode(),
                ])));

                return [['items' => $timelineItems]];
            }
        );
    }

    protected function assertTimelineJson(
        string $expectedTimelineContentFileName,
        array $parserData,
        string $entityName,
        ?callable $modifier = null
    ) {
        $this->doAssertTimelineJson(
            codecept_data_dir("timeline/mobile/3.x/{$expectedTimelineContentFileName}"),
            $parserData,
            2,
            $entityName,
            function (array $entities) use ($modifier) {
                if (null === $modifier) {
                    $modifier = [$this, 'notesMofifier'];
                } else {
                    $modifier();
                }

                array_walk($entities, $modifier);
                $this->em->flush();

                return $this->container->get('aw.timeline.helper.mobile')->getUserTimelines($this->user);
            }
        );
    }

    protected function notesMofifier(Itinerary $entity)
    {
        $entity->setNotes(self::NOTES);
    }

    protected function generateItineraries(array $itineraries, $maxCheckCount = 2)
    {
        $checkCount = 1;
        $evaluate = \Closure::fromCallable([$this, 'evaluate']);
        $this->aw->createAwProvider(
            $providerName = 'mblfmt' . StringUtils::getRandomCode(12),
            $providerName,
            [
                'ShortName' => 'Some New Provider',
            ],
            [
                'ParseItineraries' => function () use (&$checkCount, $itineraries, $evaluate) {
                    array_walk_recursive($itineraries, function (&$value, $key) use (&$checkCount, $evaluate) {
                        $value = $evaluate($value, $checkCount);

                        return $value;
                    });

                    $checkCount++;

                    return $itineraries;
                },
            ]
        );
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $providerName, 'some.login');

        while ($checkCount <= $maxCheckCount) {
            $this->aw->checkAccount($accountId);
            $this->setGateAndBaggageClaim($itineraries, $checkCount - 1, $accountId);
        }

        $this->container->get('doctrine.orm.entity_manager')->clear();
    }

    private function evaluate($value, int $checkCount)
    {
        if ($value instanceof Diff) {
            $value = $value->storage[$checkCount - 1] ?? $value->storage[count($value->storage) - 1];
        }

        if ($value instanceof \Closure) {
            $value = $value($checkCount);
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->getTimestamp();
        }

        return $value;
    }

    /**
     * Only works for 1 segment.
     */
    private function setGateAndBaggageClaim(array $itineraries, int $checkCount, int $accoundId): void
    {
        foreach ($itineraries as $flight) {
            if ('T' !== $flight['Kind']) {
                return;
            }
            $tripId = $this->db->grabFromDatabase('Trip', 'TripID', [
                'AccountID' => $accoundId,
                'Category' => Trip::CATEGORY_AIR,
            ]);

            if (empty($flight['TripSegments'])) {
                return;
            }
            $segment = $flight['TripSegments'][0];
            $update = function ($newValue, string $fieldName) use ($checkCount, $tripId) {
                $oldValue = $this->db->grabFromDatabase(
                    'TripSegment',
                    $fieldName,
                    ['TripID' => $tripId]
                );
                $newValue = $this->evaluate($newValue, $checkCount);
                $tripSegmentId = $this->db->grabFromDatabase(
                    'TripSegment',
                    'TripSegmentID',
                    ['TripID' => $tripId]
                );
                $this->db->updateInDatabase(
                    'TripSegment',
                    [$fieldName => $newValue],
                    ['TripID' => $tripId]
                );

                if (null !== $oldValue && $oldValue !== $newValue) {
                    $this->db->haveInDatabase('DiffChange', [
                        'SourceID' => "S.$tripSegmentId",
                        'Property' => $fieldName,
                        'oldVal' => $oldValue,
                        'newVal' => $newValue,
                        'ChangeDate' => date('Y:m:d H:i:s'),
                        'ExpirationDate' => (new \DateTime())->modify('+6 months')->format('Y:m:d H:i:s'),
                    ]);
                }
            };

            if (isset($segment['Gate'])) {
                $update($segment['Gate'], 'DepartureGate');
            }

            if (isset($segment['ArrivalGate'])) {
                $update($segment['ArrivalGate'], 'ArrivalGate');
            }

            if (isset($segment['BaggageClaim'])) {
                $update($segment['BaggageClaim'], 'BaggageClaim');
            }
        }
    }
}

class Diff
{
    public $storage;

    public function __construct(...$diff)
    {
        $this->storage = $diff;
    }
}

function diff(...$values)
{
    return new Diff(...$values);
}

function array_walk_recursive(array &$array, callable $callback)
{
    foreach ($array as $key => &$value) {
        $res = $callback($value, $key);

        if ($res instanceof None) {
            unset($array[$key]);
        }

        if (is_array($value)) {
            array_walk_recursive($value, $callback);
        }
    }
}
