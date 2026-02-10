<?php

namespace AwardWallet\MainBundle\Security\OAuth;

class Factory implements OAuthFactoryInterface
{
    /**
     * @var BaseOAuth[]
     */
    private $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getByType(string $type): BaseOAuth
    {
        foreach ($this->providers as $provider) {
            if ($provider->getType() === $type) {
                return $provider;
            }
        }

        throw new \Exception("unknown oauth provider: $type");
    }
}
