<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;

trait ProviderRepositoryAwareTrait
{
    protected ProviderRepository $providerRepository;
}
