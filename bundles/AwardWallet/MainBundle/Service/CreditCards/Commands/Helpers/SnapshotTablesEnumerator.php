<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers;

use Doctrine\DBAL\Connection;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SnapshotTablesEnumerator
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return iterable<SnapshotTable>
     */
    public function enumerate(string $pattern): iterable
    {
        return
            it(
                $this->connection->executeQuery("show tables like ?", [$pattern])
                ->fetchFirstColumn()
            )
            ->map(fn (string $table) => new SnapshotTable(
                $table,
                self::extractDate($table),
                self::extractDays($table)
            ));
    }

    public static function extractDate(string $input): \DateTimeImmutable
    {
        if (\preg_match('#(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})#', $input, $matches)) {
            [$_, $year, $month, $day, $hour, $minute, $seconds] = $matches;

            return new \DateTimeImmutable("{$year}-{$month}-{$day} {$hour}:{$minute}:{$seconds}");
        }

        throw new \RuntimeException("Invalid date format for: {$input}");
    }

    private static function extractDays(string $input): ?int
    {
        if (\preg_match('/_(\d+)d$/', $input, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
