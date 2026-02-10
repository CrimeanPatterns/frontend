<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Support\VoteProgram;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProviderTableSyncSuccessCommand extends Command
{
    private const ALLOW_TYPE = ['added', 'fixed'];
    protected static $defaultName = 'aw:provider-table-sync-success';

    /** @var LoggerInterface */
    private $logger;

    /** @var Connection */
    private $connection;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Mailer */
    private $mailer;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Mailer $mailer
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;

        $this->connection = $entityManager->getConnection();
    }

    public function sendVoted(int $providerId, string $type): array
    {
        if (!in_array($type, self::ALLOW_TYPE)) {
            return ['Non-dispatched type'];
        }

        $users = $this->getUsers($providerId);
        $iCount = count($users);

        if (0 === $iCount) {
            return ['No subscribers for this provider.'];
        }

        $result = [];
        $userIdErrors = [];
        $userRepository = $this->entityManager->getRepository(Usr::class);
        $providerRepository = $this->entityManager->getRepository(Provider::class);
        $provider = $providerRepository->find($providerId);

        foreach ($users as $usr) {
            /** @var Usr $user */
            $user = $userRepository->find($usr['UserID']);

            $template = new VoteProgram($user, false);
            $template->provider = $provider;

            switch ($type) {
                case 'added':
                    $template->type = VoteProgram::TYPE_ADDED;

                    break;

                case 'fixed':
                    $template->type = VoteProgram::TYPE_FIXED;

                    break;

                default:
                    throw new \Exception('Error TYPE: ' . $type);
            }

            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send(
                $message,
                [
                    Mailer::OPTION_ON_FAILED_SEND => function () use (&$userIdErrors, $usr) {
                        $userIdErrors[] = $usr['UserID'];
                    },
                    Mailer::OPTION_ON_SUCCESSFUL_SEND => function () use (&$text, $usr, $message, &$result) {
                        $result[] = 'Send to ' . $usr['UserID'] . ': ' . $usr['Email'] . ', Subject(' . $message->getSubject() . ')';
                    },
                    Mailer::OPTION_SKIP_DONOTSEND => true,
                ]);
        }

        if (!empty($userIdErrors)) {
            $result[] = "Don't all emails sended!";
            $result[] = "Don't send emails, for these users: " . implode(', ', $userIdErrors);
            $this->connection->executeQuery('DELETE FROM ProviderVote WHERE ProviderID = ' . $providerId . ' AND UserID NOT IN (' . implode(',', $userIdErrors) . ')');
        } else {
            $result[] = '<p>All Email sended!</p>';
            $this->connection->executeQuery('DELETE FROM ProviderVote WHERE ProviderID = ' . $providerId);
        }

        return $result;
    }

    public function getUsers(int $providerId): array
    {
        return $this->connection->fetchAll(
            '
            SELECT p.DisplayName, u.UserID, u.Email, u.FirstName, u.LastName
            FROM Provider p
            LEFT JOIN Usr u ON (
                u.UserID IN (SELECT UserID FROM ProviderVote WHERE ProviderID = ' . $providerId . ')
			)
			WHERE
			        p.ProviderID = ' . $providerId . '
			    AND u.Email is NOT NULL
        ');
    }

    public function emailShouldSendUsers(int $providerId, string $type): void
    {
        $users = $this->getUsers($providerId);
        $userRepository = $this->entityManager->getRepository(Usr::class);
        $provider = $this->entityManager->getRepository(Provider::class)->find($providerId);

        $isAddedType = 'added' === $type;
        $isFixedType = 'fixed' === $type;
        $isBrokenType = 'broken' === $type;

        $info = [];
        $info[] = '<h3>Users who <u>should</u> receive emails</h3>';

        foreach ($users as $user) {
            if ($isBrokenType) {
                $info[] = $user['UserID'] . ': ' . $user['Email'];

                continue;
            }

            $template = new VoteProgram($userRepository->find($user['UserID']), false);
            $template->provider = $provider;

            if ($isAddedType) {
                $template->type = VoteProgram::TYPE_ADDED;
            } elseif ($isFixedType) {
                $template->type = VoteProgram::TYPE_FIXED;
            }

            $message = $this->mailer->getMessageByTemplate($template);

            $info[] = $user['UserID'] . ': ' . $user['Email'] . ', Subject(' . $message->getSubject() . ')';
        }

        echo implode('<br>', $info);
    }

    protected function configure(): void
    {
        $this->setDescription('Sending emails after provider-table-sync')
            ->addOption('voteMailer', null, InputOption::VALUE_OPTIONAL, 'Data submitted from form voteMailer.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $voteMailer = $input->getOption('voteMailer');

        if (!empty($voteMailer)) {
            parse_str(urldecode($voteMailer), $form);

            if (array_key_exists('voteMailer', $form)) {
                $form = $form['voteMailer'];
            }
            $providerId = (int) ($form['Provider'] ?? 0);
            $type = $form['Type'] ?? '';

            if ($providerId) {
                $result = $this->sendVoted($providerId, $type);
                $output->writeln(implode(PHP_EOL, $result));
            }
        }

        $output->writeln('done');

        return 0;
    }
}
