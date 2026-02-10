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
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Itineraries\TravelAgency;

/**
 * @group frontend-functional
 */
class CancelHotelCest
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

    public function cancelHotelByDates(\TestSymfonyGuy $I)
    {
        $checkinDate = date("Y-m-d 15:00", strtotime("+1 day"));
        $checkoutDate = date("Y-m-d 12:00", strtotime("+2 day"));

        $reservationId = $I->haveInDatabase("Reservation", [
            'ProviderID' => $I->grabFromDatabase("Provider", "ProviderID", ["Code" => "testprovider"]),
            'UserID' => $this->user->getUserid(),
            'TravelAgencyConfirmationNumbers' => 'CONF1',
            'HotelName' => 'Hotel1',
            'CheckInDate' => date("Y-m-d 14:00", strtotime($checkinDate)),
            'CheckOutDate' => $checkoutDate,
        ]);

        $reservation = new HotelReservation();
        $reservation->checkInDate = $checkinDate;
        $reservation->checkOutDate = $checkoutDate;
        $reservation->hotelName = 'Hotel1';
        $confNo = new ConfNo();
        $confNo->number = 'CONF2';
        $reservation->travelAgency = new TravelAgency();
        $reservation->travelAgency->confirmationNumbers = [$confNo];
        $reservation->travelAgency->providerInfo = new ProviderInfo();
        $reservation->travelAgency->providerInfo->code = 'testprovider';
        $reservation->travelAgency->providerInfo->name = 'Test Provider';
        $reservation->address = new Address();
        $reservation->address->text = "Some address";
        $reservation->cancelled = true;

        $this->itinerariesProcessor->save([$reservation], SavingOptions::savingByEmail(new Owner($this->user, null), 123, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')));
        $I->grabService('doctrine.orm.entity_manager')->flush();

        $I->assertEquals(1, $I->grabFromDatabase("Reservation", "Hidden", ["ReservationID" => $reservationId]));
        $I->assertEquals("Some address", $I->grabFromDatabase("Reservation", "Address", ["ReservationID" => $reservationId]));
    }
}
