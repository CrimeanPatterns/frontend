<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Entity\Provider;
use Doctrine\DBAL\Connection;

class ProviderOptions
{
    public const EXTEND_PROVIDER_ID = [3688, 4706, 123, 4974, 471, 1375, 783, 5042, 98, 5043, 501, 1457, 103, 106,
        5252, 5329, // hidden provider
        5418, 5419, 5420, 5421, 5422, 5423, 5424, 5425, 5426, 5427, 5428, 5429, 1376, 5430, 333, 923, 335, 5431, 1464, 5432, 1372, 5433, 5434, 5022, 285, 503, 1381, 1049,
        5567, 5568,
    ];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOptions(): array
    {
        $providersId = array_merge(Provider::EARNING_POTENTIAL_LIST, self::EXTEND_PROVIDER_ID);

        return $this->connection->executeQuery("select ProviderID, Name from Provider where ProviderID in (" . implode(", ", $providersId) . ") order by DisplayName")->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
