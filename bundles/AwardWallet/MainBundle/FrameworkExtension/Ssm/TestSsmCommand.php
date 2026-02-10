<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Ssm;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TestSsmCommand extends Command
{
    protected static $defaultName = 'aw:test:ssm';

    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct();
        $this->params = $params;
    }

    public function configure()
    {
        $this
            ->addArgument('parameter', InputArgument::REQUIRED, 'ssm parameter name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $argParam = $input->getArgument("parameter");
        $output->writeln($this->params->get($argParam));
    }
}
