<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\Itineraries;

use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;

interface ItineraryProcessorInterface
{
    public function process($schemaItinerary, SavingOptions $options): ProcessingReport;
}
