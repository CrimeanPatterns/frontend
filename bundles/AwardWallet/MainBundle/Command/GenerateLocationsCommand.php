<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\StoreLocationFinder\StoreFilter;
use AwardWallet\MainBundle\Service\StoreLocationFinder\StoreLocationFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateLocationsCommand extends Command
{
    protected static $defaultName = 'aw:locations-generate';

    private StoreLocationFinder $storeLocationFinder;

    public function __construct(
        StoreLocationFinder $storeLocationFinder
    ) {
        parent::__construct();
        $this->storeLocationFinder = $storeLocationFinder;
    }

    public function configure()
    {
        $this
            ->addOption('users', 'u', InputOption::VALUE_REQUIRED, 'user ids (comma separated)')
            ->addOption('radius', 'r', InputOption::VALUE_REQUIRED, 'radius in miles', 10)
            ->addOption('locations-limit', 'l', InputOption::VALUE_REQUIRED, 'locations limit', 20)
            ->addOption('locations-limit-per-group', 'g', InputOption::VALUE_REQUIRED, 'locations limit per group', 3)
            ->addOption('after', 'a', InputOption::VALUE_OPTIONAL, 'after user id')
            ->setDescription('Generate locations based on accounts with barcodes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->storeLocationFinder->findLocationsNearZipArea(
            (new StoreFilter())
                ->setUserIds(!empty($input->getOption('users')) ? array_map('intval', explode(',', $input->getOption('users'))) : [])
                ->setAfterUserId(null !== ($after = $input->getOption('after')) ? (int) $after : null)
                ->setRadius($input->getOption('radius') * 1600)
                ->setLoyaltyLimitPerGroup((int) $input->getOption('locations-limit-per-group'))
                ->setLocationsLimit((int) $input->getOption('locations-limit')),
            true
        );

        return 0;
    }
}
