<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider\ExpireAwPlusDataProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireAwPlusCommand extends Command
{
    protected static $defaultName = 'aw:email:expire-awplus';

    private LoggerInterface $logger;
    private ExpireAwPlusDataProvider $expireAwPlusDataProvider;
    private MailerCollection $mailerCollection;

    public function __construct(
        LoggerInterface $logger,
        ExpireAwPlusDataProvider $expireAwPlusDataProvider,
        MailerCollection $mailerCollection
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->expireAwPlusDataProvider = $expireAwPlusDataProvider;
        $this->mailerCollection = $mailerCollection;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sending notifications about aw plus expiration')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'filter by userId')
            ->addOption('expiresSoon', 'x', InputOption::VALUE_NONE, "send notifications about 'expires soon'")
            ->addOption('date', 'd', InputOption::VALUE_NONE, 'filter by date')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->logger;
        $dataProvider = $this->expireAwPlusDataProvider;

        if ($usersIds = $input->getOption("userId")) {
            $usersIds = array_map("intval", $usersIds);
            $logger->info(sprintf("filter by userId: [%s]", implode(", ", $usersIds)));
            $dataProvider->setFilterUsersIds($usersIds);
        }

        $dataProvider->setNotifyExpiresSoon(false);

        if ($input->getOption("expiresSoon")) {
            $logger->info("send notifications about 'expires soon'");
            $dataProvider->setNotifyExpiresSoon(true);
        }
        $dataProvider->setFilterByDate($input->getOption("date"));

        // Mailer
        $collectionMailer = $this->mailerCollection;
        $collectionMailer->setDataProvider($dataProvider);
        $collectionMailer->send();
        $output->writeln(sprintf("sent %d emails", $collectionMailer->getTotalSends()));
        $output->writeln("done.");

        return 0;
    }
}
