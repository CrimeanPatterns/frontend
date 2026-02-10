<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FlightInfoUpdateCommand extends Command
{
    protected static $defaultName = 'aw:flightinfo:update';

    private FlightInfo $flightInfo;

    public function __construct(
        FlightInfo $flightInfo
    ) {
        parent::__construct();
        $this->flightInfo = $flightInfo;
    }

    public function configure()
    {
        $this->setDescription("Update flight info from FlightStats.com");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updated = $this->flightInfo->bindAll();
        $output->writeln("TripSegments without FlightInfo: " . $updated);

        $scheduled = $this->flightInfo->scheduleAll();
        $output->writeln("Done. Scheduled {$scheduled} flight info records.");

        return 0;
    }
}
