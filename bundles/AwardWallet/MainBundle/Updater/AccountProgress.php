<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;

class AccountProgress
{
    private const CACHE_ACCOUNT_PREFIX = "AccProgress_";
    private const CACHE_LOYALTY_REQUEST_PREFIX = "LoyaltyRequestProgress_";

    private const STATE_NONE = 'none';
    private const STATE_CHECKED = 'checked';
    private const STATE_ERROR = 'error';

    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * @param array $itineraryCodes - like ['R.123', 'L.456', .. ]
     */
    public function finishAccount(int $accountId, ?string $message = null, ?int $code = null, array $itineraryCodes = [])
    {
        $this->saveAccountState($accountId, ['state' => self::STATE_CHECKED, 'message' => $message, 'code' => $code, 'itineraries' => $itineraryCodes]);
    }

    public function finishLoyaltyRequest(string $loyaltyRequestId, ?string $message = null, ?int $code = null, array $itineraryCodes = []): void
    {
        $this->saveLoyaltyRequestState($loyaltyRequestId, ['state' => self::STATE_CHECKED, 'message' => $message, 'code' => $code, 'itineraries' => $itineraryCodes]);
    }

    public function resetAccount(int $accountId)
    {
        $this->saveAccountState($accountId, ['state' => self::STATE_NONE]);
    }

    public function resetLoyaltyRequest(string $loyaltyRequestId): void
    {
        $this->saveLoyaltyRequestState($loyaltyRequestId, ['state' => self::STATE_NONE]);
    }

    public function errorAccount(int $accountId, ?string $message = null, ?int $code = null)
    {
        $this->saveAccountState($accountId, ['state' => self::STATE_ERROR, 'message' => $message, 'code' => $code]);
    }

    public function errorLoyaltyRequest(string $loyaltyRequestId, ?string $message = null, ?int $code = null)
    {
        $this->saveLoyaltyRequestState($loyaltyRequestId, ['state' => self::STATE_ERROR, 'message' => $message, 'code' => $code]);
    }

    public function getAccountInfo(int $accountId): ?ProgressInfo
    {
        return $this->getCacheInfo(self::CACHE_ACCOUNT_PREFIX . $accountId);
    }

    public function getLoyaltyRequestInfo(string $loyaltyRequestId): ?ProgressInfo
    {
        return $this->getCacheInfo(self::CACHE_LOYALTY_REQUEST_PREFIX . $loyaltyRequestId);
    }

    public function onLoyaltyPrepareAccountRequest(LoyaltyPrepareAccountRequestEvent $event)
    {
        $this->resetAccount($event->getAccount()->getId());
    }

    public function onAccountUpdated(AccountUpdatedEvent $event)
    {
        $checkAccountResponse = $event->getCheckAccountResponse();

        if ($checkAccountResponse->getState() === ACCOUNT_TIMEOUT) {
            $this->errorAccount($event->getAccount()->getId(), 'Timed out', ACCOUNT_TIMEOUT);

            if (!\is_null($checkAccountResponse->getRequestid())) {
                $this->errorLoyaltyRequest($checkAccountResponse->getRequestid());
            }

            return;
        }

        $itineraryCodes = \array_map(
            function (Itinerary $itinerary) {
                return $itinerary->getIdString();
            },
            array_merge($event->getSaveReport()->getUpdated(), $event->getSaveReport()->getAdded())
        );

        $this->finishAccount(
            $event->getAccount()->getId(),
            $checkAccountResponse->getMessage(),
            $checkAccountResponse->getState(),
            $itineraryCodes
        );
        $this->finishLoyaltyRequest(
            $checkAccountResponse->getRequestid(),
            $checkAccountResponse->getMessage(),
            $checkAccountResponse->getState(),
            $itineraryCodes
        );
    }

    private function getCacheInfo(string $key): ?ProgressInfo
    {
        $info = $this->memcached->get($key);

        if ($info === false || $info['state'] === self::STATE_NONE) {
            return null;
        }

        return new ProgressInfo($info['state'] === self::STATE_ERROR, $info['message'], $info['code'], $info['itineraries'] ?? []);
    }

    private function saveAccountState(int $accountId, array $state): void
    {
        $this->memcached->set(
            self::CACHE_ACCOUNT_PREFIX . $accountId,
            $state,
            \AccountAuditor::CHECK_TIMEOUT
        );
    }

    private function saveLoyaltyRequestState(string $loyaltyRequestId, array $state): void
    {
        $this->memcached->set(
            self::CACHE_LOYALTY_REQUEST_PREFIX . $loyaltyRequestId,
            $state,
            \AccountAuditor::CHECK_TIMEOUT
        );
    }
}
