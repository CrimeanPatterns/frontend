<?php

namespace AwardWallet\MainBundle\Updater\Formatter;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Updater\Event\AbstractAccountEvent;
use AwardWallet\MainBundle\Updater\Event\AbstractEvent;
use AwardWallet\MainBundle\Updater\Event\TranslationEventInterface;
use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use AwardWallet\MainBundle\Updater\UpdaterSession;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventsFormatterDesktop implements FormatterInterface
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
     * @var OptionsFactory
     */
    private $optionsFactory;
    /**
     * @var AwTokenStorageInterface
     */
    private $awTokenStorage;

    public function __construct(
        AccountListManager $accountListManager,
        TranslatorInterface $translator,
        OptionsFactory $optionsFactory,
        AwTokenStorageInterface $awTokenStorage
    ) {
        $this->accountListManager = $accountListManager;
        $this->translator = $translator;
        $this->optionsFactory = $optionsFactory;
        $this->awTokenStorage = $awTokenStorage;
    }

    public function setMaster(MasterInterface $master)
    {
        $this->master = $master;

        return $this;
    }

    /**
     * @param AbstractEvent[] $events
     * @return AbstractEvent[]
     */
    public function format(array $events)
    {
        // accounts data
        /** @var AbstractAccountEvent[] $accountEvents */
        $accountEvents = [];

        foreach ($events as $event) {
            if ($event instanceof AbstractAccountEvent) {
                $accountEvents[] = $event;
            }
        }
        $accountIds = array_unique(array_map(function (/** @var AbstractAccountEvent $event */ $event) { return $event->accountId; }, $accountEvents));

        if ($accountIds) {
            $accounts = $this->accountListManager
                ->getAccountList(
                    $this->optionsFactory->createDesktopListOptions(
                        (new Options())
                            ->set(Options::OPTION_USER, $this->awTokenStorage->getBusinessUser())
                            ->set(Options::OPTION_ACCOUNT_IDS, $accountIds)
                            ->set(Options::OPTION_FORMAT_FORCE_ERRORS, true)
                            ->set(Options::OPTION_LOAD_MILE_VALUE, true)
                    )
                )
                ->getAccounts();
            $accounts = array_combine(array_map(function ($a) {return $a->FID; }, $accounts), $accounts);

            foreach ($accountEvents as $event) {
                if (is_array($accounts) && array_key_exists('a' . $event->accountId, $accounts)) {
                    $event->setAccountData($accounts['a' . $event->accountId]);
                }
            }
        }

        // translate
        foreach ($events as $event) {
            if ($event instanceof TranslationEventInterface) {
                $event->translate($this->translator);
            }
        }

        return $events;
    }
}
