<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Updater\ExtensionV3IsolatedCheckWaitMapOps;
use AwardWallet\MainBundle\Updater\ExtensionV3SupportLoader;
use AwardWallet\MainBundle\Updater\InternalOptions;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\lazy;

/**
 * Class GroupCheckPlugin.
 */
class GroupCheckPlugin extends AbstractPlugin
{
    use PluginIdentity;
    public const ID = 'groupCheck';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProviderRepository
     */
    private $providerRep;
    private ExtensionV3IsolatedCheckWaitMapOps $extensionV3IsolatedCheckWaitMapOps;
    private ExtensionV3SupportLoader $extensionV3SupportLoader;
    private ProviderRepository $providerRepository;

    public function __construct(
        EntityManagerInterface $em,
        ProviderRepository $providerRep,
        ExtensionV3IsolatedCheckWaitMapOps $extensionV3IsolatedCheckWaitMapOps,
        ExtensionV3SupportLoader $extensionV3SupportLoader,
        ProviderRepository $providerRepository
    ) {
        $this->em = $em;
        $this->providerRep = $providerRep;
        $this->extensionV3IsolatedCheckWaitMapOps = $extensionV3IsolatedCheckWaitMapOps;
        $this->extensionV3SupportLoader = $extensionV3SupportLoader;
        $this->providerRepository = $providerRepository;
    }

    public function tick(MasterInterface $master, $accountStates): void
    {
        $extensionV3SupportMap = lazy(fn () => $this->extensionV3SupportLoader->loadV3SupportMap($master, $accountStates));

        foreach ($accountStates as $state) {
            $groupCheck = $state->getContextValue('groupCheck');
            $this->em->refresh($state->account);

            if (empty($groupCheck)) {
                if (ACCOUNT_INVALID_PASSWORD == $state->account->getErrorcode()) {
                    // start group check on password error only
                    $providerGroup = $this->getProviders($state->account);

                    if (!empty($providerGroup)) {
                        $groupCheck = true;
                        $state->setContextValue('groupCheck', $groupCheck);
                        $state->setContextValue('providerGroup', $providerGroup);
                        $master->log($state->account, 'Group Check start, group ' . $state->account->getProviderid()->getProvidergroup() . ', remains ' . count($providerGroup));
                    } else {
                        $state->popPlugin();
                        $master->log($state->account, 'Group Check end, one provider in group');
                    }
                } else {
                    $state->popPlugin();
                    $master->log($state->account, 'Group Check end, non-password error');
                }
            } else {
                $providerGroup = $state->getContextValue('providerGroup');

                if (empty($providerGroup)) {
                    $state->popPlugin();
                    \AccountAuditor::setGroupCheck($state->account->getAccountid(), null);
                    $master->log($state->account, 'Group Check end, group iterated');
                } else {
                    $providerId = array_shift($providerGroup);

                    // next in group
                    if (!\AccountAuditor::setGroupCheck($state->account->getAccountid(), $providerId)) {
                        $state->popPlugin();
                        $master->log($state->account, 'Group Check End, already in group check');

                        continue;
                    }

                    if (
                        $master->getOption(InternalOptions::V3_ISOLATED_CHECK_SWITCHED_TO_BROWSER, false)
                        && ($extensionV3SupportMap[ExtensionV3SupportLoader::makeV3SupportMapKey($state->account, $this->providerRepository->find($providerId))] ?? false)
                        && !$this->extensionV3IsolatedCheckWaitMapOps->hasActive($master)
                    ) {
                        $state->popPlugin();
                        $master->log($state->account, 'Group Check end, only one switch to browser supported by the client');

                        continue;
                    }

                    $state->setContextValue('providerGroup', $providerGroup);
                    $state->setContextValue('providerId', $providerId);
                    $state->pushPlugin(ServerCheckPlugin::ID, ['providerId' => $providerId]);
                    $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, null);
                    $state->pushPlugin(ClientCheckV3Plugin::ID, ['providerId' => $providerId]);

                    $master->log($state->account, 'Group Check progress, check ' . $providerId . ', remains ' . count($providerGroup));
                }
            }
        }
    }

    private function getProviders(Account $account)
    {
        $group = $account->getProviderid()->getProvidergroup();

        if (empty($group)) {
            return [];
        }

        $providerIds = [];

        foreach ($this->providerRep->findBy(['providergroup' => $group]) as $provider) {
            /** @var Provider $provider */
            if ($provider->getProviderid() == $account->getProviderid()->getProviderid()) {
                continue;
            }

            if ($provider->getCode() == 'usbank') {
                continue;
            } /* exclude USBANK because it will ask security questions for any login */

            if (!($provider->getState() >= PROVIDER_ENABLED || $provider->getState() == PROVIDER_TEST)) {
                continue;
            }
            $providerIds[] = $provider->getProviderid();
        }

        return $providerIds;
    }
}
