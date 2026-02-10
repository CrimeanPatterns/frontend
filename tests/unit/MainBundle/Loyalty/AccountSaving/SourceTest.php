<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account as AccountEntity;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Account;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\ConfirmationNumber;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\Email;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Sources\SourceListInterface;
use AwardWallet\Schema\Itineraries as ItSchema;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Tests\Unit\BaseUserTest;
use Codeception\Module\Aw;
use JMS\Serializer\Serializer;

/**
 * @group frontend-unit
 */
class SourceTest extends BaseUserTest
{
    /**
     * @var ItinerariesProcessor
     */
    private $itinerariesProcessor;

    /**
     * @var Serializer
     */
    private $jms;

    public function _before()
    {
        parent::_before();

        $this->itinerariesProcessor = $this->container->get(ItinerariesProcessor::class);
        $this->jms = $this->container->get('jms_serializer');
    }

    public function _after()
    {
        $this->jms = null;
        $this->itinerariesProcessor = null;

        parent::_after();
    }

    /**
     * @dataProvider itineraryProvider
     */
    public function testEmailSource(string $schemaType, string $table, string $schemaClass)
    {
        /** @var SchemaItinerary $it */
        /** @var ProcessingReport $report */
        [$report, $it, $options, $messageId, $source] = $this->addEmailSource($schemaType, $schemaClass);

        $this->db->seeInDatabase($table, ['UserID' => $this->user->getId()]);
        $this->assertEquals(1, count($report->getAdded()));
        $this->assertEmailSource($this->getSourcable($report->getAdded()), $messageId, $source);
        $this->assertPreventDuplicates($report, $it, $options);

        /** @var ProcessingReport $report */
        [$report] = $this->addAccountSource($schemaType, $schemaClass);
        $this->assertCount(1, $report->getUpdated());
        $this->assertEquals(1, $this->db->grabCountFromDatabase($table, ['UserID' => $this->user->getId()]));
        /** @var Itinerary|SourceListInterface $updated */
        $updated = $this->getSourcable($report->getUpdated());
        $this->assertCount(2, $updated->getSources(), var_export($updated->getSources(), true));
    }

    /**
     * @dataProvider itineraryProvider
     */
    public function testAccountSource(string $schemaType, string $table, string $schemaClass)
    {
        /** @var ProcessingReport $report */
        /** @var SchemaItinerary $it */
        /** @var AccountEntity $account */
        [$report, $it, $options, $account] = $this->addAccountSource($schemaType, $schemaClass);

        $this->db->seeInDatabase($table, ['UserID' => $this->user->getId()]);
        $this->assertAccountSource($this->getSourcable($report->getAdded()), $account);
        $this->assertPreventDuplicates($report, $it, $options);

        /** @var ProcessingReport $report */
        [$report] = $this->addConfirmationNumberSource($schemaType, $schemaClass);
        $this->assertCount(1, $report->getUpdated());
        $this->assertEquals(1, $this->db->grabCountFromDatabase($table, ['UserID' => $this->user->getId()]));
        /** @var Itinerary|SourceListInterface $updated */
        $updated = $this->getSourcable($report->getUpdated());
        $this->assertCount(2, $updated->getSources(), var_export($updated->getSources(), true));
    }

    /**
     * @dataProvider itineraryProvider
     */
    public function testConfirmationNumberSource(string $schemaType, string $table, string $schemaClass)
    {
        /** @var ProcessingReport $report */
        /** @var SchemaItinerary $it */
        [$report, $it, $options, $code, $confFields] = $this->addConfirmationNumberSource($schemaType, $schemaClass);

        $this->db->seeInDatabase($table, ['UserID' => $this->user->getId()]);
        $this->assertConfirmationNumberSource($this->getSourcable($report->getAdded()), $code, $confFields);
        $this->assertPreventDuplicates($report, $it, $options);

        /** @var ProcessingReport $report */
        [$report] = $this->addEmailSource($schemaType, $schemaClass);
        $this->assertCount(1, $report->getUpdated());
        $this->assertEquals(1, $this->db->grabCountFromDatabase($table, ['UserID' => $this->user->getId()]));
        /** @var Itinerary|SourceListInterface $updated */
        $updated = $this->getSourcable($report->getUpdated());
        $this->assertCount(2, $updated->getSources(), var_export($updated->getSources(), true));
    }

