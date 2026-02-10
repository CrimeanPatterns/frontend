<?php

namespace AwardWallet\MainBundle\Service\User\Async;

use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class FareDropUsersExecutor implements ExecutorInterface
{
    private SocksClient $sockClicent;
    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        SocksClient $sockClicent,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->sockClicent = $sockClicent;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @param FareDropUsersTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $hashes = $task->getHashes();

        if (!empty($hashes)) {
            $flipHashes = array_flip($hashes);
            $hashes = null;

            $counter = 0;
            $index = 0;

            $users = $this->connection->fetchFirstColumn('SELECT LOWER(Email) FROM Usr');
            $usersCount = count($users);

            foreach ($users as $email) {
                $hashEmail = $this->hashEmail($email);

                if (array_key_exists($hashEmail, $flipHashes)) {
                    ++$counter;
                }

                if (++$index % 15000 === 0) {
                    $this->sockClicent->publish(
                        $task->getResponseChannel(),
                        [
                            'type' => 'processed',
                            'html' => 'Processed ' . $index . ' out of ' . $usersCount . ' (found: ' . $counter . ')',
                        ]
                    );
                }
            }

            $this->sockClicent->publish(
                $task->getResponseChannel(),
                [
                    'type' => 'usersCount',
                    'html' => 'Total number of intersections based on hash file: <span class="hightlight">' . $counter . '</span>',
                ]
            );
        }

        return new Response();
    }

    private function hashEmail(string $email): string
    {
        return base64_encode(hash('sha256', $email, true));
    }
}
