<?php

namespace AwardWallet\MainBundle\Event\PushNotification;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\SubaccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Event\AccountExpiredEvent;
use AwardWallet\MainBundle\Event\AccountUpdateEvent;
use AwardWallet\MainBundle\Event\PassportExpiredEvent;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\AccountList\Options as AccountListOptions;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountListener
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    private DateTimeIntervalFormatter $intervalFormatter;

    private Sender $sender;

    private SubaccountRepository $subAccRep;

    private AccountRepository $accRep;

    private AccountListManager $accountListManager;

    private OptionsFactory $optionsFactory;

    private UsrRepository $usrRep;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        DateTimeIntervalFormatter $intervalFormatter,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        Sender $sender,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->intervalFormatter = $intervalFormatter;
        $this->sender = $sender;
        $this->translator = $translator;
        $this->subAccRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class);
        $this->accRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $this->usrRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    public function onAccountUpdate(AccountUpdateEvent $event)
    {
        $this->logger->info('onAccountUpdate');
        $accountBefore = $event->getAccountInfo();
        $accountAfter = $event->getAccount();

        $devices = $this->loadDevicesForRewardsActivity($accountAfter->getUser(), $event->getReport()->source);

        if (!$devices) {
            return;
        }

        // send status change push
        $statusAfter = $accountAfter->getAccountPropertyByKind(PROPERTY_KIND_STATUS);

        if (
            $statusAfter && isset($accountBefore['Status']) && $accountBefore['Status'] !== $statusAfter
        ) {
            $statusBefore = $accountBefore['Status'];

            $eliteLevelRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
            $providerId = $accountAfter->getProviderid()->getProviderid();

            $eliteLevelBefore = $eliteLevelRep->getEliteLevelFieldsByValue($providerId, $accountBefore['Status']);
            $eliteLevelAfter = $eliteLevelRep->getEliteLevelFieldsByValue($providerId, $statusAfter);

            if (is_array($eliteLevelBefore) && is_array($eliteLevelAfter)) {
                if (!$eliteLevelAfter['Name'] || $eliteLevelBefore['Rank'] === $eliteLevelAfter['Rank']) {
                    return;
                } else {
                    $statusBefore = $eliteLevelBefore['Name'];
                    $statusAfter = $eliteLevelAfter['Name'];
                }
            }

            $title = new Trans(
                /** @Desc("%provider-name% status change") */
                'push-notifications.status.changed.title',
                [
                    '%provider-name%' => $accountAfter->getProviderid()->getShortname(),
                ],
                'mobile'
            );

            $message = new Trans(
                /** @Desc("Status changed from %status-before% to %status-after% on %provider-name%") */
                'push-notifications.status.changed',
                [
                    '%provider-name%' => $accountAfter->getProviderid()->getShortname(),
                    '%status-before%' => $statusBefore,
                    '%status-after%' => $statusAfter,
                ],
                'mobile'
            );

            $this->sender->send(
                new Content(
                    $title,
                    $message,
                    Content::TYPE_REWARDS_ACTIVITY,
                    $accountAfter,
                    (new Options())
                        ->setDeadlineTimestamp(time() + SECONDS_PER_DAY)
                        ->setPriority(7)
                        ->setInterruptionLevel(InterruptionLevel::ACTIVE)
                ),
                $devices
            );
        }
    }

    public function onAccountExpire(AccountExpiredEvent $event)
    {
        /** @var Usr $user */
        $user = $this->usrRep->find($event->getUserId());

        if (!$user) {
            $this->logger->info(sprintf('user %d not found', $event->getUserId()));

            return;
        }

        $devices = $this->sender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_ACCOUNT_EXPIRATION);

        if (!$devices) {
            $this->logger->info(sprintf('no devices found for user %d', $user->getId()));

            return;
        }

        [$repository, $id] = $event->getExpiry();
        $expiry = $this->entityManager->find($repository, $id);

        if (!$expiry) {
            $this->logger->info(sprintf('itinerary "%s":%s not found', $repository, $id));

            return;
        }

        $target = $expiry;
        $accountLoader = $this->getAccountLoader($user);
        $couponLoader = $this->getCouponLoader($user);

        switch (true) {
            case $expiry instanceof Account:
                $user = $expiry->getUser();

                if (!$user) {
                    $this->logger->info(sprintf('user not found for account %d', $expiry->getAccountid()));

                    return;
                }

                $accountFields = $accountLoader($expiry->getAccountid());

                if (!$accountFields) {
                    $this->logger->info(sprintf('account %d not found', $expiry->getAccountid()));

                    return;
                }

                $provider = $expiry->getProviderid();
                $isCustomProgram = !$provider;

                $providerName = $isCustomProgram ? $expiry->getProgramname() : $provider->getShortname();

                if (ListenerUtils::isAnyEmptyParams([$providerName])) {
                    $this->logger->info(sprintf('provider name is empty for account %d', $expiry->getAccountid()));

                    return;
                }

                if (!empty($accountFields['Currency'])) {
                    $balance = $this->createBalanceTranslator($this->getExpiringBalance($accountFields), $accountFields['Currency']);
                } else {
                    $balance = $this->getExpiringBalance($accountFields);
                }

                $transParams = [
                    '%provider-name%' => sprintf('%s:', $providerName),
                    '%balance-expire%' => $balance,
                    '%time-interval%' => function ($id, $params, $domain, $locale) use ($expiry) {
                        return $this->intervalFormatter->longFormatViaDateTimes(
                            new \DateTime(),
                            $expiry->getExpirationdate(),
                            true,
                            true,
                            $locale
                        );
                    },
                ];

                break;

            case $expiry instanceof Subaccount:
                $account = $expiry->getAccountid();
                $user = $account->getUser();

                if (!$user) {
                    $this->logger->info(sprintf('user not found for account %d', $account->getAccountid()));

                    return;
                }

                $accountFields = $accountLoader($account->getAccountid());

                if (!isset($accountFields['SubAccountsArray'])) {
                    $this->logger->info(sprintf('subaccounts not found for account %d', $account->getAccountid()));

                    return;
                }

                $subAccIdx = array_search($expiry->getSubaccountid(), array_column($accountFields['SubAccountsArray'], 'SubAccountID'));

                if (false === $subAccIdx) {
                    $this->logger->info(sprintf('subaccount %d not found for account %d', $expiry->getSubaccountid(), $account->getAccountid()));

                    return;
                }

                $subAccount = $accountFields['SubAccountsArray'][$subAccIdx];
                $balance = $this->getExpiringBalance($subAccount);

                if (ListenerUtils::isAnyEmptyParams([
                    $account->getProviderid()->getShortname(),
                    $subAccount['DisplayName'],
                ])) {
                    $this->logger->info(sprintf('provider name or subaccount name is empty for account %d', $account->getAccountid()));

                    return;
                }

                $transParams = [
                    '%provider-name%' => sprintf('%s %s -', $account->getProviderid()->getShortname(), $subAccount['DisplayName']),
                    '%balance-expire%' => $balance,
                    '%time-interval%' => function ($id, $params, $domain, $locale) use ($expiry) {
                        return $this->intervalFormatter->longFormatViaDateTimes(
                            new \DateTime(),
                            $expiry->getExpirationdate(),
                            true,
                            true,
                            $locale
                        );
                    },
                ];

                break;

            case $expiry instanceof Providercoupon:
                $user = $expiry->getUser();

                if (!$user) {
                    $this->logger->info(sprintf('user not found for coupon %d', $expiry->getProvidercouponid()));

                    return;
                }

                $couponFields = $couponLoader($expiry->getProvidercouponid());

                if (!isset($couponFields['Value'], $couponFields['DisplayName'])) {
                    $this->logger->info(sprintf('coupon value or display name is empty for coupon %d', $expiry->getProvidercouponid()));

                    return;
                }

                if (ListenerUtils::isAnyEmptyParams([$couponFields['DisplayName']])) {
                    $this->logger->info(sprintf('provider name is empty for coupon %d', $expiry->getProvidercouponid()));

                    return;
                }

                $transParams = [
                    '%provider-name%' => sprintf("%s:", $couponFields['DisplayName']),
                    '%balance-expire%' => $couponFields['Value'],
                    '%time-interval%' => function ($id, $params, $domain, $locale) use ($expiry) {
                        return $this->intervalFormatter->longFormatViaDateTimes(
                            new \DateTime(),
                            $expiry->getExpirationdate(),
                            true,
                            true,
                            $locale
                        );
                    },
                ];

                break;

            default:
                $this->logger->error(sprintf('Unknown expiry type: %s', is_object($expiry) ? get_class($expiry) : gettype($expiry)), ['_aw_server_module' => 'push']);

                return;
        }

        $transParams = ListenerUtils::decodeStrings($transParams);

        // check for empty params
        if (ListenerUtils::isAnyEmptyParams($transParams)) {
            $this->logger->info('empty params');

            return;
        }

        $message = new Trans(
            /** @Desc("%provider-name% %balance-expire% expiring %time-interval%") */
            'push-notifications.balance.expire',
            $transParams,
            'mobile'
        );
        $this->sender->send(
            new Content(
                new Trans(/** @Desc("!!Expiration warning!!") */ 'balance-expire.title'),
                $message,
                Content::TYPE_ACCOUNT_EXPIRATION,
                $target,
                (new Options())
                    ->setDeadlineTimestamp(time() + SECONDS_PER_DAY)
                    ->setPriority(8)
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    public function onPassportExpire(PassportExpiredEvent $event)
    {
        /** @var Usr $user */
        $user = $this->usrRep->find($event->getUserId());
        $devices = $this->sender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_PASSPORT_EXPIRATION);

        if (!$devices) {
            return;
        }

        /** @var Providercoupon $passport */
        $passport = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Providercoupon::class)->find($event->getPassportId());

        if (!$passport) {
            return;
        }

        if (empty($event->getUserName())) {
            return;
        }

        $transParams = [
            '%user_name%' => $event->getUserName(),
            '%months%' => $event->getMonths(),
        ];

        $transParams = ListenerUtils::decodeStrings($transParams);

        // check for empty params
        if (ListenerUtils::isAnyEmptyParams($transParams)) {
            return;
        }

        $message = new Trans(
            /** @Desc("%user_name%'s passport is expiring in %months% months") */
            'push-notifications.passport.expire',
            $transParams,
            'mobile'
        );
        $this->sender->send(
            new Content(
                new Trans(/** @Desc("!!Expiration warning!!") */ 'balance-expire.title'),
                $message,
                Content::TYPE_PASSPORT_EXPIRATION,
                $passport,
                (new Options())
                    ->setDeadlineTimestamp(time() + SECONDS_PER_DAY)
                    ->setPriority(8)
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    public function onAccountBalanceChanged(AccountBalanceChangedEvent $event)
    {
        $this->logger->info('onAccountBalanceChanged');

        if ($event->getSource() === AccountBalanceChangedEvent::SOURCE_MANUAL) {
            return;
        }

        $account = $event->getAccount();
        $user = $account->getUser();
        $devices = $this->loadDevicesForRewardsActivity($user, $event->getSource());

        if (!$devices) {
            return;
        }

        $accountLoader = $this->getAccountLoader($user);
        $formattedAccount = null;

        if (count($event->getSubAccounts()) > 0) {
            foreach ($event->getSubAccounts() as $subAccount) {
                /** @var Subaccount $subAccount */
                if (is_null($formattedAccount)) {
                    $formattedAccount = $accountLoader($account->getAccountid());

                    if (!$formattedAccount) {
                        return;
                    }
                }

                if (!isset($formattedAccount['SubAccountsArray'])) {
                    break;
                }

                $subAccIdx = array_search($subAccount->getSubaccountid(), array_column($formattedAccount['SubAccountsArray'], 'SubAccountID'));

                if (false === $subAccIdx) {
                    continue;
                }

                $formattedSubAccount = $formattedAccount['SubAccountsArray'][$subAccIdx];

                if (
                    !isset($formattedSubAccount['LastChange'], $formattedSubAccount['LastBalance'], $formattedSubAccount['Balance'])
                    || ListenerUtils::isAnyEmptyParams(array_map('htmlspecialchars_decode', [
                        $account->getProviderid()->getShortname(),
                        $formattedSubAccount['DisplayName'],
                    ]))
                ) {
                    continue;
                }

                $params = ListenerUtils::decodeStrings(
                    [
                        '%provider-name%' => sprintf('%s %s:', $account->getProviderid()->getShortname(), $formattedSubAccount['DisplayName']),
                        '%balance-change%' => $formattedSubAccount['LastChange'],
                        '%balance-from%' => $formattedSubAccount['LastBalance'],
                        '%balance-to%' => $formattedSubAccount['Balance'],
                    ]
                );

                // check for empty params
                if (ListenerUtils::isAnyEmptyParams($params)) {
                    continue;
                }

                $message = new Trans(
                    /** @Desc("%provider-name% %balance-change% (from %balance-from% to %balance-to%)") */
                    'push-notifications.balance.changed',
                    $params,
                    'mobile'
                );

                $isFICOCode = preg_match('/.*FICO$/ms', $subAccount->getCode());

                if ($isFICOCode) {
                    $title = new Trans(/** @Desc("Credit Score Update") */ 'notification.credit-score-update');
                } else {
                    $title = new Trans('notification.rewards-activity');
                }

                $this->sender->send(
                    new Content(
                        $title,
                        $message,
                        Content::TYPE_REWARDS_ACTIVITY,
                        $subAccount,
                        (new Options())
                            ->setDeadlineTimestamp(time() + SECONDS_PER_DAY)
                            ->setPriority(7)
                            ->setInterruptionLevel(InterruptionLevel::ACTIVE)
                    ),
                    $devices
                );
            }
        }

        if (!$event->isAccountChanged()) {
            return;
        }

        if (is_null($formattedAccount)) {
            $formattedAccount = $accountLoader($account->getAccountid());

            if (!$formattedAccount) {
                return;
            }
        }

        if (!$formattedAccount || !isset($formattedAccount['LastChange'])) {
            return;
        }

        $provider = $account->getProviderid();

        if (!$provider || ListenerUtils::isAnyEmptyParams([$provider->getShortname()])) {
            return;
        }

        $params = [
            '%provider-name%' => sprintf('%s:', $provider->getShortname()),
            '%balance-change%' => isset($formattedAccount['Currency']) ?
                $this->createBalanceTranslator($formattedAccount['LastChange'], $formattedAccount['Currency']) :
                $formattedAccount['LastChange'],
            '%balance-from%' => $formattedAccount['LastBalance'],
            '%balance-to%' => $formattedAccount['Balance'],
        ];

        // check for empty params
        if (ListenerUtils::isAnyEmptyParams($params)) {
            return;
        }

        $message = new Trans(
            'push-notifications.balance.changed',
            ListenerUtils::decodeStrings($params),
            'mobile'
        );

        $this->sender->send(
            new Content(
                new Trans('notification.rewards-activity'),
                $message,
                Content::TYPE_REWARDS_ACTIVITY,
                $account,
                (new Options())
                    ->setDeadlineTimestamp(time() + SECONDS_PER_DAY)
                    ->setPriority(7)
                    ->setInterruptionLevel(InterruptionLevel::ACTIVE)
            ),
            $devices
        );
    }

    /**
     * @param string $balance
     * @param string $currency
     * @return \Closure
     */
    protected function createBalanceTranslator($balance, $currency)
    {
        return function ($id, $params, $domain, $locale) use ($balance, $currency) {
            // check that balance is number-like without currency or other modificators
            if (is_numeric(str_replace(['.', ',', ' ', '+', '-'], '', $balance))) {
                $key = 'name.' . $currency;
                $translatedCurrency = $this->translator->trans('name.' . $currency, [], 'currency', $locale);

                if ($translatedCurrency !== $key) {
                    $balance .= ' ' . $translatedCurrency;
                }
            }

            return $balance;
        };
    }

    /**
     * @return MobileDevice[]
     */
    private function loadDevicesForRewardsActivity(Usr $user, ?int $source): ?array
    {
        if ($source === UpdaterEngineInterface::SOURCE_DESKTOP) {
            $types = MobileDevice::TYPES_MOBILE;
        } elseif ($source === UpdaterEngineInterface::SOURCE_MOBILE) {
            $types = MobileDevice::TYPES_DESKTOP;
        } else {
            $types = MobileDevice::TYPES_ALL;
        }

        $devices = $this->sender->loadDevices([$user], $types, Content::TYPE_REWARDS_ACTIVITY);
        $this->logger->info("loaded devices for rewards activity push, user: " . $user->getId() . ", source: " . $source . ", devices: " . implode(", ", array_map(function (MobileDevice $device) {
            return $device->getMobileDeviceId();
        }, $devices)));

        if (empty($devices)) {
            return null;
        }

        foreach ($devices as $device) {
            if ($device->getUser()->getId() != $user->getId()) {
                $this->logger->critical("userId mismatch for device " . $device->getMobileDeviceId());
            }
        }

        return $devices;
    }

    private function getExpiringBalance(array $fields)
    {
        $balance = $fields['Balance'];

        if (empty($fields['Properties']) || !is_array($fields['Properties'])) {
            return $balance;
        }

        foreach ($fields['Properties'] as $property) {
            if (isset($property['Kind']) && $property['Kind'] == PROPERTY_KIND_EXPIRING_BALANCE && isset($property['Val'])) {
                return $property['Val'];
            }
        }

        return $balance;
    }

    private function getAccountLoader(Usr $user): \Closure
    {
        $listOptions = $this->optionsFactory
            ->createDefaultOptions()
            ->set(AccountListOptions::OPTION_USER, $user)
            ->set(AccountListOptions::OPTION_LOAD_SUBACCOUNTS, true);

        return function ($id) use ($listOptions): ?array {
            return $this->accountListManager->getAccount($listOptions, $id);
        };
    }

    private function getCouponLoader(Usr $user): \Closure
    {
        $listOptions = $this->optionsFactory
            ->createDefaultOptions()
            ->set(AccountListOptions::OPTION_USER, $user)
            ->set(AccountListOptions::OPTION_LOAD_SUBACCOUNTS, true);

        return function ($id) use ($listOptions): ?array {
            return $this->accountListManager->getCoupon($listOptions, $id);
        };
    }
}
