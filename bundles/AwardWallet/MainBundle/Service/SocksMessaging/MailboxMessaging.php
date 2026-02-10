<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelAccessCheckerInterface;

class MailboxMessaging implements MessagingChannelAccessCheckerInterface
{
    public const CHANNEL_MAILBOXES = '$mailboxes';

    private AwTokenStorageInterface $tokenStorage;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function checkChannelAuth(string $channelName): bool
    {
        $parts = explode('_', $channelName);
        [$prefix, $id] = $parts;

        if (count($parts) !== 2 && !(int) $id) {
            return false;
        }

        if ($prefix === self::CHANNEL_MAILBOXES) {
            $currentUser = $this->tokenStorage->getUser();

            if ($currentUser instanceof Usr && $currentUser->getUserid() === (int) $id) {
                return true;
            }

            $businessUser = $this->tokenStorage->getBusinessUser();

            if ($businessUser instanceof Usr && $businessUser->getUserid() === (int) $id) {
                return true;
            }
        }

        return false;
    }
}
