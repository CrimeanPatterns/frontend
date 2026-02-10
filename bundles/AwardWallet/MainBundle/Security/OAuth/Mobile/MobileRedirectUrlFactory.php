<?php

namespace AwardWallet\MainBundle\Security\OAuth\Mobile;

use AwardWallet\MainBundle\Security\OAuth\OAuthType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MobileRedirectUrlFactory
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function make(string $type): string
    {
        switch ($type) {
            case OAuthType::GOOGLE:
                return $this->urlGenerator->generate("aw_usermailbox_oauthcallback", ["type" => $type], UrlGeneratorInterface::ABSOLUTE_URL);

            case OAuthType::MICROSOFT:
                return $this->urlGenerator->generate('awm_native_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);

            case OAuthType::APPLE:
                return $this->urlGenerator->generate("awm_native_redirect_apple", [], UrlGeneratorInterface::ABSOLUTE_URL);

            case OAuthType::YAHOO:
            case OAuthType::AOL:
                return MobileSchemaRedirectUrlFactory::make($type);

            default:
                throw new \LogicException('undefined oauth type');
        }
    }
}
