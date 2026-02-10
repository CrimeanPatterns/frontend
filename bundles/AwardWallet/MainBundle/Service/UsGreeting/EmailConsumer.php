<?php

namespace AwardWallet\MainBundle\Service\UsGreeting;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Service\TaskScheduler\ConsumerInterface;
use AwardWallet\MainBundle\Service\TaskScheduler\TaskInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EmailConsumer implements ConsumerInterface
{
    private Mailer $mailer;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    public function __construct(Mailer $mailer, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param EmailTask $task
     */
    public function consume(TaskInterface $task): void
    {
        $now = time();
        /** @var Usr $user */
        $user = $this->entityManager->find(Usr::class, $task->getUserId());
        $taskName = sprintf('%s_%s', get_class($task), $task->getRequestId());

        if (!$user) {
            $this->logger->info(
                sprintf('EmailConsumer, task %s: user not found, userId: %d', $taskName, $task->getUserId())
            );

            return;
        }

        $context = [
            'userId_int' => $user->getId(),
            'registeredAt_string' => $user->getCreationdatetime()->format('Y-m-d H:i:s'),
            'emailClass_string' => $task->getEmailClass(),
            'skipDoNotSend_bool' => $task->getSkipDoNotSend(),
            'deadline_string' => date('Y-m-d H:i:s', $task->getDeadline()),
        ];

        if (!$user->isUsGreeting()) {
            $this->logger->info(
                sprintf('EmailConsumer, task %s: user is not American, userId: %d', $taskName, $task->getUserId()),
                $context
            );

            return;
        }

        $emailClass = $task->getEmailClass();

        if (!class_exists($emailClass)) {
            $this->logger->info(
                sprintf('EmailConsumer, task %s: email class not found, userId: %d, emailClass: %s', $taskName, $user->getId(), $emailClass),
                $context
            );

            return;
        }

        if ($task->getDeadline() < $now) {
            $this->logger->info(sprintf(
                'EmailConsumer, task %s: deadline is reached, deadline: %s, now: %s',
                $taskName,
                date('Y-m-d H:i:s', $task->getDeadline()),
                date('Y-m-d H:i:s', $now)
            ));

            return;
        }

        $template = new $emailClass($user);
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message, [
            Mailer::OPTION_SKIP_DONOTSEND => $task->getSkipDoNotSend(),
        ]);
    }
}
