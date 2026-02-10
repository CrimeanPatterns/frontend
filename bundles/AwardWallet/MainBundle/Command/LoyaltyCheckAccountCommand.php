<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 24.03.16
 * Time: 10:56.
 */

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\PostCheckAccountResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoyaltyCheckAccountCommand extends Command
{
    protected static $defaultName = 'aw:check-account';

    private EntityManagerInterface $entityManager;
    private ApiCommunicator $apiCommunicator;
    private Converter $converter;

    public function __construct(
        EntityManagerInterface $entityManager,
        ApiCommunicator $apiCommunicator,
        Converter $converter
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->apiCommunicator = $apiCommunicator;
        $this->converter = $converter;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check account command')
            ->addOption('accountId', 'a', InputOption::VALUE_REQUIRED, 'check this account')
            ->addOption('parseHistory', 'b', InputOption::VALUE_OPTIONAL, 'parse history flag')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'parse priority');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($input->getOption('accountId'))) {
            $output->writeln("Undefined accountId param\n");

            return 0;
        }
        $parseHistory = empty($input->getOption('parseHistory')) ? null : true;
        $priority = $input->getOption('priority') ?? Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MIN;

        $accountId = $input->getOption('accountId');
        /** @var Account $account */
        $account = $this->entityManager->getRepository(Account::class)->find($accountId);

        if (!is_object($account)) {
            $output->writeln("Can not find account by accountId = {$accountId}\n");

            return 0;
        }

        $options = new ConverterOptions();
        $options->setParseHistory($parseHistory);
        /** @var CheckAccountRequest $request */
        $request = $this->converter->prepareCheckAccountRequest($account, $options, $priority);

        /** @var PostCheckAccountResponse $response */
        $response = $this->apiCommunicator->CheckAccount($request);
        $output->writeln("Account successfully sent to check. RequestId: " . $response->getRequestid());

        return 0;
    }
}
