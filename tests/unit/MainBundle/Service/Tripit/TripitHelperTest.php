<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Tripit;

use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Service\Tripit\Serializer\ProfileEmailAddressObject;
use AwardWallet\MainBundle\Service\Tripit\TripitConverter;
use AwardWallet\MainBundle\Service\Tripit\TripitHelper;
use AwardWallet\MainBundle\Service\Tripit\TripitHttpClient;
use AwardWallet\MainBundle\Service\Tripit\TripitImportResult;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @group frontend-unit
 */
class TripitHelperTest extends BaseContainerTest
{
    private ?int $userId;
    private ?Usr $user;
    private ?TripitHelper $helper;
    private ?TripRepository $tripRepository;

    public function _before()
    {
        parent::_before();
        $this->userId = $this->aw->createAwUser();
        $this->user = $this->em->getRepository(Usr::class)->find($this->userId);
        $this->tripRepository = $this->em->getRepository(Trip::class);
    }

    public function _after()
    {
        $this->userId = null;
        $this->user = null;
        $this->helper = null;
        $this->tripRepository = null;
        parent::_after();
    }

    /**
     * Проверяет получение всех резерваций.
     */
    public function testListTrip()
    {
        $this->createHelper(function ($user, $verb, $entity, $queryParams) {
            switch ($entity) {
                case 'trip':
                    return self::getTrips();

                case 'profile':
                    return self::getProfile();

                default:
                    throw new \RuntimeException('Incorrect verb or entity');
            }
        });

        /** @var Trip[] $trips */
        $trips = $this->tripRepository->findBy(['user' => $this->userId]);
        $this->assertCount(0, $trips);

        $tripitUser = new TripitUser($this->user, $this->em);
        $result = $this->helper->list($tripitUser);

        $this->assertInstanceOf(TripitImportResult::class, $result);
        $this->assertEquals(true, $result->getSuccess());
        $this->assertCount(1, $result->getItineraries());

        $trip = $this->tripRepository->findOneBy(['user' => $this->userId]);
        $tripSegments = $trip->getSegments()->getValues();
        $this->assertCount(2, $tripSegments);

        $jsonArray = json_decode(self::getTrips(), true);

        foreach ($jsonArray['AirObject']['Segment'] as $key => $segment) {
            $this->assertEquals(
                ['DepCode' => $segment['start_airport_code'], 'ArrCode' => $segment['end_airport_code']],
                ['DepCode' => $tripSegments[$key]->getDepcode(), 'ArrCode' => $tripSegments[$key]->getArrcode()]
            );
        }
    }

    /**
     * Проверяет получение всех резерваций, если предстоящие события отсутствуют.
     */
    public function testListTripEmptyReservations()
    {
        $this->createHelper(function ($user, $verb, $entity, $queryParams) {
            switch ($entity) {
                case 'trip':
                    return '[]';

                case 'profile':
                    return self::getProfile();

                default:
                    throw new \RuntimeException('Incorrect verb or entity');
            }
        });

        /** @var Trip[] $trips */
        $trips = $this->tripRepository->findBy(['user' => $this->userId]);
        $this->assertCount(0, $trips);

        $tripitUser = new TripitUser($this->user, $this->em);
        $result = $this->helper->list($tripitUser);

        $this->assertInstanceOf(TripitImportResult::class, $result);
        $this->assertEquals(true, $result->getSuccess());
        $this->assertCount(0, $result->getItineraries());

        $trip = $this->tripRepository->findOneBy(['user' => $this->userId]);
        $this->assertEquals(null, $trip);
    }

    /**
     * Проверяет получение всех резерваций, в случае, если токен пользователя недействителен.
     */
    public function testListTripUnauthorized()
    {
        $this->createHelper(function ($user, $verb, $entity, $queryParams) {
            throw new UnauthorizedHttpException('Unauthorized');
        });

        $tripitUser = new TripitUser($this->user, $this->em);
        $result = $this->helper->list($tripitUser);

        $this->assertInstanceOf(TripitImportResult::class, $result);
        $this->assertEquals(false, $result->getSuccess());
        $this->assertCount(0, $result->getItineraries());
    }

    /**
     * Проверяет получение информации о профиле пользователя.
     */
    public function testGetProfile()
    {
        $this->createHelper(function ($user, $verb, $entity, $queryParams) {
            return self::getProfile();
        });

        $tripitUser = new TripitUser($this->user, $this->em);
        $result = $this->helper->getProfile($tripitUser);

        $this->assertInstanceOf(ProfileEmailAddressObject::class, $result);
        $this->assertEquals([
            'address' => 'admin@awardwallet.com',
            'is_confirmed' => true,
            'is_primary' => true,
        ], [
            'address' => $result->getAddress(),
            'is_confirmed' => $result->getIsConfirmed(),
            'is_primary' => $result->getIsPrimary(),
        ]);
    }

