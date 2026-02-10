<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Itinerary;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\PricingInfo;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AdvtTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Service\ItineraryMail\Formatter;
use AwardWallet\MainBundle\Service\ItineraryMail\Itinerary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

abstract class AbstractItineraryTemplate extends AbstractTemplate
{
    use AdvtTrait;

    public const TO_USER = 1;
    public const TO_FM_WITH_CODE = 2;
    public const TO_FM_WITHOUT_CODE = 3;
    public const TO_USER_COPY = 4;

    /**
     * @var Useragent
     */
    public $originalRecipient;

    /**
     * @var Itinerary[]
     */
    public $itineraries = [];
    public ?array $noForeignFeesCards;

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('to', ChoiceType::class, [
            'required' => false,
            'choices' => [
                /** @Ignore */
                'To user' => self::TO_USER,
                /** @Ignore */
                'To family member (with share code)' => self::TO_FM_WITH_CODE,
                /** @Ignore */
                'To family member (without share code)' => self::TO_FM_WITHOUT_CODE,
                /** @Ignore */
                'To user (copy of email for family member)' => self::TO_USER_COPY,
            ],
            'label' => /** @Ignore */ 'To',
        ]);

        Tools::addAdvtByItineraryIdForm($builder, $container, [
            'label_attr' => [
                'title' => /** @Ignore */ '
Эмулируется ситуация добавления/изменения резервации. Для выборки. 
Она не будет показана в письме. В рассылку попадает реклама только по тем провайдерам, которые связаны с резервациями.
"T": "Trip"
"L": "Rental"
"R": "Reservation"
"E": "Restaurant"',
            ],
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = [])
    {
        $lang = $options['controllerLang'] ?? null;
        $locale = $options['controllerLocale'] ?? null;
        $template = new static();
        $template->toUser($user = Tools::createUser(), false);

        if (isset($options['to'])) {
            if (in_array($options['to'], [self::TO_FM_WITH_CODE, self::TO_FM_WITHOUT_CODE])) {
                $template->toFamilyMember($familyMember = Tools::createFamilyMember($user));
                $familyMember->setSharecode($options['to'] == self::TO_FM_WITH_CODE ? 'abcdef' : null);
            } elseif ($options['to'] == self::TO_USER_COPY) {
                $template->originalRecipient = Tools::createFamilyMember($user);
            }
        }

        if (isset($options['AdItID'])) {
            $template->advt = Tools::getAdvtByItineraryId($container, $options['AdItID'], static::getEmailKind());
        }

        $changed = isset($options['changed']) && $options['changed'];

        // Airlines
        $trip = Tools::createTrip(
            "GSQ123",
            TRIP_CATEGORY_AIR,
            $container->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find(161)
        );
        $trip->setTravelerNames(['Rosia Jaso', 'Tiny Devries']);
        $trip->setPricingInfo(
            new PricingInfo(
                356,
                'USD',
                null,
                [new Fee('Fees', 100)],
                456,
                null,
                null,
                null
            )
        );

        // # Segment 1
        $tripSeg1 = Tools::createTripSegment();
        $tripSeg1->setMarketingAirlineConfirmationNumber('GSQ123');
        $tripSeg1->setDepname("Seattle");
        $tripSeg1->setDepcode("SEA");
        $tripSeg1->setDepartureDate(new \DateTime());
        $tripSeg1->setArrname("New York");
        $tripSeg1->setArrcode("JFK");
        $tripSeg1->setArrivalDate(new \DateTime("+3 hour"));
        $tripSeg1->setAirlineName("Emirates");
        $tripSeg1->setFlightNumber("F91699");
        $tripSeg1->setCabinClass('Economy / Coach');
        $tripSeg1->setSeats(['A12', 'B13']);
        $tripSeg1->setArrivalTerminal('4');
        $tripSeg1->setAircraftName("Boeing Douglas MD-90");
        $tripSeg1->setDuration("5h 20m");
        $tripSeg1->setMeal("Standard");
        $tripSeg1->setTraveledMiles("1542");
        $trip->addSegment($tripSeg1);

        // # Segment 2
        $tripSeg2 = Tools::createTripSegment();
        $tripSeg2->setMarketingAirlineConfirmationNumber('GSQ123');
        $tripSeg2->setDepname("London");
        $tripSeg2->setDepcode(null);
        $tripSeg2->setDepartureDate(new \DateTime("+6 hour"));
        $tripSeg2->setArrname("Seattle");
        $tripSeg2->setArrcode("SEA");
        $tripSeg2->setArrivalDate(new \DateTime("+8 hour"));
        $tripSeg2->setCabinClass("First");
        $tripSeg2->setSeats(['A12', 'B13']);
        $tripSeg2->setAircraftName("S80");
        $tripSeg2->setParsedStatus("TICKETED");
        $tripSeg2->setBaggageClaim(3);
        $trip->addSegment($tripSeg2);

        // # Segment 3
        $tripSeg3 = Tools::createTripSegment();
        $tripSeg3->setMarketingAirlineConfirmationNumber('GSQ123');
        $tripSeg3->setDepname("Seattle");
        $tripSeg3->setDepcode("SEA");
        $tripSeg3->setDepartureDate(new \DateTime("+10 hour"));
        $tripSeg3->setArrname("Bolshoe Savino");
        $tripSeg3->setArrcode("PEE");
        $tripSeg3->setArrivalDate(new \DateTime("+13 hour"));
        $tripSeg3->setAirlineName("Aeroflot");
        $tripSeg3->setFlightNumber("SU1219");
        $tripSeg3->setCabinClass("First");
        $tripSeg3->setSeats(["A22", "B23"]);
        $tripSeg3->setParsedStatus("TICKETED");
        $tripSeg3->setDepartureTerminal("B");
        $tripSeg3->setDepartureGate("3");
        $tripSeg3->setArrivalGate("8");
        $trip->addSegment($tripSeg3);

        $it1 = new Itinerary();
        $it1->setEntity($trip);
        $segments = $container->get(Formatter::class)->format($trip, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::ARRIVAL_TERMINAL)->setOldValue("7");
            $segments[0]->getProperty(PropertiesList::AIRLINE_NAME)->setOldValue("Delta");
            $segments[1]->getProperty(PropertiesList::ARRIVAL_NAME)->setOldValue("Seoul (GMP)");
            $segments[2]->getProperty(PropertiesList::DEPARTURE_GATE)->setOldValue("6");
            $segments[2]->getProperty(PropertiesList::ARRIVAL_GATE)->setOldValue("2");
            $segments[2]->getProperty(PropertiesList::ARRIVAL_DATE)->setOldValue((new \DateTime("+14 hour"))->format('Y-m-d'));
        }
        $it1->setSegments($segments);

        // Block No Transaction Fees
        $template->noForeignFeesCards = [
            'isFlat' => true,
            'isOne' => true,
            'list' => [
                ['id' => 1, 'name' => 'No Foreign Fees Card Name', 'image' => ''],
            ],
        ];

        // Hotel
        $reservation = Tools::createReservation("J3MC2643");
        $reservation->setHotelname("Radisson Blu Hotel, Nice &amp; Beautiful");
        $reservation->setCheckindate(new \DateTime("+1 day"));
        $reservation->setCheckoutdate(new \DateTime("+4 day 5 hour"));
        $reservation->setAddress("6855 Fairview Avenue Halethorpe, MD 21227");
        $reservation->setPhone("345-122-11222");
        $reservation->setGuestCount(2);
        $reservation->setTravelerNames(["Sherie Husband", "Terrance Sawicki"]);
        $reservation->setKidsCount(0);
        $reservation->setRoomCount(2);
        $reservation->setRooms([
            new Room("1 King bed - Non-smoking", null, null, "Club Carlson - Standard Award-City View Room"),
        ]);
        $reservation->setParsedAccountNumbers(["1234567890"]);
        $reservation->setPricingInfo(
            new PricingInfo(123.6, 'USD', null, null, null, "5900", null, null)
        );

        $it2 = new Itinerary();
        $it2->setEntity($reservation);
        $segments = $container->get(Formatter::class)->format($reservation, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::HOTEL_NAME)->setOldValue("Radisson Blu Hotel");
            $segments[0]->getProperty(PropertiesList::PHONE)->setOldValue("876-123-456");
            $segments[0]->getProperty(PropertiesList::CURRENCY)->setOldValue("EUR");
            $segments[0]->getProperty(PropertiesList::COST)->setOldValue('€5100.30');
        }
        $it2->setSegments($segments);

