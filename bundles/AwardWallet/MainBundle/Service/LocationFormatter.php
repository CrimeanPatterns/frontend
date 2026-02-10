<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;

class LocationFormatter
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function formatLocationName(string $cityName, string $countryCode, ?string $countryName = null, ?string $stateName = null, ?string $stateCode = null): string
    {
        $parts = [$cityName];

        $countryWithStates = $this->entityManager->getRepository(Country::class)->getCountriesWithStates();

        if (empty($countryName)) {
            $countryCodes = $this->entityManager->getRepository(Country::class)->getCountriesByCode();

            if (array_key_exists($countryCode, $countryCodes)) {
                $countryName = $countryCodes[$countryCode]['Name'];
            }
        }

        if (in_array(strtoupper($countryCode), ['US', 'CA'])) {
            if (!empty($stateCode) && array_key_exists($stateCode, $countryWithStates[$countryCode]['states'])) {
                $state = $countryWithStates[$countryCode]['states'][$stateCode];
            } else {
                $state = array_key_exists($countryCode, $countryWithStates) && (false !== ($stateCode = array_search($stateName, $countryWithStates[$countryCode]['states']))) ? $countryWithStates[$countryCode]['states'][$stateCode] : null;
            }

            if (!empty($stateCode) && !empty($state) && $stateCode !== $cityName) {
                $parts[] = $stateCode;
            }

            if (empty($state) && !empty($countryName)) {
                $parts[] = $countryName;
            }
        } elseif (!empty($countryName)) {
            $parts[] = $countryName;
        }

        $parts = array_filter($parts, static function ($name) {
            return !empty($name);
        });

        return implode(', ', array_unique($parts));
    }
}
