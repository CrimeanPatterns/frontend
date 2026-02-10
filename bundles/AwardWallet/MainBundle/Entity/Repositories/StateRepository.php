<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\State;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<State>
 */
class StateRepository extends EntityRepository
{
    public function getStatesByCountry(int $countryId): array
    {
        return $this->getEntityManager()->getConnection()
            ->fetchAllKeyValue('
                SELECT StateID, Name 
                FROM State 
                WHERE CountryID = :countryId
                ORDER BY Name ASC
            ',
                ['countryId' => $countryId],
                ['countryId' => \PDO::PARAM_INT]
            );
    }

    public function getLocalizedCountriesStates(array $countries): array
    {
        $states = $this->getEntityManager()->getConnection()
            ->fetchAllAssociative('
                SELECT s.StateID, s.Name, s.Code, c.CountryID 
                FROM State s
                JOIN Country c ON (s.CountryID = c.CountryID)
                WHERE
                        c.CountryID IN (:countriesId)
                    AND c.HaveStates > 0
                ORDER BY s.Name ASC
            ',
                ['countriesId' => array_keys($countries)],
                ['countriesId' => Connection::PARAM_INT_ARRAY]
            );
        $result = [];

        foreach ($countries as $countryId => $countryName) {
            $result[$countryId] = [
                'CountryID' => $countryId,
                'Name' => $countryName,
            ];
        }

        foreach ($states as $state) {
            $countryId = $state['CountryID'];

            if (array_key_exists($countryId, $result)) {
                if (!array_key_exists('states', $result[$countryId])) {
                    $result[$countryId]['states'] = [];
                }
                unset($state['CountryID']);
                $result[$countryId]['states'][$state['StateID']] = $state;
            }
        }

        return $result;
    }
}
