<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\PricingInfo;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\RentalMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\CarRental as SchemaRental;
use AwardWallet\Schema\Itineraries\CarRentalLocation;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class RentalMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var RentalMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        $this->matcher = new RentalMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
    }

    public function testSupports()
    {
        /** @var SchemaRental $schemaRental */
        $schemaRental = new SchemaRental();
        /** @var EntityRental $entityRental */
        $entityRental = new EntityRental();
        $invalidEntity = new class() extends EntityItinerary {
            public function getStartDate()
            {
            }

            public function getEndDate()
            {
            }

            public function getUTCStartDate()
            {
            }

            public function getUTCEndDate()
            {
            }

            public function getPhones()
            {
            }

            public function getGeoTags()
            {
            }

            public function getType(): string
            {
                return '';
            }

            public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
            {
                return [];
            }

            public function getKind(): string
            {
                return 'L';
            }
        };
        $this->assertTrue($this->matcher->supports($entityRental, $schemaRental));
        $this->assertFalse($this->matcher->supports($entityRental, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaRental));
    }

    public function testUpdateWithWrongEntityType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $invalidEntity = new class() extends EntityItinerary {
            public function getStartDate()
            {
            }

            public function getEndDate()
            {
            }

            public function getUTCStartDate()
            {
            }

            public function getUTCEndDate()
            {
            }

            public function getPhones()
            {
            }

            public function getGeoTags()
            {
            }

            public function getType(): string
            {
                return '';
            }

            public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null): array
            {
                return [];
            }

            public function getKind(): string
            {
                return 'L';
            }
        };
        $this->matcher->match($invalidEntity, new SchemaRental());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityRental(), new Cruise());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        ?string $schemaConfNo,
        ?string $entityConfNo,
        \DateTime $pickUpDate,
        \DateTime $dropOffDate,
        string $pickUpLocation,
        string $dropOffLocation,
        string $companyName,
        array $travelerNames
    ) {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => $schemaConfNo,
        ]);
        $matcher = new RentalMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class, ['match' => function ($location1, $location2) {
            return $location1 === $location2;
        }]), new NullLogger());
        /** @var EntityRental $entityRental */
        $entityRental = $this->makeEmpty(EntityRental::class, [
            'getConfirmationNumber' => $entityConfNo,
            'getPickupdatetime' => $pickUpDate,
            'getDropoffdatetime' => $dropOffDate,
            'getPickuplocation' => $pickUpLocation,
            'getDropofflocation' => $dropOffLocation,
            'getRentalCompanyName' => $companyName,
            'getTravelerNames' => $travelerNames,
        ]);
        /** @var SchemaRental $schemaRental */
        $schemaRental = $this->getSchemaRental();
        $this->assertSame($expectedConfidence, $matcher->match($entityRental, $schemaRental));
    }

    public function dataProvider()
    {
        $sameConfirmationNumber = 'same_number';
        $differentConfirmationNumber = 'different_number';

        $samePickUpDate = new \DateTime('+1 day 12:00');
        $differentPickUpDate = new \DateTime('+2 days');

        $sameDropOffDate = new \DateTime('+1 day 16:00');
        $differentDropOffDate = new \DateTime('+2 days');

        $samePickUpLocation = 'same pickup address';
        $differentPickUpLocation = 'different pickup address';

        $sameDropOffLocation = 'same drop-off address';
        $differentDropOffLocation = 'different drop-off address';

        $sameCompany = 'same company';
        $differentCompany = 'different company';

        $sameName = 'John Smith';
        $differentName = 'Alice Vox';

        return [
            [
                .96,
                $sameConfirmationNumber,
                $differentConfirmationNumber,
                $samePickUpDate,
                $sameDropOffDate,
                $differentPickUpLocation,
                $differentDropOffLocation,
                $sameCompany,
                [$sameName],
            ],
            [
                .96,
                $sameConfirmationNumber,
                $differentConfirmationNumber,
                $samePickUpDate,
                $sameDropOffDate,
                $differentPickUpLocation,
                $differentDropOffLocation,
                $sameCompany,
                [strtoupper($sameName)],
            ],
            [
                .00,
                $sameConfirmationNumber,
                $differentConfirmationNumber,
                $samePickUpDate,
                $sameDropOffDate,
                $differentPickUpLocation,
                $differentDropOffLocation,
                $differentCompany,
                [$differentName],
            ],
            [
                .99,
                $sameConfirmationNumber,
                $sameConfirmationNumber,
                $differentPickUpDate,
                $differentDropOffDate,
                $differentPickUpLocation,
                $differentDropOffLocation,
                $differentCompany,
                [$sameName],
            ],
            [
                .99,
                $sameConfirmationNumber,
                strtoupper($sameConfirmationNumber),
                $differentPickUpDate,
                $differentDropOffDate,
                $differentPickUpLocation,
                $differentDropOffLocation,
                $differentCompany,
                [$sameName],
            ],
            [
                .97,
                null,
                null,
                $samePickUpDate,
                $sameDropOffDate,
                $samePickUpLocation,
                $sameDropOffLocation,
                $sameCompany,
                [$sameName],
            ],
            [
                .00,
                $sameConfirmationNumber,
                null,
                $differentPickUpDate,
                $sameDropOffDate,
                $samePickUpLocation,
                $sameDropOffLocation,
                $sameCompany,
                [$sameName],
            ],
        ];
    }

    public function testAvisMatcher()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => '25296542US5',
        ]);
        $matcher = new RentalMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class, ['match' => function ($location1, $location2) {
            return $location1 === $location2;
        }]), new NullLogger());
        /** @var EntityRental $entityRental */
        $entityRental = $this->makeEmpty(EntityRental::class, [
            'getConfirmationNumber' => '25296542US5PEXP',
            'getPickupdatetime' => new \DateTime('+1 day 12:00'),
            'getDropoffdatetime' => new \DateTime('+1 day 16:00'),
            'getPickuplocation' => 'same pickup address',
            'getDropofflocation' => 'same drop-off address',
            'getRentalCompanyName' => 'same company',
            'getTravelerNames' => ['John Smith'],
            'getProvider' => $this->makeEmpty(Provider::class, ['getCode' => 'provider']),
        ]);
        /** @var SchemaRental $schemaRental */
        $schemaRental = $this->getSchemaRental();
        $schemaRental->confirmationNumbers[0]->number = '25296542US5';
        $this->assertSame(0.97, $matcher->match($entityRental, $schemaRental));
    }

    public function testDifferentProviderAndConfNo()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => '12345',
        ]);
        $matcher = new RentalMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class, ['match' => function ($location1, $location2) {
            return $location1 === $location2;
        }]), new NullLogger());
        /** @var EntityRental $entityRental */
        $entityRental = $this->makeEmpty(EntityRental::class, [
            'getConfirmationNumber' => '56789',
            'getPickupdatetime' => new \DateTime('+1 day 12:00'),
            'getDropoffdatetime' => new \DateTime('+1 day 16:00'),
            'getPickuplocation' => 'same pickup address',
            'getDropofflocation' => 'same drop-off address',
            'getRentalCompanyName' => 'same company',
            'getTravelerNames' => ['John Smith'],
            'getProvider' => $this->makeEmpty(Provider::class, ['getCode' => 'provider2']),
            'getPricingInfo' => $this->makeEmpty(PricingInfo::class, ['getTotal' => 10]),
        ]);
        /** @var SchemaRental $schemaRental */
        $schemaRental = $this->getSchemaRental();
        $schemaRental->pricingInfo = new \AwardWallet\Schema\Itineraries\PricingInfo();
        $schemaRental->pricingInfo->total = 20;
        $this->assertSame(0.00, $matcher->match($entityRental, $schemaRental));
    }

    private function getSchemaRental(): SchemaRental
    {
        $rental = new SchemaRental();
        $rental->providerInfo = new ProviderInfo();
        $rental->providerInfo->code = 'provider';
        $rental->pickup = new CarRentalLocation();
        $rental->dropoff = new CarRentalLocation();
        $rental->pickup->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $rental->pickup->address = new Address();
        $rental->pickup->address->text = 'same pickup address';
        $rental->dropoff->localDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 16:00'));
        $rental->dropoff->address = new Address();
        $rental->dropoff->address->text = 'same drop-off address';
        $rental->rentalCompany = 'same company';
        $rental->confirmationNumbers = [new ConfNo()];
        $rental->confirmationNumbers[0]->number = 'same_number';
        $rental->confirmationNumbers[0]->isPrimary = true;
        $rental->driver = new Person();
        $rental->driver->name = 'John Smith';
        $rental->driver->full = true;

        return $rental;
    }
}
