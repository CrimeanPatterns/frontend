<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\BackgroundCheckUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateBackgroundCheckAccountsCommand extends Command
{
    public const LIMIT = 1000;

    public static $defaultName = 'aw:update-bgcheck-accounts';

    private LoggerInterface $logger;
    private BackgroundCheckUpdater $backgroundCheckUpdater;
    private ProviderRepository $providerRepo;

    public function __construct(LoggerInterface $logger, BackgroundCheckUpdater $backgroundCheckUpdater, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->backgroundCheckUpdater = $backgroundCheckUpdater;
        $this->providerRepo = $entityManager->getRepository(Provider::class);
    }

    protected function configure()
    {
        $this
            ->setDescription('Update Background Check Flag in Accounts')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'limit to this provider code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("updating BackgroundCheck in Account table");

        $changedAccounts = 0;

        if (!empty($input->getOption('provider'))) {
            $providers = $this->providerRepo->findBy(['code' => $input->getOption('provider')]);
        } else {
            $providers = $this->providerRepo->findAll();
        }

        $time = time();

        foreach ($providers as $provider) {
            /** @var $provider Provider */
            if (empty($time) || (time() - $time) > 30) {
                $this->logger->info("updating " . $provider->getCode());
                $time = time();
            }
            $changedAccounts += $this->backgroundCheckUpdater->updateProvider($provider->getId());
        }

        $this->logger->info("done, $changedAccounts accounts updated");

        return 0;
    }
}
