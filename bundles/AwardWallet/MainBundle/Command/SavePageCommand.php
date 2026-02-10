<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class SavePageCommand extends Command
{
    protected static $defaultName = 'aw:save-page';

    private KernelInterface $kernel;
    private $requiresChannelParameter;

    public function __construct(
        KernelInterface $kernel,
        $requiresChannelParameter
    ) {
        parent::__construct();
        $this->kernel = $kernel;
        $this->requiresChannelParameter = $requiresChannelParameter;
    }

    protected function configure()
    {
        $this
            ->setDescription('download page')
            ->setDefinition([
                new InputArgument('url', InputArgument::REQUIRED),
                new InputOption('expected-http-code', null, InputOption::VALUE_REQUIRED, '', 200),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->kernel->handle(Request::create($input->getArgument('url'), 'GET', [], [], [], ['HTTP_X_FORWARDED_PROTO' => $this->requiresChannelParameter]), HttpKernelInterface::SUB_REQUEST);
        $output->writeln($response->getContent());

        if ($response->getStatusCode() != $input->getOption('expected-http-code')) {
            return 100;
        }

        return 0;
    }
}
