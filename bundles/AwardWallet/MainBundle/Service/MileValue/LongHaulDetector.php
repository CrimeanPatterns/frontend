<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class LongHaulDetector
{
    private Connection $connection;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @param array $routes - [['DepCode' => 'JFK', 'ArrCode' => 'LAX'], ['DepCode' => ...
     * DepCode and ArrCode could be CityCode or AirCode
     */
    public function isLongHaulRoutes(array $routes): ?bool
    {
        foreach ($routes as $route) {
            $depHaulRegionId = $this->findHaulParent($route['DepCode']);

            if ($depHaulRegionId === null) {
                return null;
            }
            $arrHaulRegionId = $this->findHaulParent($route['ArrCode']);

            if ($arrHaulRegionId === null) {
                return null;
            }

            if ($depHaulRegionId === $arrHaulRegionId) {
                return false;
            }

            if (!in_array($arrHaulRegionId, $this->loadShortHaulChildren($depHaulRegionId))) {
                return true;
            }
        }

        return false;
    }

    private function findHaulParent(string $airCode): ?int
    {
        $region = $this->connection->executeQuery("
        select 
            coalesce(sr.RegionID, cr.RegionID) as RegionID,
            coalesce(sr.UseForLongOrShortHaul, cr.UseForLongOrShortHaul) as UseForLongOrShortHaul
        from 
            AirCode ac
            join Country c on c.Code = ac.CountryCode
            left outer join State s on s.CountryID = c.CountryID and s.Code = ac.State
            left outer join Region cr on cr.Kind = " . REGION_KIND_COUNTRY . " and cr.CountryID = c.CountryID
            left outer join Region sr on sr.Kind = " . REGION_KIND_STATE . " and sr.StateID = s.StateID
        where
            ac.CityCode = ?
            or ac.AirCode = ?
        order by
            case when ac.CityCode is not null then 1 else 2 end
        limit
            1
        ", [$airCode, $airCode])->fetch(FetchMode::ASSOCIATIVE);

        if ($region == false || empty($region['RegionID'])) {
            $this->logger->warning("failed to match country/state for {$airCode}");

            return null;
        }

        if ($region['UseForLongOrShortHaul'] == 1) {
            return $region['RegionID'];
        }

        $result = $this->findRegionParent($region['RegionID']);

        if ($result === null) {
            $this->logger->warning("failed to detect haul parent for {$airCode}, region {$region['RegionID']}");
        }

        return $result;
    }

    private function findRegionParent(int $regionId, array $visited = []): ?int
    {
        $visited[] = $regionId;
        $parents = $this->connection->executeQuery("
            select
                rc.RegionID,
                r.UseForLongOrShortHaul
            from
                RegionContent rc
                join Region r on rc.RegionID = r.RegionID
            where 
                rc.SubRegionID = ?
                and rc.Exclude = 0
        ", [$regionId])->fetchAll(FetchMode::ASSOCIATIVE);

        $haulRegions = array_values(array_filter($parents, function (array $region) {
            return $region['UseForLongOrShortHaul'] == '1';
        }));

        if (count($haulRegions) > 0) {
            return $haulRegions[0]['RegionID'];
        }

        foreach ($parents as $region) {
            if (in_array($region['RegionID'], $visited)) {
                $this->logger->warning("infinite parent loop for {$region['RegionID']}, visited: " . json_encode($visited));

                return null;
            }

            $parentId = $this->findRegionParent($region['RegionID'], $visited);

            if ($parentId !== null) {
                return $parentId;
            }
        }

        return null;
    }

    private function loadShortHaulChildren(int $regionId): array
    {
        return $this->connection->executeQuery("
        select 
            rc.SubRegionID
        from
            RegionContent rc
        where 
            rc.Exclude = 2
            and rc.RegionID = ?
        ", [$regionId])->fetchAll(FetchMode::COLUMN);
    }
}
