<?php

namespace AwardWallet\MainBundle\Service\FlightSearch\Place;

use AwardWallet\MainBundle\Service\FlightSearch\PlaceQuery;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class PlaceFactory
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function build(int $type, int $id): ?PlaceItem
    {
        $place = $this->fetchPlace($type, $id);

        if (empty($place)) {
            return null;
        }

        return $this->getPlaceItem($type, $place);
    }

    public function getPlaceItem(int $type, array $place): PlaceItem
    {
        switch ($type) {
            case PlaceQuery::TYPE_AIRPORT:
                return (new PlaceItem(
                    $type,
                    $place['AirCodeID'],
                    $place['AirName'],
                    $place['AirCode'],
                    (empty($place['StateName']) ? '' : $place['StateName'] . ', ') . $place['CountryName'],
                    $place['AirCode'] . ', ' . $place['CityName'] . ', ' . $place['CountryName']))
                    ->setCountryCode($place['CountryCode'])
                    ->setCountryName($place['CountryName'])
                    ->setStateCode($place['State'])
                    ->setStateName($place['StateName'])
                    ->setCityCode($place['CityCode'])
                    ->setCityName($place['CityName'])
                    ->setAirCode($place['AirCode']);

            case PlaceQuery::TYPE_CITY:
                return (new PlaceItem(
                    $type,
                    $place['AirCodeID'],
                    $place['CityName'],
                    $place['CityCode'],
                    $place['CountryName'],
                    $place['CityName'] . ', ' . $place['CountryName']))
                    ->setCountryCode($place['CountryCode'])
                    ->setCountryName($place['CountryName'])
                    ->setStateCode($place['State'])
                    ->setStateName($place['StateName'])
                    ->setCityCode($place['CityCode'])
                    ->setCityName($place['CityName']);

            case PlaceQuery::TYPE_STATE:
                return (new PlaceItem(
                    $type,
                    $place['StateID'],
                    $place['StateName'] ?? $place['Name'],
                    $place['Code'],
                    $place['CountryName'],
                    ($place['StateName'] ?? $place['Name']) . ', ' . $place['CountryName']))
                    ->setCountryCode($place['CountryCode'])
                    ->setCountryName($place['CountryName'])
                    ->setStateCode($place['Code'])
                    ->setStateName($place['StateName'] ?? $place['Name']);

            case PlaceQuery::TYPE_COUNTRY:
                return (new PlaceItem(
                    $type,
                    $place['CountryID'],
                    $place['Name'],
                    $place['Code'],
                    '',
                    $place['Name']))
                    ->setCountryCode($place['Code'])
                    ->setCountryName($place['Name']);

            case PlaceQuery::TYPE_REGION:
                return new PlaceItem(
                    $type,
                    $place['RegionID'],
                    $place['Name'],
                    '',
                    '',
                    $place['Name']);
        }

        throw new \Exception('Unknown type: ' . $type);
    }

    private function fetchPlace(int $type, int $id)
    {
        $connection = $this->entityManager->getConnection();

        switch ($type) {
            case PlaceQuery::TYPE_AIRPORT:
                $place = $connection->fetchAssociative(
                    'SELECT AirCodeID, AirCode, AirName, CityCode, CityName, State, StateName, CountryCode, CountryName FROM AirCode WHERE AirCodeID = ?',
                    [$id],
                    [\PDO::PARAM_INT]
                );

                break;

            case PlaceQuery::TYPE_CITY:
                $place = $connection->fetchAssociative(
                    'SELECT AirCodeID, AirCode, AirName, CityName, State, StateName, CityCode, CountryCode, CountryName FROM AirCode WHERE AirCodeID = ?',
                    [$id],
                    [\PDO::PARAM_INT]
                );

                break;

            case PlaceQuery::TYPE_STATE:
                $place = $connection->fetchAssociative(
                    'SELECT s.StateID, s.Code, s.Name, c.Code AS CountryCode, c.Name AS CountryName FROM State s JOIN Country c ON s.CountryID = c.CountryID WHERE StateID = ?',
                    [$id],
                    [\PDO::PARAM_INT]
                );

                break;

            case PlaceQuery::TYPE_COUNTRY:
                $place = $connection->fetchAssociative(
                    'SELECT CountryID, Code, Name FROM Country WHERE CountryID = ?',
                    [$id],
                    [\PDO::PARAM_INT]
                );

                break;

            case PlaceQuery::TYPE_REGION:
                $place = $connection->fetchAssociative(
                    'SELECT RegionID, Kind, Name FROM Region WHERE RegionID = ? AND RegionID NOT IN (?)',
                    [$id, PlaceQuery::EXCLUDE_REGION_ID],
                    [\PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]
                );

                break;

            default:
                throw new \Exception('Unknown type: ' . $type);
        }

        return $place;
    }
}
