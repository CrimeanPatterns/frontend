<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Service\BalanceWatch\Model\BalanceWatchTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BalanceWatchCommand extends Command
{
    public static $defaultName = 'aw:balancewatch-update';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LoggerInterface
     */
    private $paymentLogger;
    /**
     * @var ApiCommunicator
     */
    private $apiCommunicator;
    /**
     * @var Converter
     */
    private $converter;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Timeout
     */
    private $bwTimeout;

    /** @var Process */
    private $asyncTaskExecutor;

    private Stopper $stopper;

    public function __construct(
        LoggerInterface $logger,
        LoggerInterface $paymentLogger,
        EntityManagerInterface $em,
        ApiCommunicator $apiCommunicator,
        Converter $converter,
        Timeout $bwTimeout,
        Process $asyncTaskExecutor,
        Stopper $stopper
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->paymentLogger = $paymentLogger;
        $this->apiCommunicator = $apiCommunicator;
        $this->converter = $converter;
        $this->em = $em;
        $this->bwTimeout = $bwTimeout;
        $this->asyncTaskExecutor = $asyncTaskExecutor;
        $this->stopper = $stopper;
    }

    public function checkAccount(Account $account)
    {
        if (null === $account->getBalanceWatchStartDate()) {
            return;
        }
        $this->paymentLogger->info('BalanceWatch command update - sending', ['accountId' => $account->getAccountid()]);

        $options = new ConverterOptions();
        $options->setParseHistory(false);
        $request = $this->converter->prepareCheckAccountRequest($account, $options, Converter::USER_CHECK_REQUEST_PRIORITY);

        try {
            $this->apiCommunicator->CheckAccount($request);
            $this->paymentLogger->info('BalanceWatch command update - sent', ['accountId' => $account->getAccountid()]);
        } catch (ApiCommunicatorException $e) {
            $this->logger->warning($e->getMessage(), ['accountId' => $account->getAccountid()]);

            return;
        }

        // @TODO: call this method when sending account to update in oher ways, from ServerCheckPlugin,
        // from GroupWSDLCheck, from CheckBalancesCommand, etc. There should be one way to send account to update
        $account->sentToUpdate();
        $this->em->persist($account);
        $this->em->flush();

        if (null !== $account->getBalanceWatchStartDate()
            && (($account->getBalanceWatchStartDate()->getTimestamp() + $this->bwTimeout->getTimeoutSeconds($account)) - time()) < (3 * 60)) {
            $this->paymentLogger->info('BalanceWatch command update - short', ['accountId' => $account->getAccountid()]);
            $this->logger->info('BalanceWatch - STOP', ['accountId' => $account->getAccountid(), 'place' => 'BalanceWatchCommand::checkAccount']);
            $this->stopper->stopBalanceWatch($account, Constants::EVENT_TIMEOUT);
        }
    }

    protected function configure()
    {
        $this
            ->setName('aw:balancewatch-update')
            ->addOption('accountId', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("checking for balancewatch accounts");

        $q = $this->em->createQuery("
        select 
            a
        from 
            AwardWallet\MainBundle\Entity\Account a
        where 
                a.balanceWatchStartDate IS NOT NULL
            and (a.sentToUpdateDate is null or a.sentToUpdateDate < DATE_SUB(CURRENT_TIMESTAMP(), 56, 'minute'))
            " . ($input->getOption('accountId') ? " and a.accountid = " . $input->getOption('accountId') : "")
        );

        /** @var Account $account */
        foreach ($q->execute() as $account) {
            if ($account->isDisabled()) {
                $this->paymentLogger->info('BalanceWatch - STOP, account disabled', ['accountId' => $account->getAccountid()]);
                $this->stopper->stopBalanceWatch($account, Constants::EVENT_UPDATE_ERROR);
            } elseif ($account->getBalanceWatchStartDate()->getTimestamp() >= (time() - $this->bwTimeout->getTimeoutSeconds($account))) {
                if (($ttl = ($account->getBalanceWatchStartDate()->getTimestamp() + $this->bwTimeout->getTimeoutSeconds($account) - time())) < (60 * 60)) { // next check in less than 60 minutes
                    $this->asyncTaskExecutor->execute(new BalanceWatchTask($account->getId()), $ttl);
                }

                $this->checkAccount($account);
            } else {
                $this->paymentLogger->info('BalanceWatch command update - timeout', ['accountId' => $account->getAccountid()]);
                $this->logger->info('BalanceWatch - STOP', ['accountId' => $account->getAccountid(), 'place' => 'BalanceWatchCommand::execute']);
                $this->stopper->stopBalanceWatch($account, Constants::EVENT_TIMEOUT);
            }
        }

        $this->logger->info("done");

        return 0;
    }
}
