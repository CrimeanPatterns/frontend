<?php

namespace AwardWallet\MainBundle\Service\AccountBalanceCombinator;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\AmericanAirlinesAAdvantageDetector;

class AccountLoader
{
    private AccountListManager $accountListManager;

    private OptionsFactory $optionsFactory;

    public function __construct(AccountListManager $accountListManager, OptionsFactory $optionsFactory)
    {
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @param int[] $providerIds
     * @return [][] - array of accounts grouped by providerId
     *
     * will return AA accounts if Provider::AA_ID is in $providerIds
     */
    public function load(Usr $user, array $providerIds): array
    {
        if (empty($providerIds)) {
            throw new \InvalidArgumentException('targetProviderIds cannot be empty');
        }

        if (in_array(Provider::AA_ID, $providerIds)) {
            $aaFilter = sprintf(' OR (a.ProviderID IS NULL AND %s)', AmericanAirlinesAAdvantageDetector::getSQLFilter('a'));
        } else {
            $aaFilter = '';
        }

        $filter = sprintf(
            " 
                AND (a.ProviderID IN (%s)%s)
                AND a.Balance IS NOT NULL 
                AND a.Balance <> '' 
                AND a.Balance > 0
            ",
            implode(',', $providerIds),
            $aaFilter
        );

        $accountList = $this->accountListManager->getAccountList(
            $this->optionsFactory->createDesktopListOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $user)
                    ->set(Options::OPTION_FILTER, $filter)
                    ->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1')
                    ->set(Options::OPTION_LOAD_MILE_VALUE, true)
                    ->set(Options::OPTION_LOAD_SUBACCOUNTS, false)
                    ->set(Options::OPTION_LOAD_CARD_IMAGES, false)
                    ->set(Options::OPTION_LOAD_LOYALTY_LOCATIONS, false)
                    ->set(Options::OPTION_LOAD_HISTORY_PRESENCE, false)
                    ->set(Options::OPTION_LOAD_BALANCE_CHANGES_COUNT, false)
                    ->set(Options::OPTION_LOAD_PENDING_SCAN_DATA, false)
                    ->set(Options::OPTION_LOAD_BLOG_POSTS, false)
                    ->set(Options::OPTION_LOAD_MERCHANT_RECOMMENDATIONS, false)
            )
        );
        $result = [];

        foreach ($accountList as $account) {
            if (empty($account['ProviderID'])) {
                $providerId = Provider::AA_ID;
            } else {
                $providerId = (int) $account['ProviderID'];
            }

            if (isset($account['AccountOwner']) && is_numeric($account['AccountOwner'])) {
                $ua = (int) $account['AccountOwner'];
                $isShareable = (bool) $account['IsShareable'];
            } else {
                $ua = null;
                $isShareable = true;
            }

            if (!isset($result[$providerId])) {
                $result[$providerId] = [];
            }

            $result[$providerId][] = [
                'ID' => (int) $account['ID'],
                'ProviderID' => $providerId,
                'UserAgent' => $ua,
                'IsShareable' => $isShareable,
                'DisplayName' => $account['DisplayName'],
                'Balance' => $account['BalanceRaw'],
                'AvgPointValue' => $account['MileValue']['awEstimate']['raw'] ?? null,
            ];
        }

        return $result;
    }
}
