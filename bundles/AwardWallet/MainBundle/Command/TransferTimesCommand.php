<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\TransferTimes;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransferTimesCommand extends Command
{
    /** @var TransferTimes */
    private $transferTimes;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TransferTimes $transferTimes,
        LoggerInterface $logger
    ) {
        $this->transferTimes = $transferTimes;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('aw:update-transfer-times')
            ->setDescription('Calculate average transfer times and insert into db');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("Start updating transfer times");
        $result = $this->transferTimes->updateTransferTimes();
        $this->logger->warning("Transfer times update status: " . $result['status'], ['result' => $result['message']]);

        return 0;
    }
}
