<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EmailPerformanceCommand extends Command
{
    public static $defaultName = 'aw:test:email-performance';
    /**
     * @var Mailer
     */
    private $mailer;
    /**
     * @var UsrRepository
     */
    private $usrRepository;

    public function __construct(Mailer $mailer, UsrRepository $usrRepository)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->usrRepository = $usrRepository;
    }

    public function configure()
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'how many emails to send', 1000)
            ->addArgument('smtp-host', InputArgument::REQUIRED, 'smtp server address, host')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'user id', 2110)
            ->addOption('smtp-port', null, InputOption::VALUE_REQUIRED, 'smtp port', 25)
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'email address to send to, you can use %number% placeholder', 'tacahen377@tgres24.com')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("creating message");
        /** @var Usr $user */
        $user = $this->usrRepository->find($input->getOption('user-id'));
        $template = new WelcomeToAw($user, false);

        $options = [
            Mailer::OPTION_TRANSPORT => new \Swift_SmtpTransport($input->getArgument('smtp-host'), $input->getOption('smtp-port')),
        ];

        $output->writeln("mailing {$input->getOption('count')} messages to user {$user->getUserid()} / {$input->getOption('email')} through {$input->getArgument('smtp-host')}:{$input->getOption('smtp-port')}");
        $startTime = microtime(true);

        for ($n = 0; $n < $input->getOption('count'); $n++) {
            $email = str_replace('%number%', $n, $input->getOption('email'));
            $message = $this->mailer->getMessageByTemplate($template);
            $message->setBcc([]);
            $message->setTo($email);
            $this->mailer->send($message, $options);
        }

        $duration = round(microtime(true) - $startTime, 1);
        $speed = round($input->getOption('count') / $duration);
        $output->writeln("done, sent {$input->getOption('count')} messages in {$duration} seconds, {$speed} messages/sec");

        return 0;
    }
}
