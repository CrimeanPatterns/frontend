<?php

namespace AwardWallet\MainBundle\Service\SocksMessaging;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Security\Voter\MessagingChannelVoter\MessagingChannelAccessCheckerInterface;

class BookingMessaging implements MessagingChannelAccessCheckerInterface
{
    public const CHANNEL_MESSAGES = '$abrequestmessages';
    public const CHANNEL_ONLINE = '$abrequestonline';
    public const CHANNEL_BOOKER_ONLINE = '$booker';
    public const CHANNEL_USER_MESSAGES = '$bookinguser';

    private AbRequestRepository $abRequestRepository;

    private UsrRepository $usrRepository;

    private AwTokenStorageInterface $tokenStorage;

    public function __construct(
        AbRequestRepository $requestRepository,
        UsrRepository $usrRepository,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->abRequestRepository = $requestRepository;
        $this->usrRepository = $usrRepository;
        $this->tokenStorage = $tokenStorage;
    }

    public function getChannels(AbRequest $request, Usr $user): array
    {
        return [
            self::CHANNEL_MESSAGES => $this->getMessagesChannel($request),
            self::CHANNEL_ONLINE => $this->getOnlineChannel($request),
            self::CHANNEL_BOOKER_ONLINE => $this->getBookerOnlineChannel($request),
            self::CHANNEL_USER_MESSAGES => $this->getUserMessagesChannel($user),
        ];
    }

    public function getMessagesChannel(AbRequest $request): string
    {
        return self::CHANNEL_MESSAGES . '_' . $request->getAbRequestID();
    }

    public function getOnlineChannel(AbRequest $request): string
    {
        return self::CHANNEL_ONLINE . '_' . $request->getAbRequestID();
    }

    public function getBookerOnlineChannel(AbRequest $request): string
    {
        return self::CHANNEL_BOOKER_ONLINE . '_' . $request->getAbRequestID();
    }

    public function getUserMessagesChannel(Usr $user): string
    {
        return self::CHANNEL_USER_MESSAGES . '_' . $user->getUserid();
    }

    public function checkChannelAuth(string $channelName): bool
    {
        $parts = explode('_', $channelName);
        [$prefix, $id] = $parts;

        if (count($parts) !== 2 && !(int) $id) {
            return false;
        }

        $currentUser = $this->tokenStorage->getUser();

        if (!$currentUser instanceof Usr) {
            return false;
        }

        if (in_array($prefix, [self::CHANNEL_MESSAGES, self::CHANNEL_ONLINE, self::CHANNEL_BOOKER_ONLINE], true)) {
            /** @var AbRequest $request */
            $request = $this->abRequestRepository->find($id);

            if ($request) {
                $requestUser = $request->getUser();
                $requestBooker = $request->getBooker();
                $currentBusinessUser = $this->tokenStorage->getBusinessUser();

                if ($requestUser instanceof Usr && $currentUser instanceof Usr && $requestUser->getUserid() === $currentUser->getUserid()) {
                    return true;
                }

                if ($requestBooker instanceof Usr && $currentBusinessUser instanceof Usr && $requestBooker->getUserid() === $currentBusinessUser->getUserid()) {
                    return true;
                }
            }
        }

        if ($prefix === self::CHANNEL_USER_MESSAGES) {
            if ($currentUser instanceof Usr && $currentUser->getUserid() === (int) $id) {
                return true;
            }
        }

        return false;
    }
}
