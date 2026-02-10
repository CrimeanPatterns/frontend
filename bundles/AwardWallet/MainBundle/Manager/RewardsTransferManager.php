<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\RewardsTransferRepository;
use AwardWallet\MainBundle\Entity\RewardsTransfer;
use Psr\Log\LoggerInterface;

class RewardsTransferManager
{
    protected $entityManager;

    /** @var ProviderRepository */
    protected $providerRepository;

    /** @var AccountRepository */
    protected $accountRepository;

    /** @var RewardsTransferRepository */
    protected $rewardsTransferRepository;

    /** @var \HttpBrowser */
    protected $http;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        $entityManager,
        $providerRepository,
        $accountRepository,
        $rewardsTransferRepository,
        LoggerInterface $logger
    ) {
        $this->http = new \HttpBrowser('none', new \CurlDriver());
        $this->entityManager = $entityManager;
        $this->providerRepository = $providerRepository;
        $this->accountRepository = $accountRepository;
        $this->rewardsTransferRepository = $rewardsTransferRepository;
        $this->logger = $logger;
    }

    /**
     * @return array|bool If success - returns array of update info for each checked provider. Update info format is described in updateRewardsTransferRatesForProvider
     */
    public function updateRewardsTransferRatesForAllProviders()
    {
        $transferingProviders = $this->providerRepository->findBy(['canTransferRewards' => 1]);
        $transferingProvidersCount = count($transferingProviders);
        $this->logger->info(date('c') . ' Rewards Transfer info update');
        $this->logger->info("$transferingProvidersCount provider(s) supporting rewards transfer found");
        $i = 1;
        $result = [];

        foreach ($transferingProviders as $tp /** @var Provider $tp */) {
            $this->logger->info("[$i/$transferingProvidersCount] Getting rewards transfer data for '{$tp->getCode()}'");

            if ($tp->getCode() != 'testprovider') {
                $partialResult = $this->updateRewardsTransferRatesForProvider($tp);
                $this->logger->info("Rewards Transfer update for {$tp->getCode()}: " . ($partialResult !== false ? 'SUCCEEDED' : 'FAILED'));
                $result[$tp->getCode()] = $partialResult;
            } else {
                $this->logger->info('Ignored. Test provider is excluded from all providers update, one provider update should be used');
            }
            $i++;
        }

        return $result;
    }

    /**
     * @param Provider $provider
     * @return array|bool If success - returns array of three subarrays of providers codes ('Added', 'Updated', 'Removed'), false - otherwise
     */
    public function updateRewardsTransferRatesForProvider(
        /** @var Provider $provider */
        $provider,
        /** @var \TAccountChecker $checker */
        $checker = null // In some cases it is more convenient to pass logged in checker
    ) {
        //
        // Account search & login
        //
        if (!$checker) {
            $status = false;
            $retries = 3;

            for ($i = 0; $i < $retries; $i++) {
                /** @var Account */
                $account = $this->getLastSuccessfullyUpdatedAccountForProvider($provider);
                $checkerClassName = "TAccountChecker" . ucfirst($provider->getCode());

                if (!class_exists($checkerClassName)) {
                    $this->logger->error('Account checker for ' . $provider->getCode() . ' not found');

                    return false;
                }
                $checker = new $checkerClassName();
                $checker->SetAccount($account->getAccountInfo());
                $checker->InitBrowser();

                try {
                    $status = $this->loginToProvider($account, $checker);
                } catch (\CheckException $e) {
                    if ($e->getCode() == ACCOUNT_INVALID_PASSWORD) {
                        $status = false;
                        $this->logger->error('Bad credentials, trying another account');

                        continue;
                    }
                }
            }

            if (!$status) {
                $this->logger->error('Login failed');

                return false;
            }
        }

        //
        // Fetch rewards transfer data
        //
        if (!method_exists($checker, 'getRewardsTransferRates')) {
            $e = 'Getting rewards transfer rates for "' . $provider->getCode() . '" is not implemented yet';
            $this->logger->error($e);

            return false;
        }
        $rates = $checker->getRewardsTransferRates();

        if (!$rates) {
            $e = 'No rewards transfer data found for "' . $provider->getCode() . '"';
            $this->logger->error($e);

            return false;
        }

        //
        // Update database and gather result
        //
        $result = [];
        $sourceProvider = $provider;
        $addedRewardsTransfers = [];
        $updatedRewardsTransfers = [];
        $removedRewardsTransfersCount = 0;
        $pendingRemovalRewardsTransfers = [];
        $currentRewardsTransfers = $this->rewardsTransferRepository->findBy(['sourceProvider' => $sourceProvider]);

        foreach ($rates as $r) {
            $sourceProviderCode = $r['SourceProviderCode'];
            $targetProviderCode = $r['TargetProviderCode'];
            $targetProvider = $this->providerRepository->findOneBy(['code' => $targetProviderCode]);

            if (!$targetProvider) {
                $this->logger->error('Bad target provider code "' . $targetProviderCode . '"');

                return false;
            }
            $conditions = [
                'sourceProvider' => $sourceProvider,
                'targetProvider' => $targetProvider,
            ];
            /** @var RewardsTransfer $rewardsTransfer */
            $rewardsTransfer = $this->rewardsTransferRepository->findOneBy($conditions);

            if ($rewardsTransfer) {
                $rewardsTransfer->setSourceRate($r['SourceRate']);
                $rewardsTransfer->setTargetRate($r['TargetRate']);
                $updatedRewardsTransfers[] = [
                    'SourceProviderCode' => $sourceProviderCode,
                    'TargetProviderCode' => $targetProviderCode,
                ];
                $result['Updated'][] = $targetProviderCode;
                $this->logger->debug('Updated rewards Transfer "' . $sourceProviderCode . ' -> ' . $targetProviderCode . '"');
            } else {
                $rewardsTransfer = new RewardsTransfer();
                $rewardsTransfer->setSourceProvider($sourceProvider);
                $rewardsTransfer->setTargetProvider($targetProvider);
                $rewardsTransfer->setSourceRate($r['SourceRate']);
                $rewardsTransfer->setTargetRate($r['TargetRate']);
                $rewardsTransfer->setEnabled(false);
                $this->entityManager->persist($rewardsTransfer);
                $addedRewardsTransfers[] = [
                    'SourceProviderCode' => $sourceProviderCode,
                    'TargetProviderCode' => $targetProviderCode,
                ];
                $result['Added'][] = $targetProviderCode;
                $this->logger->debug('Added new rewards Transfer "' . $sourceProviderCode . ' -> ' . $targetProviderCode . '"');
            }
        }
        $this->entityManager->flush();

        //
        // Brief report (added and updated reward transfers)
        //
        if (!$addedRewardsTransfers and !$updatedRewardsTransfers) {
            // TODO: Throw exception
            $this->logger->error('No Rewards Transfers added, no updated. Seems like provider rewards transfer update '
                                    . ' code is broken.');

            return false;
        }

        $this->logger->info('-> ' . count($addedRewardsTransfers) . ' new Rewards Transfer variants added');

        if (!$updatedRewardsTransfers) {
            $this->logger->info('No updated Rewards Transfers, skipping outdated check.');

            return $result;
        }

        $this->logger->info('-> ' . count($updatedRewardsTransfers) . ' existing Rewards Transfer variants updated');

        //
        // Check if some rewards transfers should be removed and report about it
        //
        //		$updatedRewardsTransfers = [];
        if (count($updatedRewardsTransfers) < count($currentRewardsTransfers)) {
            $this->logger->info('Not all rewards transfer variants for this provider were updated, seems like'
                . ' some variants are no longer supported. Remove them.');

            foreach ($currentRewardsTransfers as $crt /** @var RewardsTransfer $crt */) {
                $sourceProviderCode = $crt->getSourceProvider()->getCode();
                $targetProviderCode = $crt->getTargetProvider()->getCode();
                $gotIt = false;

                foreach ($updatedRewardsTransfers as $urt) {
                    if ($sourceProviderCode == $urt['SourceProviderCode']
                        and $targetProviderCode == $urt['TargetProviderCode']) {
                        $gotIt = true;

                        break;
                    }
                }

                if (!$gotIt) {
                    $this->logger->debug('! Rewards transfer ' . $sourceProviderCode . ' -> ' . $targetProviderCode
                        . ' was added to remove queue');
                    $pendingRemovalRewardsTransfers[] = $crt;
                }
            }
        } elseif (count($updatedRewardsTransfers) == count($currentRewardsTransfers)) {
            $this->logger->info('Great! All rewards transfers for this provider were updated');
        } else {
            // TODO: Throw exception
            $this->logger->critical('Count of updated rewards transfers is more than count of previously existing. '
                                        . 'This should never happen. You definitely must look at it');

            return false;
        }

        if ($pendingRemovalRewardsTransfers) {
            if (count($pendingRemovalRewardsTransfers) < count($currentRewardsTransfers)) {
                $this->logger->info(count($pendingRemovalRewardsTransfers) . ' rewards transfer are pending removal. Do it.');

                foreach ($pendingRemovalRewardsTransfers as $prrt /** @var RewardsTransfer $prrt */) {
                    $sourceProviderCode = $prrt->getSourceProvider()->getCode();
                    $targetProviderCode = $prrt->getTargetProvider()->getCode();
                    $this->entityManager->remove($prrt);
                    $this->logger->debug('Removed Rewards Transfer "'
                                                . $sourceProviderCode . ' -> ' . $targetProviderCode . '"');
                    $result['Removed'][] = $targetProviderCode;
                    $removedRewardsTransfersCount++;
                }
                $this->entityManager->flush();
                $this->logger->info($removedRewardsTransfersCount . ' Rewards Transfers removed');
            } elseif (count($pendingRemovalRewardsTransfers) == count($currentRewardsTransfers)) {
                $this->logger->error('All Rewards Transfers for this provider were marked for removal. Seems'
                    . ' that something is broken.');

                // TODO: Throw exception
                return false;
            } else {
                $this->logger->critical('Count of Rewards Transfers pending for removal is more than count of '
                                            . 'previously existing. This should never happen. You definitely must '
                                            . 'look at it.');

                // TODO: Throw exception
                return false;
            }
        }

        return $result;
    }

    protected function getLastSuccessfullyUpdatedAccountForProvider(
        /** @var Provider $provider */
        $provider
    ) {
        $conditions = [
            'errorcode' => ACCOUNT_CHECKED,
            'providerid' => $provider->getProviderid(),
            'savepassword' => SAVE_PASSWORD_DATABASE,
        ];
        $orderBy = [
            'updatedate' => 'DESC',
        ];

        return $this->accountRepository->findOneBy($conditions, $orderBy);
    }

    protected function loginToProvider(
        /** @var Account $account */
        $account,
        /** @var \TAccountChecker $checker */
        &$checker
    ) {
        $status = $checker->LoadLoginForm();

        if (!$status) {
            $this->logger->error('Failed to load login form');

            return false;
        }
        $status = $checker->Login();

        if (!$status) {
            $this->logger->error('Login failed');

            return false;
        }

        return true;
    }
}
