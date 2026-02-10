<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\Counter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSiteStatisticsCommand extends Command
{
    protected static $defaultName = 'aw:update-site-statistics';
    private ParameterRepository $parameterRepository;
    private Counter $userCounter;

    public function __construct(
        ParameterRepository $parameterRepository,
        Counter $userCounter
    ) {
        parent::__construct();

        $this->parameterRepository = $parameterRepository;
        $this->userCounter = $userCounter;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Updating stats...');
        $output->writeln('Total: ' . $this->parameterRepository->getMilesCount(true));
        $output->writeln('Users Count: ' . $this->userCounter->getUsersCount(true));
        $output->writeln("done");
    }
}