        // Rental
        $rental = Tools::createRental("32426711US1");
        $rental->setPickuplocation("Calgary Intl Airport - YYC 2000 Airport Road NA Calgary, AB T2E 6W5, Canada");
        $rental->setPickupdatetime(new \DateTime("+1 day"));
        $rental->setPickuphours("11 a.m.");
        $rental->setPickupphone("12-3333-111112-33");
        $rental->setDropofflocation("Calgary Intl Airport - YYC 2000 Airport Road NA Calgary, AB T2E 6W5, Canada");
        $rental->setDropoffdatetime(new \DateTime("+4 day"));
        $rental->setDropoffhours("8 a.m.");
        $rental->setDropoffphone("343512-12312");
        $rental->setPickUpFax("23412-121231");
        $rental->setDropOffFax("23412-121231");
        $rental->setRentalCompanyName("Avis+");
        $rental->setPricingInfo(
            new PricingInfo(null, "EUR", null, null, 1711.32, null, null, null)
        );
        $rental->setTravelerNames(["John Chyb"]);
        $rental->setCarType("Sport Utility");
        $rental->setCarModel("Jeep Cherokee or similar");
        $rental->setParsedStatus("ACTIVE");

        $it3 = new Itinerary();
        $it3->setEntity($rental);
        $segments = $container->get(Formatter::class)->format($rental, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::TOTAL_CHARGE)->setOldValue('€1541.70');
            $segments[0]->getProperty(PropertiesList::PICK_UP_HOURS)->setOldValue("10 a.m.");
        }
        $it3->setSegments($segments);

        // Restaurant
        $restaurant = Tools::createRestaurant("1657148622");
        $restaurant->setStartdate(new \DateTime("+1 day"));
        $restaurant->setEnddate(new \DateTime("+1 day 2 hour"));
        $restaurant->setName("Caffe Gelato");
        $restaurant->setAddress("90 East Main Street Newark, DE 19711");
        $restaurant->setPhone("233-123-11111");
        $restaurant->setGuestCount(2);
        $restaurant->setPricingInfo(
            new PricingInfo(null, 'USD', null, [new Fee('Fee', 123.5)], null, null, null, '34001')
        );
        $restaurant->setEventtype(Restaurant::EVENT_RESTAURANT);
        $restaurant->setTravelerNames(["John Dogget"]);

        $it4 = new Itinerary();
        $it4->setEntity($restaurant);
        $segments = $container->get(Formatter::class)->format($restaurant, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::GUEST_COUNT)->setOldValue(1);
            $segments[0]->getProperty(PropertiesList::EVENT_NAME)->setOldValue("Caffe");
        }
        $it4->setSegments($segments);

        // Trains
        $train = Tools::createTrip("5D2S68", TRIP_CATEGORY_TRAIN);
        $train->setTravelerNames(['John Do', 'Jane Doe']);
        $train->setPricingInfo(new PricingInfo(100.5, 'EUR', null, null, 500, null, null, null));

        // # Segment 1
        $trainSeg1 = Tools::createTripSegment();
        $trainSeg1->setDepname("Washington, DC");
        $trainSeg1->setDepartureDate(new \DateTime());
        $trainSeg1->setArrname("New York, NY");
        $trainSeg1->setArrivalDate(new \DateTime("+3 hour"));
        $trainSeg1->setAirlineName("Northeast Regional");
        $trainSeg1->setFlightNumber("126");
        $trainSeg1->setCabinClass("1 Reserved Coach Seat");
        $trainSeg1->setDuration("5h 20m");
        $trainSeg1->setTraveledMiles(1141);
        $train->addSegment($trainSeg1);

        $it5 = new Itinerary();
        $it5->setEntity($train);
        $segments = $container->get(Formatter::class)->format($train, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::TRAVELER_NAMES)->setOldValue("John D");
        }
        $it5->setSegments($segments);

        // Carrier
        $bus = Tools::createTrip("12345", TRIP_CATEGORY_BUS);
        $bus->setTravelerNames(['John Do', 'Jane Doe']);
        $bus->setPricingInfo(new PricingInfo(100.5, 'EUR', null, null, 500, null, null, null));
        // # Segment 1
        $busSeg1 = Tools::createTripSegment();
        $busSeg1->setDepname("Washington, DC");
        $busSeg1->setDepartureDate(new \DateTime());
        $busSeg1->setArrname("New York, NY");
        $busSeg1->setArrivalDate(new \DateTime("+3 hour"));
        $busSeg1->setAirlineName("Northeast Regional");
        $busSeg1->setFlightNumber("126");
        $busSeg1->setCabinClass("1 Reserved Coach Seat");
        $busSeg1->setDuration("5h 20m");
        $busSeg1->setStops(2);
        $busSeg1->setTraveledMiles(1141);
        $bus->addSegment($busSeg1);

        $it6 = new Itinerary();
        $it6->setEntity($bus);
        $segments = $container->get(Formatter::class)->format($bus, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::TRAVELER_NAMES)->setOldValue("John D");
            $segments[0]->getProperty(PropertiesList::STOPS_COUNT)->setOldValue(1);
        }
        $it6->setSegments($segments);

        // Cruise
        $cruise = Tools::createTrip("12345", TRIP_CATEGORY_CRUISE);
        $cruise->setTravelerNames(['John Do', 'Jane Doe']);
        $cruise->setPricingInfo(new PricingInfo(100.5, 'EUR', null, null, 500, null, null, null));
        $cruise->setCruiseName('Cruise Z');
        // # Segment 1
        $cruiseSeg1 = Tools::createTripSegment();
        $cruiseSeg1->setDepname("Washington, DC");
        $cruiseSeg1->setDepartureDate(new \DateTime());
        $cruiseSeg1->setArrname("New York, NY");
        $cruiseSeg1->setArrivalDate(new \DateTime("+3 hour"));
        $cruiseSeg1->setAirlineName("Northeast Regional");
        $cruiseSeg1->setFlightNumber("126");
        $cruiseSeg1->setCabinClass("1 Reserved Coach Seat");
        $cruiseSeg1->setDuration("5h 20m");
        $cruiseSeg1->setStops(2);
        $cruiseSeg1->setTraveledMiles(1141);
        $cruise->addSegment($cruiseSeg1);

        $it7 = new Itinerary();
        $it7->setEntity($cruise);
        $segments = $container->get(Formatter::class)->format($cruise, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::TRAVELER_NAMES)->setOldValue("John D");
            $segments[0]->getProperty(PropertiesList::STOPS_COUNT)->setOldValue(1);
        }
        $it7->setSegments($segments);

        // Ferry
        $ferry = Tools::createTrip(null, TRIP_CATEGORY_FERRY);
        $ferry->setTravelerNames(['John Do', 'Jane Doe']);
        $ferry->setPricingInfo(new PricingInfo(100.5, 'EUR', null, null, 500, null, null, null));
        // # Segment 1
        $ferrySeg1 = Tools::createTripSegment();
        $ferrySeg1->setDepname("Washington, DC");
        $ferrySeg1->setDepartureDate(new \DateTime());
        $ferrySeg1->setArrname("New York, NY");
        $ferrySeg1->setArrivalDate(new \DateTime("+3 hour"));
        $ferrySeg1->setAirlineName("Northeast Regional");
        $ferrySeg1->setFlightNumber("126");
        $ferrySeg1->setCabinClass("1 Reserved Coach Seat");
        $ferrySeg1->setDuration("5h 20m");
        $ferrySeg1->setTraveledMiles(1141);
        $ferry->addSegment($ferrySeg1);

        $it8 = new Itinerary();
        $it8->setEntity($ferry);
        $segments = $container->get(Formatter::class)->format($ferry, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::TRAVELER_NAMES)->setOldValue("John D");
        }
        $it8->setSegments($segments);

        // Parking
        $parking = Tools::createParking('32426711US1');
        $parking->setStartDatetime(new \DateTime("+1 day"));
        $parking->setEndDatetime(new \DateTime("+1 day 2 hour"));
        $parking->setParkingCompanyName("Parking X");
        $parking->setCarDescription("Volkswagen white");
        $parking->setSpot("4");
        $parking->setLocation("Downtown 2 hr");
        $parking->setPhone("233-123-11111");
        $parking->setParsedStatus("Confirmed");
        $parking->setPricingInfo(
            new PricingInfo(120, 'USD', null, [new Fee('Fee', 123.5)], 243.5, null, null, '34001')
        );
        $parking->setTravelerNames(["John Dogget"]);

        $it9 = new Itinerary();
        $it9->setEntity($parking);
        $segments = $container->get(Formatter::class)->format($parking, null, $locale, $lang);

        if ($changed) {
            $segments[0]->getProperty(PropertiesList::PHONE)->setOldValue('233-123-11112');
            $segments[0]->getProperty(PropertiesList::LOCATION)->setOldValue("Downtown 4 hr");
        }
        $it9->setSegments($segments);

        $template->itineraries = [
            $it1,
            $it2,
            $it3,
            $it4,
            $it5,
            $it6,
            $it7,
            $it8,
            $it9,
        ];

        return $template;
    }
}
