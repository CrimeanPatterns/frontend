<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\Property;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CheckAccountResponsePreparer
{
    private IndirectAccountUpdater $indirectAccountUpdater;

    private LoggerInterface $logger;

    public function __construct(IndirectAccountUpdater $indirectAccountUpdater, LoggerInterface $logger)
    {
        $this->indirectAccountUpdater = $indirectAccountUpdater;
        $this->logger = $logger;
    }

    public function prepare(Account $account, CheckAccountResponse $response, bool $filterSubAccounts = true)
    {
        if (in_array($response->getState(), [ACCOUNT_CHECKED, ACCOUNT_WARNING])) {
            if ($filterSubAccounts) {
                $this->filterSubAccounts($account, $response);
            }
            $this->indirectAccountUpdater->tryUpdateExistingAccounts($account, $response);
        }
    }

    /**
     * - if there is single subaccount - copy it to main balance and discard
     * - discard subaccounts with same non-zero, divided to 10 with reminder, balance.
     */
    private function filterSubAccounts(Account $account, CheckAccountResponse $response)
    {
        $this->logger->info(sprintf('filter subaccounts, account %d', $account->getId()));
        $subAccounts = is_array($response->getSubaccounts()) ? $response->getSubaccounts() : [];

        if (count($subAccounts) === 0) {
            return;
        }

        $responseProps = is_array($response->getProperties()) ? $response->getProperties() : [];

        // discard subaccounts with same non-zero, not lower than 50, divided to 10 with reminder, balance
        foreach ($subAccounts as $i => &$subAccount) {
            $balance = $subAccount->getBalance();
            $subPropsMap = $this->mapProperties($subAccount->getProperties());

            if (!is_null($balance) && floatval($balance) >= 50 && (floatval($balance) % 10) > 0) {
                foreach ($subAccounts as $matchKey => &$matchedSubAccount) {
                    $matchedBalance = $matchedSubAccount->getBalance();

                    if (
                        $i != $matchKey
                        && !is_null($matchedBalance)
                        && $matchedBalance === $balance
                        && $matchedSubAccount->getDisplayname() === $subAccount->getDisplayname()
                        && (!isset($subPropsMap['Kind']) || ($subPropsMap['Kind']->getValue() !== 'C'))
                    ) {
                        unset($subAccounts[$matchKey]);
                    }
                }
            }
        }

        $response->setSubaccounts($subAccounts);

        // convert single subaccount to main account
        $propsMap = $this->mapProperties($responseProps);

        if (
            count($subAccounts) === 1 && is_null($response->getBalance())
            && (!isset($propsMap['CombineSubAccounts']) || $propsMap['CombineSubAccounts']->getValue())
        ) {
            $this->logger->info('try combine subaccounts');
            $sa = array_pop($subAccounts); // do not assume that subaccounts have numeric keys
            $subProps = is_array($sa->getProperties()) ? $sa->getProperties() : [];
            $subPropsMap = $this->mapProperties($subProps);

            if (!isset($subPropsMap['Kind']) || ($subPropsMap['Kind']->getValue() !== 'C')) {
                if (!is_null($sa->getBalance())) {
                    // all conditions matched, discard subaccount, copy to main
                    $response->setBalance($newBalance = filterBalance($sa->getBalance(), $account->getProviderid()->getAllowfloat()));
                    $this->logger->info(sprintf('combine subaccounts, new main balance: %s', $newBalance));

                    if ($subExpirationDate = $sa->getExpirationDate()) {
                        $response->setExpirationDate($subExpirationDate);
                        $account->setExpirationautoset(EXPIRATION_FROM_SUBACCOUNT);
                    }

                    $response->setProperties($subProps);
                    $response->setSubaccounts([]);
                }
            }
        }
    }

    /**
     * @return Property[]
     */
    private function mapProperties(?array $props): array
    {
        if (!is_array($props)) {
            return [];
        }

        return it($props)->reindexByPropertyPath('code')->toArrayWithKeys();
    }
}
