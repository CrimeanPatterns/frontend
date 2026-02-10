<?php

namespace AwardWallet\MainBundle\Service\AccountAccessApi;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AccountAccessApi\Model\AuthState;

class AuthStateManager
{
    private const STATE_CACHE_PREFIX = "api_auth_state_";
    private const SUCESS_CACHE_PREFIX = "api_sucess_";

    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    public function save(AuthState $authState): string
    {
        $authKey = StringUtils::getRandomCode(20);

        if (!$this->memcached->add(
            self::STATE_CACHE_PREFIX . $authKey,
            ["state" => $authState->getState(), "access" => $authState->getAccessLevel(), "businessId" => $authState->getBusinessId()], 600
        )) {
            throw new \Exception("Duplicate key / cache error");
        }

        return $authKey;
    }

    public function loadAuthState(Usr $business, string $authKey, int $accessLevel): ?AuthState
    {
        if ($business->getBusinessInfo()->getApiVersion() == 2) {
            $authState = $this->load($authKey);

            if (
                $authState === null
                || $authState->getBusinessId() !== $business->getUserid()
                || $authState->getAccessLevel() !== $accessLevel
            ) {
                return null;
            }

            return $authState;
        }

        return new AuthState($accessLevel, $business->getUserid(), null);
    }

    public function getSuccessUrl(Usr $business, Usr $user, AuthState $authState)
    {
        $callbackUrl = $this->buildCallbackUrl($business, $authState);

        if ($business->getBusinessInfo()->getApiVersion() == 2) {
            $code = StringUtils::getRandomCode(20);
            $this->memcached->set(self::SUCESS_CACHE_PREFIX . $code, json_encode(["userId" => $user->getUserid(), 'businessId' => $business->getUserid()]), 60);

            return $this->addParamsToUrl($callbackUrl, ['code' => $code]);
        }

        return $this->addParamsToUrl($callbackUrl, ['userId' => $user->getUserid()]);
    }

    public function getDenyUrl(Usr $business, AuthState $authState)
    {
        return $this->addParamsToUrl($this->buildCallbackUrl($business, $authState), ['denyAccess' => '1']);
    }

    public function getAuthUserId(Usr $business, string $code): ?int
    {
        $state = $this->memcached->get(self::SUCESS_CACHE_PREFIX . $code);

        if ($state === false) {
            return null;
        }

        $params = json_decode($state, true);

        if ($params['businessId'] !== $business->getUserid()) {
            return null;
        }

        return $params['userId'];
    }

    private function buildCallbackUrl(Usr $business, AuthState $authState): string
    {
        $callbackUrl = $business->getBusinessInfo()->getApiCallbackUrl();

        if ($business->getBusinessInfo()->getApiVersion() == 2) {
            if ($authState->getState() !== null) {
                return $this->addParamsToUrl($callbackUrl, ["state" => $authState->getState()]);
            }
        }

        return $callbackUrl;
    }

    private function addParamsToUrl(string $url, array $params): string
    {
        if (!empty($params)) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= http_build_query($params);
        }

        return $url;
    }

    private function load(string $authKey): ?AuthState
    {
        $data = $this->memcached->get(self::STATE_CACHE_PREFIX . $authKey);

        if (!is_array($data)) {
            return null;
        }

        return new AuthState($data['access'], $data['businessId'], $data['state']);
    }
}
