<?php

namespace AwardWallet\MainBundle\Command\Blog;

use AwardWallet\MainBundle\Service\Blog\UpgradeReaders;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeBlogReadersAWPlusCommand extends Command
{
    protected static $defaultName = 'aw:upgrade-blog-readers';
    private UpgradeReaders $upgradeReaders;

    public function __construct(
        UpgradeReaders $upgradeReaders
    ) {
        parent::__construct();
        $this->upgradeReaders = $upgradeReaders;
    }

    protected function configure(): void
    {
        $this->setDescription('Complimentary Upgrade to AwardWallet Plus of heavy blog readers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->upgradeReaders->execute();

        return 0;
    }
}
