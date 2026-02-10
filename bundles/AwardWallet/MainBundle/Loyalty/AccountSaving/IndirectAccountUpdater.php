<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\Property;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccount;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class IndirectAccountUpdater
{
    public const PROP_PROVIDER_CODE = 'ProviderCode';
    public const PROP_ACCOUNT_NUMBER = 'ProviderAccountNumber';
    public const PROP_USER_NAME = 'ProviderUserName';

    private EntityManagerInterface $em;

    private BalanceProcessor $balanceProcessor;

    private AccountFinder $accountFinder;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        BalanceProcessor $balanceProcessor,
        AccountFinder $accountFinder,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->balanceProcessor = $balanceProcessor;
        $this->accountFinder = $accountFinder;
        $this->logger = $logger;
    }

    /**
     * - discard subaccount when there is same provider as different account.
     */
    public function tryUpdateExistingAccounts(Account $account, CheckAccountResponse $response)
    {
        $providerCode = $account->getProviderid()->getCode();
        $this->logger->info(sprintf(
            'indirect account updater, via account %d, provider: %s',
            $account->getId(),
            $providerCode
        ));
        $subAccounts = is_array($response->getSubaccounts()) ? $response->getSubaccounts() : [];
        $responseProps = is_array($response->getProperties()) ? $response->getProperties() : [];

        if (count($subAccounts) > 0) {
            $subAccountUpdated = false;

            foreach ($subAccounts as $i => &$subAccount) {
                $subAccountId = sprintf('%d.%s', $account->getId(), $subAccount->getCode());
                $this->logger->info(sprintf('try indirect update account, subAccount: %s, balance: %s', $subAccountId, $subAccount->getBalance()));

                if ($this->tryUpdateExistingAccount($subAccount, $account, in_array($providerCode, ['testprovider', 'amex']))) {
                    $this->logger->info(sprintf('successful indirect update account attempt, remove subAccount %s', $subAccountId));
                    unset($subAccounts[$i]);
                    $subAccountUpdated = true;
                } else {
                    $this->logger->info(sprintf('indirect update account attempt failed, subAccount: %s', $subAccountId));
                }
            }

            if ($subAccountUpdated) {
                $this->logger->info(sprintf('update subAccounts, account %d, %s', $account->getId(), json_encode(it($subAccounts)->map(function (SubAccount $subAccount) {
                    return sprintf('code: %s, balance: %s, name: %s', $subAccount->getCode(), $subAccount->getBalance(), $subAccount->getDisplayname());
                })->toArrayWithKeys())));
            } else {
                $this->logger->info(sprintf('no update subAccounts, account %d', $account->getId()));
            }

            $response->setSubaccounts($subAccounts);
        } else {
            $propsMap = $this->mapProperties($responseProps);
            $subAccount = (new SubAccount())
                ->setBalance($response->getBalance());
            $subAccountProps = array_values(array_intersect_key($propsMap, array_flip([
                self::PROP_PROVIDER_CODE,
                self::PROP_ACCOUNT_NUMBER,
                self::PROP_USER_NAME,
            ])));
            $subAccount->setProperties($subAccountProps);

            $this->logger->info(sprintf('try indirect update account via main props, account %d', $account->getId()));

            if ($this->tryUpdateExistingAccount($subAccount, $account, false)) {
                $this->logger->info('successful indirect update account attempt, remove props');
                $response->setBalance(null);
                $response->setProperties(
                    array_values(array_diff_key($propsMap, array_flip([
                        self::PROP_PROVIDER_CODE,
                        self::PROP_ACCOUNT_NUMBER,
                        self::PROP_USER_NAME,
                    ])))
                );
            }
        }
    }

    private function tryUpdateExistingAccount(SubAccount $subAccount, Account $account, bool $copyProps)
    {
        $user = $account->getUser();
        $properties = $this->mapProperties($subAccount->getProperties());
        $providerCodeProperty = isset($properties[self::PROP_PROVIDER_CODE]) ? $properties[self::PROP_PROVIDER_CODE]->getValue() : null;
        $accountNumberProperty = isset($properties[self::PROP_ACCOUNT_NUMBER]) ? $properties[self::PROP_ACCOUNT_NUMBER]->getValue() : null;

        if (!in_array($providerCodeProperty, ['delta', 'rapidrewards', 'mileageplus'])) {
            $this->logger->info(sprintf('invalid provider "%s" for update', $providerCodeProperty));

            return false;
        }

        $remove = false;

        if (!empty($providerCodeProperty) && !empty($accountNumberProperty)) {
            $this->logger->info(sprintf(
                'try update existing account via number, provider: %s, number: %s', $providerCodeProperty, $accountNumberProperty
            ));
            $foundAccount = $this->accountFinder->findAccountByAccountNumber($user, $providerCodeProperty, $accountNumberProperty);
        } elseif (!empty($providerCodeProperty)) {
            $userNameProperty = isset($properties[self::PROP_USER_NAME]) ? $properties[self::PROP_USER_NAME]->getValue() : null;
            $this->logger->info(sprintf(
                'try update existing account via owner, provider: %s, owner: %s', $providerCodeProperty, $userNameProperty ?? '-'
            ));

            $foundAccount = $this->accountFinder->findAccountByOwner($account, $providerCodeProperty, $userNameProperty);
        }

        $properties = [];

        if (!$foundAccount && !empty($accountNumberProperty)) {
            $this->logger->info('account was not found, try create pending account');

            $provider = $this->em->getRepository(Provider::class)->findOneBy(['code' => $providerCodeProperty]);

            if ($provider) {
                $foundAccount = (new Account())
                    ->setLogin($accountNumberProperty)
                    ->setProviderid($provider)
                    ->setUser($user)
                    ->setErrorcode(ACCOUNT_UNCHECKED)
                    ->setState(ACCOUNT_PENDING);
                $this->em->persist($foundAccount);
                $this->em->flush();
            }
        } elseif ($foundAccount) {
            $this->logger->info(sprintf('account #%d was found', $foundAccount->getId()));

            if ($copyProps) {
                $properties = stmtAssoc($this->em->getConnection()->executeQuery("
                    SELECT ap.Val, pp.Code
                    FROM
                        AccountProperty ap
                        LEFT OUTER JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID
                    WHERE
                        ap.AccountID = ?
                ", [$foundAccount->getId()]))
                    ->flatMapIndexed(function ($row) {
                        yield $row['Code'] => $row['Val'];
                    })->toArrayWithKeys();

                $this->logger->info(sprintf('new account props %s', json_encode($properties)));
            }

            $remove = true;
        }

        if (isset($foundAccount)) {
            $report = new \AccountCheckReport();
            $report->account = new \Account($foundAccount->getId());
            $report->balance = $subAccount->getBalance();
            $report->properties = $properties;
            $report->errorCode = ACCOUNT_CHECKED;
            $report->errorMessage = '';
            $report->filter();
            $options = new \AuditorOptions();
            $options->checkedBy = Account::CHECKED_BY_SUBACCOUNT;
            $options->checkIts = false;
            \CommonCheckAccountFactory::manuallySave($foundAccount->getId(), $report, $options);
            $this->balanceProcessor->saveAccountBalance($foundAccount, $subAccount->getBalance());
            $remove = true;
        }

        return $remove;
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
