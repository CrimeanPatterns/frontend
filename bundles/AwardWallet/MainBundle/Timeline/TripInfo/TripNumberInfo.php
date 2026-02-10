<?php

namespace AwardWallet\MainBundle\Timeline\TripInfo;

class TripNumberInfo
{
    public ?string $tripNumber = null;

    public ?CompanyInfo $companyInfo = null;

    public function __construct(?string $tripNumber, ?CompanyInfo $companyInfo = null)
    {
        $this->tripNumber = $tripNumber;
        $this->companyInfo = $companyInfo;
    }
}
