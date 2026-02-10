<?php

namespace AwardWallet\Tests\Unit\Timeline;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Timeline\Formatter\ItemFormatterInterface;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\SegmentItem;
use AwardWallet\MainBundle\Timeline\Item;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\Event;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Tests\Modules\DbBuilder\GeoTag as DbGeoTag;
use AwardWallet\Tests\Modules\DbBuilder\Reservation as DbReservation;
use AwardWallet\Tests\Modules\DbBuilder\Trip as DbTrip;
use AwardWallet\Tests\Modules\DbBuilder\TripSegment as DbTripSegment;
use AwardWallet\Tests\Modules\DbBuilder\User as DbUser;
use Codeception\Module\Aw;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @group frontend-unit
 */
class TimelineTest extends BaseTimelineTest
{
    public function testCheckoutBeforeDeparture()
    {
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "Timeline.Checkout");
        $this->aw->checkAccount($accountId);
        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user));
        $this->assertEquals(5, count($items));
        $this->assertInstanceOf(Item\Checkout::class, $items[3]);
        $this->assertInstanceOf(Item\AirTrip::class, $items[4]);
    }

    /**
     * @dataProvider checkinTimeAdjustmentProvider
     */
    public function testCheckinAfterFlightAndCheckoutBeforeFlight(
        int $transportCategory,
        string $depDateTime,
        string $arrDateTime,
        string $originalCheckinTime,
        string $originalCheckoutTime,
        ?string $expectedCheckinTime,
        ?string $expectedCheckoutTime = null
    ) {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $year = date('Y') + 1;
        $depDate = date_create(str_replace('{year}', $year, $depDateTime));
        $arrDate = date_create(str_replace('{year}', $year, $arrDateTime));
        $originalCheckin = date_create(str_replace('{year}', $year, $originalCheckinTime));
        $originalCheckout = date_create(str_replace('{year}', $year, $originalCheckoutTime));

        // Create trip with specified category and arrival time
        $this->dbBuilder->makeTrip(
            $trip = new DbTrip(
                'CBDF_TEST',
                [
                    (new DbTripSegment(
                        'JFK',
                        'JFK',
                        clone $depDate,
                        'LAX',
                        'LAX',
                        clone $arrDate,
                    ))
                        ->setDepGeoTag(new DbGeoTag('JFK'))
                        ->setArrGeoTag(new DbGeoTag('LAX')),
                ],
                $user = new DbUser(),
                [
                    'Category' => $transportCategory,
                ]
            )
        );

        $this->dbBuilder->makeReservation(
            (new DbReservation(
                'HOTEL_TEST',
                'Test Hotel LAX',
                clone $originalCheckin,
                clone $originalCheckout,
                $user
            ))->setGeoTag(new DbGeoTag('LAX'))
        );

        $entityUser = $em->find(Usr::class, $user->getId());
        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($entityUser));

        $checkinItem = null;
        $checkoutItem = null;
        $airTripItem = null;

        foreach ($items as $item) {
            if ($item instanceof Item\Checkin) {
                $checkinItem = $item;
            } elseif ($item instanceof Item\Checkout) {
                $checkoutItem = $item;
            } elseif ($item instanceof Item\AbstractTrip) {
                $airTripItem = $item;
            }
        }

        $this->assertNotNull($airTripItem);

        if ($expectedCheckinTime) {
            $this->assertNotNull($checkinItem);
            $checkinDateTime = $checkinItem->getStartDate();

            // Verify expected checkin time
            $expectedDateTime = date_create(str_replace('{year}', $year, $expectedCheckinTime));
            $this->assertEquals($expectedDateTime->format('Y-m-d H:i'), $checkinDateTime->format('Y-m-d H:i'));
        }

        if ($expectedCheckoutTime) {
            $this->assertNotNull($checkoutItem);
            $checkoutDateTime = $checkoutItem->getStartDate();

            // Verify expected checkout time
            $expectedDateTime = date_create(str_replace('{year}', $year, $expectedCheckoutTime));
            $this->assertEquals($expectedDateTime->format('Y-m-d H:i'), $checkoutDateTime->format('Y-m-d H:i'));
        }
    }

    public function checkinTimeAdjustmentProvider()
    {
        return [
            // AIR CATEGORY (Trip::CATEGORY_AIR = 1) - +1 hour after arrival

            // Basic scenarios for AIR
            'AIR - Early arrival 08:00, checkin 06:00 -> adjusted to 09:00' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '06:00'), // departure time (within correction window)
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '06:00'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '09:00'), // expected checkin (arrival + 1h = 09:00)
            ],

            'AIR - Arrival 10:00, checkin 12:00 -> preserved at 12:00 (later than arrival+1h)' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '09:00'), // departure time
                self::dateTime(1, '10:00'), // arrival time
                self::dateTime(1, '12:00'), // original checkin time (already after arrival+1h)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '12:00'), // expected checkin (preserved)
            ],

            'AIR - Late arrival 23:30, checkin next day 12:00 -> preserve next day checkin' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '14:00'), // departure time (within correction window)
                self::dateTime(1, '23:30'), // arrival time (very late)
                self::dateTime(2, '12:00'), // original checkin time (next day)
                self::dateTime(3, '12:00'), // original checkout time
                self::dateTime(2, '12:00'), // expected checkin (max(arrival+1h=00:30 next day, original=12:00 next day) = 12:00 next day, limited by endOfArrivalDay but since code has bug using $reserved date = next day 23:59, result is 12:00)
            ],

            // Departure time does NOT affect checkin correction (only checkout correction has this restriction)
            'AIR - Departure 16:00 -> checkin still gets corrected' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '16:00'), // departure time (after 15:00, but this doesn't affect checkin correction)
                self::dateTime(1, '18:00'), // arrival time
                self::dateTime(1, '15:00'), // original checkin time (too early - within search window from 00:00 to 21:00)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '19:00'), // expected checkin (arrival + 1h = 19:00, max with original = 19:00)
            ],

            // BUS CATEGORY (Trip::CATEGORY_BUS = 2) - +30 minutes after arrival

            'BUS - Early arrival 08:00, checkin 07:00 -> adjusted to 08:30' => [
                Trip::CATEGORY_BUS,
                self::dateTime(1, '06:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '07:00'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '08:30'), // expected checkin (arrival + 30min)
            ],

            'BUS - Arrival 10:00, checkin 11:00 -> preserved at 11:00' => [
                Trip::CATEGORY_BUS,
                self::dateTime(1, '09:00'), // departure time
                self::dateTime(1, '10:00'), // arrival time
                self::dateTime(1, '11:00'), // original checkin time (already after arrival+30min)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '11:00'), // expected checkin (preserved)
            ],

            'BUS - Late arrival 23:45, checkin next day 12:00 -> no correction (outside search window)' => [
                Trip::CATEGORY_BUS,
                self::dateTime(1, '14:00'), // departure time
                self::dateTime(1, '23:45'), // arrival time (very late)
                self::dateTime(2, '12:00'), // original checkin time (next day - outside search window from 00:00 to 02:45 of arrival day)
                self::dateTime(3, '12:00'), // original checkout time
                self::dateTime(2, '12:00'), // expected checkin (no correction - outside search window)
            ],

            // TRAIN CATEGORY (Trip::CATEGORY_TRAIN = 3) - +30 minutes after arrival

            'TRAIN - Early arrival 08:00, checkin 07:30 -> adjusted to 08:30' => [
                Trip::CATEGORY_TRAIN,
                self::dateTime(1, '10:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '07:30'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '08:30'), // expected checkin (arrival + 30min)
            ],

            'TRAIN - Arrival 14:00, checkin 15:00 -> preserved at 15:00' => [
                Trip::CATEGORY_TRAIN,
                self::dateTime(1, '12:00'), // departure time
                self::dateTime(1, '14:00'), // arrival time
                self::dateTime(1, '15:00'), // original checkin time (already after arrival+30min)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '15:00'), // expected checkin (preserved)
            ],

            // CRUISE CATEGORY (Trip::CATEGORY_CRUISE = 4) - +3 hours after arrival

            'CRUISE - Early arrival 08:00, checkin 10:00 -> adjusted to 11:00' => [
                Trip::CATEGORY_CRUISE,
                self::dateTime(1, '06:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '10:00'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '11:00'), // expected checkin (arrival + 3h)
            ],

            'CRUISE - Arrival 10:00, checkin 14:00 -> preserved at 14:00' => [
                Trip::CATEGORY_CRUISE,
                self::dateTime(1, '08:00'), // departure time
                self::dateTime(1, '10:00'), // arrival time
                self::dateTime(1, '14:00'), // original checkin time (already after arrival+3h)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '14:00'), // expected checkin (preserved)
            ],

            'CRUISE - Late arrival 22:00, checkin next day 12:00 -> no correction (outside search window)' => [
                Trip::CATEGORY_CRUISE,
                self::dateTime(1, '14:00'), // departure time
                self::dateTime(1, '22:00'), // arrival time (late)
                self::dateTime(2, '12:00'), // original checkin time (next day - outside search window from 00:00 to 01:00 next day of arrival day)
                self::dateTime(3, '12:00'), // original checkout time
                self::dateTime(2, '12:00'), // expected checkin (no correction - outside search window)
            ],

            // FERRY CATEGORY (Trip::CATEGORY_FERRY = 5) - +1 hour after arrival

            'FERRY - Early arrival 08:00, checkin 08:30 -> adjusted to 09:00' => [
                Trip::CATEGORY_FERRY,
                self::dateTime(1, '07:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '08:30'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '09:00'), // expected checkin (arrival + 1h)
            ],

            'FERRY - Arrival 10:00, checkin 12:00 -> preserved at 12:00' => [
                Trip::CATEGORY_FERRY,
                self::dateTime(1, '09:00'), // departure time
                self::dateTime(1, '10:00'), // arrival time
                self::dateTime(1, '12:00'), // original checkin time (already after arrival+1h)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '12:00'), // expected checkin (preserved)
            ],

            // TRANSFER CATEGORY (Trip::CATEGORY_TRANSFER = 6) - +15 minutes after arrival

            'TRANSFER - Early arrival 08:00, checkin 08:10 -> adjusted to 08:15' => [
                Trip::CATEGORY_TRANSFER,
                self::dateTime(1, '07:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '08:10'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '08:15'), // expected checkin (arrival + 15min)
            ],

            'TRANSFER - Arrival 10:00, checkin 11:00 -> preserved at 11:00' => [
                Trip::CATEGORY_TRANSFER,
                self::dateTime(1, '09:00'), // departure time
                self::dateTime(1, '10:00'), // arrival time
                self::dateTime(1, '11:00'), // original checkin time (already after arrival+15min)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '11:00'), // expected checkin (preserved)
            ],

            // Departure time boundary testing (15:00 is the checkout correction boundary)

            'AIR - Departure exactly at 15:00 (boundary) -> checkin still corrected' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '15:00'), // departure time (exactly at boundary - affects checkout but not checkin)
                self::dateTime(1, '17:00'), // arrival time
                self::dateTime(1, '16:00'), // original checkin time (within search window from 00:00 to 20:00)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '18:00'), // expected checkin (max(arrival + 1h = 18:00, original = 16:00) = 18:00)
            ],

            'AIR - Early morning departure 03:00 -> checkin corrected' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '03:00'), // departure time (very early, within window)
                self::dateTime(1, '05:00'), // arrival time
                self::dateTime(1, '04:00'), // original checkin time (too early)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '06:00'), // expected checkin (arrival + 1h)
            ],

            // Checkout correction scenarios (only when departure < 15:00)
            'AIR - Early departure 08:00, checkout 09:00 -> checkout adjusted to 05:00' => [
                Trip::CATEGORY_AIR,
                self::dateTime(2, '08:00'), // departure time (early, within correction window <15:00)
                self::dateTime(2, '10:00'), // arrival time
                self::dateTime(1, '12:00'), // original checkin time
                self::dateTime(2, '09:00'), // original checkout time (after departure - should be moved)
                self::dateTime(1, '12:00'), // expected checkin (no change)
                self::dateTime(2, '05:00'), // expected checkout (departure - 3h = 08:00 - 3h = 05:00)
            ],

            // Test checkout correction doesn't happen for late departure
            'AIR - Late departure 16:00, checkout 17:00 -> checkout not adjusted' => [
                Trip::CATEGORY_AIR,
                self::dateTime(2, '16:00'), // departure time (late, outside correction window >=15:00)
                self::dateTime(2, '18:00'), // arrival time
                self::dateTime(1, '12:00'), // original checkin time
                self::dateTime(2, '17:00'), // original checkout time
                self::dateTime(1, '12:00'), // expected checkin (no change)
                self::dateTime(2, '17:00'), // expected checkout
            ],

            // Various trip scenarios
            'AIR - Same day trip: arrival 14:00, checkin 13:00 -> adjusted to 15:00' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '12:00'), // departure time (within correction window)
                self::dateTime(1, '14:00'), // arrival time (same day)
                self::dateTime(1, '13:00'), // original checkin time (too early)
                self::dateTime(1, '18:00'), // original checkout time (same day)
                self::dateTime(1, '15:00'), // expected checkin (arrival + 1h)
            ],

            'CRUISE - Checkin already optimal -> no adjustment needed' => [
                Trip::CATEGORY_CRUISE,
                self::dateTime(1, '06:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '11:00'), // original checkin time (exactly arrival + 3h)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '11:00'), // expected checkin (no change needed)
            ],

            'TRANSFER - Small time adjustment' => [
                Trip::CATEGORY_TRANSFER,
                self::dateTime(1, '07:00'), // departure time
                self::dateTime(1, '08:00'), // arrival time
                self::dateTime(1, '08:05'), // original checkin time (only 5min after arrival)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '08:15'), // expected checkin (arrival + 15min)
            ],

            // Late evening arrival scenarios
            'AIR - Late arrival 22:00, same day checkin 21:00 -> adjusted to 23:00' => [
                Trip::CATEGORY_AIR,
                self::dateTime(1, '14:00'), // departure time
                self::dateTime(1, '22:00'), // arrival time (late evening)
                self::dateTime(1, '21:00'), // original checkin time (same day, too early - within search window)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '23:00'), // expected checkin (arrival + 1h = 23:00)
            ],

            // Test late arrival with same-day checkin limited by end of day
            'BUS - Very late arrival 23:30, same day checkin 23:15 -> limited to 23:59' => [
                Trip::CATEGORY_BUS,
                self::dateTime(1, '14:00'), // departure time
                self::dateTime(1, '23:30'), // arrival time (very late)
                self::dateTime(1, '23:15'), // original checkin time (same day, within search window)
                self::dateTime(2, '12:00'), // original checkout time
                self::dateTime(1, '23:59'), // expected checkin (max(arrival+30min=00:00 next day, original=23:15) = 00:00 next day, but limited by endOfArrivalDay = 23:59)
            ],
        ];
    }

    public function testAllTypes()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "itmaster.10ta10r10l10e");
        $this->aw->checkAccount($accountId);
        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $this->assertEquals(7, count($items));

        // try to load itineraries without accountId / providerId
        $this->em->getConnection()->executeUpdate("UPDATE Reservation SET AccountID = NULL, ProviderID = NULL WHERE AccountID = ?", [$accountId]);
        $this->em->getConnection()->executeUpdate("UPDATE Rental SET AccountID = NULL, ProviderID = NULL WHERE AccountID = ?", [$accountId]);
        $this->em->getConnection()->executeUpdate("UPDATE Trip SET AccountID = NULL, ProviderID = NULL WHERE AccountID = ?", [$accountId]);
        $this->em->getConnection()->executeUpdate("UPDATE Restaurant SET AccountID = NULL, ProviderID = NULL WHERE AccountID = ?", [$accountId]);
        $this->em->clear();
        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $this->assertCount(7, $items);
    }

    /**
     * @dataProvider getKind
     */
    public function testShareCode($kind, $expectedCount)
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "itmaster.10" . $kind);
        $this->aw->checkAccount($accountId);
        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $this->assertEquals($expectedCount, count($items));

        $shared = array_filter($items, function (array $item) {
            return !empty($item['details']['shareCode']);
        });
        $this->assertNotEmpty($shared);
        $shareCode = array_shift($shared)['details']['shareCode'];

        $token = new AnonymousToken('anon.', 'anon.');
        $this->container->get('security.token_storage')->setToken($token);
        $options = $this->getDefaultDesktopQueryOptions()->setWithDetails(true);
        $items = $this->manager->queryByShareCode($shareCode, $options);
        $items = array_filter($items, [$this, "removeDates"]);
        $this->assertEquals($expectedCount, count($items));
    }

    public function getKind()
    {
        return [
            ['ta', 2],
            ['r', 2],
            ['l', 2],
            ['e', 1],
        ];
    }

    public function testTotals()
    {
        $totals = $this->manager->getTotals($this->user);
        $this->assertCount(1, $totals);
        $this->assertEquals(0, $totals['']['count']);

        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "itmaster.10ta");
        $this->aw->checkAccount($accountId);
        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $this->assertCount(2, $items);

        $totals = $this->manager->getTotals($this->user);
        $this->assertCount(1, $totals);
        $this->assertEquals(1, $totals['']['count']);
        $this->assertEquals(1, $this->manager->getSegmentCount($this->user));

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $this->em->remove($account);
        $this->em->flush();
        $totals = $this->manager->getTotals($this->user);
        $this->assertCount(1, $totals);
        $this->assertEquals(0, $totals['']['count']);
    }

    public function testTripPropertiesHiddenOnSharedLink()
    {
        // Create trip from schema
        $schemaSource = file_get_contents(__DIR__ . '/../Itineraries/../../_data/itineraries/schemaFlight.json');
        $schema = $this->container->get('jms_serializer')->deserialize($schemaSource, Flight::class, 'json');
        $providerId = $this->aw->createAwProvider();
        /** @var Account $account */
        $account = $this->container->get('doctrine')->getRepository(Account::class)->find(
            $this->aw->createAwAccount($this->user->getUserid(), $providerId, 'login', null, ['ErrorCode' => 1])
        );
        $report = $this->container->get(ItinerariesProcessor::class)
            ->save([$schema], SavingOptions::savingByAccount($account, false));
        $this->assertCount(1, $report->getAdded());
        /** @var Trip $flight */
        $flight = $report->getAdded()[0];
        $flight->setNotes('some notes');
        $this->assertCount(2, $flight->getSegments());
        $segment = $flight->getSegments()[0];
        // Add gates and terminals because schema does not contain such info
        $segment->setDepartureGate('DG1');
        $segment->setArrivalGate('AG1');
        $segment->setDepartureTerminal('DT1');
        $segment->setArrivalTerminal('AT1');
        $this->container->get('doctrine.orm.entity_manager')->flush();
        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $item = array_shift($items);
        $this->assertArrayHasKey('confNo', $item);
        $this->assertArrayHasKey('group', $item);
        $this->assertSame('airport', $item['details']['columns'][0]['rows'][0]['type']);
        $this->assertSame('datetime', $item['details']['columns'][0]['rows'][1]['type']);
        $this->assertSame('airport', $item['details']['columns'][2]['rows'][0]['type']);
        $this->assertSame('datetime', $item['details']['columns'][2]['rows'][1]['type']);
        $this->assertSame('Gate', $item['details']['columns'][0]['rows'][3]['name']);
        $this->assertSame('Arrival Gate', $item['details']['columns'][2]['rows'][3]['name']);
        $this->assertSame('Departure Terminal', $item['details']['columns'][0]['rows'][2]['name']);
        $this->assertSame('Arrival Terminal', $item['details']['columns'][2]['rows'][2]['name']);
        $this->assertSame('Seats', $item['details']['columns'][0]['rows'][4]['name']);
        $this->assertSame('Duration', $item['details']['columns'][2]['rows'][4]['name']);
        $this->assertArrayHasKey('notes', $item['details']);
        $this->assertArrayHasKey('Spent Awards', $item['details']['pricing']);
        $this->assertArrayHasKey('Confirmation Numbers', $item['details']);
        $this->assertArrayHasKey('Account #', $item['details']);
        $this->assertArrayHasKey('Ticket Numbers', $item['details']);
        $this->assertArrayHasKey('Base Fare', $item['details']['pricing']);
        $this->assertArrayHasKey('Tax', $item['details']['pricing']);
        $this->assertArrayHasKey('Seat selection', $item['details']['pricing']);
        $this->assertArrayHasKey('Baggage fee', $item['details']['pricing']);
        $this->assertArrayHasKey('Total Charge', $item['details']['pricing']);
        $this->assertArrayHasKey('Spent Awards', $item['details']['pricing']);
        $this->assertArrayHasKey('Earned Awards', $item['details']);
        $this->assertArrayHasKey('Travelled Miles', $item['details']);
        $this->assertArrayHasKey('Meal', $item['details']);
        $this->assertArrayHasKey('Booking class', $item['details']);
        $this->assertArrayHasKey('Cabin', $item['details']);
        $this->assertArrayHasKey('monitoredStatus', $item['details']);
        $this->assertArrayHasKey('lastSync', $item);
        $this->assertArrayHasKey('lastUpdated', $item);

        $token = new AnonymousToken('anon.', 'anon.');
        $this->container->get('security.token_storage')->setToken($token);
        $options = $this->getDefaultDesktopQueryOptions()->setWithDetails(true);
        $items = $this->manager->queryByShareCode($item['details']['shareCode'], $options);
        $items = array_filter($items, [$this, "removeDates"]);
        $item = array_shift($items);
        $this->assertArrayNotHasKey('confNo', $item);
        $this->assertArrayNotHasKey('group', $item);
        $this->assertCount(2, $item['details']['columns'][0]['rows']);
        $this->assertCount(2, $item['details']['columns'][2]['rows']);
        $this->assertSame('airport', $item['details']['columns'][0]['rows'][0]['type']);
        $this->assertSame('datetime', $item['details']['columns'][0]['rows'][1]['type']);
        $this->assertSame('airport', $item['details']['columns'][2]['rows'][0]['type']);
        $this->assertSame('datetime', $item['details']['columns'][2]['rows'][1]['type']);
        $this->assertArrayNotHasKey('notes', $item['details']);
        $this->assertArrayNotHasKey('Confirmation Numbers', $item['details']);
        $this->assertArrayNotHasKey('Account #', $item['details']);
        $this->assertArrayNotHasKey('Ticket Numbers', $item['details']);
        $this->assertArrayNotHasKey('Base Fare', $item['details']);
        $this->assertArrayNotHasKey('Tax', $item['details']);
        $this->assertArrayNotHasKey('pricing', $item['details']);
        $this->assertArrayNotHasKey('Seat selection', $item['details']);
        $this->assertArrayNotHasKey('Baggage fee', $item['details']);
        $this->assertArrayNotHasKey('Total Charge', $item['details']);
        $this->assertArrayNotHasKey('Earned Awards', $item['details']);
        $this->assertArrayNotHasKey('Travelled Miles', $item['details']);
        $this->assertArrayNotHasKey('Meal', $item['details']);
        $this->assertArrayNotHasKey('Booking Class', $item['details']);
        $this->assertArrayNotHasKey('Cabin', $item['details']);
        $this->assertArrayNotHasKey('monitoredStatus', $item['details']);
        $this->assertArrayNotHasKey('lastSync', $item);
        $this->assertArrayNotHasKey('lastUpdated', $item);
        $this->assertArrayNotHasKey('pricing', $item);
    }

    public function testRestaurantPropertiesHiddenOnSharedLink()
    {
        $schemaSource = file_get_contents(__DIR__ . '/../Itineraries/../../_data/itineraries/schemaEvent.json');
        $schema = $this->container->get('jms_serializer')->deserialize($schemaSource, Event::class, 'json');
        $report = $this->container->get(ItinerariesProcessor::class)
            ->save([$schema], SavingOptions::savingByConfirmationNumber(new Owner($this->user), "testprovider", []));
        $this->assertCount(1, $report->getAdded());

        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $item = array_shift($items);
        $this->assertArrayHasKey('Guests', $item['details']);

        $token = new AnonymousToken('anon.', 'anon.');
        $this->container->get('security.token_storage')->setToken($token);
        $options = $this->getDefaultDesktopQueryOptions()->setWithDetails(true);
        $items = $this->manager->queryByShareCode($item['details']['shareCode'], $options);
        $items = array_filter($items, [$this, "removeDates"]);
        $item = array_shift($items);
        $this->assertArrayNotHasKey('Guests', $item['details']);
    }

    public function testRefreshByConfNo()
    {
        $tripRepository = $this->container->get('doctrine')->getRepository(Trip::class);
        $this->assertNull($tripRepository->findOneBy(['user' => $this->user]));
        $confFields = ['ConfNo' => 'future.trip', 'LastName' => 'Smith'];
        $provider = $this->container->get('doctrine')->getRepository(Provider::class)->find(Aw::TEST_PROVIDER_ID);
        $error = $this->aw->retrieveByConfNo($this->user, null, $provider, $confFields);
        $this->assertEmpty($error);
        /** @var Trip $savedTrip */
        $savedTrip = $tripRepository->findOneBy(['user' => $this->user]);
        $this->assertNotNull($savedTrip);

        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $route = $this->container->get("router")->generate('aw_trips_retrieve_confirmation', ['providerId' => Aw::TEST_PROVIDER_ID, 'itKind' => 'T', 'itId' => $savedTrip->getId()]);
        $this->assertEquals($route, $items[1]['details']['refreshLink']);

        $itinerary = $this->em->getRepository(Trip::class)->find($savedTrip->getId());
        $this->assertEquals($confFields, $itinerary->getConfFields());
    }

    public function testRefreshByAccount()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "future.trip");
        $this->aw->checkAccount($accountId);

        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $route = $this->container->get("router")->generate('aw_trips_update', ['accounts' => [$accountId]]);
        $this->assertEquals($route, $items[1]['details']['refreshLink']);
    }

    public function testTimeInterval()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "itmaster.10ta10r10l10e");
        $this->aw->checkAccount($accountId);

        // max segments 0 mean do not load past segments
        $emptyItems = $this->manager->query(
            $this->getDefaultDesktopQueryOptions()
                ->setStartDate((new \DateTime())->setTimestamp(time() + SECONDS_PER_DAY * 30 * 6))
                ->setMaxSegments(0)
                ->setUser($this->user)
        );
        $this->assertEquals([], $emptyItems);

        // max segments 4 mean load 4 past segments
        $pastItems = $this->manager->query(
            $this->getDefaultDesktopQueryOptions()
                ->setStartDate((new \DateTime())->setTimestamp(time() + SECONDS_PER_DAY * 30 * 6))
                ->setMaxSegments(4)
                ->setUser($this->user)
        );
        $this->assertGreaterThanOrEqual(4, count($pastItems));

        $currentItems = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user));
        $this->assertEquals($currentItems, $this->manager->query($this->getDefaultDesktopQueryOptions()->setStartDate((new \DateTime())->setTimestamp(time() - 3600))->setUser($this->user)->setMaxSegments(0)));
    }

    public function testIntervals()
    {
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "Timeline.Intervals");
        $this->aw->checkAccount($accountId);

        //		$items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setEndDate(new \DateTime('2030-01-01 14:00:00')));
        //		$this->assertEquals(0, count($items));

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setEndDate(new \DateTime('2030-02-02 13:00:00'))->setMaxSegments(3));
        $this->assertEquals(5, count($items));

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setStartDate(new \DateTime('2030-02-02 13:00:00'))->setMaxSegments(0));
        $this->assertEquals(9, count($items));

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setStartDate(new \DateTime('2029-01-01 00:00:00'))->setMaxSegments(0));
        $this->assertEquals(15, count($items));

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setEndDate(new \DateTime('2030-02-02 13:00:00')));
        $this->assertEquals(9, count($items));

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user));
        $this->assertEquals(15, count($items));
    }

    public function testPastReturnedWhenNoFuture()
    {
        $accountId = $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "Timeline.Past", "-1 month");
        $this->aw->checkAccount($accountId);

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setFuture(true));
        $segments = array_filter($items, function ($item) { return $item instanceof Item\ItineraryInterface; });

        $this->assertGreaterThanOrEqual(Manager::DEFAULT_PAST_SEGMENTS_AMOUNT, count($segments)); // no exact limit wheh withDetails is false
        $this->assertLessThan(Manager::DEFAULT_PAST_SEGMENTS_AMOUNT * 2, count($segments));
    }

    public function testHiddenTrips()
    {
        $options = $this->getDefaultDesktopQueryOptions()->setUser($this->user);
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "future.trip");

        $this->aw->checkAccount($accountId);
        $items = $this->manager->query($options);
        $this->assertEquals(2, count($items));

        $this->db->executeQuery("update Account set Login = 'itmaster.no.trle' where AccountID = $accountId");
        $this->aw->checkAccount($accountId); // reservations should be deleted and timeline will be empty
        $items = $this->manager->query($options);
        $this->assertEquals(0, count($items));

        $options = $options->setShowDeleted(true);
        $items = $this->manager->query($options);
        $this->assertEquals(2, count($items));
    }

    // TODO: break into smaller test cases
    public function testChunkLoad()
    {
        $userId = $this->user->getId();

        // RENTALS
        $rentals = [];

        foreach (range(1, 2) as $i) {
            $pnr = 'Rental' . $i;
            $location = 'location for ' . $pnr;
            $rowValues = sprintf("(
                '%s',
                '%s',
                '%s',
                DATE_ADD('2010-08-14 09:00:00', INTERVAL + 4 * %d - 100 DAY),
                DATE_ADD(CreateDate, INTERVAL +1 HOUR),
                PickupLocation,
                DATE_ADD(PickupDatetime, INTERVAL +36 HOUR),
                %d
            )", $pnr, $pnr, $location, $i, $userId);

            $this->db->executeQuery('INSERT INTO Rental (Number, RentalCompanyName, PickupLocation, CreateDate, PickupDatetime, DropoffLocation, DropoffDatetime, UserID) VALUES ' . $rowValues);
            $rentals[] = $this->db->getLastInsertId();
        }

        // RESERVATIONS
        $reservations = [];

        foreach (range(1, 2) as $i) {
            $pnr = 'Hotel' . $i;
            $rowValues = sprintf("(
                '%s',
                '%s',
                DATE_ADD('2010-08-14 09:00:00', INTERVAL + 4 * %d - 50 DAY),
                DATE_ADD(CreateDate, INTERVAL +1 HOUR),
                DATE_ADD(CheckInDate, INTERVAL +36 HOUR),
                %d,
                'a:0:{}'
            )", $pnr, $pnr, $i, $userId);
            $this->db->executeQuery('INSERT INTO Reservation (HotelName, ConfirmationNumber, CreateDate, CheckInDate, CheckOutDate, UserID, Rooms) VALUES ' . $rowValues);
            $reservations[] = $this->db->getLastInsertId();
        }

        // RESTAURANT
        $restaurants = [];

        foreach (range(1, 2) as $i) {
            foreach (range(0, 1) as $hour) {
                $pnr = 'Restaurant' . $i . '_hour' . $hour;
                $rowValues = sprintf("(
                    '%s',
                    '%s',
                    DATE_ADD(DATE_ADD('2010-08-14 09:00:00', INTERVAL + %d DAY), INTERVAL + %d * 4 HOUR),
                    DATE_ADD(CreateDate, INTERVAL +1 HOUR),
                    DATE_ADD(StartDate, INTERVAL +3 HOUR),
                    %d
                    )",
                    $pnr, $pnr, $i, $hour, $userId
                );
                $this->db->executeQuery('INSERT INTO Restaurant (ConfNo, Name, CreateDate, StartDate, EndDate, UserID) VALUES ' . $rowValues);
                $restaurants[] = $this->db->getLastInsertId();
            }
        }

        // TRIP
        $trips = [];

        foreach ([
            [
                ['JFK' => 'SFO'],
                ['SFO' => 'LAX'],
            ],
            [
                ['LAX' => 'FLL'],
                ['FLL' => 'JFK'],
                ['JFK' => 'ATL'],
            ],
            [
                ['ORD' => 'DFW'],
                ['DFW' => 'PHL'],
            ],
        ] as $i => $weeklyFlightData) {
            $recordLocator = strtoupper(substr(md5(print_r($weeklyFlightData, true)), 0, 8));
            $this->db->executeQuery(sprintf("INSERT INTO Trip (RecordLocator, CreateDate, UserID) VALUES('%s', %s, %d)",
                $recordLocator,
                $createDate = sprintf('DATE_ADD(\'2010-08-14 09:00:00\', INTERVAL + 1 + %d WEEK)', $i),
                $userId
            ));
            $tripId = $this->db->getLastInsertId();
            $date = $createDate;

            foreach ($weeklyFlightData as $flight) {
                $from = key($flight);
                $to = $flight[$from];

                $this->db->executeQuery(sprintf("INSERT INTO TripSegment (TripID, MarketingAirlineConfirmationNumber, DepCode, ArrCode, DepName, ArrName, DepDate, ScheduledDepDate, ArrDate, ScheduledArrDate, FlightNumber)
                                                                   VALUES(%d,     '%s',     '%s',    '%s',    '%s',    '%s',    %s,      %s,    %s,      %s,      '%s')",
                    $tripId,
                    $recordLocator,
                    $from,
                    $to,
                    $from,
                    $to,
                    $date = sprintf('DATE_ADD(%s, INTERVAL + 12 HOUR)', $date),
                    sprintf('DATE_ADD(%s, INTERVAL + 12 HOUR)', $date),
                    $date = sprintf('DATE_ADD(%s, INTERVAL + 6 HOUR)', $date),
                    sprintf('DATE_ADD(%s, INTERVAL + 6 HOUR)', $date),
                    'TE' . crc32(print_r([$from, $to], true))
                ));
                $trips[] = $this->db->getLastInsertId();
            }
        }

        // add segment in far future
        $this->db->executeQuery(sprintf("INSERT INTO TripSegment (TripID, DepCode, ArrCode, DepName, ArrName, DepDate, ScheduledDepDate, ArrDate, ScheduledArrDate, FlightNumber)
                                                           VALUES(%d,     '%s',    '%s',    '%s',    '%s',    %s,      %s,      %s,      %s,      '%s')",
            $tripId,
            $to,
            $from,
            $to,
            $from,
            $date = sprintf('DATE_ADD(NOW(), INTERVAL + 2 YEAR)', $date),
            sprintf('DATE_ADD(NOW(), INTERVAL + 2 YEAR)', $date),
            $date = sprintf('DATE_ADD(DepDate, INTERVAL + 6 HOUR)', $date),
            sprintf('DATE_ADD(DepDate, INTERVAL + 6 HOUR)', $date),
            'TE' . crc32(print_r([$from, $to], true))
        ));
        $trips[] = $this->db->getLastInsertId();

        $options = QueryOptions::createMobile()
            ->setWithDetails(true)
            ->setUser($this->user)
            ->lock();

        /** @var SegmentItem[] $items */
        $items = $this->manager->query($options);

        // full timeline map test
        $this->expectSegments($items, [
            'date',
            $this->pickup($rentals[0]),

            'date',
            $this->dropoff($rentals[0]),

            'date',
            $this->pickup($rentals[1]),

            'date',
            $this->dropoff($rentals[1]),

            'date',
            $this->checkin($reservations[0]),

            'date',
            $this->checkout($reservations[0]),

            'date',
            $this->checkin($reservations[1]),

            'date',
            $this->checkout($reservations[1]),

            'date',
            $this->restaurant($restaurants[0]),
            $this->restaurant($restaurants[1]),

            'date',
            $this->restaurant($restaurants[2]),
            $this->restaurant($restaurants[3]),

            'date',
            $this->trip($trips[0]),
            'date',
            $this->layover($trips[0]),
            $this->trip($trips[1]),

            'date',
            $this->trip($trips[2]),
            'date',
            $this->layover($trips[2]),
            $this->trip($trips[3]),
            $this->layover($trips[3]),

            'date',
            $this->trip($trips[4]),

            'date',
            $this->trip($trips[5]),
            'date',
            $this->layover($trips[5]),
            $this->trip($trips[6]),

            'date',
            $this->trip($trips[7]),
        ]);

        // test future
        $optionsForFuture = QueryOptions::createMobile()
            ->setWithDetails(true)
            ->setFuture(true)
            ->setMaxSegments(10)
            ->setUser($this->user)
            ->lock();

        /** @var SegmentItem[] $items */
        $items = $this->manager->query($optionsForFuture);
        $segments = array_filter($items, function (SegmentItem $item) { return !in_array($item->type, ["layover", "date"]); });

        $this->expectSegments($items, [
            'date',
            $this->restaurant($restaurants[0]), // one extra segment, because we expect full day
            $this->restaurant($restaurants[1]),

            'date',
            $this->restaurant($restaurants[2]),
            $this->restaurant($restaurants[3]),

            'date',
            $this->trip($trips[0]),
            'date',
            $this->layover($trips[0]),
            $this->trip($trips[1]),

            'date',
            $this->trip($trips[2]),
            'date',
            $this->layover($trips[2]),
            $this->trip($trips[3]),
            $this->layover($trips[3]),

            'date',
            $this->trip($trips[4]),

            'date',
            $this->trip($trips[5]),
            'date',
            $this->layover($trips[5]),
            $this->trip($trips[6]),

            'date',
            $this->trip($trips[7]),
        ]);

        $options = $options->setEndDate(new \DateTime('+ 1 YEAR'));

        // test layover-trip-layover chains capturing inside one day
        foreach ([1, 2] as $maxSegments) {
            $options = $options->setMaxSegments($maxSegments);
            /** @var SegmentItem[] $items */
            $items = $this->manager->query($options);
            $this->expectSegments($items, [
                'date',
                $this->trip($trips[5]),
                'date',
                $this->layover($trips[5]),
                $this->trip($trips[6]),
            ]);
        }

        // test layover-trip-layover chains capturing across many days
        $options = $options->setMaxSegments(3);
        /** @var SegmentItem[] $items */
        $items = $this->manager->query($options);
        $this->expectSegments($items, [
            'date',
            $this->trip($trips[3]),
            $this->layover($trips[3]),

            'date',
            $this->trip($trips[4]),

            'date',
            $this->trip($trips[5]),
            'date',
            $this->layover($trips[5]),
            $this->trip($trips[6]),
        ]);
        $options = $options->setMaxSegments(4);
        /** @var SegmentItem[] $items */
        $items = $this->manager->query($options);
        $this->expectSegments($items, [
            'date',
            $this->trip($trips[2]),
            'date',
            $this->layover($trips[2]),
            $this->trip($trips[3]),
            $this->layover($trips[3]),

            'date',
            $this->trip($trips[4]),

            'date',
            $this->trip($trips[5]),
            'date',
            $this->layover($trips[5]),
            $this->trip($trips[6]),
        ]);
        $options = $options->setMaxSegments(5);
        /** @var SegmentItem[] $items */
        $items = $this->manager->query($options);
        $this->expectSegments($items, [
            'date',
            $this->trip($trips[2]),
            'date',
            $this->layover($trips[2]),
            $this->trip($trips[3]),
            $this->layover($trips[3]),

            'date',
            $this->trip($trips[4]),

            'date',
            $this->trip($trips[5]),
            'date',
            $this->layover($trips[5]),
            $this->trip($trips[6]),
        ]);

        /** @var SegmentItem[] $items */
        /** @var SegmentItem[] $past */
        $past = $items = $this->manager->query(
            $options
                ->setMaxSegments(3)
                ->setEndDate($date = new \DateTime('@' . $items[1]->startDate->ts))
        );
        // test full day capturing
        $this->expectSegments($items, [
            'date',
            $this->restaurant($restaurants[2]),
            $this->restaurant($restaurants[3]),

            'date',
            $this->trip($trips[0]),
            'date',
            $this->layover($trips[0]),
            $this->trip($trips[1]),
        ]);

        // test full day capturing
        foreach ([1, 2] as $maxSegments) {
            $restaurantItems = $this->manager->query(
                $options
                    ->setMaxSegments($maxSegments)
                    ->setEndDate(new \DateTime('@' . $items[3]->startDate->ts))
            );

            $this->expectSegments($restaurantItems, [
                'date',
                $this->restaurant($restaurants[2]),
                $this->restaurant($restaurants[3]),
            ]
            );
        }

        // test trip full day capturing
        foreach ([1, 2] as $maxSegments) {
            $tripItems = $this->manager->query(
                $options
                    ->setMaxSegments($maxSegments)
                    ->setEndDate($date)
            );

            $this->expectSegments($tripItems, [
                'date',
                $this->trip($trips[0]),
                'date',
                $this->layover($trips[0]),
                $this->trip($trips[1]),
            ]);
        }

        $items = $this->manager->query(
            $options
                ->setMaxSegments(8)
                ->setEndDate(new \DateTime('@' . $past[1]->startDate->ts))
        );

        // test large(???) chunk
        $this->expectSegments($items, [
            'date',
            $this->pickup($rentals[1]),

            'date',
            $this->dropoff($rentals[1]),

            'date',
            $this->checkin($reservations[0]),

            'date',
            $this->checkout($reservations[0]),

            'date',
            $this->checkin($reservations[1]),

            'date',
            $this->checkout($reservations[1]),

            'date',
            $this->restaurant($restaurants[0]),
            $this->restaurant($restaurants[1]),
        ]);

        $items = $this->manager->query(
            $options
                ->setMaxSegments(3)
                ->setEndDate(new \DateTime('@' . $items[1]->startDate->ts))
        );

        // check end
        $this->expectSegments($items, [
            'date',
            $this->pickup($rentals[0]),

            'date',
            $this->dropoff($rentals[0]),
        ]);

        foreach (range(1, 10) as $maxSegments) {
            $this->assertEquals([],
                $this->manager->query(
                    $options
                        ->setMaxSegments($maxSegments)
                        ->setEndDate(new \DateTime('@' . $items[1]->startDate->ts))
                )
            );
        }
    }

    public function testDeletedTripSegment()
    {
        $tripId = $this->db->haveInDatabase(
            "Trip",
            [
                "RecordLocator" => "ManualTrip01",
                "UserID" => $this->user->getUserid(),
                "UpdateDate" => date("Y-m-d H:i:s"),
                "Category" => TRIP_CATEGORY_TRAIN,
            ]
        );
        $this->db->haveInDatabase(
            "TripSegment",
            [
                "TripID" => $tripId,
                "DepName" => "Perm",
                "ArrName" => "Saransk",
                "DepDate" => date("Y-m-d H:i:s", time() + SECONDS_PER_DAY),
                "ScheduledDepDate" => date("Y-m-d H:i:s", time() + SECONDS_PER_DAY),
                "ArrDate" => date("Y-m-d H:i:s", time() + SECONDS_PER_DAY * 2),
                "ScheduledArrDate" => date("Y-m-d H:i:s", time() + SECONDS_PER_DAY * 2),
                "Hidden" => 1,
                "MarketingAirlineConfirmationNumber" => "ManualTrip01",
            ]
        );

        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setShowDeleted(true));
        $this->assertCount(2, $items);
    }

    public function testFutureNotCutByMaxSegments()
    {
        $startDate = time() + 3600;

        for ($n = 0; $n < 20; $n++) {
            $tripId = $this->db->haveInDatabase(
                "Trip",
                [
                    "RecordLocator" => "ManualTrip" . $n,
                    "UserID" => $this->user->getUserid(),
                    "UpdateDate" => date("Y-m-d H:i:s"),
                    "Category" => TRIP_CATEGORY_TRAIN,
                ]
            );
            $this->db->haveInDatabase(
                "TripSegment",
                [
                    "TripID" => $tripId,
                    "MarketingAirlineConfirmationNumber" => "ManualTrip" . $n,
                    "DepName" => "Perm",
                    "ArrName" => "Saransk",
                    "DepDate" => date("Y-m-d H:i:s", $startDate + SECONDS_PER_DAY * $n),
                    "ScheduledDepDate" => date("Y-m-d H:i:s", $startDate + SECONDS_PER_DAY * $n),
                    "ArrDate" => date("Y-m-d H:i:s", $startDate + SECONDS_PER_DAY * $n + 3600),
                    "ScheduledArrDate" => date("Y-m-d H:i:s", $startDate + SECONDS_PER_DAY * $n + 3600),
                ]
            );
        }

        /** @var Item\ItemInterface[] $items */
        $items = $this->manager->query($this->getDefaultDesktopQueryOptions()->setUser($this->user)->setFuture(true));
        $this->assertCount(40, $items);
        $this->assertEquals(date("Y-m-d H:i:s", $startDate), $items[0]->getStartDate()->format("Y-m-d H:i:s"));
    }

    public function testNotes()
    {
        $accountId = $this->aw->createAwAccount($this->user->getUserid(), Aw::TEST_PROVIDER_ID, "itmaster.10ta10r10l10e");
        $this->aw->checkAccount($accountId);

        // add notes
        $this->em->getConnection()->executeUpdate("UPDATE Reservation SET Notes = 'Some notes' WHERE AccountID = ?", [$accountId]);
        $this->em->getConnection()->executeUpdate("UPDATE Rental SET Notes = 'Some notes' WHERE AccountID = ?", [$accountId]);
        $this->em->getConnection()->executeUpdate("UPDATE Trip SET Notes = 'Some notes' WHERE AccountID = ?", [$accountId]);
        $this->em->getConnection()->executeUpdate("UPDATE Restaurant SET Notes = 'Some notes' WHERE AccountID = ?", [$accountId]);
        $this->em->clear();

        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user));
        $items = array_filter($items, [$this, "removeDates"]);
        $this->assertEquals(7, count($items));

        foreach ($items as $item) {
            if (isset($item['details'])) {
                $this->assertEquals('Some notes', $item['details']['notes']);
            }
        }
    }

    public function testPastCutByDay()
    {
        // create reservations, 12 for future and 120 for past, 12 reservation each day
        $now = strtotime('today');
        $this->manager->getUserStartDate(
            $this->user,
            new \DateTime('@' . $now),
        );

        for ($n = -120; $n < 12; $n++) {
            $offset = $n * (3600 * 2);
            $date = $now + $offset;
            $this->db->executeQuery("INSERT INTO Restaurant (ConfNo, Name, CreateDate, StartDate, EndDate, UserID)
       		VALUES ('Confno{$n}', 'Restaurant name " . date("Y-m-d", $date) . "', FROM_UNIXTIME($now), FROM_UNIXTIME($date), NULL, {$this->user->getUserid()})");
        }

        $items = $this->manager->query((new QueryOptions())->setFormat(ItemFormatterInterface::DESKTOP)->setWithDetails(true)->setUser($this->user)->setFuture(true));
        $items = array_filter($items, function ($item) {
            return $item['type'] != 'date';
        });
        // we expect 30 past segments, but it will be extended to full day - 36, plus 12 future segments
        $this->assertCount(48, $items);
    }

    public function _after()
    {
        if (isset($_GLOBALS['kernel'])) {
            unset($_GLOBALS['kernel']);
        }

        $this->manager = null;

        parent::_after();
    }

    /**
     * @param SegmentItem[] $items
     * @param string[] $types
     */
    protected function expectSegments(array $items, $types)
    {
        $this->assertEquals($types, $actual = array_map(function ($item) use (&$types) {
            $actual = 'date' === $item->type ? 'date' : $item->id;

            return $actual;
        }, $items), var_export(['actual' => $actual, 'expected' => $types], true));
    }

    protected function trip($id)
    {
        return 'T.' . $id;
    }

    protected function layover($id)
    {
        return 'L.' . $id;
    }

    protected function checkin($id)
    {
        return 'CI.' . $id;
    }

    protected function checkout($id)
    {
        return 'CO.' . $id;
    }

    protected function pickup($id)
    {
        return 'PU.' . $id;
    }

    protected function dropoff($id)
    {
        return 'DO.' . $id;
    }

    protected function restaurant($id)
    {
        return 'E.' . $id;
    }

    // days could be inserted anywhere, discard them
    private function removeDates(array $item)
    {
        return $item['type'] != 'date';
    }

    private static function dateTime(int $day, string $time): string
    {
        return sprintf('{year}-01-%02d %s:00', $day, $time);
    }
}
