<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Service\InAppPurchase\Exception\UnknownPlatformException;
use Symfony\Component\HttpFoundation\Request;

class ProviderRegistry
{
    /**
     * @var ProviderInterface[]
     */
    protected array $providers;

    public function addProvider(ProviderInterface $provider): void
    {
        $this->providers[$provider->getPlatformId()] = $provider;
    }

    public function hasProvider(string $platformId): bool
    {
        return isset($this->providers[$platformId]);
    }

    /**
     * @throws UnknownPlatformException
     */
    public function getProvider(string $platformId): ProviderInterface
    {
        if (!$this->hasProvider($platformId)) {
            throw new UnknownPlatformException($platformId);
        }

        return $this->providers[$platformId];
    }

    /**
     * @throws UnknownPlatformException
     */
    public function detectProvider(Request $request, bool $strict = false): ?ProviderInterface
    {
        try {
            if ($request->headers->has(MobileHeaders::MOBILE_PLATFORM)) {
                $platform = strtolower($request->headers->get(MobileHeaders::MOBILE_PLATFORM));

                if ($platform === 'android') {
                    $platform = 'android-v3';
                }

                return $this->getProvider($platform);
            }

            throw new UnknownPlatformException();
        } catch (UnknownPlatformException $e) {
            if ($strict) {
                throw $e;
            }

            return null;
        }
    }
}
