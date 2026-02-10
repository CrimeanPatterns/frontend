<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\Aircraft;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\Bus;
use AwardWallet\Schema\Itineraries\BusSegment;
use AwardWallet\Schema\Itineraries\Car;
use AwardWallet\Schema\Itineraries\CarRental;
use AwardWallet\Schema\Itineraries\CarRentalDiscount;
use AwardWallet\Schema\Itineraries\CarRentalLocation;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\CruiseDetails;
use AwardWallet\Schema\Itineraries\CruiseSegment;
use AwardWallet\Schema\Itineraries\Event;
use AwardWallet\Schema\Itineraries\Fee;
use AwardWallet\Schema\Itineraries\Ferry;
use AwardWallet\Schema\Itineraries\FerrySegment;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\HotelReservation;
use AwardWallet\Schema\Itineraries\IssuingCarrier;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\OperatingCarrier;
use AwardWallet\Schema\Itineraries\Parking;
use AwardWallet\Schema\Itineraries\ParsedNumber;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\PhoneNumber;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Itineraries\Room;
use AwardWallet\Schema\Itineraries\Train;
use AwardWallet\Schema\Itineraries\TrainSegment;
use AwardWallet\Schema\Itineraries\Transfer;
use AwardWallet\Schema\Itineraries\TransferLocation;
use AwardWallet\Schema\Itineraries\TransferSegment;
use AwardWallet\Schema\Itineraries\TransportLocation;
use AwardWallet\Schema\Itineraries\TravelAgency;
use AwardWallet\Schema\Itineraries\TripLocation;
use AwardWallet\Schema\Itineraries\VehicleExt;
use AwardWallet\Tests\Modules\Utils\ClosureEvaluator\Counter;
use AwardWallet\Tests\Modules\Utils\ClosureEvaluator\DateTimeImmutableFormatted;
use AwardWallet\Tests\Modules\Utils\ClosureEvaluator\DiffFactory;
use Clock\ClockNative;
use Clock\ClockTest;
use Duration\Duration;
use Herrera\Version\Parser;

use function AwardWallet\MainBundle\Globals\Utils\iter\randomOf;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @group frontend-unit
 */
class MobileFormat4xTest extends BaseTimelineTest
{
    private const OPERATING_AIRLINE_ID = 'oper';
    private const OPERATING_AIRLINE_SHORT_ID = 'op';

    private const ISSUING_AIRLINE_ID = 'issu';
    private const ISSUING_AIRLINE_SHORT_ID = 'is';

    private const MARKETING_AIRLINE_ID = 'mark';
    private const MARKETING_AIRLINE_SHORT_ID = 'ma';

    private const ELITE_LEVELS = [
        0 => 'Member 0',
        1 => 'Elite 1',
        2 => 'Elite 2',
        3 => 'Elite 3',
    ];

    private const DYNAMIC_DATA_PATTERN = [
        '#(/[^/]+)(/m/account/details/)a?\d+#' => 'awardwallet.com$2GENERATED',
    ];

    /**
     * @var array
     */
    private $travelAgencyInfo;

    /**
     * @var array
     */
    private $providerInfo;

    /**
     * @var DiffFactory
     */
    private $diffFactory;
    /**
     * @var Counter
     */
    private $diffCounter;

    /**
     * @var string[]
     */
    private $randomData = [];

    /**
     * Special characters.
     *
     * @var string
     */
    private $airlineChar1Alpha = [];

    /**
     * Special characters + letters.
     *
     * @var string
     */
    private $airlineChar2Alpha = [];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // special characters
        $this->airlineChar1Alpha = '!#$%&\'()*+0.:;<=>?@[^_`{|}~';
        // special characters + letters
        $this->airlineChar2Alpha = $this->airlineChar1Alpha . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    public function _before()
    {
        global $kernel;
        parent::_before();

        $kernel = new \AppKernel('test', true);
        $this->mockService(ClockNative::class, new ClockTest(Duration::fromDateTime(new \DateTimeImmutable('Jan 1, 2023'))));
        $this->container->get('aw.api.versioning')
            ->setVersionsProvider(new MobileVersions('ios'))
            ->setVersion(Parser::toVersion('4.49.6+abc100500'));

        $this->diffCounter = new Counter();
        $this->diffFactory = new DiffFactory($this->diffCounter);
    }

    public function _after()
    {
        parent::_after();

        $this->diffFactory = null;
        $this->randomData = [];
    }

    public function diff(...$values)
    {
        return ($this->diffFactory)(...$values);
    }

    public function testRental()
    {
        $this->assertTimelineJson('rental.json', fn () => $this->createRentalItineraries());
    }

    public function testParking()
    {
        $this->assertTimelineJson('parking.json', fn () => $this->createParkingItineraries());
    }

    public function testHotel()
    {
        $this->assertTimelineJson('reservation.json', fn () => $this->createReservationItineraries());
    }

    public function testRestaurant()
    {
        $this->assertTimelineJson('restaurant.json', fn () => $this->createRestaurantItineraries());
    }

    public function testBus()
    {
        $this->assertTimelineJson('bus.json', fn () => $this->createBusItineraries());
    }

    public function testTrain()
    {
        $this->assertTimelineJson('train.json', fn () => $this->createTrainItineraries());
    }

    public function testFerry()
    {
        $this->assertTimelineJson('ferry.json', fn () => $this->createFerryItineraries());
    }

    public function testTransfer()
    {
        $this->assertTimelineJson('transfer.json', fn () => $this->createTransferItineraries());
    }

