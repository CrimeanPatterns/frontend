<?php

namespace AwardWallet\MainBundle\Service\MobileData;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;

class DataFormatter
{
    private TravelSummary $travelSummary;
    private SafeExecutorFactory $safeExecutorFactory;

    public function __construct(
        TravelSummary $travelSummary,
        SafeExecutorFactory $safeExecutorFactory
    ) {
        $this->travelSummary = $travelSummary;
        $this->safeExecutorFactory = $safeExecutorFactory;
    }

    public function getData(Usr $user): array
    {
        return [
            'travelSummary' => $this->safeExecutorFactory->make(fn () => $this->travelSummary->getData($user))->runOrValue([]),
        ];
    }
}
