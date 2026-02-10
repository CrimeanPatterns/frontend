<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\RA\RewardAvailabilityStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RACheckChangesCommand extends Command
{
    public static $defaultName = 'aw:ra-check-changes';

    private LoggerInterface $logger;
    private RewardAvailabilityStatus $status;

    public function __construct(LoggerInterface $logger, RewardAvailabilityStatus $status)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->status = $status;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("start checking for changes in RA rates");

        $hours = 2;
        $this->status->checkChanges($hours);

        $this->logger->info("stop checking for changes in RA rates");

        return 0;
    }
}
