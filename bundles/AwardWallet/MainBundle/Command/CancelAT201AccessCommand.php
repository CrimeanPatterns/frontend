<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Groupuserlink;
use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\AT201SubscriptionInterface;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\AppBot\AT201Notifier;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CancelAT201AccessCommand extends Command
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var AT201Notifier */
    private $notifier;

    /** @var ExpirationCalculator */
    private $calculator;

    /** @var RecurringManager */
    private $recurringManager;

    public function __construct(
        LoggerInterface $paymentLogger,
        EntityManagerInterface $em,
        AT201Notifier $notifier,
        ExpirationCalculator $calculator,
        RecurringManager $recurringManager
    ) {
        parent::__construct();
        $this->logger = new ContextAwareLoggerWrapper($paymentLogger);
        $this->logger->pushContext(['command' => 'CancelAT201AccessCommand']);
        $this->em = $em;
        $this->notifier = $notifier;
        $this->calculator = $calculator;
        $this->recurringManager = $recurringManager;
    }

    protected function configure()
    {
        $this
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED, 'UserID filter')
            ->addOption('apply', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->notice('CancelAT201AccessCommand Started!');
        $userId = $input->getOption('userId');

        $qb = $this->em->createQueryBuilder();
        $now = new \DateTime();

        if ($userId) {
            $expr = $qb->expr()->eq('u.userid', (int) $userId);
        } else {
            $expr = $qb->expr()->lt('u.at201ExpirationDate', ':current_date');
            $qb->setParameter('current_date', $now, Type::DATETIME);
        }

        $list =
            $qb->select('u')
                ->from(Usr::class, 'u')
                ->where($expr)
                ->getQuery()
                ->getResult();

        /** @var Usr $user */
        foreach ($list as $user) {
            ['date' => $expirationDate] = $this->calculator->getAccountExpiration(
                $user->getId(),
                AT201SubscriptionInterface::class
            );
            $this->logger->info("at 201 expiration date, Usr: " . ($user->getAt201ExpirationDate() ? $user->getAt201ExpirationDate()->format("Y-m-d") : "null")
                . ", calculated: " . date('Y-m-d', $expirationDate), ["UserID" => $user->getId()]);

            if ($expirationDate < $now->getTimestamp()) {
                $this->cancelAT201Access($user, $input->getOption('apply'));
            } else {
                $user->setAt201ExpirationDate(new \DateTime('@' . $expirationDate));
                $this->logger->info("updated at 201 expiration date to: " . $user->getAt201ExpirationDate()->format("Y-m-d"), ["UserID" => $user->getId()]);
            }

            $this->em->persist($user);
            $this->em->flush();
        }

        $this->logger->notice('CancelAT201AccessCommand Stopped!');

        return 0;
    }

    private function cancelAT201Access(Usr $user, bool $apply)
    {
        $this->logger->info("removing at 201 access", ["UserID" => $user->getId()]);
        $cartRepo = $this->em->getRepository(Cart::class);
        $siteGroup = $this->em->getRepository(Sitegroup::class)->findOneBy(['groupname' => 'AT201']);
        $groupLink = $this->em->getRepository(Groupuserlink::class)->findOneBy([
            'userid' => $user,
            'sitegroupid' => $siteGroup,
        ]);

        if ($groupLink !== null) {
            if ($apply) {
                $user->removeGroup($siteGroup);
                $this->logger->info("removed from at 201 group", ["UserID" => $user->getId()]);
            } else {
                $this->logger->info("dry run, should remove from group");
            }
        }

        if ($user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201) {
            if ($apply) {
                $this->logger->info("cancelling at 201 subscription", ["UserID" => $user->getId()]);
                $this->recurringManager->cancelRecurringPayment($user);
            } else {
                $this->logger->info("dry run, should cancel at 201 subscription", ["UserID" => $user->getId()]);
            }
        }

        if ($apply) {
            $user->setAt201ExpirationDate(null);
            $cart = $cartRepo->getLastAT201Cart($user);
            $this->notifier->unsubscribed($cart);
        }
    }
}
