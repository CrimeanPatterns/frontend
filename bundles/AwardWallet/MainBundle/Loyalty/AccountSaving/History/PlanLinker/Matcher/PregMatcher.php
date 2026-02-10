<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Matcher;

use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Match;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\MatcherInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class PregMatcher implements MatcherInterface
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;
    /**
     * @var string
     */
    private $pattern;
    /**
     * @var string
     */
    private $provider;
    /**
     * @var int
     */
    private $providerId;

    public function __construct(Connection $connection, string $provider, string $pattern)
    {
        $this->connection = $connection;
        $this->pattern = $pattern;
        $this->provider = $provider;
    }

    public function getProviderCodes(): array
    {
        return [$this->provider];
    }

    /**
     * @return Match[]
     */
    public function findMatchingItineraries(string $provider, int $userId, ?int $userAgentId, array $row): array
    {
        if ($row['Miles'] >= 0 || strtotime($row['PostingDate']) < strtotime("-" . TRIPS_DELETE_DAYS . " days")) {
            return [];
        }

        if (!preg_match($this->pattern, $row['Description'], $matches)) {
            return [];
        }

        $this->prepareQuery();
        $this->query->execute(["userId" => $userId, "userAgentId" => $userAgentId, "confNo" => $matches[1], "providerId" => $this->providerId]);

        return array_map(function (int $tripId) {
            return new Match("Trip", $tripId);
        }, $this->query->fetchAll(FetchMode::COLUMN));
    }

    private function prepareQuery()
    {
        if ($this->providerId === null) {
            $this->providerId = $this->connection->fetchColumn("select ProviderID from Provider where Code = ?", [$this->provider]);
        }

        if ($this->query === null) {
            $this->query = $this->connection->prepare("
            select 
                t.TripID 
            from 
                Trip t 
            where 
                t.UserID = :userId
                and (t.UserAgentID = :userAgentId or (t.UserAgentID is null and :userAgentId is null))
                and t.ProviderID = :providerId
                and t.IssuingAirlineConfirmationNumber = :confNo
            ");
        }
    }
}
