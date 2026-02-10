<?php

namespace AwardWallet\MainBundle\Command\CreditCards;

use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateDetectedCardsCommand extends Command
{
    public static $defaultName = 'aw:validate:account-property-detected-cards';

    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        LoggerInterface $logger,
        Connection $connection
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function configure(): void
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $isFix = $input->getOption('fix');
        $ppValues = $this->getPropertyValues();

        $output->writeln('found ' . count($ppValues) . ' records');
        $cards = false;

        foreach ($ppValues as $row) {
            try {
                $cards = unserialize($row['Val'], ['allowed_classes' => false]);
            } catch (\Exception $exception) {
                $output->writeln($exception->getMessage());
                $output->writeln(var_export($row, true));
                $output->writeln('----');
            }

            if (!is_array($cards)) {
                $this->logger->warning(
                    'Validate AccountPropertyDetectedCards unserialize error' . ($isFix ? ' (DELETED)' : ''),
                    $row
                );

                if ($isFix) {
                    $this->connection->executeQuery('
                        DELETE FROM AccountProperty WHERE AccountPropertyID = :id
                    ', ['id' => $row['AccountPropertyID']], ['id' => \PDO::PARAM_INT]);
                }
            }
        }

        $output->writeln('done.');

        return 0;
    }

    private function getPropertyValues(): array
    {
        return $this->connection->fetchAllAssociative('
            SELECT AccountPropertyID, AccountID, Val
            FROM AccountProperty ap
            WHERE ap.ProviderPropertyID = :ppDetectedCardId',
            [
                'ppDetectedCardId' => Providerproperty::DETECTEDCARD_PROPERTY_ID,
            ],
            [
                'ppDetectedCardId' => \PDO::PARAM_INT,
            ]);
    }
}
