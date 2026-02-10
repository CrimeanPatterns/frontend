<?php

namespace AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter;

interface MessagingChannelAccessCheckerInterface
{
    public function checkChannelAuth(string $channelName): bool;
}