    /**
     * Создаёт экземпляр класса TripitHelper.
     *
     * @param callable $request анонимная функция, которая переопределяет метод `request()`
     */
    private function createHelper(callable $request)
    {
        $mock = $this->mockServiceWithBuilder(TripitHttpClient::class);
        $mock->method('request')->willReturnCallback($request);

        $this->helper = new TripitHelper(
            new TripitConverter($this->container->get('jms_serializer'), $this->container->get('validator'), $this->container->get('logger')),
            $mock,
            $this->container->get(ItinerariesProcessor::class),
            new TestLogger(),
            $this->em,
            [
                $this->container->get(TripRepository::class),
            ]
        );
    }

    /**
     * Получить объекты путешествий, которые приходят от API.
     */
    private static function getTrips(): string
    {
        return json_encode([
            'AirObject' => [
                'id' => 20,
                'trip_id' => 2,
                'booking_date' => '2023-08-02',
                'booking_site_conf_num' => 'DMP000',
                'booking_site_name' => 'Air Serbia',
                'booking_site_phone' => '+381 11 300 00 00',
                'booking_site_url' => 'https://www.airserbia.com/',
                'supplier_conf_num' => 'DMP000',
                'supplier_name' => '',
                'supplier_phone' => '',
                'supplier_url' => '',
                'Segment' => [
                    [
                        'StartDateTime' => [
                            'date' => '2023-09-05',
                            'time' => '02:00:00',
                            'timezone' => 'Europe/Moscow',
                            'utc_offset' => '+03:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-09-05',
                            'time' => '04:30:00',
                            'timezone' => 'Europe/Belgrade',
                            'utc_offset' => '+02:00',
                        ],
                        'start_airport_code' => 'SVO',
                        'start_airport_latitude' => '55.966324',
                        'start_airport_longitude' => '37.416574',
                        'start_city_name' => 'Moscow',
                        'start_country_code' => 'RU',
                        'start_terminal' => 'C - International',
                        'end_airport_code' => 'BEG',
                        'end_airport_latitude' => '44.819444',
                        'end_airport_longitude' => '20.306944',
                        'end_city_name' => 'Belgrade',
                        'end_country_code' => 'RS',
                        'end_terminal' => '2',
                        'marketing_airline' => 'Air Serbia',
                        'marketing_airline_code' => 'JU',
                        'marketing_flight_number' => '659',
                        'aircraft' => '319',
                        'aircraft_display_name' => 'Airbus A319',
                        'distance' => '1,071 mi',
                        'duration' => '3h, 05m',
                    ],
                    [
                        'StartDateTime' => [
                            'date' => '2023-09-11',
                            'time' => '20:40:00',
                            'timezone' => 'Europe/Belgrade',
                            'utc_offset' => '+02:00',
                        ],
                        'EndDateTime' => [
                            'date' => '2023-09-12',
                            'time' => '00:50:00',
                            'timezone' => 'Europe/Moscow',
                            'utc_offset' => '+03:00',
                        ],
                        'start_airport_code' => 'BEG',
                        'start_airport_latitude' => '44.819444',
                        'start_airport_longitude' => '20.306944',
                        'start_city_name' => 'Belgrade',
                        'start_country_code' => 'RS',
                        'start_terminal' => '2',
                        'end_airport_code' => 'SVO',
                        'end_airport_latitude' => '55.966324',
                        'end_airport_longitude' => '37.416574',
                        'end_city_name' => 'Moscow',
                        'end_country_code' => 'RU',
                        'end_terminal' => 'C - International',
                        'marketing_airline' => 'Air Serbia',
                        'marketing_airline_code' => 'JU',
                        'marketing_flight_number' => '658',
                        'aircraft' => '319',
                        'aircraft_display_name' => 'Airbus A319',
                        'distance' => '1,071 mi',
                        'duration' => '3h, 10m',
                    ],
                ],
                'Traveler' => [
                    [
                        'first_name' => 'Kyou',
                        'last_name' => 'Fujibayashi',
                        'ticket_num' => '1152100000001',
                    ],
                    [
                        'first_name' => 'Ryou',
                        'last_name' => 'Fujibayashi',
                        'ticket_num' => '1152100000002',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Получить объект профиля пользователя, который приходит от API.
     */
    private static function getProfile(): string
    {
        return json_encode([
            'Profile' => [
                'ProfileEmailAddresses' => [
                    'ProfileEmailAddress' => [
                        [
                            'uuid' => '4a8f0000-0000-0000-0000-000000000000',
                            'uuid_ref' => '',
                            'email_ref' => 'TJmn000000000000000000',
                            'address' => 'admin@awardwallet.com',
                            'is_auto_import' => false,
                            'is_confirmed' => true,
                            'is_primary' => true,
                            'is_auto_inbox_eligible' => true,
                        ],
                        [
                            'uuid' => '9f270000-0000-0000-0000-000000000000',
                            'uuid_ref' => '',
                            'email_ref' => 'xJno000000000000000000',
                            'address' => 'user@awardwallet.com',
                            'is_auto_import' => false,
                            'is_confirmed' => false,
                            'is_primary' => false,
                            'is_auto_inbox_eligible' => true,
                        ],
                    ],
                ],
                'is_client' => true,
                'is_pro' => false,
            ],
        ]);
    }
}
