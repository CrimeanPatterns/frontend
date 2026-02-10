<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\GoogleClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailCheckCommand extends Command
{
    protected static $defaultName = 'aw:email:check';

    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    private $messageTypes = [
        [
            'name' => 'Expiration notice',
            'criteria' => [
                'subject:"expiring in"',
            ],
        ],
    ];
    private \AwardWallet\MainBundle\Service\GoogleClient $googleClient;
    private \AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer $mailer;
    private string $notificationsMailboxLogin;

    public function __construct(
        GoogleClient $gmailClient,
        Mailer $mailer,
        string $notificationsMailboxLogin
    ) {
        $this->googleClient = $gmailClient;
        parent::__construct();
        $this->mailer = $mailer;
        $this->notificationsMailboxLogin = $notificationsMailboxLogin;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check delivery of notification emails')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $login = $this->notificationsMailboxLogin;

        /** @var GoogleClient $googleClient */
        $googleClient = $this->googleClient;
        $googleClient->fetchAccessTokenWithRefreshToken();
        $service = new \Google_Service_Gmail($googleClient);

        $missingTypes = null;

        foreach ($this->messageTypes as $mt) {
            $emails = $service->users_messages->listUsersMessages($login, ['q' => implode("|", $mt['criteria']) . " newer_than:36h"]);

            foreach ($mt['criteria'] as $c) {
                if (0 === count($emails->getMessages())) {
                    $missingTypes[$mt['name']][] = $c;
                }
            }
        }

        if ($missingTypes) {
            $errorMessage = "Today there was no delivery of following email notifications type(s):\n";

            foreach ($missingTypes as $name => $criteria) {
                $errorMessage .= "- $name (" . implode(', ', $criteria) . ")\n";
            }
            $this->reportError($errorMessage);
            $this->output->writeln(trim($errorMessage));
        } else {
            $successMessage = "All email notification types were sent today, there is no reason to worry\n";
            $this->output->writeln(trim($successMessage));
        }

        return 0;
    }

    protected function reportError($errorMessage)
    {
        $mailer = $this->mailer;
        $message = $mailer->getMessage();
        $message->setContentType('text/plain');
        $message->setTo($mailer->getEmail('error'))
            ->setSubject('Email notifications delivery broken')
            ->setBody($errorMessage, 'text/plain', 'utf-8');
        $status = $mailer->send($message, [Mailer::OPTION_FIX_BODY => false, Mailer::OPTION_SKIP_STAT => true]);

        if (!$status) {
            throw new \Exception("Could not send email error report");
        }
    }
}
