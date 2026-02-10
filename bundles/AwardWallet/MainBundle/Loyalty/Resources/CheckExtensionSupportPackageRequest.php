<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class CheckExtensionSupportPackageRequest
{
    /**
     * @var CheckExtensionSupportRequest[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportRequest>")
     */
    private $package;

    /**
     * @return CheckExtensionSupportRequest[]
     */
    public function getPackage(): array
    {
        return $this->package;
    }

    public function setPackage(?array $package): self
    {
        $this->package = $package;

        return $this;
    }
}
