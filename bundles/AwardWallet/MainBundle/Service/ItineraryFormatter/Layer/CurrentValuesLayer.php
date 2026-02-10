<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EmptyListNullifierEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListImplodingEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\NonEmptyStringEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\NonEmptyStringsArrayEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;
use AwardWallet\MainBundle\Service\ItineraryFormatter\ItineraryWrapper;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\OperatedByResolver;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CurrentValuesLayer implements DILayerInterface
{
    use CachedLayerTrait;

    private NonEmptyStringEncoder $nonEmptyStringEncoder;

    private NonEmptyStringsArrayEncoder $nonEmptyStringsArrayEncoder;

    private OperatedByResolver $operatedByResolver;

    private ListImplodingEncoderFactory $listImplodingEncoderFactory;

    private EmptyListNullifierEncoder $emptyListNullifierEncoder;

    private Formatter $formatter;

    private ItineraryFileManager $itineraryFileManager;

    public function __construct(
        NonEmptyStringEncoder $nonEmptyStringEncoder,
        NonEmptyStringsArrayEncoder $nonEmptyStringsArrayEncoder,
        ListImplodingEncoderFactory $listImplodingEncoderFactory,
        OperatedByResolver $operatedByResolver,
        EmptyListNullifierEncoder $emptyListNullifierEncoder,
        Formatter $formatter,
        ItineraryFileManager $itineraryFileManager
    ) {
        $this->nonEmptyStringEncoder = $nonEmptyStringEncoder;
        $this->nonEmptyStringsArrayEncoder = $nonEmptyStringsArrayEncoder;
        $this->operatedByResolver = $operatedByResolver;
        $this->listImplodingEncoderFactory = $listImplodingEncoderFactory;
        $this->emptyListNullifierEncoder = $emptyListNullifierEncoder;
        $this->formatter = $formatter;
        $this->itineraryFileManager = $itineraryFileManager;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        return \array_merge(
            $this->getBaseItineraryEncoders(),
            $this->getTripEncoders(),
            $this->getRentalEncoders(),
            $this->getRestaurantEncoders(),
            $this->getReservationEncoders(),
            $this->getParkingEncoders()
        );
    }

    /**
     * @return EncoderInterface[]
     */
    private function getTripEncoders(): array
    {
        $toScalarEncoder = function (callable $callable): EncoderInterface {
            return
                (new CallableEncoder($callable))
                ->andThenIfExists($this->nonEmptyStringEncoder);
        };

        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        return [
            PropertiesList::STATUS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripsegment = $inputWrapper->getSource();

                return $tripsegment->getParsedStatus();
            }),

            PropertiesList::TICKET_NUMBERS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getTicketNumbers();
            }),

            PropertiesList::TRAIN_SERVICE_NAME => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getServiceName();
            }),

            PropertiesList::ADULTS_COUNT => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getAdultsCount();
            }),

            PropertiesList::KIDS_COUNT => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getKidsCount();
            }),

            PropertiesList::TRAIN_CAR_NUMBER => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getCarNumber();
            }),

            PropertiesList::DEPARTURE_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return ($tripSegment->getTripid()->getCategory() == TRIP_CATEGORY_AIR) ? $tripSegment->getDepAirportName(false) : $tripSegment->getDepname();
            }),

            PropertiesList::DEPARTURE_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getDepdate();
            }),

            PropertiesList::ARRIVAL_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return ($tripSegment->getTripid()->getCategory() == TRIP_CATEGORY_AIR) ? $tripSegment->getArrAirportName(false) : $tripSegment->getArrname();
            }),

            PropertiesList::ARRIVAL_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getArrivalDate();
            }),

            PropertiesList::AIRLINE_NAME => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $this->getAirlineProvider($tripSegment);
            }),

            PropertiesList::DEPARTURE_AIRPORT_CODE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getDepcode();
            }),

            PropertiesList::ARRIVAL_AIRPORT_CODE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getArrcode();
            }),

            PropertiesList::OPERATING_AIRLINE_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getOperatingAirlineName();
            }),

            PropertiesList::FLIGHT_NUMBER => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getFlightNumber();
            }),

            PropertiesList::CRUISE_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getCruiseName();
            }),

            PropertiesList::DECK => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getDeck();
            }),

            PropertiesList::SHIP_CABIN_CLASS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getShipCabinClass();
            }),

            PropertiesList::SHIP_CABIN_NUMBER => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getCabinNumber();
            }),

            PropertiesList::SHIP_CODE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getShipCode();
            }),

            PropertiesList::SHIP_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTripid()->getShipName();
            }),

            PropertiesList::AIRCRAFT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getAircraftName();
            }),

            PropertiesList::ARRIVAL_GATE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getArrivalGate();
            }),

            PropertiesList::DEPARTURE_GATE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getDepartureGate();
            }),

            PropertiesList::ARRIVAL_TERMINAL => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getArrivalTerminal();
            }),

            PropertiesList::DEPARTURE_TERMINAL => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getDepartureTerminal();
            }),

            PropertiesList::BAGGAGE_CLAIM => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getBaggageClaim();
            }),

            PropertiesList::BOOKING_CLASS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getBookingClass();
            }),

            PropertiesList::FLIGHT_CABIN_CLASS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getCabinClass();
            }),

            PropertiesList::DURATION => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $this->formatter->formatDurationInHours(
                    $tripSegment->getUTCStartDate(),
                    $tripSegment->getUTCEndDate(),
                    $encoderContext->lang
                );
            }),

            PropertiesList::IS_SMOKING => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->isSmoking();
            }),

            PropertiesList::STOPS_COUNT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getStops();
            }),

            PropertiesList::TRAVELED_MILES => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getTraveledMiles();
            }),

            PropertiesList::MEAL => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getMeal();
            }),

            PropertiesList::SEATS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getSeats();
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::FARE_BASIS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getServiceClasses();
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::ACCOMMODATIONS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getAccommodations();
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::VESSEL => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getVessel();
            }),

            PropertiesList::PETS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getPets();
            }),

            PropertiesList::VEHICLES => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $tripSegment = $inputWrapper->getSource();

                return $tripSegment->getVehicles();
            }),
        ];
    }

    /**
     * @return EncoderInterface[]
     */
    private function getRentalEncoders(): array
    {
        $toScalarEncoder = function (callable $callable): EncoderInterface {
            return
                (new CallableEncoder($callable))
                ->andThenIfExists($this->nonEmptyStringEncoder);
        };

        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        return [
            PropertiesList::RENTAL_COMPANY => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getRentalCompanyName();
            }),

            PropertiesList::PICK_UP_LOCATION => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getPickuplocation();
            }),

            PropertiesList::PICK_UP_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getPickupdatetime();
            }),

            PropertiesList::PICK_UP_HOURS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getPickuphours();
            }),

            PropertiesList::PICK_UP_PHONE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getPickupphone();
            }),

            PropertiesList::DROP_OFF_LOCATION => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getDropofflocation();
            }),

            PropertiesList::DROP_OFF_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getDropoffdatetime();
            }),

            PropertiesList::DROP_OFF_HOURS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getDropoffhours();
            }),

            PropertiesList::DROP_OFF_PHONE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getDropoffphone();
            }),

            PropertiesList::CAR_IMAGE_URL => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getCarImageUrl();
            }),

            PropertiesList::CAR_MODEL => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getCarModel();
            }),

            PropertiesList::CAR_TYPE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getCarType();
            }),

            PropertiesList::PICK_UP_FAX => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getPickUpFax();
            }),

            PropertiesList::DROP_OFF_FAX => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $rental = $inputWrapper->getSource();

                return $rental->getDropOffFax();
            }),

            PropertiesList::DISCOUNT_DETAILS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Rental $rental */
                $rental = $inputWrapper->getSource();

                return $rental->getDiscountDetails();
            }),

            PropertiesList::PRICED_EQUIPMENT => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Rental $rental */
                $rental = $inputWrapper->getSource();

                return $rental->getPricedEquipment();
            }),
        ];
    }

    /**
     * @return EncoderInterface[]
     */
    private function getReservationEncoders(): array
    {
        $toScalarEncoder = function (callable $callable): EncoderInterface {
            return
                (new CallableEncoder($callable))
                ->andThenIfExists($this->nonEmptyStringEncoder);
        };

        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        return [
            PropertiesList::HOTEL_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getHotelname();
            }),

            PropertiesList::CHECK_IN_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getCheckindate();
            }),

            PropertiesList::CHECK_OUT_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getCheckoutdate();
            }),

            PropertiesList::ADDRESS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getAddress();
            }),

            PropertiesList::PHONE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getPhone();
            }),

            PropertiesList::CANCELLATION_POLICY => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getCancellationPolicy();
            }),

            PropertiesList::CANCELLATION_DEADLINE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Reservation $reservation */
                $reservation = $inputWrapper->getSource();

                return $reservation->getCancellationDeadline();
            }),

            PropertiesList::FAX => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getFax();
            }),

            PropertiesList::GUEST_COUNT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getGuestCount();
            }),

            PropertiesList::KIDS_COUNT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getKidsCount();
            }),

            PropertiesList::ROOM_RATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return
                        it($reservation->getRooms())
                        ->map(function (Room $room) { return $room->getRate(); })
                        ->filterNotEmptyString()
                        ->toArray();
            }),

            PropertiesList::ROOM_RATE_DESCRIPTION => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return
                    it($reservation->getRooms())
                    ->map(function (Room $room) { return $room->getRateDescription(); })
                    ->filterNotEmptyString()
                    ->toArray();
            }),

            PropertiesList::ROOM_SHORT_DESCRIPTION => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return
                    it($reservation->getRooms())
                    ->map(function (Room $room) { return $room->getShortDescription(); })
                    ->filterNotEmptyString()
                    ->toArray();
            }),

            PropertiesList::ROOM_LONG_DESCRIPTION => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return
                    it($reservation->getRooms())
                    ->map(function (Room $room) { return $room->getLongDescription(); })
                    ->filterNotEmptyString()
                    ->toArray();
            }),

            PropertiesList::ROOM_COUNT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getRoomCount();
            }),

            PropertiesList::FREE_NIGHTS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Reservation $reservation */
                $reservation = $inputWrapper->getSource();

                return $reservation->getFreeNights() > 0 ? $reservation->getFreeNights() : null;
            }),

            PropertiesList::NON_REFUNDABLE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Reservation $reservation */
                $reservation = $inputWrapper->getSource();

                return $reservation->getNonRefundable();
            }),
        ];
    }

    /**
     * @return EncoderInterface[]
     */
    private function getRestaurantEncoders(): array
    {
        $toScalarEncoder = function (callable $callable): EncoderInterface {
            return
                (new CallableEncoder($callable))
                ->andThenIfExists($this->nonEmptyStringEncoder);
        };

        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        return [
            PropertiesList::EVENT_NAME => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $restaurant = $inputWrapper->getSource();

                return $restaurant->getName();
            }),
            PropertiesList::ADDRESS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $restaurant = $inputWrapper->getSource();

                return $restaurant->getAddress();
            }),
            PropertiesList::PHONE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $restaurant = $inputWrapper->getSource();

                return $restaurant->getPhone();
            }),

            PropertiesList::GUEST_COUNT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $restaurant = $inputWrapper->getSource();

                return $restaurant->getGuestCount();
            }),
        ];
    }

    /**
     * @return EncoderInterface[]
     */
    private function getBaseItineraryEncoders(): array
    {
        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        $toScalarEncoder = function (callable $callable): EncoderInterface {
            return
                (new CallableEncoder($callable))
                ->andThenIfExists($this->nonEmptyStringEncoder);
        };

        return [
            PropertiesList::START_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $restaurant = $inputWrapper->getSource();

                return $restaurant->getStartdate();
            }),

            PropertiesList::END_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $restaurant = $inputWrapper->getSource();

                return $restaurant->getEnddate();
            }),

            PropertiesList::CONFIRMATION_NUMBERS => $toEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $itinerary = $inputWrapper->getSource();

                return \array_values(array_diff(
                    $itinerary->getAllConfirmationNumbers(),
                    [$encoderContext->getProperty(PropertiesList::CONFIRMATION_NUMBER, self::class)]
                ));
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::CONFIRMATION_NUMBER => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                return
                        it(call(function () use ($itinerary) {
                            yield $itinerary->getConfirmationNumber();

                            if ($itinerary instanceof Tripsegment) {
                                yield $itinerary->getTripid()->getConfirmationNumber();
                            }
                        }))
                        ->find(function ($code) { return StringUtils::isNotEmpty($code); });
            }),

            PropertiesList::ACCOUNT_NUMBERS => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                return $itinerary->getParsedAccountNumbers();
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                return $itinerary->getTravelAgencyParsedAccountNumbers();
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::COST => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getCost();
                }
            }),

            PropertiesList::CURRENCY => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getCurrencyCode();
                }
            }),

            PropertiesList::DISCOUNT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getDiscount();
                }
            }),

            PropertiesList::FEES_LIST => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    $fees = [];

                    /** @var Fee $fee */
                    foreach ($pricingInfo->getFees() ?: [] as $fee) {
                        if (
                            StringUtils::isNotEmpty($feeName = $fee->getName())
                            && StringUtils::isNotEmpty($feeCharge = $fee->getCharge())
                        ) {
                            $fees[$fee->getName()] = $fee;
                        }
                    }

                    return $fees;
                }
            }),

            PropertiesList::TOTAL_CHARGE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getTotal();
                }
            }),

            PropertiesList::COMMENT => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                return ($itinerary instanceof Tripsegment) ? $itinerary->getTripid()->getComment() : $itinerary->getComment();
            }),

            PropertiesList::SPENT_AWARDS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getSpentAwards();
                }
            }),

            PropertiesList::EARNED_AWARDS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getEarnedAwards();
                }
            }),

            PropertiesList::TRAVEL_AGENCY_EARNED_AWARDS => $toScalarEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                if ($pricingInfo = $itinerary->getPricingInfo()) {
                    return $pricingInfo->getTravelAgencyEarnedAwards();
                }
            }),

            PropertiesList::RESERVATION_DATE => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                return $itinerary->getReservationDate();
            }),

            PropertiesList::TRAVELER_NAMES => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $itinerary = $inputWrapper->getSource();

                return $itinerary->getTravelerNames();
            })
                ->andThenIfExists($this->nonEmptyStringsArrayEncoder),

            PropertiesList::RETRIEVE_FROM => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                $trip = $inputWrapper->getItinerary();

                return null !== $trip->getProvider() ? $trip->getProvider()->getDisplayname() : null;
            }),

            PropertiesList::NOTES => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $reservation = $inputWrapper->getSource();

                return $reservation->getNotes();
            }),

            PropertiesList::FILES => $toEncoder(function (ItineraryWrapper $inputWrapper) {
                $files = $inputWrapper->getItinerary()->getFiles();

                return array_values($this->itineraryFileManager->getListFiles($files));
            }),
        ];
    }

    /**
     * @return Provider|string|null
     */
    private function getAirlineProvider(Tripsegment $segment)
    {
        $provider = $this->operatedByResolver
            ->resolveAirProvider($segment);

        if (!$provider) {
            return $segment->getAirlineName();
        }

        return $provider;
    }

    /**
     * @return EncoderInterface[]
     */
    private function getParkingEncoders(): array
    {
        $toScalarEncoder = function (callable $callable): EncoderInterface {
            return
                (new CallableEncoder($callable))
                    ->andThenIfExists($this->nonEmptyStringEncoder);
        };

        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        return [
            PropertiesList::PARKING_COMPANY => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getParkingCompanyName();
            }),

            PropertiesList::LOCATION => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getLocation();
            }),

            PropertiesList::PHONE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getPhone();
            }),

            PropertiesList::LICENSE_PLATE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getPlate();
            }),

            PropertiesList::SPOT_NUMBER => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getSpot();
            }),

            PropertiesList::CAR_DESCRIPTION => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getCarDescription();
            }),

            PropertiesList::RATE_TYPE => $toScalarEncoder(function (ItineraryWrapper $inputWrapper, EncoderContext $encoderContext) {
                /** @var Parking $parking */
                $parking = $inputWrapper->getSource();

                return $parking->getRateType();
            }),
        ];
    }
}
