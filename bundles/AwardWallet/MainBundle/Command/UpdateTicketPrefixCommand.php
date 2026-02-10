<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTicketPrefixCommand extends Command
{
    protected static $defaultName = 'aw:update:ticket-prefix';

    private $airlines;
    private $prefixes;
    private $report;
    /** @var Connection */
    private $db;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();
        $this->db = $connection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update airline ticket prefixes');
    }

    /*
     * main list http://www.kovrik.com/sib/travel/airline-codes.txt
     * alternative list https://zbordirect.com/en/tools/iata-airlines-codes
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ignore = [
            '934', '399', '573',
        ];
        $overwrite = [
            '838' => ['WS', 'WJA'],
        ];

        foreach ($ignore as $prefix) {
            $this->db->delete('AirlineTicketPrefix', ['Prefix' => $prefix], [\PDO::PARAM_STR]);
        }
        $this->airlines = $this->db->executeQuery('select Code, Icao, AirlineID, Name from Airline where Active = 1')->fetchAllAssociative();
        $this->prefixes = $this->db->executeQuery('select p.AirlineTicketPrefixID as ID, p.Prefix, a.Code, a.AirlineID from AirlineTicketPrefix p left join Airline a on p.AirlineID = a.AirlineID')->fetchAllAssociative();
        $this->report = ['updated' => 0, 'added' => 0, 'deleted' => 0, 'skipped' => 0, 'ignored' => 0];
        $file = (new \CurlDriver())->request(new \HttpDriverRequest('http://www.kovrik.com/sib/travel/airline-codes.txt'))->body;
        $lines = explode("\n", $file);

        foreach ($lines as $line) {
            if (preg_match('/^(\s{3}|\w{3})\s{6}(\w{2})\s(.+)\b(\d{3})$/', $line, $m) > 0) {
                if (!in_array($m[4], $ignore) && !array_key_exists($m[4], $overwrite)) {
                    $m[1] = trim($m[1]);
                    $this->update($m[2], $m[1], $m[4]);
                } else {
                    $this->report['ignored']++;
                }
            }
        }

        foreach ($overwrite as $prefix => $codes) {
            $this->update($codes[0], $codes[1], $prefix);
        }
        $output->writeln(json_encode($this->report));

        return 0;
    }

    private function update($iata, $icao, $prefix)
    {
        $airline = $this->match($iata, $icao);

        if (null === $airline) {
            $this->report['skipped']++;

            return;
        }

        foreach ($this->prefixes as $idx => $prefixRow) {
            if (isset($prefixRow['Del'])) {
                continue;
            }

            if ($prefixRow['AirlineID'] === $airline['AirlineID'] && $prefixRow['Prefix'] === $prefix) {
                $this->report['updated']++;

                return;
            }

            if ($prefixRow['AirlineID'] == $airline['AirlineID'] xor $prefixRow['Prefix'] == $prefix) {
                $this->db->delete('AirlineTicketPrefix', ['AirlineTicketPrefixID' => $prefixRow['ID']]);
                $this->report['deleted']++;
                $this->prefixes[$idx]['Del'] = true;
            }
        }

        try {
            $this->db->insert('AirlineTicketPrefix', ['Prefix' => $prefix, 'AirlineID' => $airline['AirlineID']]);
            $this->report['added']++;
        } catch (Exception $e) {
            $this->report['skipped']++;
        }
    }

    private function match($iata, $icao)
    {
        foreach ($this->airlines as $row) {
            if ($row['Code'] === $iata && (empty($icao) || $row['Icao'] === $icao)) {
                return $row;
            }
        }

        return null;
    }
}
