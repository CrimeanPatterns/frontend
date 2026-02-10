<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class CheckExtensionSupportPackageResponse
{
    /**
     * @var bool[]
     * @Type("array<string, boolean>")
     */
    private $package;

    public function __construct(array $package = [])
    {
        $this->package = $package;
    }

    public function getPackage(): array
    {
        return $this->package;
    }

    public function setPackage(array $package): self
    {
        $this->package = $package;

        return $this;
    }
}
