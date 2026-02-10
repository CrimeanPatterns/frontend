<?php

namespace AwardWallet\MainBundle\Command\Test;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackExecutor;
use AwardWallet\MainBundle\Worker\AsyncProcess\EmailCallbackTask;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestEmailSavingCommand extends Command
{
    protected static $defaultName = 'aw:test:email-saving';

    private EmailCallbackExecutor $executor;

    public function __construct(EmailCallbackExecutor $executor)
    {
        parent::__construct();
        $this->executor = $executor;
    }

    public function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'file with json data from email, get it from email queue')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $json = file_get_contents($input->getArgument('file'));
        $response = @json_decode($json);
        $response->email = base64_encode("From: from@some.email\nTo: to@some.email\nSubject: subject\n\nemail body");
        $task = new EmailCallbackTask(json_encode($response));
        $this->executor->execute($task);

        return 0;
    }
}
