<?php

namespace AwardWallet\MainBundle\Service\AccountCounter;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Summary
{
    private int $userId;

    private iterable $items;

    private bool $initialized;

    private array $users;

    public function __construct(int $userId, iterable $items)
    {
        $this->userId = $userId;
        $this->items = $items;
        $this->initialized = false;
    }

    /**
     * @param int|null $userAgentId "0" for my accounts, null for all accounts
     */
    public function getCount(?int $userAgentId = null): int
    {
        $this->init();

        if (is_null($userAgentId)) {
            $count = 0;

            foreach ($this->users as $user) {
                $count += count($user['accounts']) + count($user['coupons']);
            }

            return $count;
        }

        if ($userAgentId === 0) {
            return count($this->users['my']['accounts']) + count($this->users['my']['coupons']);
        }

        return
            count($this->users[$userAgentId]['accounts'] ?? [])
            + count($this->users[$userAgentId]['coupons'] ?? []);
    }

    public function getCountAccounts(?int $userAgentId = null): int
    {
        $this->init();

        if (is_null($userAgentId)) {
            $count = 0;

            foreach ($this->users as $user) {
                $count += count($user['accounts']);
            }

            return $count;
        }

        if ($userAgentId === 0) {
            return count($this->users['my']['accounts']);
        }

        return count($this->users[$userAgentId]['accounts'] ?? []);
    }

    /**
     * @param int[] $providerIds
     */
    public function getCountAccountsByProviderIds(array $providerIds, ?int $userAgentId = null): int
    {
        $this->init();

        if (is_null($userAgentId)) {
            $count = 0;

            foreach ($this->users as $user) {
                foreach ($providerIds as $providerId) {
                    $count += count($user['providers'][$providerId] ?? []);
                }
            }

            return $count;
        }

        if ($userAgentId === 0) {
            $count = 0;

            foreach ($providerIds as $providerId) {
                $count += count($this->users['my']['providers'][$providerId] ?? []);
            }

            return $count;
        }

        $count = 0;

        foreach ($providerIds as $providerId) {
            $count += count($this->users[$userAgentId]['providers'][$providerId] ?? []);
        }

        return $count;
    }

    public function getCountCoupons(?int $userAgentId = null): int
    {
        $this->init();

        if (is_null($userAgentId)) {
            $count = 0;

            foreach ($this->users as $user) {
                $count += count($user['coupons']);
            }

            return $count;
        }

        if ($userAgentId === 0) {
            return count($this->users['my']['coupons']);
        }

        return count($this->users[$userAgentId]['coupons'] ?? []);
    }

    public function getDebugInfo(): array
    {
        $this->init();

        return $this->users;
    }

    private function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->users = [
            'my' => [
                'userAgentId' => 'my',
                'ownerUserId' => $this->userId,
                'ownerUserAgentId' => null,
                'accounts' => [],
                'coupons' => [],
                'couponsWithAccount' => [],
                'providers' => [],
            ],
        ];
        $coupons = [];

        foreach ($this->items as $item) {
            if ($item['UserID'] == $this->userId) {
                $key = is_null($item['UserAgentID']) ? 'my' : $item['UserAgentID'];
            } else {
                $key = $item['SharedUserAgentID'];
            }

            if (!isset($this->users[$key])) {
                $this->users[$key] = [
                    'userAgentId' => $key,
                    'ownerUserId' => $item['UserID'],
                    'ownerUserAgentId' => $item['UserAgentID'],
                    'accounts' => [],
                    'coupons' => [],
                    'couponsWithAccount' => [],
                    'providers' => [],
                ];
            }

            if ($item['TableName'] === 'Account') {
                $this->users[$key]['accounts'][] = $item['ID'];

                if (!empty($item['ProviderID'])) {
                    if (!isset($this->users[$key]['providers'][$item['ProviderID']])) {
                        $this->users[$key]['providers'][$item['ProviderID']] = [];
                    }

                    $this->users[$key]['providers'][$item['ProviderID']][] = $item['ID'];
                }
            } elseif ($item['TableName'] === 'Coupon') {
                if (is_null($item['ParentID'])) {
                    $this->users[$key]['coupons'][] = $item['ID'];
                } else {
                    $coupons[] = [$key, $item];
                }
            }
        }

        foreach ($coupons as [$key, $item]) {
            $found = false;

            foreach ($this->users as $user) {
                if (in_array($item['ParentID'], $user['accounts'])) {
                    $this->users[$key]['couponsWithAccount'][] = [
                        'couponId' => $item['ID'],
                        'accountId' => $item['ParentID'],
                    ];
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                $this->users[$key]['coupons'][] = $item['ID'];
            }
        }

        $this->initialized = true;
    }
}
