<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Record\Subdivision;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Geo
{
    /**
     * miles.
     */
    public const EARTH_RADIUS_MILES = 3950;
    public const KM_IN_MILE = 1.609344;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Reader
     */
    private $geoReader;

    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        Reader $geoReader
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->geoReader = $geoReader;
    }

    public static function km2Mi(float $km): float
    {
        return $km / self::KM_IN_MILE;
    }

    /**
     * returns distance between two locations in miles.
     *
     * @return float|int
     */
    public static function distance($srcLat, $srcLng, $dstLat, $dstLng)
    {
        if ($srcLat == $dstLat && $srcLng == $dstLng) {
            return 0;
        }

        $srcLat = deg2rad($srcLat);
        $srcLng = deg2rad($srcLng);
        $dstLat = deg2rad($dstLat);
        $dstLng = deg2rad($dstLng);
        $distance = acos(sin($srcLat) * sin($dstLat) + cos($srcLat) * cos($dstLat) * cos($dstLng - $srcLng)) * self::EARTH_RADIUS_MILES;

        return $distance;
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param string $latField
     * @param string $lngField
     * @param bool $needParams
     * @param float $squareSide
     * @return array|string
     */
    public static function getSquareGeofenceSQLCondition($lat, $lng, $latField, $lngField, $needParams = true, $squareSide = 4.0)
    {
        if ($squareSide > Geo::EARTH_RADIUS_MILES) {
            $squareSide = Geo::EARTH_RADIUS_MILES;
        }

        $degDiff = rad2deg($squareSide / Geo::EARTH_RADIUS_MILES) * 0.5;

        $latNorthBoundary = $lat + $degDiff;

        if ($latNorthBoundary > 90) {
            $latNorthBoundary = 90 - ($latNorthBoundary - 90);
        }

        $latSouthBoundary = $lat - $degDiff;

        if ($latSouthBoundary < -90) {
            $latSouthBoundary = -90 + (-$latSouthBoundary - 90);
        }

        $lngEastBoundary = $lng + $degDiff;

        if ($lngEastBoundary > 180) {
            $lngEastBoundary = -180 + ($lngEastBoundary - 180);
        }

        $lngWestBoundary = $lng - $degDiff;

        if ($lngWestBoundary < -180) {
            $lngWestBoundary = 180 + ($lngWestBoundary + 180);
        }

        if ($needParams) {
            $conditions = /** @lang MySQL */
                "
                (
                    ({$latField} <= :latNorth) AND
                    ({$latField} >= :latSouth)
                )
            ";
            $paramsList = [
                [':latNorth', $latNorthBoundary, \PDO::PARAM_STR],
                [':latSouth', $latSouthBoundary, \PDO::PARAM_STR],
            ];
        } else {
            $conditions = /** @lang MySQL */
                "
                (
                    ({$latField} <= $latNorthBoundary) AND
                    ({$latField} >= $latSouthBoundary)
                )
            ";
            $paramsList = null;
        }

        if ($lngEastBoundary >= $lngWestBoundary) {
            if ($needParams) {
                $conditions = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= :lngWest) AND
                    ({$lngField} <= :lngEast)
                ";
                $paramsList[] = [':lngWest', $lngWestBoundary, \PDO::PARAM_STR];
                $paramsList[] = [':lngEast', $lngEastBoundary, \PDO::PARAM_STR];
            } else {
                $conditions = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= {$lngWestBoundary}) AND
                    ({$lngField} <= {$lngEastBoundary})
                ";
            }
        } else {
            if ($needParams) {
                $conditions = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= :lngWest)
                ";
                $paramsList[] = [':lngWest', $lngWestBoundary, \PDO::PARAM_STR];
            } else {
                $conditions = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= {$lngWestBoundary})
                ";
            }
        }

        if ($needParams) {
            return [$conditions, $paramsList];
        } else {
            return $conditions;
        }
    }

    /**
     * @param string $srcLat circle center
     * @param string $srcLng circle center
     * @param string $dstLat
     * @param string $dstLng
     * @return string
     */
    public static function getDistanceSQL($srcLat, $srcLng, $dstLat, $dstLng)
    {
        $srcLat = "radians({$srcLat})";
        $srcLng = "radians({$srcLng})";
        $dstLat = "radians({$dstLat})";
        $dstLng = "radians({$dstLng})";

        return "(
            acos(
                sin({$srcLat}) * sin({$dstLat}) + 
                cos({$srcLat}) * cos({$dstLat}) * cos({$dstLng} - {$srcLng})
            ) * " . self::EARTH_RADIUS_MILES .
        ")";
    }

    /**
     * @param string|null $ip
     * @throws
     */
    public function getLocationByIp($ip): array
    {
        if (empty($ip)) {
            return ['ip' => null];
        }

        try {
            $record = $this->geoReader->city($ip);

            $parts = [];
            $parts[] = $record->city->name;

            if ('US' === $record->country->isoCode && !empty($record->subdivisions)) {
                /** @var Subdivision $state */
                $state = $record->subdivisions[0];
                $parts[] = $state->isoCode;
            }
            $parts[] = $record->country->name;
            $parts = array_filter($parts, function ($name) {
                return !empty($name);
            });

            if (empty($parts)) {
                $parts[] = $this->translator->trans('unknown');
            }

            $result = [
                'ip' => $ip,
                'lat' => $record->location->latitude,
                'lng' => $record->location->longitude,
                'name' => implode(', ', $parts),
            ];
        } catch (AddressNotFoundException $e) {
            $this->logger->info('IP address not found in geoip database: ' . $ip);
            $result = ['ip' => $ip];
        }

        return $result;
    }

    /**
     * @return mixed|string
     */
    public function getLocationName(?array $location = null)
    {
        if (!empty($location['name'])) {
            return $location['name'];
        }

        return $this->translator->trans('unknown');
    }

    /**
     * @param array|string $currentIp
     * @param array|string $lastIp
     * @throws \BadMethodCallException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isUserMatchLocations(Usr $user, $currentIp, $lastIp): bool
    {
        is_string($currentIp) && filter_var($currentIp, FILTER_VALIDATE_IP) ? $currentIp = $this->getLocationByIp($currentIp) : null;
        is_string($lastIp) && filter_var($lastIp, FILTER_VALIDATE_IP) ? $lastIp = $this->getLocationByIp($lastIp) : null;

        if (empty($currentIp['ip']) || empty($lastIp['ip'])) {
            return false;
        }

        $lastIps = $this->entityManager->getConnection()
            ->executeQuery('SELECT IP FROM UserIP WHERE UserID = :userId', ['userId' => $user->getUserid()], [\PDO::PARAM_INT])
            ->fetchAll(\PDO::FETCH_COLUMN);
        $lastIps[] = $lastIp['ip'];

        foreach ($lastIps as $ip) {
            if ($currentIp['ip'] === $ip) {
                return true;
            }

            $lastIp = $this->getLocationByIp($ip);

            if (!empty($lastIp['lat']) && !empty($currentIp['lat'])) {
                $distance = self::distance($lastIp['lat'], $lastIp['lng'], $currentIp['lat'], $currentIp['lng']);

                if ($distance <= 100) {
                    return true;
                }
            }
        }

        $this->logger->warning('last locations does not match', ['UserID' => $user->getUserid(), 'current' => $currentIp, 'lastIps' => $lastIps]);

        return false;
    }
}
