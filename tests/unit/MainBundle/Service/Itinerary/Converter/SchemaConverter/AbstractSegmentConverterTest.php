<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\Itinerary\Converter\SchemaConverter;

use AwardWallet\MainBundle\Entity\Rental as EntityRental;
use AwardWallet\MainBundle\Entity\Trip as EntityTrip;

abstract class AbstractSegmentConverterTest extends AbstractConverterTest
{
    public function testValidateEntity()
    {
        $this->expectExceptionMessage(sprintf('Expected "%s", got "%s"', EntityTrip::class, EntityRental::class));
        $this->getConverter()->convert(
            $this->getSchemaItinerary(),
            new EntityRental(),
            $this->getEmailSavingOptions()
        );
    }
}
