<?php

namespace AwardWallet\MainBundle\Loyalty\Stats;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalcStatsCommand extends Command
{
    public static $defaultName = 'aw:loyalty:calc-stats';
    /**
     * @var Calculator
     */
    private $calculator;

    public function __construct(Calculator $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("calculating");
        $this->calculator->calc();
        $output->writeln("done");
    }
}
