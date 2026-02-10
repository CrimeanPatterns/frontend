<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

class TestHeadersSentCommand extends Command
{
    public static $defaultName = 'aw:test-headers-sent';

    private Environment $twig;

    public function __construct(
        Environment $twig
    ) {
        parent::__construct();
        $this->twig = $twig;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        echo "headers sent\n";
        $twig = $this->twig;

        return 0;
    }
}
