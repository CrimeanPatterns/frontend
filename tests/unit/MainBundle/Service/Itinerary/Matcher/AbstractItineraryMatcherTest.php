<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use AwardWallet\Tests\Modules\DbBuilder\AbstractItinerary;

abstract class AbstractItineraryMatcherTest extends AbstractTest
{
    public function dataProvider(): array
    {
        return [
            'match by primary conf no' =>
                [0.99, static::getEntity('A04-33984-12'), static::getSchema('A04-33984-12')],
            'match by primary conf no case insensitive' =>
                [0.99, static::getEntity('a04-33984-12'), static::getSchema('A04-33984-12')],
            'match by primary conf no with suffix' =>
                [0.99, static::getEntity('A04-33984-12-000'), static::getSchema('A04-33984-12')],
            'match by travel agency conf no' =>
                [0.99, static::getEntity('98765', ['11122', 'J3HND-8776']), static::getSchema('4444', ['j3HND-8776'])],
            'match by travel agency conf no with prefix' =>
                [0.99, static::getEntity('98765', ['11122', 'j3HND-877687']), static::getSchema('4444', ['J3HND-877687-666'])],
            'match by all conf no' =>
                [0.99, static::getEntity('8877569812'), static::getSchema(null, ['8877569812-111'])],
            'not match by all conf no' =>
                [0, static::getEntity('8877569812'), static::getSchema(null, ['123', '8877569812-111'])],
            'not match by primary conf no' =>
                [0, static::getEntity('12345', ['abc']), static::getSchema('67890', ['def'])],
            'some providers but not match conf no' =>
                [
                    0,
                    static::getEntity('12345', ['abc'], 'aaa', 'some'),
                    static::getSchema('12345', ['def', 'ghi'], 'bbb', 'some'),
                ],
        ];
    }

    abstract protected static function getEntity(
        ?string $confNo,
        ?array $travelAgencyConfNumbers = null,
        ?string $providerCode = null,
        ?string $travelAgencyCode = null
    ): AbstractItinerary;

    abstract protected static function getSchema(
        ?string $confNo,
        ?array $travelAgencyConfNumbers = null,
        ?string $providerCode = null,
        ?string $travelAgencyCode = null
    ): SchemaItinerary;
}
