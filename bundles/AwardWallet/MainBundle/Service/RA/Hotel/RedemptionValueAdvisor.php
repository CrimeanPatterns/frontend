<?php

namespace AwardWallet\MainBundle\Service\RA\Hotel;

use AwardWallet\MainBundle\Service\RA\Hotel\DTO\RedemptionDeal;
use Doctrine\DBAL\Connection;

class RedemptionValueAdvisor
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $providerCode hotel provider code
     * @param string $hotelName hotel name for matching
     * @param float $providerUsdPerNight price per night in USD
     * @param int $providerPointsPerNight price per night in points
     * @param int|null $maxResults null - return all results, otherwise return only $maxResults
     * @return RedemptionDeal[]
     */
    public function calculateRedemptionDeals(
        string $providerCode,
        string $hotelName,
        float $providerUsdPerNight,
        int $providerPointsPerNight,
        ?int $maxResults = null
    ): array {
        return [];
    }
}
