<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Email\BlogUnsubscriber;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\UserEmailVerificationChangedEvent;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NDRManager
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LoggerInterface
     */
    private $loggerStat;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    private BlogUnsubscriber $blogUnsubscriber;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        LoggerInterface $statLogger,
        EntityManagerInterface $em,
        BlogUnsubscriber $blogUnsubscriber,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->loggerStat = $statLogger;
        $this->em = $em;
        $this->blogUnsubscriber = $blogUnsubscriber;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function recordNDR($email, $messageId, $doNotSend, $errorMessage, $category = null)
    {
        $this->logger->warning("recordNDR", ["email" => $email, "doNotSend" => $doNotSend, "errorMessage" => $errorMessage, "category" => $category]);

        $addNdr = true;

        if ($doNotSend) {
            if (!empty($category)) {
                /** @var Usr $user */
                $this->logger->warning("unsubscribing from category $category", ["email" => $email]);
                $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['email' => $email]);

                if (!empty($user)) {
                    $method = "setEmail" . $category;
                    $user->$method(false);
                    $this->em->flush();
                    $addNdr = false;

                    $this->loggerStat->info('mail_unsubscribe', ['userid' => $user->getUserid(), 'source' => 'sparkpost', 'category' => $category]);
                }
            } else {
                $this->logger->warning("adding to DoNotSend", ["email" => $email]);
                $this->connection->executeUpdate("insert ignore into DoNotSend(Email, AddTime, IP) values(:email, now(), '')", ["email" => $email]);

                $this->loggerStat->info('mail_unsubscribe', ['email' => $email, 'source' => 'sparkpost']);
            }
        }

        if ($addNdr) {
            // checking of the existence
            $this->connection->executeUpdate("insert ignore into EmailNDR(Address) values(:email)", ["email" => $email]);
            $emailNdrId = $this->connection->executeQuery("SELECT EmailNDRID FROM EmailNDR WHERE Address = :email", ["email" => $email])->fetchColumn(0);
            // add error message
            $this->connection->executeUpdate(
                "insert ignore into EmailNDRContent(EmailNDRID, Msg, MessageID) values(:emailNdrId, :errorMessage, :messageId)",
                ["emailNdrId" => $emailNdrId, "errorMessage" => $errorMessage, "messageId" => $messageId]
            );
            $this->updateUserNDRStatus($emailNdrId, $email);
        }
    }

    public function ndrExists($email, $date)
    {
        return $this->connection->executeQuery("select
			1
		from
			EmailNDR n
			join EmailNDRContent c on n.EmailNDRID = c.EmailNDRID
		where
			n.Address = :email
			and MessageDate >= FROM_UNIXTIME(" . ($date - 3600) . ")
			and MessageDate < FROM_UNIXTIME(" . ($date + 3600) . ")", ["email" => $email])->fetchColumn() !== false;
    }

    private function updateUserNDRStatus($emailNdrId, $email)
    {
        $markNdr = false;

        $hardBounces = $this->connection->executeQuery("
			select
				count(*) as Cnt
			from
				EmailNDRContent
			where
				EmailNDRID = :emailNdrId and MessageDate > adddate(now(), -30)
				and Msg in ('mandrill:hard-bounce', 'abuse', 'mandrill:spam', 'hard-bounce')",
            ["emailNdrId" => $emailNdrId]
        )->fetchColumn();

        if ($hardBounces > 0) {
            $markNdr = true;
        }

        $days = $this->connection->executeQuery("
                select
                    count(distinct date(MessageDate)) as Days
                from
                    EmailNDRContent
                where
                    EmailNDRID = :emailNdrId and MessageDate > adddate(now(), -30)",
            ["emailNdrId" => $emailNdrId]
        )->fetchColumn();

        if ($days > 3) {
            $markNdr = true;
        }

        $this->logger->info("updating user ndr status", ["Email" => $email, "NDR" => $markNdr, "HardBounces" => $hardBounces, "Days" => $days]);

        if ($markNdr) {
            $this->connection->executeStatement(
                "update
					Usr
				set
					OldEmailVerified = IF(OldEmailVerified IS NULL, EmailVerified, OldEmailVerified),
					EmailVerified = " . EMAIL_NDR . "
				where
					Email = :email",
                ["email" => $email]
            );

            $this->blogUnsubscriber->unsubscribe($email);

            $this->connection->executeStatement(
                "update MediaContact set NDR = " . EMAIL_NDR . " where Email = :email",
                ["email" => $email]
            );

            $user = $this->em->getRepository(Usr::class)->findOneBy(['email' => $email]);

            if ($user) {
                $this->eventDispatcher->dispatch(new UserEmailVerificationChangedEvent($user));
            }
        }
    }
}
