<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixInvalidEmailsCommand extends Command
{
    protected static $defaultName = 'aw:fix:invalid-emails';
    protected static $defaultDescription = 'Validates email addresses in the Usr table and marks invalid ones as NDR';

    private Connection $unbufferedConnection;
    private LoggerInterface $logger;
    private Connection $connection;

    public function __construct(
        Connection $replicaUnbufferedConnection,
        Connection $connection,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->unbufferedConnection = $replicaUnbufferedConnection;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->setHelp('This command checks all records in the Usr table, validates each Email field using filter_var, and if it is not a valid email - sets EmailVerified field to Usr::EMAIL_NDR');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Starting email validation process for Usr table');

        $processedCount = 0;
        $invalidCount = 0;
        $updatedCount = 0;
        $startTime = microtime(true);

        // Fetch records using unbuffered query to avoid loading everything into memory
        // Exclude records where EmailVerified is already EMAIL_NDR
        $sql = 'SELECT UserID, Email, EmailVerified FROM Usr WHERE EmailVerified != ?';
        $stmt = $this->unbufferedConnection->executeQuery($sql, [Usr::EMAIL_NDR]);

        while ($row = $stmt->fetchAssociative()) {
            $processedCount++;

            $userId = $row['UserID'];
            $email = $row['Email'];

            $isValidEmail = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

            if (!$isValidEmail) {
                $invalidCount++;

                $this->connection->executeStatement(
                    'UPDATE Usr SET EmailVerified = ? WHERE UserID = ?',
                    [Usr::EMAIL_NDR, $userId]
                );

                $updatedCount++;
                $this->logger->info(sprintf('Marked invalid email: %s (UserID: %d)', $email, $userId));
            }

            if ($processedCount % 5000 === 0) {
                $this->logger->info(sprintf('Processed %d records. Invalid: %d, Updated: %d',
                    $processedCount, $invalidCount, $updatedCount));
            }
        }

        $executionTime = microtime(true) - $startTime;

        $this->logger->info(sprintf(
            'Email validation completed. Processed: %d, Invalid: %d, Updated: %d, Execution time: %.2f seconds',
            $processedCount, $invalidCount, $updatedCount, $executionTime
        ));

        return 0;
    }
}
