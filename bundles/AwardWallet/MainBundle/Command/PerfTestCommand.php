<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerfTestCommand extends Command
{
    protected static $defaultName = 'aw:perf-test';

    private EntityManagerInterface $entityManager;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private DesktopListMapper $desktopListMapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        DesktopListMapper $desktopListMapper
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->desktopListMapper = $desktopListMapper;
    }

    protected function configure()
    {
        $this
            ->setDescription('Performance test on account list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("loading users");

        $q = $this->entityManager->createQuery("select u from AwardWallet\MainBundle\Entity\Usr u where u.accountlevel <> " . ACCOUNT_LEVEL_BUSINESS);
        $q->setMaxResults(1000);
        $users = $q->execute();

        $startTime = microtime(true);
        $rows = 0;

        foreach ($users as $user) {
            /** @var Usr $user */
            $options = $this->optionsFactory->createDefaultOptions()
                ->set(Options::OPTION_USER, $user)
                ->set(Options::OPTION_LOAD_HAS_ACTIVE_TRIPS, true)
                ->set(Options::OPTION_LOAD_PENDING_SCAN_DATA, false)
                ->set(Options::OPTION_STATEFILTER, 'a.State > 0')
                ->set(Options::OPTION_FORMATTER, $this->desktopListMapper);

            $data = $this->accountListManager->getAccountList($options);
            $output->writeln("user " . $user->getId() . ", accounts: " . count($data));
            $rows += count($data);
        }
        $output->writeln("done, duration: " . round(microtime(true) - $startTime, 3) . " seconds, users: " . count($users) . ", accounts: " . $rows);

        return 0;
    }
}
