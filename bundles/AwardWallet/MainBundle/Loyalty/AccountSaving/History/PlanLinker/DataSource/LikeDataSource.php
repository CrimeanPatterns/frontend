<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\DataSource;

use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\DataSourceInterface;
use Doctrine\DBAL\Connection;

class LikeDataSource implements DataSourceInterface
{
    /**
     * @var Connection
     */
    private $unbufConnection;
    /**
     * @var string
     */
    private $pattern;
    /**
     * @var string
     */
    private $provider;

    public function __construct(Connection $unbufConnection, string $pattern, string $provider)
    {
        $this->unbufConnection = $unbufConnection;
        $this->pattern = $pattern;
        $this->provider = $provider;
    }

    public function getRows(string $provider): iterable
    {
        $providerId = $this->unbufConnection->executeQuery("select ProviderID from Provider where Code = ?", [$provider])->fetchColumn();

        $q = $this->unbufConnection->executeQuery("
        select
            h.*,
            a.UserID,
            a.UserAgentID
        from 
            AccountHistory h
            join Account a on h.AccountID = a.AccountID 
        where
            a.ProviderID = :providerId
            and h.Description like :pattern
            and h.Miles < 0
            and h.PostingDate > adddate(now(), -" . TRIPS_DELETE_DAYS . ")
        ", ["providerId" => $providerId, "pattern" => $this->pattern]);

        return $q;
    }

    public function getProviderCodes(): array
    {
        return [$this->provider];
    }
}
