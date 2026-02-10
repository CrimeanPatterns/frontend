<?php

namespace AwardWallet\MainBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * resolve theme light or dark via the cookie.
 */
class ThemeResolver
{
    public const COOKIE_NAME = 'force_color_schema';
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';
    public const THEMES = [self::THEME_LIGHT, self::THEME_DARK];

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * get the current theme from the cookie.
     */
    public function getCurrentTheme(?Request $request = null): ?string
    {
        $request = $request ?? $this->getCurrentRequest();

        // check if the theme is set in the cookie
        if ($request && $this->validateTheme($cookieTheme = $request->cookies->get(self::COOKIE_NAME))) {
            return $cookieTheme;
        }

        return null;
    }

    public function validateTheme($theme): bool
    {
        return is_string($theme) && in_array($theme, self::THEMES);
    }

    private function getCurrentRequest(): ?Request
    {
        return $this->requestStack->getMasterRequest();
    }
}
