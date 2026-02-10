<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\Common\Monolog\Handler\FluentHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestLoggingCommand extends Command
{
    public static $defaultName = 'aw:test-logging';
    private LoggerInterface $logger;
    private LoggerInterface $paymentLogger;
    private LoggerInterface $statLogger;

    public function __construct(LoggerInterface $logger, LoggerInterface $paymentLogger, LoggerInterface $statLogger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->paymentLogger = $paymentLogger;
        $this->statLogger = $statLogger;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        //        for ($n = 0; $n < 1000000; $n++) // 10 mb
        $this->logger->info("testlogging", ["data" => bin2hex(random_bytes(2))]);
        $this->statLogger->info("statlogger", ["data" => bin2hex(random_bytes(2))]);
        $this->paymentLogger->info("paymentlogger", ["data" => bin2hex(random_bytes(2))]);
        $this->logger->info(
            "test deep",
            [
                FluentHandler::MAX_RECURSION_LEVEL_KEY => 10,
                "level1" => [
                    "level1child" => "child1",
                    "level2" => [
                        "level2child" => "child2",
                        "level3" => [
                            "level3child" => "child3",
                            "level4" => [
                                "level4child" => "child4",
                                "level5" => [
                                    "level5child" => "child5",
                                    "level6" => [
                                        "level6child" => "child6",
                                        "level7" => "hello",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return 0;
    }
}
