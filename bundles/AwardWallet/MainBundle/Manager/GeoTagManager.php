<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

class GeoTagManager
{
    /**
     * @var Statement
     */
    private $query;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->query = $connection->prepare("
		SELECT
			'Rental' AS TableName,
			RentalID AS ID,
			PickupLocation as Location,
			'PickupGeoTagID' as Field
		FROM
			Rental
		WHERE
			PickupGeoTagID IS NULL
			AND UserID = :userId

		UNION SELECT
			'Rental' AS TableName,
			RentalID AS ID,
			DropoffLocation as Location,
			'DropoffGeoTagID' as Field
		FROM
			Rental
		WHERE
			DropoffGeoTagID IS NULL
			AND UserID = :userId

		UNION SELECT
			'Restaurant' AS TableName,
			RestaurantID AS ID,
			Address as Location,
			'GeoTagID' as Field
		FROM
			Restaurant
		WHERE
			GeoTagID IS NULL
			AND Address IS NOT NULL AND Address <> ''
			AND UserID = :userId

		UNION SELECT
			'Reservation' AS TableName,
			ReservationID AS ID,
			Address as Location,
			'GeoTagID' as Field
		FROM
			Reservation
		WHERE
			GeoTagID IS NULL
			AND Address IS NOT NULL AND Address <> ''
			AND UserID = :userId
		
		UNION SELECT
			'Parking' AS TableName,
			ParkingID AS ID,
			Location,
			'GeoTagID' as Field
		FROM
			Parking
		WHERE
			GeoTagID IS NULL
			AND Location IS NOT NULL AND Location <> ''
			AND UserID = :userId

		UNION SELECT
			'TripSegment' AS TableName,
			TripSegmentID AS ID,
			DepCode as Location,
			'DepGeoTagID' as Field
		FROM
			TripSegment ts
			JOIN Trip t on ts.TripID = t.TripID
		WHERE
			DepGeoTagID IS NULL
			AND DepCode IS NOT NULL AND DepCode <> ''
			AND UserID = :userId

		UNION SELECT
			'TripSegment' AS TableName,
			TripSegmentID AS ID,
			ArrCode as Location,
			'ArrGeoTagID' as Field
		FROM
			TripSegment ts
			JOIN Trip t on ts.TripID = t.TripID
		WHERE
			ArrGeoTagID IS NULL
			AND ArrCode IS NOT NULL AND ArrCode <> ''
			AND UserID = :userId
		");
    }

    public function restoreUserGeoTags(Usr $user)
    {
        $this->query->execute(['userId' => $user->getUserid()]);

        foreach ($this->query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $tag = FindGeoTag($row['Location']);
            $this->connection->executeUpdate("UPDATE {$row['TableName']} SET {$row['Field']} = {$tag['GeoTagID']} WHERE {$row['TableName']}ID = {$row['ID']}");
        }
    }
}
