<?php

namespace AwardWallet\MainBundle\Service\Lounge\OpeningHours;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\Discriminator(
 *     field = "type",
 *     map = {
 *         "structured": "AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours",
 *         "raw": "AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours"
 *     }
 * )
 */
abstract class AbstractOpeningHours implements \JsonSerializable
{
    abstract public function isEquals(AbstractOpeningHours $openingHours): bool;
}
