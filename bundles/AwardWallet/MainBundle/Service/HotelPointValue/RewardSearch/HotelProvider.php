<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue\RewardSearch;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\DBAL\Connection;

class HotelProvider
{
    private MileValueService $mileValueService;
    private HotelHandler $hotelHandler;
    private Connection $connection;
    private CacheManager $cacheManager;

    public function __construct(
        MileValueService $mileValueService,
        HotelHandler $hotelHandler,
        Connection $connection,
        CacheManager $cacheManager
    ) {
        $this->mileValueService = $mileValueService;
        $this->hotelHandler = $hotelHandler;
        $this->connection = $connection;
        $this->cacheManager = $cacheManager;
    }

    public function getAll(int $itMinCount): array
    {
        $cacheRef = new CacheItemReference(HotelSearch::CACHE_KEY . '_all', [], function () use ($itMinCount) {
            $providers = $this->connection->fetchAll('
                SELECT DISTINCT h.ProviderID, MAX(h.PointValue) AS _maxPointValue,
                       p.ShortName AS ProviderShortName
                FROM Hotel h
                JOIN Provider p ON (p.ProviderID = h.ProviderID)
                GROUP BY ProviderID
                ORDER BY _maxPointValue DESC'
            );

            $result = [];

            foreach ($providers as $provider) {
                $providerId = (int) $provider['ProviderID'];
                $hotels = $this->hotelHandler->getHotels($providerId, HotelHandler::HOTEL_PROVIDER_LIMIT, $itMinCount);
                $avgPointValue = $this->mileValueService->getProviderValue($providerId, 'AvgPointValue');

                $result[] = new HotelProviderItem(
                    $providerId,
                    html_entity_decode($provider['ProviderShortName']),
                    $avgPointValue,
                    $hotels
                );
            }

            return $result;
        });
        $cacheRef->setExpiration(HotelSearch::CACHE_LIFETIME);

        return $this->cacheManager->load($cacheRef);
    }
}
