<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\HotelReservation;
use AwardWallet\Schema\Itineraries\Room;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-functional
 */
class SpecialCharsInRoomsCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Usr $user;

    private ?ItinerariesProcessor $itinerariesProcessor;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->itinerariesProcessor = null;
    }

    /**
     * this test will grab reservation from account and email
     * check serialization.
     */
    public function smile(\TestSymfonyGuy $I)
    {
        $checkinDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $checkoutDate = date("Y-m-d 12:00", strtotime("+2 day"));
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');

        $reservation = new HotelReservation();
        $reservation->checkInDate = $checkinDate;
        $reservation->checkOutDate = $checkoutDate;
        $reservation->hotelName = 'Marriott';
        $confNo = new ConfNo();
        $confNo->number = 'CONF2';
        $reservation->confirmationNumbers = [$confNo];
        $reservation->address = new Address();
        $reservation->address->text = "Some address";
        $room = new Room();
        $room->type = "Deluxe Double Room";
        $room->description = "NonSmoke, LargeBed, AdditionalNotes:Bitte große Dusche 2x Parkplatz PKW Danke 😃";
        $reservation->rooms = [
            $room,
        ];

        $this->itinerariesProcessor->save([$reservation], SavingOptions::savingByEmail(new Owner($this->user, null), 123, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')));
        $em->flush();

        // check deserialization
        $em->clear();
        $reservation = $em->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class)->findOneBy(['user' => $this->user]);
        /** @var \AwardWallet\MainBundle\Entity\Room $roomEntity */
        $roomEntity = $reservation->getRooms()[0];
        $I->assertEquals(
            "NonSmoke, LargeBed, AdditionalNotes:Bitte große Dusche 2x Parkplatz PKW Danke 😃",
            $roomEntity->getLongDescription()
        );
    }
}
