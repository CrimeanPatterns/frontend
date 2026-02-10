<?php

namespace AwardWallet\MainBundle\Command\Fix;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixPlusExpirationCommand extends Command
{
    protected static $defaultName = 'aw:fix-plus-expiration';

    private LoggerInterface $logger;
    private ExpirationCalculator $expirationCalculator;
    private PlusManager $plusManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        ExpirationCalculator $expirationCalculator,
        PlusManager $plusManager,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->expirationCalculator = $expirationCalculator;
        $this->plusManager = $plusManager;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check and fix PlusExpirationDate column in Usr table')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'filter by UserID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $processed = 0;
        $corrected = 0;
        $sql = "select u from AwardWallet\MainBundle\Entity\Usr u where u.accountlevel = " . ACCOUNT_LEVEL_AWPLUS;

        if (!empty($input->getOption("userId"))) {
            $sql .= " and u.userid in (" . implode(", ", array_map("intval", explode(",", $input->getOption("userId")))) . ")";
        }
        $output->writeln($sql);
        $users = $this->entityManager->createQuery($sql);
        $startTime = microtime(true);

        foreach ($users->iterate() as $user) {
            /** @var Usr $user */
            $user = $user[0];
            $expiration = $this->expirationCalculator->getAccountExpiration($user->getId());

            if ($this->plusManager->correctExpirationDate($user, $expiration['date'], "expiration recalculated")) {
                $corrected++;
            }

            $processed++;

            if (($processed % 100) == 0) {
                $this->entityManager->clear();
                $now = microtime(true);
                $speed = round(100 / ($now - $startTime), 1);
                $this->logger->info("processed {$processed} users, mem: " . round(memory_get_usage(true) / 1024 / 1024, 1) . " Mb, speed: $speed u/s..");
                $startTime = $now;
            }
        }

        $output->writeln("done, processed $processed users, corrected: $corrected");

        return 0;
    }
}
