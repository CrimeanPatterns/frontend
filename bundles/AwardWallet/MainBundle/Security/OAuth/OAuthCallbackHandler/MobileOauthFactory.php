<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Security\OAuth\AppleOAuth;
use AwardWallet\MainBundle\Security\OAuth\BaseOAuth;
use AwardWallet\MainBundle\Security\OAuth\Factory;
use AwardWallet\MainBundle\Security\OAuth\OAuthFactoryInterface;
use AwardWallet\MainBundle\Security\OAuth\OAuthType;

class MobileOauthFactory implements OAuthFactoryInterface
{
    /**
     * @var Factory
     */
    private $baseOauthFactory;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioningService;
    /**
     * @var AppleOAuth
     */
    private $mobileAppleOauth;

    public function __construct(
        Factory $baseOauthFactory,
        ApiVersioningService $apiVersioningService,
        AppleOAuth $mobileAppleOauth
    ) {
        $this->baseOauthFactory = $baseOauthFactory;
        $this->apiVersioningService = $apiVersioningService;
        $this->mobileAppleOauth = $mobileAppleOauth;
    }

    public function getByType(string $type): BaseOAuth
    {
        if (
            ($type === OAuthType::APPLE)
            && $this->apiVersioningService->supports(MobileVersions::IOS)
        ) {
            return $this->mobileAppleOauth;
        }

        return $this->baseOauthFactory->getByType($type);
    }
}
