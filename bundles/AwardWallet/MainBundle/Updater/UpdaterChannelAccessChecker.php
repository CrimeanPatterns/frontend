<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelAccessCheckerInterface;

class UpdaterChannelAccessChecker implements MessagingChannelAccessCheckerInterface
{
    private UpdaterSessionStorage $updaterSessionStorage;
    private EventsChannelMigrator $eventsChannelMigrator;
    private AwTokenStorageInterface $tokenStorage;
    private EventsChannelMigrator $channelMigrator;

    public function __construct(
        UpdaterSessionStorage $updaterSessionStorage,
        EventsChannelMigrator $eventsChannelMigrator,
        AwTokenStorageInterface $tokenStorage,
        EventsChannelMigrator $channelMigrator
    ) {
        $this->updaterSessionStorage = $updaterSessionStorage;
        $this->eventsChannelMigrator = $eventsChannelMigrator;
        $this->tokenStorage = $tokenStorage;
        $this->channelMigrator = $channelMigrator;
    }

    public function checkChannelAuth(string $channelName): bool
    {
        if (!(\strpos($channelName, UpdaterSessionManager::MESSAGING_CHANNEL_PREFIX) === 0)) {
            return false;
        }

        $user = $this->tokenStorage->getUser();

        if (!($user instanceof Usr) || $this->channelMigrator->isJustMigrated()) {
            // check in anonymous mobile browser tab
            return $this->eventsChannelMigrator->validate($channelName);
        }

        if (!$user) {
            return false;
        }

        $sessionKey = \substr($channelName, \strlen(UpdaterSessionManager::MESSAGING_CHANNEL_PREFIX));
        $sessionMeta = $this->updaterSessionStorage->loadSessionData($sessionKey);

        return
            $sessionMeta
            && ($sessionMeta->getUserId() === $user->getId());
    }
}
