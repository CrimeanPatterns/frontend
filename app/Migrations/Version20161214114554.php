<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20161214114554 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('fs_start', 1, 'flight_stats.update', '', '-2 day', '', 0, 1, 0, 2, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('sita_start', 1, 'sita_aero.update', '', '-2 day', 'Gate\nArrivalGate\nDepartureTerminal\nArrivalTerminal', 0, 1, 0, 0, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('sita_restart', 1, 'sita_aero.update', 'If first return error', '-1 day', 'Gate\nArrivalGate\nDepartureTerminal\nArrivalTerminal', 0, 1, 0, 0, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('fs_domestic', 3, 'flight_stats.update', '', 'DepDate -2 hour', '', 0, 1, 0, 2, 1)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('fs_int', 3, 'flight_stats.update', '', 'DepDate -4 hour', '', 0, 1, 0, 2, 2)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('sita_dep_subs', 2, 'sita_aero.departure_subscribe', '', 'DepDate -12 hour', 'Gate\nArrivalGate\nDepartureTerminal\nArrivalTerminal', 0, 1, 0, 0, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('sita_arr_subs', 2, 'sita_aero.arrival_subscribe', '', 'DepDate\nArrDate -2 hour', 'Gate\nArrivalGate\nDepartureTerminal\nArrivalTerminal', 0, 1, 0, 0, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('fs_debug', 3, 'flight_stats.update', '', '', '', 1, 0, 1, 0, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('sita_debug', 3, 'sita_aero.update', '', '', 'Gate\nArrivalGate\nDepartureTerminal\nArrivalTerminal', 1, 0, 1, 0, 0)");
        $this->addSql("INSERT INTO FlightInfoConfig (Name, Type, Service, Comment, ScheduleRules, IgnoreFields, Enable, Schedule, Debug, AWPlusFlag, RegionFlag) VALUES ('fs_update', 3, 'flight_stats.update', '', 'DepDate -12 hour\nFlightDate -12 hour', '', 0, 0, 0, 2, 0)");
    }

    public function down(Schema $schema): void
    {
    }
}
