<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearUnlinkedCardImagesCommand extends Command
{
    protected static $defaultName = 'aw:clear-unlinked-card-images';
    private Connection $connection;
    private CardImageManager $cardImageManager;

    public function __construct(Connection $connection, CardImageManager $cardImageManager)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->cardImageManager = $cardImageManager;
    }

    public function configure()
    {
        $this
            ->setDescription('Clear unlinked card images');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $cardImageManager = $this->cardImageManager;
        /** @var Connection $connection */
        $connection = $this->connection;
        $stmt = $connection->executeQuery('
            SELECT
                CardImageID, StorageKey
            FROM CardImage
            WHERE
                (
                    (
                        AccountID IS NULL AND
                        SubAccountID IS NULL AND
                        ProviderCouponID IS NULL
                    )
                    OR
                    (
                        IF(AccountID IS NULL, 0, 1) +
                        IF(SubAccountID IS NULL, 0, 1) +
                        IF(ProviderCouponID IS NULL, 0, 1)
                    ) > 1
                ) AND
                UploadDate < DATE_ADD(NOW(), INTERVAL -? HOUR)',
            [3],
            [\PDO::PARAM_INT]
        );
        $count = 0;

        while ($cardImage = $stmt->fetch()) {
            $cardImageManager->deleteImageById(...array_values($cardImage));
            $count++;
        }

        $output->writeln("{$count} token(s) removed.");

        return 0;
    }
}
