<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<Country>
 */
class CountryRepository extends EntityRepository
{
    /**
     * @var array cache locations by IP
     */
    private $locationCache = [];
    /**
     * @var array<int, string>
     */
    private $countryCodes;
    /**
     * @var array<string, array>
     */
    private $countriesByCode;

    /** @var array<string, array> */
    private $countriesWithStates;

    public function getCountryCodes()
    {
        if (!isset($this->countryCodes)) {
            $connection = $this->getEntityManager()->getConnection();
            $stmt = $connection->executeQuery("SELECT CountryID, Code FROM Country WHERE Code IS NOT NULL");

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->countryCodes[(int) $row['CountryID']] = $row['Code'];
            }
        }

        return $this->countryCodes;
    }

    public function getCountriesByCode()
    {
        if (!isset($this->countriesByCode)) {
            $connection = $this->getEntityManager()->getConnection();
            $stmt = $connection->executeQuery("SELECT * FROM Country WHERE Code IS NOT NULL");

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (!StringUtils::isEmpty($row['Code'])) {
                    $this->countriesByCode[$row['Code']] = $row;
                }
            }
        }

        return $this->countriesByCode;
    }

    public function getCountriesWithStates(): array
    {
        if (!empty($this->countriesWithStates)) {
            return $this->countriesWithStates;
        }

        $rowsWithStates = $this->getEntityManager()->getConnection()->fetchAll('SELECT * FROM Country WHERE HaveStates > 0');
        $rowsWithStates = array_combine(array_column($rowsWithStates, 'CountryID'), $rowsWithStates);

        $states = $this->getEntityManager()->getConnection()->fetchAll(
            'SELECT CountryID, Code, Name FROM State WHERE CountryID IN(?)',
            [array_column($rowsWithStates, 'CountryID')],
            [$this->getEntityManager()->getConnection()::PARAM_INT_ARRAY]
        );

        foreach ($states as $state) {
            $countryId = $state['CountryID'];

            if (!array_key_exists('states', $rowsWithStates[$countryId])) {
                $rowsWithStates[$countryId]['states'] = [];
            }
            $rowsWithStates[$countryId]['states'][$state['Code']] = $state['Name'];
        }
        $this->countriesWithStates = array_combine(array_column($rowsWithStates, 'Code'), $rowsWithStates);

        return $this->countriesWithStates;
    }
}
