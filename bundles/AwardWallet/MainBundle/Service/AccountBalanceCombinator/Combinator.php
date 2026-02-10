<?php

namespace AwardWallet\MainBundle\Service\AccountBalanceCombinator;

use AwardWallet\MainBundle\Entity\Usr;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Combinator
{
    private AccountLoader $accountLoader;

    private TransferStatLoader $transferStatLoader;

    private ?Usr $lastUser = null;

    private array $transferStats = [];

    private array $accounts = [];

    public function __construct(AccountLoader $accountLoader, TransferStatLoader $transferStatLoader)
    {
        $this->accountLoader = $accountLoader;
        $this->transferStatLoader = $transferStatLoader;
    }

    /**
     * @param Usr $user - user whose accounts will be combined
     * @param int $targetProviderId - provider id of the points we want to get in the end directly or through points transfer from other accounts
     * @param float $targetPoints - number of points we want to get
     */
    public function findCombinations(Usr $user, int $targetProviderId, float $targetPoints): iterable
    {
        if ($user !== $this->lastUser) {
            $this->bootstrap($user, [$targetProviderId]);
        }

        $userAccounts = [];

        foreach ($this->accounts[$targetProviderId] ?? [] as $account) {
            $userAccounts[] = Account::create(
                $account['ID'],
                $account['ProviderID'],
                $account['UserAgent'],
                $account['IsShareable'],
                $account['DisplayName'],
                $account['Balance'],
                $account['AvgPointValue'],
                1,
                null,
                false
            );
        }

        foreach ($this->transferStats[$targetProviderId] ?? [] as $sourceProviderId => $stats) {
            $sourceAccounts = $this->accounts[$sourceProviderId] ?? [];

            foreach ($sourceAccounts as $sourceAccount) {
                if ($stats['minimumTransfer'] > $sourceAccount['Balance'] || $stats['sourceStep'] > $sourceAccount['Balance']) {
                    continue;
                }

                $userAccounts[] = Account::create(
                    $sourceAccount['ID'],
                    $sourceAccount['ProviderID'],
                    $sourceAccount['UserAgent'],
                    $sourceAccount['IsShareable'],
                    $sourceAccount['DisplayName'],
                    $sourceAccount['Balance'],
                    $sourceAccount['AvgPointValue'],
                    $stats['multiplier'],
                    $stats['sourceStep'],
                    true
                );
            }
        }

        yield from $this->calculateCombinations([], 0, $targetPoints, $userAccounts);
    }

    /**
     * Preload accounts and transfer stats for the given user and target providers.
     *
     * @param int[] $targetProviderIds
     */
    public function bootstrap(Usr $user, array $targetProviderIds): void
    {
        $this->lastUser = $user;
        $this->transferStats = $this->transferStatLoader->load($targetProviderIds);
        $affectedProviderIds = $targetProviderIds;

        foreach ($this->transferStats as $sourceProviders) {
            foreach (array_keys($sourceProviders) as $sourceProviderId) {
                $affectedProviderIds[] = $sourceProviderId;
            }
        }

        $affectedProviderIds = array_unique($affectedProviderIds);
        $this->accounts = $this->accountLoader->load($user, $affectedProviderIds);
    }

    /**
     * @param BalanceInterface[] $combination - current combination
     * @param int $value - sum of points in the combination
     * @param int $target - target value
     * @param BalanceInterface[] $items - all available balances to combine
     */
    private function calculateCombinations(array $combination, int $value, int $target, array $items): iterable
    {
        $countItems = count($items);

        for ($i = 0; $i < $countItems; $i++) {
            $account = $items[$i];

            if (in_array($account, $combination)) {
                continue;
            }

            if ($account->getConvertedBalance() >= $target) {
                if (empty($combination)) {
                    yield from $this->combination([$account], true);
                }

                continue;
            }

            $newCombination = array_merge($combination, [$account]);
            $newValue = $value + $account->getConvertedBalance();
            $partial = ($account->getTotalConvertedBalance() - $account->getConvertedBalance()) > 0;

            if ($newValue >= $target) {
                yield from $this->combination(
                    $newCombination,
                    $partial || $newValue != $target
                );
            } elseif (!$partial) {
                yield from $this->calculateCombinations($newCombination, $newValue, $target, $items);
            }
        }
    }

    /**
     * @param BalanceInterface[] $combination
     */
    private function combination(array $combination, bool $partial): iterable
    {
        if ($partial) {
            /** @var BalanceInterface $last */
            $last = array_pop($combination);
        }

        $combinationIt = it($combination)
            ->usort(fn (BalanceInterface $a, BalanceInterface $b) => $a->getId() <=> $b->getId());

        if (isset($last)) {
            $combinationIt->chain([$last]);
        }

        $result = $combinationIt->toArray();

        yield $this->combinationKey($result) => $result;
    }

    /**
     * @param BalanceInterface[] $combination
     */
    private function combinationKey(array $combination): string
    {
        return implode('-', array_map(fn (BalanceInterface $account) => $account->getId(), $combination));
    }
}
