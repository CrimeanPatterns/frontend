<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\ReservationRepository;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Service\HotelPointValue\AutoReviewer;
use AwardWallet\MainBundle\Service\HotelPointValue\PointValueCalculator;
use AwardWallet\MainBundle\Service\HotelPointValue\PointValueParams;
use AwardWallet\MainBundle\Service\HotelPointValue\Price;
use AwardWallet\MainBundle\Service\HotelPointValue\PriceFinder;
use AwardWallet\MainBundle\Service\HotelPointValue\UpdateTask;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\HotelReservation;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @covers \AwardWallet\MainBundle\Service\HotelPointValue\ItineraryUpdateListener
 * @group frontend-functional
 */
class ItineraryUpdateListenerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var Usr
     */
    private $user;
    /**
     * @var string
     */
    private $providerCode;
    /**
     * @var int
     */
    private $providerId;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->providerCode = "p" . bin2hex(random_bytes(8));
        $this->providerId = $I->createAwProvider(null, $this->providerCode, ['Kind' => PROVIDER_KIND_HOTEL]);
    }

    public function createPointValueTaskWhenSpentAwardsReceived(\TestSymfonyGuy $I)
    {
        $reservation = $this->createLoyaltyReservation();

        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => Stub::once(function (UpdateTask $task) use ($I) {
                $I->assertNotEmpty($task->getReservationId());

                return new Response();
            }),
        ]));

        $itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
        $itinerariesProcessor->save([$reservation], SavingOptions::savingByEmail(new Owner($this->user), "123", null));

        $reservation = $I->query("select * from Reservation where UserID = ?", [$this->user->getId()])->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($reservation);
        $I->assertEquals(10000, $reservation['SpentAwards']);
        $hpv = $I->query("select * from HotelPointValue where ReservationID = ?", [$reservation['ReservationID']])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEmpty($hpv);
        $I->verifyMocks();
    }

    public function callAsyncWhenSpentAwardsReceived(\TestSymfonyGuy $I)
    {
        // step 1, create reservation without spent awards, no hpv record created, because no spent awards
        $reservationId = $I->createAwReservation($this->user->getId(), ["ProviderID" => $this->providerId]);

        $row = $I->query("select * from Reservation where ReservationID = ?", [$reservationId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($row);
        $I->assertEmpty($row['SpentAwards']);
        $hpv = $I->query("select * from HotelPointValue where ReservationID = ?", [$row['ReservationID']])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEmpty($hpv);

        // step 2, received spent awards, should calls async worker
        $reservation = $this->createLoyaltyReservation();

        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => Stub::once(function (UpdateTask $task) use ($I) {
                $I->assertNotEmpty($task->getReservationId());

                return new Response();
            }),
        ]));

        $reservation->pricingInfo->spentAwards = "5000";

        $itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
        $itinerariesProcessor->save([$reservation], SavingOptions::savingByEmail(new Owner($this->user), "123", null));

        $row = $I->query("select * from Reservation where ReservationID = ?", [$reservationId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(5000, $row['SpentAwards']);
        $hpv = $I->query("select * from HotelPointValue where ReservationID = ?", [$row['ReservationID']])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEmpty($hpv);

        $I->verifyMocks();
    }

    public function updateSyncWhenSpentAwardsReceived(\TestSymfonyGuy $I)
    {
        // step 1, create reservation with spent awards 5000, and create hpv record
        $reservationId = $I->createAwReservation($this->user->getId(), ["SpentAwards" => "5000", "ProviderID" => $this->providerId]);
        /** @var ReservationRepository $reservationRepo */
        $reservationRepo = $I->grabService(ReservationRepository::class);
        /** @var Reservation $reservationEntity */
        $reservationEntity = $reservationRepo->find($reservationId);

        $I->mockService(PriceFinder::class, $I->stubMakeEmpty(PriceFinder::class, [
            'search' => Stub::exactly(2, function (PointValueParams $params) {
                return new Price(1000, 0, 0, 'http://some.hotel', 'http://some.booking', 'Santa Barbara', 'Some address');
            }),
        ]));

        $I->mockService(AutoReviewer::class, $I->stubMakeEmpty(AutoReviewer::class, [
            'check' => 'deviation: 20, average: 50, delta: 30',
        ]));

        /** @var PointValueCalculator $calculator */
        $calculator = $I->grabService(PointValueCalculator::class);
        $calculator->updateItinerary($reservationEntity, false);

        $hpv = $I->query("select * from HotelPointValue where ReservationID = ?", [$reservationId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($hpv);
        $I->assertEquals(5000, $hpv['TotalPointsSpent']);
        $I->assertEquals(1000, $hpv['AlternativeCost']);
        $I->assertEquals(18, $hpv['PointValue']);
        $I->assertEquals('deviation: 20, average: 50, delta: 30', $hpv['Note']);
        $I->assertEquals(CalcMileValueCommand::STATUS_AUTO_REVIEW, $hpv['Status']);

        // step 2, received spent awards 10000, should recalc point value
        /** @var EntityManagerInterface $em */
        $em = $I->grabService('doctrine.orm.entity_manager');
        $em->refresh($reservationEntity);

        $reservation = $this->createLoyaltyReservation();

        $I->mockService(Process::class, $I->stubMakeEmpty(Process::class, [
            'execute' => Stub::never(),
        ]));

        $itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
        $itinerariesProcessor->save([$reservation], SavingOptions::savingByEmail(new Owner($this->user), "123", null));

        $row = $I->query("select * from Reservation where ReservationID = ?", [$reservationId])->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(10000, $row['SpentAwards']);
        $hpv = $I->query("select * from HotelPointValue where ReservationID = ?", [$row['ReservationID']])->fetch(\PDO::FETCH_ASSOC);
        $I->assertNotEmpty($hpv);
        $I->assertEquals(10000, $hpv['TotalPointsSpent']);
        $I->assertEquals(9, $hpv['PointValue']);

        $I->verifyMocks();
    }

    private function createLoyaltyReservation(): HotelReservation
    {
        return create(function (HotelReservation $reservation) {
            $reservation->providerInfo = create(function (ProviderInfo $providerInfo) {
                $providerInfo->name = "Some Test Provider";
                $providerInfo->code = $this->providerCode;
            });
            $reservation->confirmationNumbers = [create(function (ConfNo $confNo) {
                $confNo->number = "CONFN1";
            })];
            $reservation->hotelName = "Scorpion";
            $reservation->roomsCount = 1;
            $reservation->address = create(function (Address $address) {
                $address->text = "PHL";
            });
            $reservation->checkInDate = date("Y-m-d H:i", strtotime("+1 week"));
            $reservation->checkOutDate = date("Y-m-d H:i", strtotime("+2 week"));
            $reservation->guestCount = 1;
            $reservation->pricingInfo = create(function (PricingInfo $pricingInfo) {
                $pricingInfo->spentAwards = "10000";
                $pricingInfo->total = 100;
                $pricingInfo->currencyCode = 'USD';
            });
        });
    }
}
