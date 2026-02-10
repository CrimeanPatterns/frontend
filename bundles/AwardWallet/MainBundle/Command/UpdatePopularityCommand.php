<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\PopularityHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePopularityCommand extends Command
{
    public static $defaultName = 'aw:update-popularity';

    private PopularityHandler $popularityHandler;

    public function __construct(PopularityHandler $popularityHandler)
    {
        parent::__construct();
        $this->popularityHandler = $popularityHandler;
    }

    protected function configure()
    {
        $this->setDescription('Update programs popularity by country');
        $this->setDefinition([
            new InputOption('chunk-size', 'c', InputOption::VALUE_OPTIONAL, 'size of one accounts chunk', 1000),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->popularityHandler->chunkSize = $input->getOption('chunk-size');
        $this->popularityHandler->startPopularityTransaction($output);

        return 0;
    }
}
