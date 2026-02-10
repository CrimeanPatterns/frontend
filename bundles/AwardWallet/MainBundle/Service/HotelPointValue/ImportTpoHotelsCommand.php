<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\Common\Doctrine\BatchUpdater;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class ImportTpoHotelsCommand extends Command
{
    public static $defaultName = 'aw:import-tpo-hotels';

    private Connection $connection;
    private OutputInterface $output;

    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    public function configure()
    {
        $this
            ->addOption('csv-file', null, InputOption::VALUE_REQUIRED, 'csv file downloaded from http://yasen.hotellook.com/tp/v1/hotels?language=en')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $updateDate = date("Y-m-d H:i:s");
        $this->readFile($input->getOption('csv-file'), $updateDate);
        $this->deleteOutdated($updateDate);
        $output->writeln("done");
    }

    private function readFile(string $file, string $updateDate): void
    {
        $this->output->writeln("reading file " . $file . " into TpoHotel table");

        $f = fopen($file, "rb");

        if ($f === false) {
            throw new \Exception("could not open csv file");
        }

        try {
            $headers = fgetcsv($f);

            if (!is_array($headers)) {
                throw new \Exception("failed to read headers");
            }

            $fieldNames = $this->connection->fetchFirstColumn("describe TpoHotel");
            $paramNames = array_map(fn (string $field) => ':' . $field, $fieldNames);
            $sql = "insert into TpoHotel(" . implode(", ", $fieldNames) . ") values (" . implode(", ", $paramNames) . ")
        on duplicate key update " . implode(", ", array_map(fn ($field) => "{$field} = values($field)", $fieldNames));

            $this->output->writeln("got " . count($headers) . " headers");
            $batcher = new BatchUpdater($this->connection);
            $batch = [];

            $dataSource = function () use ($f) {
                while ($result = fgetcsv($f)) {
                    yield $result;
                }
            };

            it($dataSource())
                ->onNthMillisAndLast(10000, function (int $millisFromStart, int $iteration, $currentValue, $currentKey, bool $isLast) {
                    $this->output->writeln("processed $iteration rows..");
                })
                ->apply(function (array $values) use ($batcher, $sql, $headers, $fieldNames, $updateDate, &$batch) {
                    if (count($values) !== count($headers)) {
                        throw new \Exception("values count " . count($values) . " does not match headers count " . count($headers));
                    }

                    $fields = array_combine($headers, $values);
                    $fields = array_intersect_key($fields, array_flip($fieldNames));
                    $fields["update_date"] = $updateDate;
                    $batch[] = $fields;
                    $batcher->batchUpdate($batch, $sql, 50);
                });
        } finally {
            fclose($f);
        }

        $batcher->batchUpdate($batch, $sql, 0);
    }

    private function deleteOutdated(string $updateDate): void
    {
        $this->output->writeln("deleting outdated records");
        $total = $this->connection->fetchOne("select count(*) from TpoHotel");
        $toDelete = $this->connection->fetchOne("select count(*) from TpoHotel where update_date < ?", [$updateDate]);
        $this->output->writeln("total: {$total}, to delete: {$toDelete}");

        if ($toDelete > 0 && $total > 0 && ($toDelete / $total) > 0.1) {
            throw new \Exception("too many records to delete: {$toDelete} of {$total}");
        }

        do {
            $deleted = $this->connection->executeStatement("delete from TpoHotel where update_date < ? limit 100", [$updateDate]);
            $this->output->writeln("deleted $deleted rows");
        } while ($deleted > 0);
    }
}
