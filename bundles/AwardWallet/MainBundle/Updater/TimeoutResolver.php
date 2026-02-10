<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Provider;
use Duration\Duration;

use function Duration\seconds;

class TimeoutResolver
{
    private Duration $defaultTimeout;
    private Duration $checkTripsOrAmexChaseCitibankAccountUpdateTimeout;

    public function __construct(
        int $defaultTimeoutSeconds,
        int $checkTripsOrAmexChaseCitibankAccountUpdateTimeoutSeconds
    ) {
        $this->defaultTimeout = seconds($defaultTimeoutSeconds);
        $this->checkTripsOrAmexChaseCitibankAccountUpdateTimeout = seconds($checkTripsOrAmexChaseCitibankAccountUpdateTimeoutSeconds);
    }

    public function resolveForProvider(?Provider $provider): Duration
    {
        if (!$provider) {
            return $this->defaultTimeout;
        }

        return
            (
                $provider->getCancheckitinerary()
                || in_array(
                    $provider->getId(),
                    [
                        Provider::CHASE_ID,
                        Provider::AMEX_ID,
                        Provider::CITI_ID,
                    ],
                    true
                )
            ) ?
                $this->checkTripsOrAmexChaseCitibankAccountUpdateTimeout :
                $this->defaultTimeout;
    }
}
