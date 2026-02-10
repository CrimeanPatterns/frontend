<?php

namespace AwardWallet\MainBundle\Timeline\TripInfo;

class ConfirmationNumberInfo
{
    public ?CompanyInfo $airlineInfo = null;

    public ?string $confirmationNumber = null;

    public function __construct(string $confirmationNumber, ?CompanyInfo $airlineInfo = null)
    {
        $this->confirmationNumber = $confirmationNumber;
        $this->airlineInfo = $airlineInfo;
    }
}
