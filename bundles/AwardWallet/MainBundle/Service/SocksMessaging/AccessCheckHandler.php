<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelVoter;
use AwardWallet\MainBundle\Security\Voter\Subject\MessagingChannel;
use AwardWallet\MainBundle\Service\FriendsOfLoggerTrait;
use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccessCheckHandler
{
    use FriendsOfLoggerTrait;
    private ClientInterface $messagingClient;
    private AuthorizationCheckerInterface $authorizationChecker;
    private BinaryLoggerFactory $binaryLoggerFactory;

    public function __construct(
        ClientInterface $messagingClient,
        LoggerInterface $securityLogger,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->messagingClient = $messagingClient;
        $this->authorizationChecker = $authorizationChecker;
        $contextLogger = $this
            ->makeContextAwareLogger($securityLogger)
            ->setMessagePrefix('centrifuge channel access check: ');
        $this->binaryLoggerFactory = $this->makeBinaryLoggerFactory($contextLogger, new Slugify(['separator' => '_']));
    }

    public function checkAuth(object $accessRequest): array
    {
        if (!\is_array($accessRequest->channels ?? null)) {
            throw new BadRequestHttpException('Invalid channels');
        }

        if (!isset($accessRequest->client)) {
            throw new BadRequestHttpException('Invalid client');
        }

        $log = $this->binaryLoggerFactory;
        $accessResult = [];

        foreach ($accessRequest->channels as $idx => $channel) {
            $accessGranted = $this->checkAccess($channel);
            $log
                ->that('access')
                ->is("granted")
                ->with(
                    "for channel idx: {$idx}, (sha256, first 32 chars, hexed salt: "
                    . \bin2hex($salt = \random_bytes(16)) . "): "
                    . \substr(\hash('sha256', $salt . $channel), 0, 32)
                )
                ->on($accessGranted);

            if ($accessGranted) {
                $accessResult[$channel] = [
                    'sign' => $this->messagingClient->generateChannelSign($accessRequest->client, $channel),
                    'info' => '',
                ];
            } else {
                $accessResult[$channel] = [
                    'status' => 403,
                ];
            }
        }

        return $accessResult;
    }

    public function makeFailResult(object $accessRequest): array
    {
        if (!\is_array($accessRequest->channels ?? null)) {
            throw new BadRequestHttpException('Invalid channels');
        }

        $accessResult = [];

        foreach ($accessRequest->channels as $channel) {
            $accessResult[$channel] = [
                'status' => 403,
            ];
        }

        return $accessResult;
    }

    protected function checkAccess(string $channel): bool
    {
        return $this->authorizationChecker->isGranted(MessagingChannelVoter::ATTRIBUTE_READ, new MessagingChannel($channel));
    }
}
