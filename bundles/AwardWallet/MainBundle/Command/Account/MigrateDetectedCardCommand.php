<?php

namespace AwardWallet\MainBundle\Command\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providerproperty;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\DetectedCardProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class MigrateDetectedCardCommand extends Command
{
    public static $defaultName = 'aw:migrate-detected-card';

    private DetectedCardProcessor $processor;

    private LoggerInterface $logger;

    private Connection $connection;

    private Connection $unbufferedConnection;

    private EntityManagerInterface $entityManager;

    public function __construct(
        DetectedCardProcessor $processor,
        LoggerInterface $logger,
        Connection $connection,
        $replicaUnbufferedConnection,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->processor = $processor;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->unbufferedConnection = $replicaUnbufferedConnection;
    }

    protected function configure()
    {
        $this->setDescription('Migrate Detected Cards From Properties To Table')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'apply fixes, otherwise dry run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = !empty($input->getOption('apply'));

        $prevAccountID = null;
        $prevVal = null;
        $diffDuplicate = [];
        $diffSaved = [];
        $savedBefore = [];
        $new = [];
        $processedAccounts = 0;

        $chain = stmtAssoc($this->unbufferedConnection->executeQuery("SELECT AccountPropertyID, AccountID, Val FROM AccountProperty WHERE ProviderPropertyID = ? ORDER BY AccountID",
            [Providerproperty::DETECTEDCARD_PROPERTY_ID]))
            ->onNthMillis(10000, function ($time, $ticksCounter, $value, $key) use ($output) {
                $output->writeln("processed $ticksCounter records..");
            });
        $chain
            ->apply(function (array $property) use ($apply, &$processedAccounts, &$prevAccountID, &$prevVal, &$new, &$diffDuplicate, &$savedBefore, &$diffSaved) {
                if ($prevAccountID === $property['AccountID']) {
                    if ($prevVal !== $property['Val']) {
                        $diffDuplicate[] = $property['AccountID'];
                        $prevVal = $property['Val'];
                    }

                    return;
                }
                $processedAccounts++;
                $prevAccountID = $property['AccountID'];
                $prevVal = $property['Val'];

                $account = $this->entityManager->getRepository(Account::class)->find($property['AccountID']);
                $dc = @unserialize($property['Val'], ['allowed_classes' => false]);
                $savedData = $this->connection->executeQuery(/** @lang MySQL */ "SELECT * FROM DetectedCard WHERE AccountID = ?",
                    [$property['AccountID']])->fetchAllAssociative();

                if (count($savedData) === count($dc)) {
                    $savedBefore[] = $property['AccountID'];

                    return;
                }

                if (count($savedData) > 0) {
                    $diffSaved[] = $property['AccountID'];
                }
                $dcObj = [];

                foreach ($dc as $d) {
                    $dcObj[] =
                        (new \AwardWallet\MainBundle\Loyalty\Resources\DetectedCard())
                            ->setCode($d['Code'])
                            ->setDisplayname($d['DisplayName'])
                            ->setCarddescription($d['CardDescription']);
                }
                $response = (new CheckAccountResponse())->setDetectedcards($dcObj);

                if ($apply) {
                    $this->processor->process($account, $response);
                }
                $this->entityManager->clear();
                $new[] = $prevAccountID;
            });

        if (!empty($diffDuplicate)) {
            $this->logger->info(sprintf('different data have %d accounts', count($diffDuplicate)),
                ['diffDuplicates' => var_export($diffDuplicate, true)]);
        }

        if (!empty($diffSaved)) {
            $this->logger->info(sprintf('different saved data have %d accounts', count($diffSaved)),
                ['diffDuplicates' => var_export($diffSaved, true)]);
        }
        $this->logger->info(sprintf('processed %d accounts', $processedAccounts));
        $this->logger->info(sprintf('saved before for  %d accounts', count($savedBefore)));
        $this->logger->info(sprintf('added new for %d accounts', count($new)));

        $output->writeln('done.');

        return 0;
    }
}
