<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\PasswordReset;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailCommand extends Command
{
    protected static $defaultName = 'aw:send-email';

    private LoggerInterface $logger;
    private Mailer $mailer;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        Mailer $mailer,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send emails to users')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'user list, split by ","')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("searching users");
        $users = $this->entityManager->getRepository(Usr::class)->findBy(["userid" => explode(",", $input->getOption("userId"))]);

        foreach ($users as $user) {
            $template = new PasswordReset($user);
            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send([$message], [Mailer::OPTION_SKIP_DONOTSEND => true]);
        }
        $this->logger->info("done");

        return 0;
    }
}
