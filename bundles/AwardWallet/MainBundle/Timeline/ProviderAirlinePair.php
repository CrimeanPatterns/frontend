<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;

class ProviderAirlinePair
{
    /**
     * @var Airline
     */
    private $airline;
    /**
     * @var Provider[]
     */
    private $providers;

    /**
     * @param Provider[] $providers
     */
    public function __construct(Airline $airline, array $providers)
    {
        if (empty($providers)) {
            throw new \InvalidArgumentException('Providers should not be empty');
        }

        $this->airline = $airline;
        $this->providers = $providers;
    }

    public function getAirline(): Airline
    {
        return $this->airline;
    }

    /**
     * @return Provider[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
