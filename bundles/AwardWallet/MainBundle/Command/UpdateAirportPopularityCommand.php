<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Query\AirportQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAirportPopularityCommand extends Command
{
    public static $defaultName = 'aw:update-airport-popularity';

    private AirportQuery $query;

    public function __construct(AirportQuery $query)
    {
        parent::__construct();

        $this->query = $query;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('update airport popularity from TripSegment')
            ->addOption('airCode', null, InputOption::VALUE_REQUIRED, 'update only this airport');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->query->updateAircodePopularity($input->getOption('airCode'));

        $output->writeln("Airport popularity updated successfuly. $result row(s) affected");

        return 0;
    }
}