    /**
     * @dataProvider itinerarySegmentProvider
     */
    public function testRemoveObsoleteSegments(string $schemaType, string $table, string $schemaClass)
    {
        /** @var ItSchema\Flight|ItSchema\Train|ItSchema\Cruise|ItSchema\Bus|ItSchema\Transfer|ItSchema\Ferry $it */
        $it = $this->getSchema($schemaType, $schemaClass);
        $options = SavingOptions::savingByEmail(
            OwnerRepository::getOwner($this->user, null),
            bin2hex(random_bytes(3)),
            new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')
        );
        $report = $this->itinerariesProcessor->save([$it], $options);
        $this->assertCount(1, $report->getAdded());
        $this->assertCount(0, $report->getUpdated());
        $this->assertCount(0, $report->getRemoved());
        /** @var Trip $trip */
        $trip = current($report->getAdded());
        $segmentsCount = count($trip->getVisibleSegments());

        // add obsolete segment
        $obsolete = $this->getObsoleteSegment(
            $trip,
            new Email(
                'obsolete_message_id',
                bin2hex(random_bytes(3)),
                new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'obsolete@test.com'),
                new \DateTimeImmutable()
            )
        );

        // partial source Email is not a reason to delete segments
        $report = $this->itinerariesProcessor->save([$it], $options);
        $this->assertCount(0, $report->getAdded());
        $this->assertCount(1, $report->getUpdated());
        $this->assertCount(0, $report->getRemoved());
        $this->assertFalse($obsolete->isHiddenByUpdater());
        $this->assertCount($segmentsCount + 1, $trip->getVisibleSegments());

