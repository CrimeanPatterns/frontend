<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EmailTestCommand extends Command
{
    public static $defaultName = 'aw:email-test';
    private Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        parent::__construct();

        $this->mailer = $mailer;
    }

    public function configure()
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("sending test email");

        $message = $this->mailer->getMessage();
        $message
            ->setTo($input->getOption('email'))
            ->setSubject('Test subject')
            ->setBody('Test body', 'text/plain')
        ;

        $this->mailer->addKindHeader('test', $message);
        $options = [
            Mailer::OPTION_TRANSPORT => new \Swift_SmtpTransport('host.docker.internal', 8025),
            Mailer::OPTION_TRANSACTIONAL => true,
        ];
        $this->mailer->send(clone $message, $options);
        $output->writeln("sent");

        return 0;
    }
}
