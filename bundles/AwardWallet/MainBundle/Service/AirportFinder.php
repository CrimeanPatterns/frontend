<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Globals\Geo;
use Doctrine\DBAL\Connection;

class AirportFinder
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * find nearest airports by array of coordinates.
     *
     * @param array $points array of coordinates, each coordinate
     *                      is an array with keys 'id', 'lat', 'lng', 'filter' and 'radius' in miles
     * @return array key is the id of the point, value is the array of arrays with keys 'airport' and 'distance'
     */
    public function findNearestAirports(array $points): array
    {
        if (empty($points)) {
            return [];
        }

        $queries = [];

        foreach ($points as $point) {
            $lat = (float) $point['lat'];
            $lng = (float) $point['lng'];
            $radius = (float) $point['radius'];
            $id = (string) $point['id'];
            $filter = !is_null($point['filter'] ?? null) ? (string) $point['filter'] : '';

            // only for use index in query
            // it would seem that the side of the square is equal to twice the radius of the inscribed circle,
            // but by multiplying by 2, the point at the maximum distance is filtered out
            // that's why the coefficient was selected experimentally
            $coeff = 2.1;
            $conditions = Geo::getSquareGeofenceSQLCondition(
                $lat,
                $lng,
                'Lat',
                'Lng',
                false,
                $radius * $coeff
            );

            // calculate in radius instead of square
            $distanceSQL = Geo::getDistanceSQL($lat, $lng, 'Lat', 'Lng');
            $queries[] = "
                SELECT
                    '{$id}' AS PointID,
                    AirCode,
                    {$distanceSQL} AS Distance
                FROM AirCode
                WHERE 
                    {$conditions}
                    {$filter}
                HAVING Distance <= {$radius} AND Distance > 0
            ";
        }

        $query = implode(' UNION ALL ', $queries);
        $query .= ' ORDER BY PointID, Distance';
        $stmt = $this->connection->executeQuery($query);
        $result = [];

        while ($row = $stmt->fetchAssociative()) {
            if (!isset($result[$row['PointID']])) {
                $result[$row['PointID']] = [];
            }

            $result[$row['PointID']][] = [
                'airport' => $row['AirCode'],
                'distance' => (float) $row['Distance'],
            ];
        }

        return $result;
    }
}
