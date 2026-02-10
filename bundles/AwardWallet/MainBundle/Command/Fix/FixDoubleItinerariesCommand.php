<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\Strings\Strings;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixDoubleItinerariesCommand extends Command
{
    protected static $defaultName = 'aw:fix-double-itineraries';

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var bool
     */
    private $confident;
    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'days to scan back', 20)
            ->addOption('fix', null, InputOption::VALUE_NONE, 'fix doubles')
            ->addOption('confident', null, InputOption::VALUE_NONE, 'fix even when not sure what variant to select. will select almost random variant')
            ->addOption('types', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'itinerary types', ['Trip', 'Reservation', 'Rental', 'Restaurant'])
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'limit to this user')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->input = $input;
        $this->confident = $input->getOption('confident');

        if (in_array('Trip', $input->getOption('types'))) {
            $this->scanReservations("Trip", "coalesce(r.RecordLocator, r.ConfirmationNumbers, r.TravelAgencyConfirmationNumbers, r.IssuingAirlineConfirmationNumber)", $input->getOption('days'), $input->getOption('fix'), "join TripSegment ts on r.TripID = ts.TripID", "and ts.DepDate > now()", "r.Category");
        }

        if (in_array('Reservation', $input->getOption('types'))) {
            $this->scanReservations("Reservation", "coalesce(r.ConfirmationNumber, r.ConfirmationNumbers, r.TravelAgencyConfirmationNumbers)", $input->getOption('days'),
                $input->getOption('fix'), null, "", "coalesce(r.ConfirmationNumber, r.ConfirmationNumbers, r.TravelAgencyConfirmationNumbers), date(r.CheckInDate), date(r.CheckOutDate), r.HotelName");
        }

        if (in_array('Rental', $input->getOption('types'))) {
            $this->scanReservations(
                "Rental",
                "coalesce(r.Number, r.ConfirmationNumbers, r.TravelAgencyConfirmationNumbers)",
                $input->getOption('days'),
                $input->getOption('fix'),
                null,
                "and r.DropoffDateTime > adddate(now(), -" . $input->getOption('days') . ")",
                "date(r.PickupDatetime), date(r.DropoffDatetime), r.RentalCompanyName"
            );
        }

        if (in_array('Restaurant', $input->getOption('types'))) {
            $this->scanReservations("Restaurant", "coalesce(r.ConfNo, r.ConfirmationNumbers, r.TravelAgencyConfirmationNumbers)", $input->getOption('days'),
                $input->getOption('fix'), null, "and r.StartDate > now()");
        }

        return 0;
    }

    private function scanReservations(string $tableName, string $confNoField, int $days, bool $fix, ?string $joins, string $where, ?string $extraGroupByFields = null)
    {
        if ($userId = $this->input->getOption('userId')) {
            $where .= " and r.UserID = $userId";
        }
        $this->processTable(
            /** @lang SQL */
            "
            select 
                r.AccountID,
                r.UserID, 
                r.UserAgentID, 
                count(distinct {$confNoField}) as ConfNoCount, 
                " . ($extraGroupByFields ? $extraGroupByFields . ", " : "") . " 
                min(date(r.CreateDate)) as MinCreateDate,
                max(date(r.CreateDate)) as MaxCreateDate,
                count(distinct r.{$tableName}ID) as DoubleCount,
                group_concat(distinct r.{$tableName}ID separator ', ') as IDs,
                count(distinct(r.TravelerNames)) as TravelerNamesCount
            from
                {$tableName} r
                {$joins}
            where
                r.LastParseDate > adddate(now(), -{$days})
                and r.Copied = 0
                {$where}
            group by
                r.AccountID,
                r.UserID, 
                r.UserAgentID
                " . ($extraGroupByFields ? ", " . $extraGroupByFields : "") . "
            having  
                count(distinct r.{$tableName}ID) > 1
                and count(distinct r.TravelerNames) <= 1
            limit 5000
            ",
            $tableName,
            $fix
        );
    }

    private function processTable(string $sql, string $tableName, bool $fix)
    {
        $this->output->writeln($sql);
        $rows = $this->connection->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $this->output->writeln("no data found for $tableName");

            return;
        }

        usort($rows, function (array $a, array $b) {
            return $b['MaxCreateDate'] <=> $a['MaxCreateDate'];
        });

        $this->output->writeln("processing " . count($rows) . " duplicates from {$tableName}");
        $table = new Table($this->output);
        $table->addRows($rows);
        $table->setHeaders(array_keys($rows[0]));
        $table->render();

        foreach ($rows as $row) {
            $this->processRow($row, $tableName, $fix);
        }
    }

    private function processRow(array $row, string $tableName, bool $fix)
    {
        $ids = explode(", ", $row['IDs']);

        $diffs = $this->loadDifferences($tableName, $ids);

        if (!empty($diffs)) {
            $table = new Table($this->output);
            $table->addRows($diffs);
            $table->setHeaders(array_keys($diffs[0]));
            $table->render();

            $winner = $this->calcWinner($diffs);
        } else {
            $this->output->writeln("{$row['IDs']} looks same, will select first one");
            $winner = $ids[0];
        }

        if ($winner !== null) {
            $this->output->writeln("winner is: " . $winner);

            if ($fix) {
                foreach ($ids as $id) {
                    if ($id == $winner) {
                        continue;
                    }
                    $this->output->writeln("removing dublicate {$id}");
                    $this->connection->executeUpdate("delete from $tableName where {$tableName}ID = $id");
                }
            }
        } else {
            $this->output->writeln("no winner, need review");
        }
    }

    private function loadDifferences(string $tableName, array $ids): array
    {
        $result = [];

        $sourceRows = [];

        foreach ($ids as $id) {
            $sourceRows[] = $this->loadSourceRow($id, $tableName);
        }

        $fields = array_keys($sourceRows[0]);

        foreach ($fields as $field) {
            $values = array_map(function (array $row) use ($field) { return $row[$field]; }, $sourceRows);

            if (count(array_unique($values)) > 1) {
                $diffRow = ["Field" => $field];

                foreach ($sourceRows as $rowIndex => $row) {
                    $diffRow["V." . $ids[$rowIndex]] = Strings::cutInMiddle($row[$field], 20);
                }
                $result[] = $diffRow;
            }
        }

        return $result;
    }

    private function loadSourceRow(int $id, string $tableName): array
    {
        $row = $this->connection->executeQuery("select * from {$tableName} where {$tableName}ID = ?", [$id])->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new \Exception("Failed to load row {$id}");
        }
        $row = array_diff_key($row, [
            $tableName . "ID" => false,
            "CreateDate" => false,
            "LastParseDate" => false,
            "UpdateDate" => false,
            "Hash" => false,
            "ShareCode" => false,
            "ExtPropertyMerged" => false,
        ]);

        return $row;
    }

    private function calcWinner(array $diffs): ?int
    {
        $scores = [];

        foreach ($diffs as $diff) {
            $field = array_shift($diff);
            $isDate = preg_match('#Date$#ims', $field) && $field !== "MailDate";

            if ($field === "Copied" || $field === "Hidden") {
                $winner = $this->getMaxScoreIndex(array_map(function (int $value) { return $value ^ 1; }, $diff));

                if ($winner !== null) {
                    return substr($winner, 2);
                }
            }

            $weights = array_map(function ($value) use ($isDate, $field) {
                if ($isDate) {
                    if (empty($value)) {
                        return 0;
                    }

                    return strtotime($value);
                }

                if ($field === "Copied" || $field === "Hidden") {
                    return $value ^ 1;
                }

                return empty($value) ? 0 : 1;
            }, $diff);

            $winner = $this->getMaxScoreIndex($weights);

            if ($winner !== null) {
                if (!isset($scores[$winner])) {
                    $scores[$winner] = 1;
                } else {
                    $scores[$winner]++;
                }
            }
        }

        $maxScoreIndex = $this->getMaxScoreIndex($scores);

        if ($maxScoreIndex === null) {
            return null;
        }

        return substr($maxScoreIndex, 2);
    }

    private function getMaxScoreIndex(array $scores): ?string
    {
        if (count($scores) === 0) {
            return null;
        }

        if (count($scores) === 1) {
            return array_keys($scores)[0];
        }

        asort($scores);
        $keys = array_keys($scores);
        $maxScore = array_pop($scores);
        $prevScore = array_pop($scores);

        if ($maxScore == $prevScore && !$this->confident) {
            return null;
        }

        return array_pop($keys);
    }
}
