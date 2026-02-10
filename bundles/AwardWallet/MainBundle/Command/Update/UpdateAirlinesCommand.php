<?php

namespace AwardWallet\MainBundle\Command\Update;

use AwardWallet\MainBundle\Service\FlightStats\AirlinesUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAirlinesCommand extends Command
{
    public static $defaultName = 'aw:update-airlines';
    private AirlinesUpdater $airlinesUpdater;

    public function __construct(AirlinesUpdater $airlinesUpdater)
    {
        parent::__construct();

        $this->airlinesUpdater = $airlinesUpdater;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sync airlines with FlightStats')
            ->setHelp("Updates table `Airline` with data received from FlightStatsAPI (https://api.flightstats.com/flex/airlines/rest/v1/json/all)")
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run command without actually updating records');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->airlinesUpdater->sync($input->getOption('dry-run'));

        return 0;
    }
}
