<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Manager\RewardsTransferManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRewardsTransferTableCommand extends Command
{
    protected static $defaultName = 'aw:providers:update-rewards-transfer-table';

    private RewardsTransferManager $rewardsTransferManager;

    public function __construct(
        RewardsTransferManager $rewardsTransferManager
    ) {
        parent::__construct();
        $this->rewardsTransferManager = $rewardsTransferManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update Rewards Transfer table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(date('c') . ' Rewards Transfer info update');
        $result = $this->rewardsTransferManager->updateRewardsTransferRatesForAllProviders();

        if (!$result) {
            $output->writeln('FAILED');
        } else {
            $i = 1;
            $count = count($result);
            $output->writeln("$count provider(s) checked");

            foreach ($result as $key => $partialResult) {
                $output->write($count > 1 ? "[$i/$count] " : "");

                if (!$partialResult) {
                    $output->writeln("Rewards Transfer update for $key failed");
                } else {
                    $output->writeln("Rewards Transfer update result for $key:");

                    foreach (['Added', 'Updated', 'Removed'] as $key2) {
                        if (isset($partialResult[$key2]) and $partialResult[$key2]) {
                            $output->writeln('- ' . $key2 . ' ' . count($partialResult[$key2]) . ' rewards transfer(s)');
                        } else {
                            $output->writeln('- No ' . strtolower($key2) . ' rewards transfers');
                        }
                    }
                }
                $i++;
            }
        }

        return 0;
    }
}
