<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\UserDeleted;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserDelete;
use AwardWallet\MainBundle\Manager\Files\ItineraryFileManager;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Manager\LegacySchemaManagerFactory;
use AwardWallet\MainBundle\Scanner\MailboxManager;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UserRemover
{
    private LoggerInterface $logger;

    private Mailer $mailer;

    private Counter $counter;

    private UsrRepository $usrRepository;

    private LegacySchemaManagerFactory $legacySchemaManagerFactory;

    private AccountRepository $accountRepository;

    private MailboxManager $mailboxManager;

    private EntityManagerInterface $entityManager;

    private PlanFileManager $planFileManager;

    private ItineraryFileManager $itineraryFileManager;
    private RecurringManager $recurringManager;

    public function __construct(
        LoggerInterface $logger,
        Mailer $mailer,
        Counter $counter,
        EntityManagerInterface $entityManager,
        LegacySchemaManagerFactory $legacySchemaManagerFactory,
        AccountRepository $accountRepository,
        MailboxManager $mailboxManager,
        PlanFileManager $planFileManager,
        ItineraryFileManager $itineraryFileManager,
        RecurringManager $recurringManager
    ) {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->counter = $counter;
        $this->entityManager = $entityManager;
        $this->legacySchemaManagerFactory = $legacySchemaManagerFactory;
        $this->accountRepository = $accountRepository;
        $this->mailboxManager = $mailboxManager;
        $this->planFileManager = $planFileManager;
        $this->usrRepository = $entityManager->getRepository(Usr::class);
        $this->itineraryFileManager = $itineraryFileManager;
        $this->recurringManager = $recurringManager;
    }

    public function deleteUser(Usr $user, string $reason): void
    {
        $state = $this->fetchState($user, $reason);

        $this->saveState($user, $state);
        $this->removeAttachments($user);
        $this->mailboxManager->deleteAllUserMailboxes($user);
        $this->recurringManager->cancelRecurringPayment($user, false, true);
        $this->sendMail($user, $state);

        $schemaManager = $this->legacySchemaManagerFactory->make();
        $schemaManager->DeleteRow("Usr", $user->getId(), true);
    }

    private function sendMail(Usr $user, array $state): void
    {
        $this->logger->warning("Deleted AwardWallet Account", ["UserID" => $user->getId()]);
        $template = new UserDelete();
        $template->toUser($user, false, $this->mailer->getEmail('support'));

        foreach ($state as $key => $value) {
            $template->{$key} = $value;
        }
        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
    }

    private function removeAttachments(Usr $user): void
    {
        // PlanFile
        $plans = $this->entityManager->getRepository(Plan::class)->findBy(['user' => $user]);

        foreach ($plans as $plan) {
            foreach ($plan->getFiles() as $file) {
                $this->planFileManager->removeFile($file);
            }
        }

        // ItineraryFiles
        $this->itineraryFileManager->removeAllFilesByUser($user->getId());
    }

    private function saveState(Usr $user, array $state): UserDeleted
    {
        $deletedUser = (new UserDeleted())
            ->setUserId($user->getId())
            ->setRegistrationDate($user->getCreationdatetime())
            ->setFirstName($user->getFirstname())
            ->setLastName($user->getLastname())
            ->setEmail($user->getEmail())
            ->setCountryId($user->getCountryid())
            ->setAccounts($state['accounts'])
            ->setValidMailboxesCount($user->getValidMailboxesCount())
            ->setDeletionDate(new \DateTime())
            ->setTotalContribution($state['lifetimeContribution'])
            ->setCameFrom($user->getCamefrom())
            ->setReferer($user->getReferer())
            ->setReason($state['reason']);

        $cardClicks = (int) $this->entityManager->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM QsTransaction WHERE UserID = ' . $user->getId());
        $approvals = $this->entityManager->getConnection()
            ->fetchAssociative('SELECT SUM(Approvals) AS sumApprovals, SUM(Earnings) as sumEarnings FROM QsTransaction WHERE UserID = ' . $user->getId() . ' AND Approvals > 0');

        $deletedUser
            ->setCardClicks($cardClicks)
            ->setCardApprovals((int) ($approvals['sumApprovals'] ?? 0))
            ->setCardEarnings((int) ($approvals['sumEarnings'] ?? 0));

        $this->entityManager->persist($deletedUser);
        $this->entityManager->flush();

        return $deletedUser;
    }

    private function fetchState(Usr $user, string $reason): array
    {
        $paymentStats = $this->usrRepository->getPaymentStatsByUser($user->getId());

        return [
            'accounts' => $this->counter->getTotalAccounts($user->getId()),
            'trips' => $this->counter->getTotalItineraries($user->getId()),
            'pays' => $paymentStats['PaidOrders'],
            'lifetimeContribution' => $paymentStats['LifetimeContribution'],
            'reason' => $reason,
        ];
    }
}
