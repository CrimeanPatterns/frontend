<?php

namespace AwardWallet\MainBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpParamsCommand extends Command
{
    public static $defaultName = 'aw:dump-params';

    private string $cdnHost;

    public function __construct(string $cdnHost)
    {
        parent::__construct();

        $this->cdnHost = $cdnHost;
    }

    public function configure()
    {
        $this
            ->setDescription('This command is a part of production deploy process, it will get some params from parameters.yml and write them to files, to use these files on latter build stages')
            ->addOption('path', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        file_put_contents("cdn_host.txt", $this->cdnHost);
        file_put_contents("cdn_path.txt", $input->getOption('path'));

        return 0;
    }
}