    public function testTrip()
    {
        $airlines = [
            self::ISSUING_AIRLINE_ID => [
                $this->createAirline(
                    self::ISSUING_AIRLINE_ID,
                    self::ISSUING_AIRLINE_SHORT_ID
                ),
                self::ISSUING_AIRLINE_SHORT_ID,
            ],
            self::OPERATING_AIRLINE_ID => [
                $this->createAirline(
                    self::OPERATING_AIRLINE_ID,
                    self::OPERATING_AIRLINE_SHORT_ID
                ),
                self::OPERATING_AIRLINE_SHORT_ID,
            ],
            self::MARKETING_AIRLINE_ID => [
                $this->createAirline(
                    self::MARKETING_AIRLINE_ID,
                    self::MARKETING_AIRLINE_SHORT_ID
                ),
                self::MARKETING_AIRLINE_SHORT_ID,
            ],
        ];

        $i = 0;

        foreach ($airlines as $fourLetterCode => [['code' => $iata], $twoLetterCode]) {
            $provider = $this->createProviderWithPhones($fourLetterCode, $twoLetterCode, '+199 299 399' . (++$i));
            $airlines[$fourLetterCode][0]['provider'] = $provider;
            $this->db->updateInDatabase('Provider', ['IATACode' => $iata], ['ProviderID' => $provider['id']]);
            $this->db->haveInDatabase('ProviderProperty', [
                'ProviderID' => $provider['id'],
                'Name' => 'Status',
                'Code' => 'Status',
                'SortIndex' => 10,
                'Kind' => PROPERTY_KIND_STATUS,
            ]);
            $accountId = $this->aw->createAwAccount($this->user->getUserid(), (int) $provider['id'], 'some.login');
            $airlines[$fourLetterCode][0]['accountid'] = $accountId;
            $this->aw->createAccountProperty('Status', self::ELITE_LEVELS[2] . ' ' . $fourLetterCode, ['AccountID' => $accountId], $provider['id']);
        }

        $this->assertTimelineJson(
            'trip.json',
            fn () => $this->createTripItineraries($airlines),
            function () {
                /** @var Tripsegment $tripSegment */
                foreach (
                    it(
                        $this->em
                            ->getRepository(\AwardWallet\MainBundle\Entity\Trip::class)
                        ->findBy(['user' => $this->user])
                    )
                    ->flatMap(fn (Trip $trip) => $trip->getSegments())
                    ->toArray() as $tripSegment
                ) {
                    $tripSegment->setSources([
                        new Email(
                            StringHandler::getRandomCode(12),
                            StringHandler::getRandomCode(12),
                            new ParsedEmailSource(
                                ParsedEmailSource::SOURCE_SCANNER,
                                null,
                                StringHandler::getRandomCode(12),
                                true
                            ),
                            $this->createDefaultDate()
                        ),
                    ]);
                }

                $this->em->flush();
            },
            [
                'airlines' =>
                    it($airlines)
                    ->map(fn (array $data) => $data[0])
                    ->toArrayWithKeys(),
            ]
        );
    }

    public function testCruise()
    {
        $airlines = [
            self::ISSUING_AIRLINE_ID => [
                $this->createAirline(
                    self::ISSUING_AIRLINE_ID,
                    self::ISSUING_AIRLINE_SHORT_ID
                ),
                self::ISSUING_AIRLINE_SHORT_ID,
            ],
            self::OPERATING_AIRLINE_ID => [
                $this->createAirline(
                    self::OPERATING_AIRLINE_ID,
                    self::OPERATING_AIRLINE_SHORT_ID
                ),
                self::OPERATING_AIRLINE_SHORT_ID,
            ],
            self::MARKETING_AIRLINE_ID => [
                $this->createAirline(
                    self::MARKETING_AIRLINE_ID,
                    self::MARKETING_AIRLINE_SHORT_ID
                ),
                self::MARKETING_AIRLINE_SHORT_ID,
            ],
        ];

        $i = 0;

        foreach ($airlines as $fourLetterCode => [['code' => $iata], $twoLetterCode]) {
            $provider = $this->createProviderWithPhones($fourLetterCode, $twoLetterCode, '+199 299 399' . (++$i));
            $airlines[$fourLetterCode][0]['provider'] = $provider;
            $this->db->updateInDatabase('Provider', ['IATACode' => $iata], ['ProviderID' => $provider['id']]);
            $this->db->haveInDatabase('ProviderProperty', [
                'ProviderID' => $provider['id'],
                'Name' => 'Status',
                'Code' => 'Status',
                'SortIndex' => 10,
                'Kind' => PROPERTY_KIND_STATUS,
            ]);
            $accountId = $this->aw->createAwAccount($this->user->getUserid(), (int) $provider['id'], 'some.login');
            $airlines[$fourLetterCode][0]['accountid'] = $accountId;
            $this->aw->createAccountProperty('Status', self::ELITE_LEVELS[2] . ' ' . $fourLetterCode, ['AccountID' => $accountId], $provider['id']);
        }

        $this->assertTimelineJson(
            'cruise.json',
            fn () => $this->createCruiseItineraries($airlines),
            null,
            [
                'airlines' =>
                    it($airlines)
                    ->map(fn (array $data) => $data[0])
                    ->toArrayWithKeys(),
            ]
        );
    }

