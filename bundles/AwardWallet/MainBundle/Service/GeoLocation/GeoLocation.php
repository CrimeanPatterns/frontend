<?php

namespace AwardWallet\MainBundle\Service\GeoLocation;

use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Geo\OptionalGeoDataAwareInterface;
use AwardWallet\MainBundle\Entity\Geo\OptionalGeoDivisionsAwareInterface;
use AwardWallet\MainBundle\Entity\State;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\GeoIp2Exception;
use MaxMind\Db\Reader\InvalidDatabaseException;

class GeoLocation
{
    private EntityManagerInterface $em;
    private Reader $geoIpCountry;
    private Reader $geoIpCity;

    public function __construct(EntityManagerInterface $em, Reader $geoIpCountry, Reader $geoIpCity)
    {
        $this->em = $em;
        $this->geoIpCountry = $geoIpCountry;
        $this->geoIpCity = $geoIpCity;
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCountryIdByIp(string $ip): ?int
    {
        $country = $this->getCountryByIp($ip);

        if (!$country instanceof Country) {
            return null;
        }

        return $country->getCountryid();  // intval to prevent false updates, because doctrine thinks "230" != 230, see Invalidator, if ($entity instanceof Usr && isset($changeSet['countryid']))
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCountryByIp(string $ip): ?Country
    {
        $isoCode = null;

        try {
            if (!empty($ip)) {
                $isoCode = $this->geoIpCountry->country($ip)->country->isoCode;
            }
        } catch (GeoIp2Exception $e) {
        }

        if (empty($isoCode)) {
            return null;
        }

        $country = $this->getCountryEntity($isoCode);

        return $country;
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getStateByIp(string $ip): ?State
    {
        $country = $this->getCountryByIp($ip);

        if (!$country instanceof Country) {
            return null;
        }

        $isoCode = null;

        try {
            if (!empty($ip)) {
                $isoCode = $this->geoIpCity->city($ip)->mostSpecificSubdivision->isoCode;
            }
        } catch (GeoIp2Exception $e) {
        }

        if (empty($isoCode)) {
            return null;
        }

        $state = $this->getStateEntity($isoCode, $country);

        return $state;
    }

    /**
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getCityByIp(string $ip): ?string
    {
        $city = null;

        try {
            if (!empty($ip)) {
                $city = $this->geoIpCity->city($ip)->city->name;
            }
        } catch (GeoIp2Exception $e) {
        }

        return $city;
    }

    /**
     * @param string $ip
     * @return string|null
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getLocationNameByIp($ip)
    {
        $country = $this->getCountryByIp($ip);
        $state = $this->getStateByIp($ip);
        $city = $this->getCityByIp($ip);

        $stateName = $state && !intval($state->getName()) ? ', ' . $state->getName() : '';
        $cityName = $city ? ', ' . $city : '';

        return $country ? $country->getName() . $stateName . $cityName : null;
    }

    public function updateGeoDataByIp(OptionalGeoDataAwareInterface $geoDataContainer, string $ip): void
    {
        $awGeoResult = $this->getAwGeoResultByIp($ip);
        $this->updateGeoDataByAwGeoResult($geoDataContainer, $awGeoResult);
    }

    public function getAwGeoResultByIp(string $ip): AwGeoResult
    {
        $countryId = null;
        $stateId = null;
        $cityName = null;
        $point = null;

        try {
            $cityModel = $this->geoIpCity->city($ip);
        } catch (InvalidDatabaseException|GeoIp2Exception $e) {
            $cityModel = null;
        }

        if ($cityModel) {
            $cityLocation = $cityModel->location;

            if ($cityLocation) {
                $point = [$cityLocation->latitude, $cityLocation->longitude];
            }

            if (StringUtils::isNotEmpty($cityModel->city->name)) {
                $cityName = $cityModel->city->name;
            }

            $countryModel = $cityModel->country;
        } else {
            try {
                $countryModel = $this->geoIpCountry->country($ip);
            } catch (InvalidDatabaseException|GeoIp2Exception $e) {
                $countryModel = null;
            }
        }

        if ($countryModel) {
            $countryIsoCode = $countryModel->isoCode;

            if (StringUtils::isNotEmpty($countryIsoCode)) {
                $countryEntity = $this->getCountryEntity($countryIsoCode);

                if ($countryEntity) {
                    $countryId = $countryEntity->getCountryid();

                    if ($cityModel) {
                        $subdivision = $cityModel->mostSpecificSubdivision;

                        if ($subdivision) {
                            $stateIsoCode = $subdivision->isoCode;

                            if (StringUtils::isNotEmpty($stateIsoCode)) {
                                $stateEntity = $this->getStateEntity($stateIsoCode, $countryEntity);

                                if ($stateEntity) {
                                    $stateId = $stateEntity->getStateid();
                                }
                            }
                        }
                    }
                }
            }
        }

        return new AwGeoResult($countryId, $stateId, $cityName, $point);
    }

    public function updateGeoDataByAwGeoResult(OptionalGeoDivisionsAwareInterface $geoDataContainer, AwGeoResult $awGeoResult): void
    {
        $geoDataContainer->setCountryid($awGeoResult->getCountryId());
        $geoDataContainer->setStateid($awGeoResult->getStateId());
        $cityName = $awGeoResult->getCityName();
        $geoDataContainer->setCity(
            (null !== $cityName) ?
                \mb_substr($cityName, 0, 80) :
                null
        );
    }

    private function getStateEntity(string $isoCode, Country $country): ?State
    {
        return $this->em->getRepository(State::class)->findOneBy(['code' => $isoCode, 'countryid' => $country->getCountryid()]);
    }

    private function getCountryEntity(string $isoCode): ?Country
    {
        return $this->em->getRepository(Country::class)->findOneBy(['code' => $isoCode]);
    }
}
