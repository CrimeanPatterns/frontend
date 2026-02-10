<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class StringField extends AbstractField
{
    public static function create(string $property, ?string $label = null, bool $custom = false): self
    {
        return new self($property, $label, $custom);
    }
}
