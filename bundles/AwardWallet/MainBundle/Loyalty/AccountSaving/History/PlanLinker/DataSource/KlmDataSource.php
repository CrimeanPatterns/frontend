<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\DataSource;

use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\DataSourceInterface;
use Doctrine\DBAL\Connection;

class KlmDataSource implements DataSourceInterface
{
    /**
     * @var Connection
     */
    private $unbufConnection;

    public function __construct(Connection $unbufConnection)
    {
        $this->unbufConnection = $unbufConnection;
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
            a.ProviderID = ?
            and h.Description like '%My Trip to%-%'
            and h.Miles < 0
            and h.PostingDate > adddate(now(), -" . TRIPS_DELETE_DAYS . ")
        ", [$providerId]);

        return $q;
    }

    public function getProviderCodes(): array
    {
        return ['klm', 'airfrance'];
    }
}