        // full source Account is a reason to delete segments
        /** @var AccountEntity $account */
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "balance.random")
        );
        $report = $this->itinerariesProcessor->save([$it], SavingOptions::savingByAccount($account, true));
        $this->assertCount(0, $report->getAdded());
        $this->assertCount(1, $report->getUpdated());
        $this->assertCount(0, $report->getRemoved());
        $this->assertTrue($obsolete->isHiddenByUpdater());
        $this->assertCount($segmentsCount, $trip->getVisibleSegments());

        // add obsolete segment
        $obsolete = $this->getObsoleteSegment(
            $trip,
            new Account(123)
        );

        // full source ConfNo is a reason to delete segments
        $report = $this->itinerariesProcessor->save([$it], SavingOptions::savingByConfirmationNumber(
            new Owner($this->user), 'testprovider', ['ConfNumber' => 'xxx', 'LastName' => 'Petrov']
        ));
        $this->assertCount(0, $report->getAdded());
        $this->assertCount(1, $report->getUpdated());
        $this->assertCount(0, $report->getRemoved());
        $this->assertTrue($obsolete->isHiddenByUpdater());
        $this->assertCount($segmentsCount, $trip->getVisibleSegments());
    }

    public function itineraryProvider(): array
    {
        return [
            ['BusRide', 'Trip', ItSchema\Bus::class],
            ['Cruise', 'Trip', ItSchema\Cruise::class],
            ['Flight', 'Trip', ItSchema\Flight::class],
            ['TrainRide', 'Trip', ItSchema\Train::class],
            ['Transfer', 'Trip', ItSchema\Transfer::class],
            ['Ferry', 'Trip', ItSchema\Ferry::class],
            ['Event', 'Restaurant', ItSchema\Event::class],
            ['Rental', 'Rental', ItSchema\CarRental::class],
            ['Reservation', 'Reservation', ItSchema\HotelReservation::class],
            ['Parking', 'Parking', ItSchema\Parking::class],
        ];
    }

    public function itinerarySegmentProvider(): array
    {
        return [
            ['BusRide', 'Trip', ItSchema\Bus::class],
            ['Cruise', 'Trip', ItSchema\Cruise::class],
            ['Flight', 'Trip', ItSchema\Flight::class],
            ['TrainRide', 'Trip', ItSchema\Train::class],
            ['Transfer', 'Trip', ItSchema\Transfer::class],
            ['Ferry', 'Trip', ItSchema\Ferry::class],
        ];
    }

    private function getObsoleteSegment(Trip $trip, SourceInterface $source): Tripsegment
    {
        $obsolete = (new Tripsegment())
            ->setDepname('obsolete dep')
            ->setDepartureDate(new \DateTime('+1 year'))
            ->setArrname('obsolete arr')
            ->setArrivalDate(new \DateTime('+1 year'));
        $obsolete->addSource($source);
        $trip->addSegment($obsolete);
        $this->em->flush();

        return $obsolete;
    }

    private function getSchema(string $schemaType, string $schemaClass)
    {
        $schemaSource = file_get_contents(__DIR__ . "/../../../../_data/itineraries/schema{$schemaType}.json");

        return $this->jms->deserialize($schemaSource, $schemaClass, 'json');
    }

    private function getSourcable(array $list): SourceListInterface
    {
        $item = $list[0];

        return $item instanceof Trip ? $item->getSegments()[0] : $item;
    }

    private function assertEmailSource(SourceListInterface $sourcable, string $messageId, ParsedEmailSource $source)
    {
        /** @var Email $current */
        $current = current($sourcable->getSources());
        $this->assertInstanceOf(Email::class, $current);
        $this->assertEquals("e.$messageId", $current->getId());
        $this->assertEquals($source->getUserEmail(), $current->getRecipient());
    }

    private function assertAccountSource(SourceListInterface $sourcable, AccountEntity $account)
    {
        /** @var Account $current */
        $current = current($sourcable->getSources());
        $this->assertInstanceOf(Account::class, $current);
        $this->assertEquals("a.{$account->getId()}", $current->getId());
        $this->assertEquals($account->getId(), $current->getAccountId());
    }

    private function assertConfirmationNumberSource(SourceListInterface $sourcable, string $code, array $confFields)
    {
        /** @var ConfirmationNumber $current */
        $current = current($sourcable->getSources());
        $this->assertInstanceOf(ConfirmationNumber::class, $current);
        $this->assertEquals($code, $current->getProviderCode());
        $this->assertEquals($confFields, $current->getConfirmationFields());
    }

    private function assertPreventDuplicates(ProcessingReport $report, SchemaItinerary $it, SavingOptions $options)
    {
        $added = $report->getAdded()[0];
        $report = $this->itinerariesProcessor->save([$it], $options);
        $this->assertCount(0, $report->getAdded());
        $this->assertCount(1, $report->getUpdated());
        /** @var Itinerary $updated */
        $updated = $report->getUpdated()[0];
        $this->assertEquals($added->getId(), $updated->getId());

        if ($updated instanceof Trip) {
            foreach ($updated->getSegments() as $segment) {
                $this->assertCount(1, $segment->getSources());
            }
        } else {
            /** @var SourceListInterface $updated */
            $this->assertCount(1, $updated->getSources());
        }
    }

    private function addEmailSource(string $schemaType, string $schemaClass): array
    {
        /** @var SchemaItinerary $it */
        $it = $this->getSchema($schemaType, $schemaClass);
        $options = SavingOptions::savingByEmail(
            OwnerRepository::getOwner($this->user, null),
            $messageId = bin2hex(random_bytes(3)),
            $source = new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')
        );

        return [$this->itinerariesProcessor->save([$it], $options), $it, $options, $messageId, $source];
    }

    private function addAccountSource(string $schemaType, string $schemaClass): array
    {
        /** @var SchemaItinerary $it */
        $it = $this->getSchema($schemaType, $schemaClass);
        /** @var AccountEntity $account */
        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find(
            $this->aw->createAwAccount($this->user->getId(), Aw::TEST_PROVIDER_ID, "balance.random")
        );
        $options = SavingOptions::savingByAccount(
            $account,
            true
        );

        return [$this->itinerariesProcessor->save([$it], $options), $it, $options, $account];
    }

    private function addConfirmationNumberSource(string $schemaType, string $schemaClass): array
    {
        /** @var SchemaItinerary $it */
        $it = $this->getSchema($schemaType, $schemaClass);
        $options = SavingOptions::savingByConfirmationNumber(
            new Owner($this->user),
            $code = 'testprovider',
            $confFields = ['ConfNumber' => 'xxx', 'LastName' => 'Petrov']
        );

        return [$this->itinerariesProcessor->save([$it], $options), $it, $options, $code, $confFields];
    }
}
