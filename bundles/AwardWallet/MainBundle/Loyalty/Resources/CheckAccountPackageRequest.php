<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class CheckAccountPackageRequest
{
    /**
     * @var CheckAccountRequest[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest>")
     */
    private $package;

    /**
     * @param array
     * @return $this
     */
    public function setPackage($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @return array
     */
    public function getPackage()
    {
        return $this->package;
    }
}
