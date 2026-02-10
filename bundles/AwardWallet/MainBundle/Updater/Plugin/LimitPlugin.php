<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\FrameworkExtension\Translator\TransChoice;
use AwardWallet\MainBundle\Globals\ThrottlerCounter;
use AwardWallet\MainBundle\Updater\Event\LimitEvent;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LimitPlugin extends AbstractPlugin implements TranslationContainerInterface
{
    use PluginIdentity;

    public const ID = 'limit';

    public const PREFIX_KEY_ACCOUNT = 'account_update_';

    private ThrottlerCounter $throttlerCounter;
    private \Memcached $cache;
    private TranslatorInterface $translator;

    public function __construct(
        TranslatorInterface $translator,
        \Memcached $memcached
    ) {
        $this->translator = $translator;
        $this->throttlerCounter = new ThrottlerCounter($memcached, 86400);

        $this->throttlerCounter->setConditions([
            ['success' => 2],
            ['success' => 1, 'failure' => 5],
            ['failure' => 100],
        ]);
    }

    public function tick(MasterInterface $master, $accountStates): void
    {
        foreach ($accountStates as $state) {
            $updateLimit = $state->getContextValue('updateLimit');

            if (null === $updateLimit) {
                $state->popPlugin();

                continue;
            }

            $accountKey = self::PREFIX_KEY_ACCOUNT . $state->account->getAccountid();

            if ($this->throttlerCounter->throttle($accountKey)) {
                $wait = $this->throttlerCounter->getDelay($accountKey);
                $errorMessage = $this->translator->trans('updater2.messages.error.limit', [
                    '%timeleft%' => $this->getTimeleft($wait),
                ]);
                $master->addEvent(new LimitEvent($state->account->getAccountid(), $errorMessage));
                $master->removeAccount($state->account);
            }

            $state->popPlugin();
        }
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('updater2.messages.error.limit'))->setDesc('Account update limit has been reached. You\'ve already updated this account twice in the last 24 hours, please upgrade to AwardWallet Plus if you wish to be able to update your accounts more frequently or wait %timeleft% before updating this account again.'),
            (new Message('upgrade-now'))->setDesc('Upgrade now'),
        ];
    }

    private function getTimeleft(int $seconds): string
    {
        $hours = (int) ($seconds / 60 / 60);

        if ($hours >= 1) {
            $time = new TransChoice('hours', $hours, ['%count%' => $hours], 'messages');

            return $time->trans($this->translator);
        }

        $minutes = (int) ($seconds / 60);
        $minutes > 1 ?: $minutes = 1;
        $time = new TransChoice('minutes', $minutes, ['%count%' => $minutes], 'messages');

        return $time->trans($this->translator);
    }
}
