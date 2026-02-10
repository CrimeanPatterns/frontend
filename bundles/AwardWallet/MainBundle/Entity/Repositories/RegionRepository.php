<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

class RegionRepository extends EntityRepository
{
    public const REGION_KIND_REGION = 1;
    public const REGION_KIND_COUNTRY = 2;
    public const REGION_KIND_STATE = 3;
    public const REGION_KIND_CITY = 4;
    public const REGION_KIND_AIRPORT = 5;
    public const REGION_KIND_ADDRESS = 6;
    public const REGION_KIND_CONTINENT = 7;

    public $regionKindOptions = [
        self::REGION_KIND_REGION => "Region",
        self::REGION_KIND_CONTINENT => "Continent",
        self::REGION_KIND_COUNTRY => "Country",
        self::REGION_KIND_STATE => "State",
        self::REGION_KIND_CITY => "City",
        self::REGION_KIND_AIRPORT => "Airport",
        self::REGION_KIND_ADDRESS => "Address",
    ];

    public function getContinentsArray()
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
        SELECT RegionID,
            Name
        FROM   Region
        WHERE  Kind = " . self::REGION_KIND_CONTINENT . "
        ORDER BY Name
        ";
        $stmt = $connection->executeQuery($sql);
        $r = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($r as $fields) {
            $result[$fields['RegionID']] = $fields['Name'];
        }

        return $result;
    }

    public function findRegionContinentByParentsID($parentsID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
        SELECT r.RegionID,
            r.Name,
            r.Kind,
            GROUP_CONCAT(rc.RegionID) as ParentsID
        FROM Region r
        LEFT JOIN RegionContent rc ON rc.SubRegionID = r.RegionID AND rc.Exclude = 0
        WHERE r.RegionID IN ($parentsID)
        GROUP BY r.RegionID
        ORDER BY (
            CASE
                WHEN r.Kind = 7 THEN 0 ELSE 1
            END
        )
        ";
        $stmt = $connection->executeQuery($sql);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $fields) {
            if ($fields['Kind'] == self::REGION_KIND_CONTINENT) {
                return $fields['Name'];
            } elseif (!empty($fields['ParentsID'])) {
                $res = $this->findRegionContinentByParentsID($fields['ParentsID']);

                if ($res) {
                    return $res;
                }
            }
        }

        return null;
    }

    public function findParentRegions($regionId, &$parents, $topLevel = 1)
    {
        if (false !== ($cache = \Cache::getInstance()->get("regionParentsV1_" . $regionId)) && (null !== $cache) && $topLevel == 1) {
            $parents = $cache;
        } else {
            $connection = $this->getEntityManager()->getConnection();
            $sql = "
			SELECT
				pr.RegionID,
				pr.Name
			FROM
				RegionContent rc
				JOIN Region pr ON rc.RegionID = pr.RegionID
			WHERE
				rc.SubRegionID = ? AND rc.Exclude = 0";
            $stmt = $connection->executeQuery($sql, [$regionId], [\PDO::PARAM_INT]);
            $stmtResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($stmtResult as $fields) {
                $parents[$fields['RegionID']] = $fields['Name'];
                $this->findParentRegions($fields['RegionID'], $parents, 0);
            }

            if ($topLevel == 1) {
                \Cache::getInstance()->set("regionParentsV1_" . $regionId, $parents, 3600 * 24 * 3);
            }
        }
    }

    public function getUsCountryId()
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
			SELECT
				RegionID
			FROM
				Region
			WHERE
				Name = ?
		";
        $row = $connection->executeQuery($sql, ['United States'], [\PDO::PARAM_STR])->fetch(\PDO::FETCH_ASSOC);

        if ($row !== false) {
            return $row['RegionID'];
        }

        return null;
    }

    /**
     * Returns a list of country codes by id included in this region.
     *
     * @return array[countryId] => 'countryCode'
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCountryCodes(int $regionId): array
    {
        $subRegionsId = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT SubRegionID FROM RegionContent WHERE RegionID IN (?) AND Exclude = 0',
            [[$regionId]],
            [Connection::PARAM_INT_ARRAY]
        );

        if (empty($subRegionsId)) {
            return [];
        }

        return $this->getEntityManager()->getConnection()->fetchAllKeyValue('
                SELECT c.CountryID, c.Code
                FROM Region r
                JOIN Country c ON (r.CountryID = c.CountryID)
                WHERE r.RegionID IN (?)',
            [$subRegionsId],
            [Connection::PARAM_INT_ARRAY]
        );
    }
}
