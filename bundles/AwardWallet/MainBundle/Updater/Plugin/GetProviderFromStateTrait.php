<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Updater\AccountState;

trait GetProviderFromStateTrait
{
    use ProviderRepositoryAwareTrait;

    protected function getProviderFromState(AccountState $state): ?Provider
    {
        $providerId = $state->getContextValue('providerId');

        return $providerId ?
            $this->providerRepository->find($providerId) :
            $state->account->getProviderid();
    }
}
