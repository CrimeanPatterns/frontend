<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use Psr\Log\LoggerInterface;

class AccountTotalCalculator
{
    public const USD_CURRENCY = ['USD', '$', 'US$'];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function calculate(Account $account): float
    {
        $accountBalance = $account->getBalance();
        $balanceFormat = $account->getProviderid() ? $account->getProviderid()->getBalanceformat() : null;
        $allowFloat = !$account->getProviderid() || $account->getProviderid()->getAllowfloat();
        $specificTotal = $account->hasBalanceInTotalSumProperty();
        $accountTotal = 0;
        $subAccountTotal = 0;

        if (!is_null($accountBalance)) {
            $currency = $account->getAccountPropertyByCode('Currency');
            $isCustomProvider = empty($account->getProviderid());

            if (
                !$isCustomProvider
                && (
                    !$currency
                    || !in_array($currency, self::USD_CURRENCY)
                )
                && (
                    !$balanceFormat
                    || substr($balanceFormat, 0, 2) !== '$%'
                )
            ) {
                $accountTotal = filterBalance($accountBalance, $allowFloat);
            }
        }

        foreach ($account->getSubAccountsEntities() as $subAccount) {
            if (
                !is_null($balance = $subAccount->getBalance())
                && (!$subAccount->getIsHidden() || $specificTotal)
                && !preg_match('/\w+FICO$/', $subAccount->getCode())
            ) {
                $balanceInTotalSum = $subAccount->getPropertyByCode('BalanceInTotalSum');
                $currency = $subAccount->getPropertyByCode('Currency');

                if (
                    (
                        !$currency
                        || !in_array($currency, self::USD_CURRENCY)
                    ) && (
                        !$balanceFormat
                        || substr($balanceFormat, 0, 2) !== '$%'
                    ) && (
                        !$specificTotal
                        || $this->isPropertyTrue($balanceInTotalSum)
                    )
                ) {
                    $subAccountTotal += filterBalance($balance, $allowFloat);
                }
            }
        }

        if ($specificTotal) {
            $accountTotal = intval($accountTotal);
            $subAccountTotal = intval($subAccountTotal);

            if ($accountTotal !== $subAccountTotal) {
                $this->logger->warning(sprintf('main balance is not equal to the sum of sub-account balances, accountId: %d, "%d" <> "%d"', $account->getId(), $accountTotal, $subAccountTotal));
            }

            return max($accountTotal, 0);
        }

        return max(intval($accountTotal + $subAccountTotal), 0);
    }

    private function isPropertyTrue($prop): bool
    {
        return !is_null($prop) && (
            (int) $prop === 1
            || $prop === 'true'
        );
    }
}
