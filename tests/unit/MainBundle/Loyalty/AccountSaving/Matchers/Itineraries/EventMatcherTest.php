<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Entity\Restaurant as EntityEvent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries\EventMatcher;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Timeline\QueryOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Cruise;
use AwardWallet\Schema\Itineraries\Event as SchemaEvent;
use Codeception\Test\Unit;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class EventMatcherTest extends Unit
{
    protected $backupGlobalsBlacklist = ['Connection', 'symfonyContainer'];

    /**
     * @var EventMatcher
     */
    private $matcher;

    public function _before()
    {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class);
        $this->matcher = new EventMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class), new NullLogger());
    }

    public function testSupports()
    {
        /** @var SchemaEvent $schemaEvent */
        $schemaEvent = new SchemaEvent();
        /** @var EntityEvent $entityEvent */
        $entityEvent = new EntityEvent();
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
                return 'E';
            }
        };
        $this->assertTrue($this->matcher->supports($entityEvent, $schemaEvent));
        $this->assertFalse($this->matcher->supports($entityEvent, new Cruise()));
        $this->assertFalse($this->matcher->supports($invalidEntity, $schemaEvent));
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
                return 'E';
            }
        };
        $this->matcher->match($invalidEntity, new SchemaEvent());
    }

    public function testUpdateWithWrongSchemaType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->matcher->match(new EntityEvent(), new Cruise());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatch(
        float $expectedConfidence,
        ?string $schemaConfNo,
        ?string $entityConfNo,
        \DateTime $date,
        string $location,
        string $eventName,
        int $eventType,
        bool $match = true
    ) {
        /** @var Helper $helper */
        $helper = $this->makeEmpty(Helper::class, [
            'extractPrimaryConfirmationNumber' => $schemaConfNo,
        ]);
        $matcher = new EventMatcher($helper, $this->makeEmpty(GeoLocationMatcher::class, [
            'match' => $match,
        ]), new NullLogger());
        /** @var EntityEvent $entityEvent */
        $entityEvent = $this->makeEmpty(EntityEvent::class, [
            'getConfirmationNumber' => $entityConfNo,
            'getStartDate' => $date,
            'getAddress' => $location,
            'getName' => $eventName,
            'getEventtype' => $eventType,
            'getGeotagid' => (new Geotag())
                ->setLat(0)
                ->setLng(0),
        ]);
        /** @var SchemaEvent $schemaEvent */
        $schemaEvent = $this->getSchemaEvent();
        $this->assertSame($expectedConfidence, $matcher->match($entityEvent, $schemaEvent));
    }

    public function dataProvider()
    {
        $sameConfirmationNumber = 'same_number';
        $differentConfirmationNumber = 'different_number';

        $sameDate = new \DateTime('+1 day 12:00');
        $differentDate = new \DateTime('+2 days');

        $sameLocation = 'same address';
        $differentLocation = 'different address';

        $sameEventName = 'same event name';
        $differentEventName = 'different event name';

        $sameEventType = 1;
        $differentEventType = 2;

        return [
            [
                .99,
                $sameConfirmationNumber,
                $sameConfirmationNumber,
                $differentDate,
                $differentLocation,
                $differentEventName,
                $differentEventType,
            ],
            [
                .99,
                $sameConfirmationNumber,
                strtoupper($sameConfirmationNumber),
                $differentDate,
                $differentLocation,
                $differentEventName,
                $differentEventType,
            ],
            [
                .97,
                null,
                null,
                $sameDate,
                $sameLocation,
                $sameEventName,
                $sameEventType,
            ],
            [
                .97,
                null,
                null,
                $sameDate,
                $differentLocation,
                $sameEventName,
                $sameEventType,
                true,
            ],
            [
                .97,
                null,
                null,
                $sameDate,
                $differentLocation,
                $sameEventName,
                $sameEventType,
                false,
            ],
            [
                .97,
                null,
                $sameConfirmationNumber,
                $sameDate,
                $sameLocation,
                $sameEventName,
                $sameEventType,
            ],
            [
                .97,
                null,
                $sameConfirmationNumber,
                $sameDate,
                $sameLocation,
                $sameEventName,
                $sameEventType,
                true,
            ],
            [
                .00,
                $sameConfirmationNumber,
                $differentConfirmationNumber,
                $differentDate,
                $differentLocation,
                $differentEventName,
                $differentEventType,
            ],
        ];
    }

    private function getSchemaEvent(): SchemaEvent
    {
        $Event = new SchemaEvent();
        $Event->startDateTime = date('Y-m-dTH:i:s', strtotime('+1 day 12:00'));
        $Event->address = new Address();
        $Event->address->text = 'same address';
        $Event->eventName = 'same event name';
        $Event->eventType = 1;
        $Event->confirmationNumbers = [new ConfNo()];
        $Event->confirmationNumbers[0]->number = 'same_number';
        $Event->confirmationNumbers[0]->isPrimary = true;

        return $Event;
    }
}
