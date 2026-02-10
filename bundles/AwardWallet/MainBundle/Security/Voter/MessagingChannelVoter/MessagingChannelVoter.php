<?php

namespace AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter;

use AwardWallet\MainBundle\Security\Voter\AbstractVoter;
use AwardWallet\MainBundle\Security\Voter\Subject\MessagingChannel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MessagingChannelVoter extends AbstractVoter
{
    public const ATTRIBUTE_READ = 'READ';
    /**
     * @var iterable<MessagingChannelAccessCheckerInterface>
     */
    private $channelCheckers;

    /**
     * @param iterable<MessagingChannelAccessCheckerInterface> $channelCheckers
     */
    public function __construct(
        ContainerInterface $container,
        iterable $channelCheckers
    ) {
        parent::__construct($container);

        $this->container = $container;
        $this->channelCheckers = $channelCheckers;
    }

    public function canRead(TokenInterface $token, MessagingChannel $channel)
    {
        $channelName = $channel->getName();

        /** @var MessagingChannelAccessCheckerInterface $channelChecker */
        foreach ($this->channelCheckers as $channelChecker) {
            if ($channelChecker->checkChannelAuth($channelName)) {
                return true;
            }
        }

        return false;
    }

    protected function getClass()
    {
        return MessagingChannel::class;
    }

    protected function getAttributes()
    {
        return [
            self::ATTRIBUTE_READ => [$this, 'canRead'],
        ];
    }
}