    public function createCruiseItineraries(array $airlinesData): array
    {
        $baseDate = $this->createDefaultDate();
        $trip = new Cruise();
        $trip->segments = [
            create(function (CruiseSegment $segment) use ($baseDate) {
                $segment->departure = create(function (TransportLocation $location) use ($baseDate) {
                    $location->stationCode = 'JFK';
                    $location->name = 'New York John F. Kennedy International Airport';
                    $location->address = create(function (Address $address) use ($location) {
                        $address->text = $location->name;
                        $address->addressLine = $location->name;
                    });
                    $location->localDateTime = $this->diff($baseDate(), $baseDate('+20 minutes'));
                });
                $segment->arrival = create(function (TransportLocation $location) use ($baseDate) {
                    $location->stationCode = 'LAX';
                    $location->name = 'Los Angeles International Airport';
                    $location->localDateTime = $this->diff($baseDate('+2 days'), $baseDate('+2 days 12 hours'));
                    $location->address = create(function (Address $address) use ($location) {
                        $address->text = $location->name;
                        $address->addressLine = $location->name;
                    });
                });
            }),
            create(function (CruiseSegment $segment) use ($baseDate) {
                $segment->departure = create(function (TransportLocation $location) use ($baseDate) {
                    $location->stationCode = 'LAX';
                    $location->name = 'Los Angeles International Airport';
                    $location->address = create(function (Address $address) use ($location) {
                        $address->text = $location->name;
                        $address->addressLine = $location->name;
                    });
                    $location->localDateTime = $this->diff($baseDate('+3 days'), $baseDate('+3 days 12 hours'));
                });
                $segment->arrival = create(function (TransportLocation $location) use ($baseDate) {
                    $location->stationCode = 'FLL';
                    $location->name = 'Fort';
                    $location->localDateTime = $this->diff($baseDate('+5 days'), $baseDate('+5 days 12 hours'));
                    $location->address = create(function (Address $address) use ($location) {
                        $address->text = $location->name;
                        $address->addressLine = $location->name;
                    });
                });
            }),
        ];

        $trip->cruiseDetails = create(function (CruiseDetails $cruiseDetails) {
            $cruiseDetails->class = 'Regular';
            $cruiseDetails->description = 'Long cruise';
            $cruiseDetails->deck = '3';
            $cruiseDetails->room = '342';
            $cruiseDetails->ship = 'Titanic';
            $cruiseDetails->shipCode = 'SHCD';
            $cruiseDetails->voyageNumber = 'K229';
        });

        $trip->travelers = $this->diff(
            [
                create(function (Person $person) {
                    $person->name = 'John Smith';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Olivia Smith';
                    $person->full = true;
                }),
            ],
            [
                create(function (Person $person) {
                    $person->name = 'John Gates';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Olivia Gates';
                    $person->full = true;
                }),
            ]
        );

        $trip->pricingInfo = create(function (PricingInfo $info) {
            $info->currencyCode = 'USD';
            $info->cost = $this->diff(1005, 1200);
            $info->total = $this->diff(1200, 1500);
            $info->discount = $this->diff(10, 20);
            $info->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 1';
                    $fee->charge = 28.9;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 2';
                    $fee->charge = 120.9;
                }),
            ];
            $info->spentAwards = '100 burgers';
        });

        $trip->providerInfo = create(function (ProviderInfo $info) {
            $info->name = $this->providerInfo['code'];
            $info->code = $this->providerInfo['code'];
            $info->earnedRewards = '200 burgers';
            $info->accountNumbers = [create(function (ParsedNumber $number) {
                $number->number = 'ABCDEFGH123';
            })];
        });

        $trip->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->isPrimary = false;
                    $confNo->number = 'DD22EE';
                }),
            ];
        });

        return [$trip];
    }

    public function createTripItineraries(array $airlinesData): array
    {
        $baseDate = $this->createDefaultDate();
        $trip = new Flight();
        $trip->issuingCarrier = create(function (IssuingCarrier $carrier) use ($airlinesData) {
            $carrier->confirmationNumber = 'AABB11';
            $carrier->airline = create(function (Airline $airline) use ($airlinesData) {
                $airline->iata = $airlinesData[self::ISSUING_AIRLINE_ID][0]['code'];
                $airline->name = $airlinesData[self::ISSUING_AIRLINE_ID][0]['name'];
            });
            $carrier->phoneNumbers = [
                create(function (PhoneNumber $number) {
                    $number->number = '+444 555 000';
                }),
            ];
        });
        $trip->segments = [create(function (FlightSegment $segment) use ($baseDate, $airlinesData) {
            $segment->departure = create(function (TripLocation $location) use ($baseDate) {
                $location->airportCode = 'JFK';
                $location->name = 'New York John F. Kennedy International Airport';
                $location->address = create(function (Address $address) use ($location) {
                    $address->text = $location->name;
                    $address->addressLine = $location->name;
                });
                $location->terminal = $this->diff('1A', '2B');
                $location->localDateTime = $this->diff($baseDate(), $baseDate('+20 minutes'));
            });
            $segment->arrival = create(function (TripLocation $location) use ($baseDate) {
                $location->airportCode = 'LAX';
                $location->name = 'Los Angeles International Airport';
                $location->terminal = $this->diff('3C', '4D');
                $location->localDateTime = $this->diff($baseDate('+5 hour'), $baseDate('+5 hour 25 minutes'));
                $location->address = create(function (Address $address) use ($location) {
                    $address->text = $location->name;
                    $address->addressLine = $location->name;
                });
            });
            $segment->cabin = $this->diff('Economy', 'Business');
            $segment->bookingCode = $this->diff('T', 'Q');
            $segment->seats = $this->diff(['11A'], ['22B']);
            $segment->meal = $this->diff('shaverma', 'vodka');
            $segment->duration = '8h';
            $segment->smoking = $this->diff(true, false);
            $segment->stops = $this->diff(0, 1);
            $segment->traveledMiles = $this->diff('100', '200');

            $segment->marketingCarrier = create(function (MarketingCarrier $carrier) use ($airlinesData) {
                $carrier->phoneNumbers = [
                    create(function (PhoneNumber $number) {
                        $number->number = '+444 555 001';
                    }),
                ];
                $carrier->airline = create(function (Airline $airline) use ($airlinesData) {
                    $airline->name = $airlinesData[self::MARKETING_AIRLINE_ID][0]['name'];
                    $airline->iata = $airlinesData[self::MARKETING_AIRLINE_ID][0]['code'];
                });
                $carrier->flightNumber = '1200';
                $carrier->confirmationNumber = 'AABB22';
            });

            $segment->operatingCarrier = create(function (OperatingCarrier $carrier) use ($airlinesData) {
                $carrier->phoneNumbers = [
                    create(function (PhoneNumber $number) {
                        $number->number = '+444 555 001';
                    }),
                ];
                $carrier->airline = create(function (Airline $airline) use ($airlinesData) {
                    $airline->name = $airlinesData[self::OPERATING_AIRLINE_ID][0]['name'];
                    $airline->iata = $airlinesData[self::OPERATING_AIRLINE_ID][0]['code'];
                });
                $carrier->flightNumber = '1201';
                $carrier->confirmationNumber = 'AABB33';
            });

            $segment->aircraft = $this->diff(
                create(function (Aircraft $aircraft) {
                    $aircraft->jet = true;
                    $aircraft->name = 'Su-27';
                }),
                create(function (Aircraft $aircraft) {
                    $aircraft->jet = true;
                    $aircraft->name = 'Su-33';
                })
            );
        })];

        $trip->travelers = $this->diff(
            [
                create(function (Person $person) {
                    $person->name = 'John Smith';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Olivia Smith';
                    $person->full = true;
                }),
            ],
            [
                create(function (Person $person) {
                    $person->name = 'John Gates';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Olivia Gates';
                    $person->full = true;
                }),
            ]
        );

        $trip->pricingInfo = create(function (PricingInfo $info) {
            $info->currencyCode = 'USD';
            $info->cost = $this->diff(1005, 1200);
            $info->total = $this->diff(1200, 1500);
            $info->discount = $this->diff(10, 20);
            $info->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 1';
                    $fee->charge = 28.9;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 2';
                    $fee->charge = 120.9;
                }),
            ];
            $info->spentAwards = '100 burgers';
        });

        $trip->providerInfo = create(function (ProviderInfo $info) {
            $info->name = $this->providerInfo['code'];
            $info->code = $this->providerInfo['code'];
            $info->earnedRewards = '200 burgers';
            $info->accountNumbers = [create(function (ParsedNumber $number) {
                $number->number = 'ABCDEFGH123';
            })];
        });

        $trip->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->isPrimary = false;
                    $confNo->number = 'DD22EE';
                }),
            ];
        });

        return [$trip];
    }

    private function createDefaultDate(): DateTimeImmutableFormatted
    {
        return (new DateTimeImmutableFormatted('Jan 16, 2023'))->withDefaultFormat('Y-m-dTH:i:s');
    }

    private function createAirline(string $fourCodePrefix, string $twoCodePrefix): array
    {
        $airlineName = $this->createRandomData(12, $fourCodePrefix, $twoCodePrefix);

        foreach (
            it(randomOf($this->airlineChar1Alpha))
            ->zip(randomOf($this->airlineChar2Alpha))
            ->map('\\implode')
            ->take(200) as $iata
        ) {
            $count = (int) $this->db->grabCountFromDatabase('Airline', [
                'Name' => $airlineName,
                'Code' => $iata,
                'FSCode' => $iata,
            ]);

            if ($count) {
                continue;
            }

            try {
                $this->db->haveInDatabase('Airline', [
                    'Name' => $airlineName,
                    'Code' => $iata,
                    'FSCode' => $iata,
                ]);
                $this->randomData["#r#{$fourCodePrefix} ({$iata})"] = "#r#{$fourCodePrefix} ({$fourCodePrefix}_iata)";
                $this->randomData["{$iata} Confirmation #"] = "{$fourCodePrefix}_iata Confirmation #";

                return [
                    'code' => $iata,
                    'name' => $airlineName,
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        throw new \RuntimeException("Can't create airline");
    }

    private function createRandomData(int $length, string $replacePrefix, string $twoLetterPrefix): string
    {
        $length -=
            4 // for #r#..#
            + 3; // for $twoLetterPrefix and _

        if ($length < 1) {
            throw new \InvalidArgumentException('Too short random string length');
        }

        $key = $this->wrapRandomString(
            StringUtils::getRandomCode($length),
            $twoLetterPrefix
        );
        $value = '#r#' . $replacePrefix;
        $this->randomData[$key] = $value;

        return $key;
    }

    private function wrapRandomString(string $randomString, string $twoCodePrefix): string
    {
        return "#r#{$twoCodePrefix}_{$randomString}#";
    }

    private function createProviderWithPhones(string $fourLettersCode, string $twoCodePrefix, string $basePhone): array
    {
        $providerOd = $this->aw->createAwProvider(
            $programName = $this->createRandomData(12, $fourLettersCode, $twoCodePrefix),
            $programName
        );

        foreach (self::ELITE_LEVELS as $rank => $eliteLevelName) {
            $eliteLevelId = $this->db->haveInDatabase('EliteLevel', [
                'ProviderID' => $providerOd,
                'Rank' => $rank,
                'Name' => $eliteLevelName . ' ' . $fourLettersCode,
            ]);
            $this->db->haveInDatabase('TextEliteLevel', [
                'EliteLevelID' => $eliteLevelId,
                'ValueText' => $eliteLevelName . ' ' . $fourLettersCode,
            ]);
            $this->db->haveInDatabase('ProviderPhone', [
                'ProviderID' => $providerOd,
                'Phone' => $basePhone . $rank,
                'EliteLevelID' => $eliteLevelId,
                'PhoneFor' => PHONE_FOR_MEMBER_SERVICES,
                'Valid' => 1,
            ]);
        }

        $generalPhones = [
            '88',
            '99',
        ];

        foreach ($generalPhones as $phone) {
            $this->db->haveInDatabase('ProviderPhone', [
                'ProviderID' => $providerOd,
                'Phone' => $basePhone . $phone,
                'PhoneFor' => PHONE_FOR_GENERAL,
                'Valid' => 1,
            ]);
        }

        return [
            'code' => $programName,
            'id' => $providerOd,
        ];
    }

    private function checkItineraries(\Closure $itinerariesProviderThunk, int $checkTimes): array
    {
        $providers = [];
        $this->travelAgencyInfo = $providers['travel_agency'] = $this->createProviderWithPhones('t_ag', 'ta', '+1 000 111 22');
        $this->providerInfo = $providers['provider'] = $this->createProviderWithPhones('prov', 'pr', '+1 111 222 33');

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), $this->providerInfo['code'], 'some.login');
        $providers['provider']['accountid'] = $accountId;
        $tagAccountId = $this->aw->createAwAccount($this->user->getUserid(), $this->travelAgencyInfo['id'], 'some.login');
        $providers['travel_agency']['accountid'] = $tagAccountId;

        foreach ([$this->providerInfo['id'], $this->travelAgencyInfo['id']] as $providerId) {
            $this->db->haveInDatabase('ProviderProperty', [
                'ProviderID' => $providerId,
                'Name' => 'Status',
                'Code' => 'Status',
                'SortIndex' => 10,
                'Kind' => PROPERTY_KIND_STATUS,
            ]);
        }

        $this->aw->createAccountProperty('Status', self::ELITE_LEVELS[2] . ' prov', ['AccountID' => $accountId], $this->travelAgencyInfo['id']);
        $this->aw->createAccountProperty('Status', self::ELITE_LEVELS[2] . ' t_ag', ['AccountID' => $tagAccountId], $this->providerInfo['id']);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $userData = new UserData();
        $userData->setCheckIts(true);

        foreach (\range(1, $checkTimes) as $_) {
            $checkAccountResponse = new CheckAccountResponse();
            $checkAccountResponse->setState(ACCOUNT_CHECKED);
            $checkAccountResponse->setItineraries($itinerariesProviderThunk());
            $checkAccountResponse->setUserdata($userData);
            $this->container->get('aw.loyalty.account_saving.processor')->saveAccount($account, $checkAccountResponse);
            $this->diffCounter->nextCheck();
        }

        return $providers;
    }

    private function assertTimelineJson(
        string $extpectedTimelineContentFileName,
        callable $itinerariesProvider,
        ?callable $block = null,
        array $context = []
    ) {
        $providers = $this->checkItineraries($itinerariesProvider, 2);
        $this->em->flush();
        $this->em->clear();
        $entities = $this->loadHomogeneousEntities();
        $ctxMerge = $this->getCtxMergeData($entities);

        if (null !== $block) {
            $block();
        }

        $actualJson = \json_encode($this->container->get('aw.timeline.helper.mobile')->getUserTimelines($this->user));
        $this->jsonNormalizer->expectJsonTemplate(
            codecept_data_dir("timeline/mobile/4.x/{$extpectedTimelineContentFileName}"),
            $actualJson,
            \array_merge(
                $ctxMerge,
                $context,
                ['providers' => $providers]
            )
        );
    }

    private function createBusItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $trip = new Bus();

        $trip->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [create(function (ConfNo $confNo) {
                $confNo->number = '3106000000';
                $confNo->description = 'Booking Number';
            })];
        });
        $trip->status = 'confirmed';

        $trip->travelers = [create(function (Person $person) {
            $person->name = 'Kouko Ibuki';
            $person->full = true;
        })];

        $trip->segments = [create(function (BusSegment $segment) use ($baseDate) {
            $segment->scheduleNumber = '060';
            $segment->departure = create(function (TransportLocation $location) use ($baseDate) {
                $location->name = 'Berlin central bus station';
                $location->localDateTime = $this->diff($baseDate(), $baseDate('+20 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'Masurenallee 4-6, 14057 Berlin';
                    $address->addressLine = '4-6 Masurenallee';
                    $address->city = 'Berlin';
                    $address->countryName = 'Germany';
                    $address->countryCode = 'DE';
                    $address->postalCode = '14057';
                    $address->lat = 52.5077;
                    $address->lng = 13.2798;
                    $address->timezoneId = 'Europe/Berlin';
                });
            });
            $segment->arrival = create(function (TransportLocation $location) use ($baseDate) {
                $location->name = 'Prague (Central Bus Station Florenc)';
                $location->localDateTime = $this->diff($baseDate('+4 hour'), $baseDate('+4 hour 25 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'Praha, ÚAN Florenc Křižíkova 2110 2b, 186 00 Praha';
                    $address->addressLine = '2110/2b Křižíkova';
                    $address->city = 'Prague';
                    $address->countryName = 'Czechia';
                    $address->countryCode = 'CZ';
                    $address->postalCode = '186 00';
                    $address->lat = 50.0894;
                    $address->lng = 14.4393;
                    $address->timezoneId = 'Europe/Prague';
                });
            });
            $segment->seats = ['5D'];
        })];

        return [$trip];
    }

    private function createTrainItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $trip = new Train();

        $trip->pricingInfo = create(function (PricingInfo $pricingInfo) {
            $pricingInfo->total = 26;
            $pricingInfo->currencyCode = 'GBP';
        });
        $trip->status = 'confirmed';

        $trip->providerInfo = create(function (ProviderInfo $providerInfo) {
            $providerInfo->name = $this->travelAgencyInfo['code'];
            $providerInfo->code = $this->travelAgencyInfo['code'];
        });

        $trip->confirmationNumbers = [create(function (ConfNo $confNo) {
            $confNo->number = '23SV000000';
            $confNo->description = 'Collection ref';
        })];

        $trip->travelers = [create(function (Person $person) {
            $person->name = 'Kotomi Ichinose';
            $person->full = true;
        })];

        $trip->segments = [create(function (TrainSegment $segment) use ($baseDate) {
            $segment->departure = create(function (TransportLocation $location) use ($baseDate) {
                $location->name = 'London Kings Cross, UK';
                $location->localDateTime = $this->diff($baseDate(), $baseDate('+20 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'London Kings Cross, UK';
                    $address->addressLine = '23c Tavistock Place';
                    $address->city = 'London';
                    $address->stateName = 'England';
                    $address->countryName = 'United Kingdom';
                    $address->countryCode = 'GB';
                    $address->postalCode = 'WC1H 9SE';
                    $address->lat = 51.5260;
                    $address->lng = -0.1252;
                    $address->timezoneId = 'Europe/London';
                });
            });
            $segment->arrival = create(function (TransportLocation $location) use ($baseDate) {
                $location->name = 'York, UK';
                $location->localDateTime = $this->diff($baseDate('+2 hour'), $baseDate('+2 hour 25 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'York, UK';
                    $address->addressLine = null;
                    $address->city = 'York';
                    $address->stateName = 'England';
                    $address->countryName = 'United Kingdom';
                    $address->countryCode = 'GB';
                    $address->postalCode = null;
                    $address->lat = 53.9614;
                    $address->lng = -1.0739;
                    $address->timezoneId = 'Europe/London';
                });
            });
            $segment->car = 'C';
            $segment->seats = ['41'];
        })];

        return [$trip];
    }

    private function createFerryItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $trip = new Ferry();

        $trip->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [create(function (ConfNo $confNo) {
                $confNo->number = 'DFP100000000';
                $confNo->description = 'Direct Ferries';
            })];
        });

        $trip->pricingInfo = create(function (PricingInfo $pricingInfo) {
            $pricingInfo->total = 41.5;
            $pricingInfo->currencyCode = 'EUR';
        });
        $trip->status = 'confirmed';

        $trip->confirmationNumbers = [create(function (ConfNo $confNo) {
            $confNo->number = 'GR23000000000';
            $confNo->description = 'Caronte & Tourist';
        })];

        $trip->travelers = [
            create(function (Person $person) {
                $person->name = 'Youhei Sunohara';
                $person->full = true;
            }),
            create(function (Person $person) {
                $person->name = 'Mei Sunohara';
                $person->full = true;
            }),
        ];

        $trip->segments = [create(function (FerrySegment $segment) use ($baseDate) {
            $segment->departure = create(function (TransportLocation $location) use ($baseDate) {
                $location->name = 'Villa San Giovanni';
                $location->localDateTime = $this->diff($baseDate(), $baseDate('+20 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'E45, 89018 Villa San Giovanni RC, Italy';
                    $address->addressLine = 'E45';
                    $address->city = 'Reggio Calabria';
                    $address->stateName = 'Villa San Giovanni';
                    $address->countryName = 'Italy';
                    $address->countryCode = 'IT';
                    $address->postalCode = '89018';
                    $address->lat = 38.2335;
                    $address->lng = 15.6702;
                    $address->timezoneId = 'Europe/Rome';
                });
            });
            $segment->arrival = create(function (TransportLocation $location) use ($baseDate) {
                $location->name = 'Messina: Caronte & Tourist';
                $location->localDateTime = $this->diff($baseDate('+30 minutes'), $baseDate('+55 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'Messina: Caronte & Tourist';
                    $address->addressLine = null;
                    $address->city = 'Reggio Calabria';
                    $address->stateName = 'Sicily';
                    $address->countryName = 'Italy';
                    $address->countryCode = 'IT';
                    $address->postalCode = null;
                    $address->lat = 38.1937;
                    $address->lng = 15.5542;
                    $address->timezoneId = 'Europe/Rome';
                });
            });
            $segment->accommodations = ['not required'];
            $segment->carrier = 'Caronte & Tourist';
            $segment->duration = '00h 30m';
            $segment->meal = 'not required';
            $segment->vehicles = [create(function (VehicleExt $vehicle) {
                $vehicle->model = 'Fiat Tipo (Car) - GL30000';
                $vehicle->length = '4.57m';
                $vehicle->height = '1.55m';
            })];
        })];

        return [$trip];
    }

    private function createTransferItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $trip = new Transfer();

        $trip->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->phoneNumbers = [create(function (PhoneNumber $phoneNumber) {
                $phoneNumber->number = '+1-800-600-0000';
            })];
        });
        $trip->status = 'confirmed';

        $trip->confirmationNumbers = [create(function (ConfNo $confNo) {
            $confNo->number = 'MOZ6000000';
            $confNo->description = 'Confirmation Number';
        })];

        $trip->travelers = [create(function (Person $person) {
            $person->name = 'Kyou Fujibayashi';
            $person->full = true;
        })];

        $trip->segments = [create(function (TransferSegment $segment) use ($baseDate) {
            $segment->departure = create(function (TransferLocation $location) use ($baseDate) {
                $location->airportCode = 'CDG';
                $location->name = 'Charles de Gaulle Airport';
                $location->localDateTime = $this->diff($baseDate(), $baseDate('+20 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = 'CDG';
                    $address->addressLine = null;
                    $address->city = 'Paris';
                    $address->stateName = 'Ile-de-France';
                    $address->countryName = 'France';
                    $address->countryCode = 'FR';
                    $address->postalCode = null;
                    $address->lat = 49.0031;
                    $address->lng = 2.5670;
                    $address->timezoneId = 'Europe/Paris';
                });
            });
            $segment->arrival = create(function (TransferLocation $location) use ($baseDate) {
                $location->airportCode = null;
                $location->name = 'The Westin Paris Vendôme';
                $location->localDateTime = $this->diff($baseDate('+1 hour'), $baseDate('+1 hour 25 minutes'));
                $location->address = create(function (Address $address) {
                    $address->text = '3 Rue de Castiglione, 75001 Paris, France';
                    $address->addressLine = '3 Rue de Castiglione';
                    $address->city = 'Paris';
                    $address->stateName = 'Ile-de-France';
                    $address->countryName = 'France';
                    $address->countryCode = 'FR';
                    $address->postalCode = '75001';
                    $address->lat = 48.8657;
                    $address->lng = 2.3274;
                    $address->timezoneId = 'Europe/Paris';
                });
            });
            $segment->adults = 2;
            $segment->duration = '01h 00m';
        })];

        return [$trip];
    }

    private function createRentalItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $it = new CarRental();
        $it->pickup = create(function (CarRentalLocation $location) use ($baseDate) {
            $location->address = new Address();
            $location->address->text = 'LGA';
            $location->localDateTime = $this->diff($baseDate(), $baseDate('+1 day'));
            $location->openingHours = $this->diff('11:00', '12:00');
            $location->phone = $this->diff('+100 200 300', '+100 200 301');
            $location->fax = $this->diff('+100 200 401', '+100 200 402');
        });
        $it->confirmationNumbers = [
            create(function (ConfNo $confNo) {
                $confNo->isPrimary = true;
                $confNo->number = 'AB12CD';
            }),
        ];

        $it->dropoff = create(function (CarRentalLocation $location) use ($baseDate) {
            $location->address = new Address();
            $location->address->text = 'EWR';
            $location->localDateTime = $this->diff($baseDate('+1 day'), $baseDate('+4 day'));
            $location->openingHours = $this->diff('16:00', '17:00');
            $location->phone = $this->diff('+100 200 302', '+100 200 303');
            $location->fax = $this->diff('+100 200 402', '+100 200 403');
        });

        $it->car = create(function (Car $car) {
            $car->type = 'Mid-Size Economy';
            $car->model = 'Ford Focus';
            $car->imageUrl = 'car_image_url';
        });

        $it->discounts = [
            create(function (CarRentalDiscount $discount) {
                $discount->code = 'SomeCode1';
                $discount->name = 'SomeValue1';
            }),
            create(function (CarRentalDiscount $discount) {
                $discount->code = 'SomeCode2';
                $discount->name = 'SomeValue2';
            }),
        ];

        $it->driver = create(function (Person $person) {
            $person->name = 'John Smith';
            $person->full = true;
        });

        $it->pricedEquipment = [
            create(function (Fee $fee) {
                $fee->name = 'GPS';
                $fee->charge = 20;
            }),
        ];

        $it->rentalCompany = 'Test Rental Company';

        $it->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->isPrimary = false;
                    $confNo->number = 'DD22EE';
                }),
            ];
        });

        $it->reservationDate = $baseDate->modify('-1 month')();
        $it->providerInfo = create(function (ProviderInfo $providerInfo) {
            $providerInfo->earnedRewards = '20 rentals';
            $providerInfo->code = $this->providerInfo['code'];
            $providerInfo->name = $this->providerInfo['code'];
        });

        $it->status = 'confirmed';
        $it->cancellationPolicy = 'Some Cancellation policy blabla';
        $it->pricingInfo = create(function (PricingInfo $pricingInfo) {
            $pricingInfo->total = $this->diff(1200, 1500);
            $pricingInfo->cost = $this->diff(500, 600);
            $pricingInfo->discount = 100;
            $pricingInfo->spentAwards = '100 rentals';
            $pricingInfo->currencyCode = 'USD';
            $pricingInfo->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 1';
                    $fee->charge = 28.9;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 2';
                    $fee->charge = 120.9;
                }),
            ];
        });

        return [$it];
    }

    private function createParkingItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $it = new Parking();
        $it->address = create(function (Address $address) {
            $address->text = 'LGA';
        });
        $it->confirmationNumbers = [
            create(function (ConfNo $confNo) {
                $confNo->isPrimary = true;
                $confNo->number = 'AB12CD';
            }),
        ];
        $it->startDateTime = $this->diff($baseDate('+1 day'), $baseDate('+4 day'));
        $it->endDateTime = $this->diff($baseDate('+10 day'), $baseDate('+12 day'));

        $it->phone = $this->diff('+100 500 700', '+100 500 11');
        $it->carDescription = 'Volga';

        $it->locationName = 'Test Rental Company';
        $it->reservationDate = $baseDate->modify('-1 month')();
        $it->providerInfo = create(function (ProviderInfo $providerInfo) {
            $providerInfo->earnedRewards = '20 rentals';
            $providerInfo->code = $this->providerInfo['code'];
            $providerInfo->name = $this->providerInfo['code'];
        });

        $it->status = 'confirmed';
        $it->cancellationPolicy = 'Some Cancellation policy blabla';
        $it->pricingInfo = create(function (PricingInfo $pricingInfo) {
            $pricingInfo->total = $this->diff(1200, 1500);
            $pricingInfo->cost = $this->diff(500, 600);
            $pricingInfo->discount = 100;
            $pricingInfo->spentAwards = '100 parkings';
            $pricingInfo->currencyCode = 'USD';
            $pricingInfo->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 1';
                    $fee->charge = 28.9;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 2';
                    $fee->charge = 120.9;
                }),
            ];
        });
        $it->rateType = 'Standard Spot';

        return [$it];
    }

    private function createReservationItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $it = new HotelReservation();
        $it->confirmationNumbers = [create(function (ConfNo $confNo) {
            $confNo->isPrimary = true;
            $confNo->number = 'AA11BB';
        })];
        $it->hotelName = 'Sheraton Palace Hotel, Moscow';
        $it->checkInDate = $this->diff($baseDate(), $baseDate('+1 day'));
        $it->checkOutDate = $this->diff($baseDate('+1 day'), $baseDate('+5 day'));
        $it->address = create(function (Address $address) {
            $address->text = '1st Tverskaya Yamskaya Street 19, Moscow, 125047';
            $address->addressLine = '1st Tverskaya Yamskaya Street 19, Moscow, 125047';
        });
        $it->cancellationDeadline = $baseDate->modify('-10 day')->format('c');
        $it->phone = $this->diff('+100 500 700', '+100 500 11');
        $it->fax = $this->diff('+200 300 800', '+200 300 801');
        $it->guests = $this->diff(
            [
                create(function (Person $person) {
                    $person->name = 'John Smith';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Bill Gates';
                    $person->full = true;
                }),
            ],
            [
                create(function (Person $person) {
                    $person->name = 'Jessica Smith';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Julia Gates';
                    $person->full = true;
                }),
            ]
        );
        $it->freeNights = $this->diff(3, 8);
        $it->kidsCount = 2;
        $it->rooms = [create(function (Room $room) {
            $room->type = '2 QUEEN BEDS NONSMOKING';
            $room->description = "Some text with\nnew lines\nnewlines";
            $room->rate = $this->diff('109.00 EUR / night', '209.00 EUR / night');
            $room->rateType = 'some rate type';
        })];

        $it->pricingInfo = create(function (PricingInfo $info) {
            $info->currencyCode = 'EUR';
            $info->cost = $this->diff(1005, 1200);
            $info->total = $this->diff(1200, 1500);
            $info->discount = $this->diff(10, 20);
            $info->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 1';
                    $fee->charge = 28.9;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 2';
                    $fee->charge = 120.9;
                }),
            ];
            $info->spentAwards = '20 nights';
        });
        $it->providerInfo = create(function (ProviderInfo $info) {
            $info->name = $this->providerInfo['code'];
            $info->code = $this->providerInfo['code'];
            $info->earnedRewards = '10 nights';
        });

        $it->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->isPrimary = false;
                    $confNo->number = 'DD22EE';
                }),
            ];
        });

        $it->chainName = 'Carlson Wagonlit Travel';
        $it->isNonRefundable = true;

        return [$it];
    }

    private function createRestaurantItineraries(): array
    {
        $baseDate = $this->createDefaultDate();
        $it = new Event();
        $it->eventName = 'McDonalds';
        $it->eventType = EVENT_RESTAURANT;
        $it->startDateTime = $this->diff($baseDate(), $baseDate('+3 hours'));
        $it->endDateTime = $this->diff($baseDate('+3 hours'), $baseDate('+6 hours'));
        $it->address = create(function (Address $address) {
            $address->addressLine = 'Tverskya 42, Moscow, Russia';
            $address->text = 'Tverskya 42, Moscow, Russia';
        });
        $it->phone = $this->diff('+100 500', '+100 501');
        $it->guests = $this->diff(
            [
                create(function (Person $person) {
                    $person->name = 'John Smith';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Olivia Smith';
                    $person->full = true;
                }),
            ],
            [
                create(function (Person $person) {
                    $person->name = 'John Gates';
                    $person->full = true;
                }),
                create(function (Person $person) {
                    $person->name = 'Olivia Gates';
                    $person->full = true;
                }),
            ]
        );

        $it->pricingInfo = create(function (PricingInfo $info) {
            $info->currencyCode = 'EUR';
            $info->cost = $this->diff(1005, 1200);
            $info->total = $this->diff(1200, 1500);
            $info->discount = $this->diff(10, 20);
            $info->fees = [
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 1';
                    $fee->charge = 28.9;
                }),
                create(function (Fee $fee) {
                    $fee->name = 'Some Fee 2';
                    $fee->charge = 120.9;
                }),
            ];
            $info->spentAwards = '100 burgers';
        });

        $it->providerInfo = create(function (ProviderInfo $info) {
            $info->name = $this->providerInfo['code'];
            $info->code = $this->providerInfo['code'];
            $info->earnedRewards = '200 burgers';
        });

        $it->travelAgency = create(function (TravelAgency $travelAgency) {
            $travelAgency->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = $this->travelAgencyInfo['code'];
                $providerInfo->code = $this->travelAgencyInfo['code'];
            });
            $travelAgency->confirmationNumbers = [
                create(function (ConfNo $confNo) {
                    $confNo->isPrimary = false;
                    $confNo->number = 'DD22EE';
                }),
            ];
        });

        return [$it];
    }
}
