<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class MarkBadAltHotelsCommand extends Command
{
    public static $defaultName = 'aw:mark-bad-alt-hotels';

    private Connection $unbufferedConnection;
    private OutputInterface $output;
    private InputInterface $input;

    private Connection $connection;

    public function __construct(Connection $replicaUnbufferedConnection, Connection $connection)
    {
        parent::__construct();

        $this->unbufferedConnection = $replicaUnbufferedConnection;
        $this->connection = $connection;
    }

    public function configure()
    {
        parent::configure();

        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED)
            ->addOption('print', null, InputOption::VALUE_NONE)
            ->addOption('mark-as-errors', null, InputOption::VALUE_NONE, 'mark found records as errors')
            ->addOption('where', null, InputOption::VALUE_REQUIRED, 'sql where')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $output->writeln("searching for badly selected alt hotels, by coordinates");
        $sql = "
        select
          hpv.HotelPointValueID,  
          r.CheckInDate,
          r.HotelName as ResHotelName, 
          hpv.AlternativeHotelName,
          gt.GeoTagID, 
          gt.Address as GtAddress,
          gt.CountryCode,
          gt.Lat as GtLat, 
          gt.Lng as GtLng, 
          r.Address as ResAddress, 
          hpv.Address as HpvAddress, 
          hpv.LatLng as HpvLatLng, 
          hpv.AlternativeLatLng, 
          cast(substr(hpv.AlternativeLatLng, 1, instr(hpv.AlternativeLatLng, ',') - 1) as decimal(20,5)) as AltLat,
          cast(substr(hpv.AlternativeLatLng, instr(hpv.AlternativeLatLng, ',') + 1) as decimal(20,5)) as AltLng
        from 
          HotelPointValue hpv 
          join Reservation r on r.ReservationID = hpv.ReservationID 
          join GeoTag gt on r.GeoTagID = gt.GeoTagID 
        where
          (
            abs(cast(substr(hpv.AlternativeLatLng, 1, instr(hpv.AlternativeLatLng, ',') - 1) as decimal(20,5)) - gt.Lat) > 0.1
            or
            abs(cast(substr(hpv.AlternativeLatLng, instr(hpv.AlternativeLatLng, ',') + 1) as decimal(20,5)) - gt.Lng) > 0.1
          )
          and hpv.Status not in (" . it(CalcMileValueCommand::EXCLUDED_STATUSES)->map(fn (string $status) => "'" . $status . "'")->joinToString(", ") . ") 
        ";

        if ($where = $input->getOption('where')) {
            $sql .= " and $where";
        }

        if ($limit = $input->getOption('limit')) {
            $sql .= " limit $limit";
        }

        $q = $this->unbufferedConnection->executeQuery($sql);

        $this->processRows($q);
    }

    private function processRows(Result $q)
    {
        $chain = stmtAssoc($q)
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) {
                $this->output->writeln("processed $ticksCounter records..");
            });

        if ($this->input->getOption('mark-as-errors')) {
            $chain = $chain->onEach(function (array $row) {
                $this->connection->executeStatement(
                    "update HotelPointValue 
                        set Status = :status, Note = 'AltHotel coordinates too far' 
                        where HotelPointValueID = :id",
                    ["status" => CalcMileValueCommand::STATUS_ERROR, "id" => $row['HotelPointValueID']]
                );
            });
        }

        if ($this->input->getOption('print')) {
            $chain = $chain->onEach(function (array $row) {
                $this->output->writeln("{$row['HotelPointValueID']}, gt {$row['GeoTagID']}: {$row['ResAddress']} ({$row['GtLat']},{$row['GtLng']}) -> {$row['HpvAddress']} ({$row['AlternativeLatLng']})");
            });
        }

        $total = $chain->count();

        $this->output->writeln("done, processed $total rows");
    }
}
