<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountInfo\Info;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\Mapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use Doctrine\ORM\EntityManagerInterface;

class RecentlyList
{
    protected $em;

    protected $accountListManager;

    protected $accountInfo;
    /**
     * @var OptionsFactory
     */
    private $accountListOptionsFactory;

    /**
     * RecentlyList constructor.
     */
    public function __construct(EntityManagerInterface $em, AccountListManager $accountListManager, OptionsFactory $accountListOptionsFactory, Info $info)
    {
        $this->em = $em;
        $this->accountListManager = $accountListManager;
        $this->accountInfo = $info;
        $this->accountListOptionsFactory = $accountListOptionsFactory;
    }

    /**
     * @param Useragent|string|null $useragent
     * @param string $filter
     */
    public function getAccounts(
        Usr $user,
        $useragent = null,
        \DateTime $begin,
        \DateTime $end,
        $filter = ''
    ) {
        $options = $this->accountListOptionsFactory->createDefaultOptions()
            ->set(Options::OPTION_USER, $user)
            ->set(Options::OPTION_CHANGE_PERIOD, $begin->format('Y-m-d H:i:s'));

        /** @var Mapper $mapper */
        if (isset($useragent) && $useragent instanceof Useragent) {
            $options->set(Options::OPTION_USERAGENT, $useragent->getUseragentid());
        } elseif (isset($useragent)) {
            $options->set(Options::OPTION_USERAGENT, 'my' === $useragent ? 0 : $useragent);
        }
        $options
            ->set(Options::OPTION_LOAD_PHONES, Options::VALUE_PHONES_NOLOAD)
            ->set(Options::OPTION_LOAD_SUBACCOUNTS, true)
            ->set(Options::OPTION_LOAD_PROPERTIES, true)
            ->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1')
            ->set(Options::OPTION_ORDERBY, Options::VALUE_ORDERBY_LASTCHANGEDATE_DESC)
            ->set(Options::OPTION_FILTER, $filter
                . " AND a.AccountID IN
                    (SELECT
                        AccountID
                    FROM
                        AccountBalance
                    WHERE
                        UpdateDate >= '" . $begin->format('Y-m-d H:i:s') . "'
                        AND UpdateDate <= '" . $end->format('Y-m-d H:i:s') . "'
                    )"
            );
        $list = $this->accountListManager->getAccountList($options);
        $result = [];

        foreach ($list as $account) {
            // decompose by subaccounts
            $saccounts = [];

            if (isset($account['SubAccountsArray']) && is_array($account['SubAccountsArray'])) {
                foreach ($account['SubAccountsArray'] as $sub) {
                    if (
                        isset($sub['LastChangeDateTs']) && $sub['LastChangeDateTs'] >= $begin->getTimestamp()
                        && $sub['LastChangeDateTs'] <= $end->getTimestamp()
                        && (!isset($sub['isCoupon']) || !$sub['isCoupon'])
                    ) {
                        $saccounts[] = $sub;
                    }
                }
            }

            if (isset($account['MainProperties']['Login'])) {
                $account['MainProperties']['Login'] = $this->hideNumber($account['MainProperties']['Login']);
            }

            if (!sizeof($saccounts)) {
                if (!empty(getChangedAccounts($account['ID'], $begin->getTimestamp(), $end->getTimestamp()))) {
                    $result[] = array_merge($account, [
                        'isSubaccount' => false,
                        'balanceChart' => $this->getBalanceChart($account['ID']),
                    ]);
                }
            } else {
                $foradd = [];

                if ((int) $account['ChangeCount'] > 0) {
                    $foradd[] = array_merge($account, [
                        'isSubaccount' => false,
                        'balanceChart' => $this->getBalanceChart($account['ID']),
                    ]);
                }
                unset($account['Balance'], $account['LastChangeDate'], $account['LastChangeDateTs'], $account['LastChangeDateFrendly'],
                    $account['ChangedPositive'], $account['LastChange'], $account['ChangedOverPeriodPositive'],
                    $account['SubAccountsArray']
                );

                foreach ($saccounts as $sub) {
                    if ((int) $sub['ChangeCount'] === 0) {
                        continue;
                    }
                    $sub = array_merge($account, $sub);

                    if (isset($sub['MainProperties']['Login'])) {
                        $sub['MainProperties']['Login'] = $this->hideNumber($sub['MainProperties']['Login']);
                    }

                    if ($account['DisplayName'] != $sub['DisplayName']) {
                        $sub['SubDisplayName'] = $sub['DisplayName'];
                        $sub['DisplayName'] = $account['DisplayName'];
                    }
                    $sub['Kind'] = $account['Kind'];

                    if (!isset($sub['Properties']) || !is_array($sub['Properties'])) {
                        $sub['Properties'] = [];
                    }

                    if (isset($account['Properties'])) {
                        $sub['Properties'] = array_merge($sub['Properties'], $account['Properties']);
                    }
                    $foradd[] = array_merge($sub, [
                        'isSubaccount' => true,
                        'balanceChart' => $this->getBalanceChart($account['ID'], $sub['SubAccountID']),
                    ]);
                }
                usort($foradd, function ($a, $b) {
                    return $a['LastChangeDateTs'] <=> $b['LastChangeDateTs'];
                });
                $result = array_merge($result, $foradd);
            }
        }

        foreach ($result as $k => $row) {
            if (!isset($row['ChangedOverPeriodPositive'])
                || !isset($row['LastBalance'])
                || !isset($row['LastChange'])
                || !isset($row['Balance'])
            ) {
                unset($result[$k]);
            }
        }

        return $result;
    }

    private function getBalanceChart($accountId, $subAccountId = null)
    {
        return $this->accountInfo->getAccountBalanceChartQuery(
            $accountId,
            7,
            isset($subAccountId) ? (int) $subAccountId : null
        );
    }

    private function hideNumber($number, $showLast = 4)
    {
        if (preg_match("/^.+\@\S+\.\S+$/", $number)) {
            return preg_replace("/@|\\./ims", '&#8203;$0', $number);
        }

        if (preg_match("/(xxxx\s|\*)/ims", $number)) {
            return $number;
        }

        if (is_numeric($number)) {
            $strlen = strlen($number);

            if ($strlen <= $showLast) {
                return $number;
            }
            $str = '';

            for ($i = 0; $i < $strlen; $i++) {
                if ($i < $strlen - $showLast) {
                    $str .= '*';
                } else {
                    $str .= $number[$i];
                }
            }

            return $str;
        }

        return $number;
    }
}
