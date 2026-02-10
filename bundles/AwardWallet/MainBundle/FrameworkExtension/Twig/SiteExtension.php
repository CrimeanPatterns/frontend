<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\UserAgentUtils;
use Symfony\Component\HttpFoundation\RequestStack;

class SiteExtension extends \Twig_Extension
{
    protected RequestStack $requestStack;
    private string $google_tag_manager_id;

    public function __construct(
        RequestStack $requestStack,
        string $google_tag_manager_id
    ) {
        $this->requestStack = $requestStack;
        $this->google_tag_manager_id = $google_tag_manager_id;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('isHomePage', [$this, 'isHomePage']),
            new \Twig_SimpleFunction('isMobileDevice', [$this, 'isMobileDevice']),
            new \Twig_SimpleFunction('getBrowser', [$this, 'getBrowser']),
            new \Twig_SimpleFunction('getGoogleTagManager', [$this, 'getGoogleTagManager']),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('var2TrackingModify', [$this, 'var2TrackingModify']),
            new \Twig_SimpleFilter('replaceVarInLink', [$this, 'replaceVarInLink']),
        ];
    }

    public function isHomePage(): bool
    {
        $routeName = $this->requestStack->getCurrentRequest()->get('_route');

        return in_array($routeName, [
            'aw_home', 'aw_register', 'aw_login', 'aw_restore', 'aw_home_locale', 'aw_login_locale', 'aw_register_locale', 'aw_restore_locale',
            'aw_business_home', 'aw_register_business', 'aw_register_business_locale',
        ]);
    }

    public function isMobileDevice(): bool
    {
        return UserAgentUtils::isMobileBrowser($this->requestStack->getCurrentRequest()->headers->get('user_agent'));
    }

    public function getBrowser(?string $userAgent = null): array
    {
        if (null === $userAgent) {
            $userAgent = $this->requestStack->getCurrentRequest()->headers->get('user_agent');
        }

        return UserAgentUtils::getBrowser($userAgent);
    }

    public function var2TrackingModify(?string $link, array $args): ?string
    {
        if (null === $link) {
            return null;
        }

        return StringHandler::var2TrackingModify($link, $args);
    }

    public function replaceVarInLink(?string $link, array $args = [], bool $isRemoveOld = false): ?string
    {
        if (null === $link) {
            return null;
        }

        return StringHandler::replaceVarInLink($link, $args, $isRemoveOld);
    }

    public function getGoogleTagManager(): string
    {
        return $this->google_tag_manager_id;
    }
}
