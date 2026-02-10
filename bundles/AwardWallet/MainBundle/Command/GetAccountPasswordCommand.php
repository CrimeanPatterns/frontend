<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetAccountPasswordCommand extends Command
{
    protected static $defaultName = 'aw:get-account-password';
    private PasswordDecryptor $passwordDecryptor;
    private AccountRepository $accountRepository;

    public function __construct(PasswordDecryptor $passwordDecryptor, AccountRepository $accountRepository)
    {
        parent::__construct();
        $this->passwordDecryptor = $passwordDecryptor;
        $this->accountRepository = $accountRepository;
    }

    public function configure()
    {
        $this->addArgument('accountId', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($input->getArgument('accountId'));
        $output->writeln($this->passwordDecryptor->decrypt($account->getPass()));

        return 0;
    }
}
