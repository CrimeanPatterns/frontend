<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\SubscriptionRenewalReminder;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendSubscriptionRenewalReminderCommand extends Command
{
    protected static $defaultName = 'aw:send-email:subscription-renewal-reminder';

    private Connection $connection;

    private EntityManagerInterface $entityManager;

    private UsrRepository $userRepository;

    private Mailer $mailer;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private LocalizeService $localizeService;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $entityManager,
        UsrRepository $userRepository,
        Mailer $mailer,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        LocalizeService $localizer
    ) {
        parent::__construct();

        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->localizeService = $localizer;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send subscription renewal reminder emails (3 days before charge)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be sent without actually sending emails')
            ->addOption('userId', 'u', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Send to specific user ids');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');

        if ($usersIds = $input->getOption('userId')) {
            $usersIds = array_map('intval', $usersIds);
            $this->logger->info(sprintf('filter by userId: [%s]', implode(', ', $usersIds)));
        }

        if ($isDryRun) {
            $this->logger->info('Running in DRY-RUN mode - no emails will be sent');
        }

        $this->logger->info('starting subscription renewal reminder command');
        $total = 0;
        $processedUsers = 0;
        $startTime = microtime(true);
        $query = $this->executeQuery($usersIds);

        while ($row = $query->fetchAssociative()) {
            $total++;

            if (($total % 50) === 0) {
                $this->entityManager->clear();
                $output->writeln(sprintf('processed %d users...', $processedUsers));
            }

            $user = $this->userRepository->find((int) $row['UserID']);

            if (!$user) {
                $this->logger->warning(sprintf('UserID %d not found, skipping', $row['UserID']));

                continue;
            }

            if (!$user->isAwPlus()) {
                $this->logger->info(sprintf('UserID %d is not an AWPlus user, skipping', $user->getId()));

                continue;
            }

            if (!$user->hasAnyActiveSubscription()) {
                $this->logger->info(sprintf('UserID %d has no active subscription, skipping', $user->getId()));

                continue;
            }

            $lastPayment = $user->getActiveSubscriptionCart();

            if (is_null($lastPayment) || is_null($lastPayment->getPaymenttype())) {
                $this->logger->info(sprintf('UserID %d has no valid payment type, skipping', $user->getId()));

                continue;
            }

            if ($user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201 && $user->hasAt201Access()) {
                $expirationDate = clone $user->getAt201ExpirationDate();
            } else {
                $expirationDate = clone $user->getPlusExpirationDate();
            }

            if ($user->getSubscription() === Usr::SUBSCRIPTION_PAYPAL) {
                $expirationDate = clone $user->getNextBillingDate();
            }

            if (is_null($expirationDate)) {
                $this->logger->critical(sprintf(
                    'UserID %d has no expiration date set for subscription type "%s"',
                    $user->getId(),
                    $user->getSubscriptionType()
                ));

                continue;
            }

            $periodName = $this->getPeriodName($user->getSubscriptionPeriod(), $user->getLanguage());

            if (is_null($periodName)) {
                $this->logger->warning(sprintf(
                    'UserID %d has an unknown subscription period "%d", skipping',
                    $user->getId(),
                    $user->getSubscriptionPeriod()
                ));

                continue;
            }

            $paymentMethod = $this->getPaymentMethod($lastPayment, $user->getLanguage());

            if (is_null($paymentMethod)) {
                $this->logger->warning(sprintf(
                    'UserID %d has an unknown payment method, skipping',
                    $user->getId()
                ));

                continue;
            }

            $this->logger->info(sprintf(
                'processing user %d (subscription "%s") for renewal reminder, expiration in 3 days',
                $user->getId(),
                $user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201 ? 'AT201' : 'AWPlus'
            ));

            if ($isDryRun) {
                $this->logger->info(sprintf(
                    'DRY-RUN: Would send renewal reminder to user %d (%s) for %s on %s',
                    $user->getId(),
                    $user->getEmail(),
                    $user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201 ? 'AT201' : 'AWPlus',
                    $expirationDate->format('Y-m-d')
                ));
            }

            $template = new SubscriptionRenewalReminder($user);
            $template->subscriptionType = $user->getSubscriptionType();
            $template->expirationDate = $expirationDate;
            $template->amount = $this->translator->trans('price_per_period', [
                '%price%' => $this->localizeService->formatCurrency(
                    $user->getSubscriptionPrice(),
                    'USD',
                    true,
                    $user->getLocale()
                ),
                '%period%' => $periodName,
            ], 'messages', $user->getLanguage());
            $template->paymentMethod = $paymentMethod;
            $message = $this->mailer->getMessageByTemplate($template);
            $this->mailer->send($message, [Mailer::OPTION_SKIP_DONOTSEND => true]);
            $processedUsers++;
        }

        $this->entityManager->clear();
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $this->logger->info(
            sprintf(
                'processed %d users in %.2f seconds',
                $processedUsers,
                $executionTime
            ),
        );

        return 0;
    }

    private function executeQuery(array $targetUserIds = []): iterable
    {
        $filter = $targetUserIds ? sprintf(' AND u.UserID IN (%s)', implode(',', $targetUserIds)) : '';
        $targetDate = date_create('+3 day');
        $targetDateString = $targetDate->format('Y-m-d');

        return $this->connection->executeQuery("
            SELECT u.UserID
            FROM Usr u
            WHERE
                u.AccountLevel = :accountLevel
                AND (
                    (
                        u.SubscriptionType = :subscriptionTypeAt201
                        AND IF(u.Subscription <> :subscriptionPaypal, u.AT201ExpirationDate, u.NextBillingDate) IS NOT NULL
                        AND DATE(IF(u.Subscription <> :subscriptionPaypal, u.AT201ExpirationDate, u.NextBillingDate)) = :targetDate
                    )
                    OR (
                        u.SubscriptionType = :subscriptionTypeAwplus
                        AND IF(u.Subscription <> :subscriptionPaypal, u.PlusExpirationDate, u.NextBillingDate) IS NOT NULL
                        AND DATE(IF(u.Subscription <> :subscriptionPaypal, u.PlusExpirationDate, u.NextBillingDate)) = :targetDate
                    )
                )
                $filter
        ", [
            'accountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'subscriptionTypeAt201' => Usr::SUBSCRIPTION_TYPE_AT201,
            'subscriptionTypeAwplus' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
            'subscriptionPaypal' => Usr::SUBSCRIPTION_PAYPAL,
            'targetDate' => $targetDateString,
        ]);
    }

    private function getPeriodName(int $period, string $lang): ?string
    {
        switch ($period) {
            case SubscriptionPeriod::DAYS_20_YEAR:
                return $this->translator->trans(
                    'interval_short.years',
                    ['%count%' => 20],
                    'messages',
                    $lang
                );

            case SubscriptionPeriod::DAYS_1_YEAR:
                return $this->translator->trans(
                    'years',
                    ['%count%' => 1],
                    'messages',
                    $lang
                );

            case SubscriptionPeriod::DAYS_6_MONTHS:
                return $this->translator->trans(
                    'interval_short.months',
                    ['%count%' => 6],
                    'messages',
                    $lang
                );

            case SubscriptionPeriod::DAYS_3_MONTHS:
                return $this->translator->trans(
                    'interval_short.months',
                    ['%count%' => 3],
                    'messages',
                    $lang
                );

            case SubscriptionPeriod::DAYS_2_MONTHS:
                return $this->translator->trans(
                    'interval_short.months',
                    ['%count%' => 2],
                    'messages',
                    $lang
                );

            case SubscriptionPeriod::DAYS_1_MONTH:
                return $this->translator->trans(
                    'months',
                    ['%count%' => 1],
                    'messages',
                    $lang
                );

            default:
                return null;
        }
    }

    private function getPaymentMethod(Cart $cart, string $lang): ?string
    {
        global $arPaymentTypeName;

        if (!isset($arPaymentTypeName[$cart->getPaymenttype()])) {
            return null;
        }

        $paymentTypeName = $arPaymentTypeName[$cart->getPaymenttype()];

        if (
            $cart->isCreditCardPaymentType()
            && !empty($cart->getCreditcardtype())
            && !empty($cart->getCreditcardnumber())
            && preg_match('/\d{4}$/', $cart->getCreditcardnumber())
        ) {
            $lastFourCardDigits = substr($cart->getCreditcardnumber(), -4);
            $paymentTypeName = $arPaymentTypeName[PAYMENTTYPE_CREDITCARD];
        }

        if (isset($lastFourCardDigits)) {
            return $this->translator->trans(
                /** @Desc("%type% ending in %last4%") */
                'payment_method.credit_card',
                [
                    '%type%' => $paymentTypeName,
                    '%last4%' => $lastFourCardDigits,
                ],
                'messages',
                $lang
            );
        }

        return $paymentTypeName;
    }
}
