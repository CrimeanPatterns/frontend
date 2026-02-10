<?php

namespace AwardWallet\MainBundle\Updater\Formatter;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Updater\Event\AbstractAccountEvent;
use AwardWallet\MainBundle\Updater\Event\AbstractEvent;
use AwardWallet\MainBundle\Updater\Event\ExtensionV3Event;
use AwardWallet\MainBundle\Updater\Event\TranslationEventInterface;
use AwardWallet\MainBundle\Updater\Formatter\MobileEvents\ExtensionV3MobileEvent;
use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use AwardWallet\MainBundle\Updater\UpdaterSession;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class EventsFormatterMobile implements FormatterInterface
{
    /**
     * @var AccountListManager
     */
    private $accountListManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var UpdaterSession
     */
    private $master;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var OptionsFactory
     */
    private $optionsFactory;
    private AccountRepository $accountRepository;

    public function __construct(
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        TranslatorInterface $translator,
        AwTokenStorageInterface $tokenStorage,
        AccountRepository $accountRepository
    ) {
        $this->accountListManager = $accountListManager;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->optionsFactory = $optionsFactory;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @param AbstractEvent[] $events
     * @return AbstractEvent[]
     */
    public function format(array $events)
    {
        // accounts data
        /** @var AbstractEvent[] $accountEvents */
        $result = [];
        /** @var AbstractAccountEvent[] $accountEvents */
        $accountEvents = [];

        foreach ($events as $key => &$event) {
            if ($event instanceof AbstractAccountEvent) {
                $accountEvents[] = $event;
            }

            $result[$key] = $event;
        }
        unset($event);

        $accountIds = it($accountEvents)
            ->field('accountId')
            ->collect()
            ->unique()
            ->toArray();

        if ($accountIds) {
            $accounts = $this->accountListManager
                ->getAccountList(
                    $this->optionsFactory->createMobileOptions(
                        (new Options())
                            ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser())
                            ->set(Options::OPTION_ACCOUNT_IDS, $accountIds)
                    )
                )
                ->getAccounts();

            $accounts = it($accounts)
                ->reindexByColumn('ID')
                ->toArrayWithKeys();

            foreach ($accountEvents as $event) {
                if (
                    ($event instanceof AbstractAccountEvent)
                    && is_array($accounts)
                    && array_key_exists($event->accountId, $accounts)
                ) {
                    $event->setAccountData($accounts[$event->accountId]);
                }
            }
        }

        foreach ($result as $idx => $event) {
            if ($event instanceof ExtensionV3Event) {
                $event = $this->formatExtensionV3Event($event);
            }

            if ($event instanceof TranslationEventInterface) {
                $event->translate($this->translator);
            }

            $result[$idx] = $event;
        }

        return $result;
    }

    public function setMaster(MasterInterface $master)
    {
        $this->master = $master;

        return $this;
    }

    protected function formatExtensionV3Event(ExtensionV3Event $event): ExtensionV3Event
    {
        /** @var Account $account */
        $account = $this->accountRepository->find($event->accountId);

        if (!$account) {
            return $event;
        }

        $mobileEvent = new ExtensionV3MobileEvent(
            $event->accountId,
            $event->sessionId,
            $event->connectionToken,
            $event->expectedDuration
        );
        $mobileEvent->login = $account->getLogin();
        $provider = $account->getProviderid();
        $mobileEvent->displayName = $provider->getDisplayname();
        $mobileEvent->providerCode = $provider->getCode();

        return $mobileEvent;
    }
}
