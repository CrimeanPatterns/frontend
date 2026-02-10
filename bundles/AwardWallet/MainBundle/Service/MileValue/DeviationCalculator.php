<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

use function Duration\milliseconds;
use function Duration\seconds;

class DeviationCalculator
{
    private $deviationParamsCache = [];

    private Connection $connection;
    private CacheManager $cacheManager;
    private LoggerInterface $logger;

    public function __construct(Connection $connection, CacheManager $cacheManager, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    /**
     * @return array ["Deviation" => 1.3, "Average" => 100.2]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function calcDeviationParams(int $providerId, string $classOfService, array $statuses)
    {
        if (in_array($classOfService, ["Premium Economy", "Basic Economy", "Economy Plus"])) {
            $classOfService = "Economy";
        }

        if ($classOfService === "First") {
            $classOfService = "Business";
        }

        $cacheKey = "mv_dev_" . $providerId . "_" . $classOfService;
        $cacheRef = new CacheItemReference($cacheKey, [], function () use ($statuses, $providerId, $classOfService, $cacheKey) {
            $this->logger->info("computing mile value deviation for $cacheKey");

            return $this->connection->executeQuery("select 
                ROUND(STD(MileValue), 2) as Deviation,
                ROUND(AVG(MileValue), 2) as Average
            from
                MileValue
            where 
                ProviderID = ? and ClassOfService = ?
                and CreateDate >= adddate(now(), interval -18 month)
                and Status in('" . implode("', '", $statuses) . "')", [$providerId, $classOfService])->fetchAssociative();
        });

        $cacheRef
            ->setStampedeMitigationBeta(1.0)
            ->setLockTtl(seconds(45))
            ->setLockSleepInLoopInterval(milliseconds(300))
            ->setExpiration(3600)
        ;

        return $this->cacheManager->load($cacheRef);
    }
}
