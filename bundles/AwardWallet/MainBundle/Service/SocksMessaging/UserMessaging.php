<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelAccessCheckerInterface;

class UserMessaging implements MessagingChannelAccessCheckerInterface
{
    public const CHANNEL_PREFIX = '$user';

    private AwTokenStorageInterface $tokenStorage;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function checkChannelAuth(string $channelName): bool
    {
        $parts = explode('_', $channelName);

        if (count($parts) !== 3) {
            return false;
        }

        [$prefix, $topic, $userId] = $parts;

        if ($prefix !== self::CHANNEL_PREFIX) {
            return false;
        }

        $currentUser = $this->tokenStorage->getUser();

        if (!($currentUser instanceof Usr)) {
            return false;
        }

        if ($currentUser->getUserid() !== (int) $userId) {
            return false;
        }

        return true;
    }

    public static function getChannelName(string $topic, int $userId): string
    {
        if (stripos($topic, '_') !== false) {
            throw new \InvalidArgumentException('topic should not contain _');
        }

        return self::CHANNEL_PREFIX . '_' . $topic . '_' . $userId;
    }
}
