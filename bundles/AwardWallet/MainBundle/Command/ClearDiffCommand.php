<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ClearDiffCommand extends Command
{
    protected static $defaultName = 'aw:clear-diff';
    private Connection $connection;
    private LoggerInterface $logger;
    private CacheManager $cacheManager;

    public function __construct(Connection $connection, LoggerInterface $logger, CacheManager $cacheManager)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->logger = $logger;
        $this->cacheManager = $cacheManager;
    }

    public function configure()
    {
        $this
            ->setDescription('Clear expired diff history from database');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->connection;
        $result = [
            'deleted' => 0,
            'updated' => 0,
        ];
        $affectedUserIds = [];

        $connection->transactional(function () use (&$result, $connection, &$affectedUserIds) {
            $date = new \DateTime();
            $connection->executeQuery('set @userids := null');
            $result['updated'] = $connection->executeUpdate("
                UPDATE DiffChange dc
                LEFT JOIN TripSegment s ON 
                    s.TripSegmentID = IF(SUBSTR(dc.SourceID, 1, 2) = 'S.', CAST(SUBSTR(dc.SourceID, 3) as UNSIGNED INTEGER), 0)
                LEFT JOIN Trip t ON
                    s.TripID = t.TripID
                LEFT JOIN Reservation r ON 
                    r.ReservationID = IF(SUBSTR(dc.SourceID, 1, 2) = 'R.', CAST(SUBSTR(dc.SourceID, 3) as UNSIGNED INTEGER), 0)
                LEFT JOIN Rental l ON 
                    l.RentalID      = IF(SUBSTR(dc.SourceID, 1, 2) = 'L.', CAST(SUBSTR(dc.SourceID, 3) as UNSIGNED INTEGER), 0)
                LEFT JOIN Restaurant e ON 
                    e.RestaurantID  = IF(SUBSTR(dc.SourceID, 1, 2) = 'E.', CAST(SUBSTR(dc.SourceID, 3) as UNSIGNED INTEGER), 0)
                SET
                    s.ChangeDate = IF(s.ChangeDate IS NOT NULL AND s.ChangeDate = dc.ChangeDate, NULL, s.ChangeDate),
                    r.ChangeDate = IF(r.ChangeDate IS NOT NULL AND r.ChangeDate = dc.ChangeDate, NULL, r.ChangeDate),
                    l.ChangeDate = IF(l.ChangeDate IS NOT NULL AND l.ChangeDate = dc.ChangeDate, NULL, l.ChangeDate),
                    e.ChangeDate = IF(e.ChangeDate IS NOT NULL AND e.ChangeDate = dc.ChangeDate, NULL, e.ChangeDate)
                WHERE 
                    dc.ExpirationDate < ? and 
                    (select @userids := concat_ws(',', coalesce(t.UserID, r.UserID, l.UserID, e.UserID), @userids))",
                [$date],
                ['datetime']
            );
            $result['deleted'] = $connection->executeUpdate("DELETE FROM DiffChange WHERE ExpirationDate < ?", [$date], ['datetime']);

            $affectedUserIds =
                it(explodeLazy(',', $connection->executeQuery('select @userids')->fetchColumn()))
                ->mapByTrim()
                ->filterNotEmpty()
                ->uniqueAdjacent()
                ->unique();
        });

        $cacheManager = $this->cacheManager;

        foreach ($affectedUserIds as $userId) {
            $cacheManager->invalidateTags([Tags::getTimelineKey($userId)]);
        }

        $logger = $this->logger;
        $logger->info("deleted {$result['deleted']} rows");
        $logger->info("updated {$result['updated']} rows");

        return 0;
    }
}
