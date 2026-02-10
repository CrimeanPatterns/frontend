<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;

class ProfileObject
{
    /**
     * @var ProfileEmailAddressObject[]
     * @Type("array<AwardWallet\MainBundle\Service\Tripit\Serializer\ProfileEmailAddressObject>")
     */
    private $ProfileEmailAddresses;

    public function getProfileEmailAddresses(): array
    {
        return $this->ProfileEmailAddresses;
    }
}
