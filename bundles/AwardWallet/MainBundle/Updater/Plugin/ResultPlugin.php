<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\ThrottlerCounter;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Event\ChangedEvent;
use AwardWallet\MainBundle\Updater\Event\DisabledEvent;
use AwardWallet\MainBundle\Updater\Event\ErrorEvent;
use AwardWallet\MainBundle\Updater\Event\QuestionEvent;
use AwardWallet\MainBundle\Updater\Event\TripsFoundEvent;
use AwardWallet\MainBundle\Updater\Event\TripsNotFoundEvent;
use AwardWallet\MainBundle\Updater\Event\UpdatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ResultPlugin extends AbstractPlugin
{
    use PluginIdentity;
    public const ID = 'result';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Manager
     */
    private $manager;

    /** @var ThrottlerCounter */
    private $throttlerCounter;
    private ContextAwareLoggerWrapper $logger;
    private BinaryLoggerFactory $check;

    public function __construct(
        EntityManagerInterface $em,
        Manager $manager,
        ThrottlerCounter $throttlerCounter,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->manager = $manager;
        $this->throttlerCounter = $throttlerCounter;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('result plugin: ')
            ->withClass(self::class)
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo()->uppercaseInfix();
    }

    public function tick(MasterInterface $master, $accountStates): void
    {
        $this->logger->pushContext(['updater_session_key' => $master->getKey()]);

        try {
            foreach ($accountStates as $state) {
                $this->logger->pushContext(['account_id' => $state->account->getId()]);

                try {
                    $this->em->refresh($state->account); // refreshed in WaitEmailOTCPlugin

                    if (
                        $this->check->that('account')->is('disabled')
                            ->on($state->account->isDisabled())
                    ) {
                        $master->addEvent(new DisabledEvent($state->account->getAccountid()));
                    } elseif (
                        $this->check->that('account error code')->is('question')
                            ->on($state->account->getErrorcode() == ACCOUNT_QUESTION)
                    ) {
                        $master->addEvent(new ErrorEvent(
                            $state->account->getAccountid(),
                            $state->account->getErrorcode()
                        ));
                        $master->addEvent(new QuestionEvent(
                            $state->account->getAccountid(),
                            $state->account->getQuestion(),
                            //					$state->account->getQuestion() == $state->account->getErrormessage() ? null : $state->account->getErrormessage()
                            $state->account->getProviderid()->getDisplayname()
                        ));

                        $updateAborted = true;
                    } elseif (
                        $this->check->that('account error code')->isNot('checked or warning')
                            ->on(!in_array($state->account->getErrorcode(), [ACCOUNT_CHECKED, ACCOUNT_WARNING]))
                    ) {
                        $master->addEvent(new ErrorEvent(
                            $state->account->getAccountid(),
                            $state->account->getErrorcode()
                        ));
                    } else {
                        // balance
                        $accountData = $state->getContextValue('accountData');

                        if (null !== $accountData['Balance'] && null !== $state->account->getBalance()
                            && (float) $accountData['Balance'] !== (float) $state->account->getBalance()) {
                            $change = $state->account->getBalance() - $accountData['Balance'];

                            if (abs($change) > 0.001) {
                                $increased = $change > 0;
                                $master->addEvent(new ChangedEvent($state->account->getAccountid(), $state->account->getBalance(), $change, $increased));
                            } else {
                                $master->addEvent(new UpdatedEvent($state->account->getAccountid(), $state->account->getBalance()));
                            }
                        } else {
                            $master->addEvent(new UpdatedEvent($state->account->getAccountid(), $state->account->getBalance()));
                        }

                        // segments count
                        if ($state->checkIts) {
                            $trips = $state->getSharedValue(AccountState::SHARED_STATE_ITINERARIES) ?? [];

                            if (count($trips) > 0) {
                                $master->addEvent(new TripsFoundEvent($state->account->getAccountid(), count($trips), $trips));
                            } else {
                                $master->addEvent(new TripsNotFoundEvent($state->account->getAccountid()));
                            }
                        }

                        $updateSuccess = true;
                    }

                    if (!isset($updateAborted) && null !== $state->getContextValue('updateLimit')) {
                        isset($updateSuccess)
                            ? $this->throttlerCounter->success(LimitPlugin::PREFIX_KEY_ACCOUNT . $state->account->getAccountid())
                            : $this->throttlerCounter->failure(LimitPlugin::PREFIX_KEY_ACCOUNT . $state->account->getAccountid());
                    }

                    $master->removeAccount($state->account);
                } finally {
                    $this->logger->popContext();
                }
            }
        } finally {
            $this->logger->popContext();
        }
    }
}
