<?php

namespace AwardWallet\MainBundle\Loyalty\Listener;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\Converter;
use AwardWallet\MainBundle\Loyalty\ConverterOptions;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisEmailFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SpentAnalysisEmailListener
{
    private const EMAIL_INTERVAL = 30;

    /** @var LoggerInterface */
    private $logger;
    /** @var ApiCommunicator */
    private $loyalty;
    /** @var EntityManagerInterface */
    private $em;
    /** @var Converter */
    private $converter;
    /** @var SpentAnalysisEmailFactory */
    private $factory;
    /** @var Mailer */
    private $mailer;

    public function __construct(
        LoggerInterface $logger,
        ApiCommunicator $loyalty,
        Converter $converter,
        SpentAnalysisEmailFactory $factory,
        EntityManagerInterface $em,
        Mailer $mailer
    ) {
        $this->logger = $logger;
        $this->loyalty = $loyalty;
        $this->em = $em;
        $this->converter = $converter;
        $this->factory = $factory;
        $this->mailer = $mailer;
    }

    public function onAccountUpdated(AccountUpdatedEvent $event): void
    {
        $account = $event->getAccount();
        /** @var Usr $user */
        $user = $account->getUser();

        // only Alexy and Erik
        if (!in_array($user->getUserid(), [7, 36521])) {
            return;
        }

        // only earning potential providers
        if (!in_array($account->getProviderid()->getProviderid(), Provider::EARNING_POTENTIAL_LIST)) {
            return;
        }

        // only background checking
        //        if (!$event->isBackgroundCheck()) {
        //            return;
        //        }
        // last email date > EMAIL_INTERVAL days
        if (null !== $user->getLastSpendAnalysisEmail()) {
            $diff = $user->getLastSpendAnalysisEmail()->diff(new \DateTime());

            if ($diff->days <= self::EMAIL_INTERVAL) {
                return;
            }
        }

        $allBankAccounts = $this->findAccountsNeedsToCheck($account);
        $now = new \DateTime();
        $toCheck = [];
        $inChecking = [];

        /** @var Account $bankAccount */
        foreach ($allBankAccounts as $bankAccount) {
            // already checked in last 24 hours
            if ($bankAccount->getSuccesscheckdate()->diff($now)->h < 24) {
                continue;
            }

            // sended to queue
            if ($bankAccount->getQueuedate()->diff($now) < 24) {
                $inChecking[] = $bankAccount;

                continue;
            }

            // needs to check
            $toCheck[] = $bankAccount;
        }

        if (count($toCheck) > 0) {
            // send accounts to loyalty
            /** @var Account $toCheckItem */
            foreach ($toCheck as $toCheckItem) {
                $request = $this->converter->prepareCheckAccountRequest(
                    $toCheckItem,
                    new ConverterOptions(true, false, UpdaterEngineInterface::SOURCE_BACKGROUND),
                    Converter::BACKGROUND_CHECK_REQUEST_PRIORITY_MIN
                );
                // debug
                $this->logger->warning("Send account to loyalty via SpentAnalysisEmailListener " . $account->getId(),
                    ['accountId' => $toCheckItem->getId()]);
                //                $this->loyalty->CheckAccount($request);
            }

            return;
        }

        if (count($inChecking) > 0) {
            return;
        }

        $template = $this->factory->buildLastMonth($user);

        if (null === $template) {
            return;
        }

        $message = $this->mailer->getMessageByTemplate($template);
        $this->mailer->send($message);
        $user->setLastSpendAnalysisEmail(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();
        $this->logger->warning("Send email via SpentAnalysisEmailListener " . $account->getId());
    }

    public function findAccountsNeedsToCheck(Account $currentAcc): array
    {
        $user = $currentAcc->getUser();
        $accounts = $this->em->getRepository(Account::class)
            ->findBy([
                'user' => $user,
                'providerid' => Provider::EARNING_POTENTIAL_LIST,
                'errorcode' => ACCOUNT_CHECKED,
            ]);

        $list = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            if ($currentAcc->getId() === $account->getId()) {
                continue;
            }

            $list[] = $account;
        }

        return $list;
    }
}
