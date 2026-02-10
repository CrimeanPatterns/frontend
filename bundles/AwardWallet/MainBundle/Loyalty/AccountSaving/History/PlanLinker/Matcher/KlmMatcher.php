<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Matcher;

use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Match;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\MatcherInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class KlmMatcher implements MatcherInterface
{
    private const PROVIDER_IDS = [
        'klm' => 37,
        'airfrance' => 44,
    ];

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function getProviderCodes(): array
    {
        return ['klm', 'airfrance'];
    }

    /**
     * @return Match[]
     */
    public function findMatchingItineraries(string $provider, int $userId, ?int $userAgentId, array $row): array
    {
        if ($row['Miles'] >= 0 || strtotime($row['PostingDate']) < strtotime("-" . TRIPS_DELETE_DAYS . " days")) {
            return [];
        }

        if (!preg_match('#My Trip to .*; ([^\,]+)\, (.+)$#ims', $row['Description'], $matches)) {
            return [];
        }

        $airports = $this->convertRoutesToAirports($matches[1]);

        if ($airports === null) {
            $this->logger->warning("failed to parse airports in: {$row['Description']}, UUID: {$row['UUID']}, AccountID: {$row['AccountID']}, UserID: {$row['UserID']}, date: {$row['PostingDate']}, provider: {$provider}");

            return [];
        }
        $name = $matches[2];

        $info = unserialize($row['Info'], ['allowed_classes' => false]);

        if (!isset($info['Travel Date'])) {
            return [];
        }

        $this->prepareQuery();
        $this->query->execute([
            "providerId" => self::PROVIDER_IDS[$provider],
            "userId" => $userId,
            "userAgentId" => $userAgentId,
            "travelDate" => date("Y-m-d", strtotime($info['Travel Date'])),
        ]);
        $segments = $this->query->fetchAll(FetchMode::ASSOCIATIVE);

        return $this->matchHistoryRowToTrips($airports, $name, $segments);
    }

    /**
     * @param string $routeStr - like 'AMS - BUD - JFK - AMS'
     * @return string - like 'AMS,BUD,JFK' - unique airports sorted alphabetically
     */
    private function convertRoutesToAirports(string $routeStr): ?string
    {
        $codes = array_unique(explode(' - ', $routeStr));
        sort($codes);
        $result = implode(',', $codes);

        if ($result === '') {
            return null;
        }

        return $result;
    }

    private function prepareQuery()
    {
        if ($this->query === null) {
            $this->query = $this->connection->prepare("
            select 
                t.TripID,
                t.TravelerNames,
                group_concat(concat(ts.DepCode, ',', ts.ArrCode) separator ',') as Airports,
                min(ts.DepDate) as TravelDate
            from 
                Trip t 
                join TripSegment ts on t.TripID = ts.TripID
            where 
                t.UserID = :userId
                and (t.UserAgentID = :userAgentId or (t.UserAgentID is null and :userAgentId is null))
                and t.ProviderID = :providerId
                and t.TravelerNames is not null
            group by 
                t.TripID,
                t.TravelerNames
            having 
                abs(datediff(TravelDate, :travelDate)) <= 1  
            ");
        }
    }

    /**
     * @param array $airports - 'AMS,BUD,JFK' - unique airports sorted alphabetically
     * @param array $segments - [['TripID' => 123, 'TravelerNames' => 'Mr Alexi Vereschaga', 'Airports' => 'AMS,BUD,JFK,AMS'], ...
     */
    private function matchHistoryRowToTrips(string $airports, string $name, array $segments)
    {
        $segments = array_filter($segments, function (array $segment) use ($airports, $name): bool {
            $segmentAirports = array_unique(explode(',', $segment['Airports']));
            sort($segmentAirports);
            $segment['Airports'] = implode(",", $segmentAirports);

            return
                $segment['Airports'] === $airports
                && $this->similarNames($name, $segment['TravelerNames']);
        });

        return array_map(function (array $segment): Match {
            return new Match("Trip", $segment['TripID']);
        }, $segments);
    }

    private function similarNames(string $name1, string $name2): bool
    {
        $names1 = array_map(function (string $name): string { return $this->normalizeName($name); }, explode(",", $name1));
        $names2 = array_map(function (string $name): string { return $this->normalizeName($name); }, explode(",", $name2));

        return count(array_intersect($names1, $names2)) > 0;
    }

    private function normalizeName(string $name): string
    {
        $words = explode(" ", strtolower($name));
        $words = array_filter($words, function (string $word): bool {
            return strlen($word) > 3;
        });
        sort($words);

        return implode(" ", $words);
    }
}
