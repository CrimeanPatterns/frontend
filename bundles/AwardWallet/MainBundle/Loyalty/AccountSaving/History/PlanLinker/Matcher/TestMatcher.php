<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Matcher;

use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Match;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\MatcherInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class TestMatcher implements MatcherInterface
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var \Doctrine\DBAL\Statement
     */
    private $query;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getProviderCodes(): array
    {
        return ['testprovider'];
    }

    /**
     * @return Match[]
     */
    public function findMatchingItineraries(string $provider, int $userId, ?int $userAgentId, array $row): array
    {
        if ($row['Miles'] >= 0 || strtotime($row['PostingDate']) < strtotime("-" . TRIPS_DELETE_DAYS . " days")) {
            return [];
        }

        if (!preg_match('#REF (\w{6})\b#ims', $row['Description'], $matches)) {
            return [];
        }

        $this->prepareQuery();
        $this->query->execute(["userId" => $userId, "userAgentId" => $userAgentId, "confNo" => $matches[1]]);

        return array_map(function (int $tripId) {
            return new Match("Trip", $tripId);
        }, $this->query->fetchAll(FetchMode::COLUMN));
    }

    private function prepareQuery()
    {
        if ($this->query === null) {
            $this->query = $this->connection->prepare("
            select 
                t.TripID 
            from 
                Trip t 
            where 
                t.UserID = :userId
                and (t.UserAgentID = :userAgentId or (t.UserAgentID is null and :userAgentId is null))
                and t.ProviderID = 636
                and t.IssuingAirlineConfirmationNumber = :confNo
            ");
        }
    }
}
