<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;
use Doctrine\ORM\EntityRepository;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AirlineRepository extends EntityRepository
{
    public const MIN_SCORE = 80;

    /**
     * Find the most relevant airline based on provided data.
     */
    public function search(?string $icao, ?string $iata, ?string $name, ?bool $includeNonActive = false): ?Airline
    {
        // Nothing to work with
        if (null === $icao && null === $iata && null === $name) {
            return null;
        }
        // First, lets check ICAO and IATA
        $criteria = array_filter(['code' => $iata, 'icao' => $icao]);

        if (!$includeNonActive) {
            $criteria['active'] = true;
        }
        $airlinesByCodes = [];

        if (!empty($criteria)) {
            $airlinesByCodes = $this->findBy($criteria);
        }

        switch (count($airlinesByCodes)) {
            case 0:
                // If no airlines by codes were found, then try to get one by name
                /** @var Airline $airline */
                return $this->findOneBy(['name' => $name]);

            case 1:
                // If Exactly one airline was found by code, then it is it
                return reset($airlinesByCodes);

            default:
                if (null === $name) {
                    return null;
                }

                return
                    $this->selectBestMatchByName($airlinesByCodes, $name)
                    ?? $this->searchAirlineByProvider($name);
        }
    }

    private function searchAirlineByProvider(string $name): ?Airline
    {
        /** @var ProviderRepository $providerRepo */
        $providerRepo = $this->_em->getRepository(Provider::class);
        $matches = $providerRepo->searchProviderByText($name, null, null, 10);

        if (count($matches) === 0) {
            return null;
        }

        $matchedIataCodes = it($matches)
            ->map(function (array $provider) {
                return $provider['IATACode'];
            })
            ->filter(function ($code) { return $code !== null; })
            ->toArray()
        ;

        if (count($matchedIataCodes) === 0) {
            return null;
        }

        return $this->findOneBy(['code' => reset($matchedIataCodes)]);
    }

    /**
     * @param Airline[] $airlines
     */
    private function selectBestMatchByName(array $airlines, string $name): ?Airline
    {
        $bestMatch = null;
        $bestScore = 0;
        $name = $this->filterAirlineName($name);

        foreach ($airlines as $airline) {
            similar_text($this->filterAirlineName($airline->getName() ?? ''), $name, $score);

            if ($score >= self::MIN_SCORE && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $airline;
            }
        }

        return $bestMatch;
    }

    private function filterAirlineName(string $name): string
    {
        $name = str_ireplace("airlines", "", $name);
        $name = str_ireplace("airline", "", $name);

        return strtolower(trim($name));
    }
}
