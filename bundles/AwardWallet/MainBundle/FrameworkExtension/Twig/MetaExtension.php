<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

// correct creation of canonical and alternate tags for SEO
class MetaExtension extends AbstractExtension
{
    public const DEFAULT_LOCALE = 'en';

    private const CANONICAL_KEY = '_canonical';
    private const CANONICAL_CLEAN_KEY = '_canonical_clean'; // comma separator
    private const ALTERNATE_KEY = '_alternate';
    private const DISALLOW_CANONICAL_KEY = 'disallowCanonical';
    private const WITHOUT_LOCALE = '_withoutLocale';

    private RequestStack $requestStack;

    private RouterInterface $router;

    private string $protoAndHost;

    private array $routeLocales;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        string $protoAndHost,
        string $routeLocales
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->protoAndHost = $protoAndHost;
        $this->routeLocales = explode('|', $routeLocales);
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('canonical_tag', [$this, 'canonicalTag'], ['is_safe' => ['html']]),
            new TwigFunction('alternate_tag', [$this, 'alternateTag'], ['is_safe' => ['html']]),
            new TwigFunction('urlLocale', [$this, 'urlLocale']),
        ];
    }

    public function canonicalTag(): string
    {
        $request = $this->requestStack->getMasterRequest();

        if (
            !$request
            || !($canonicalRouteName = $request->attributes->get(self::CANONICAL_KEY))
            || $request->attributes->has(self::DISALLOW_CANONICAL_KEY)
        ) {
            return '';
        }

        $withoutLocale = true; // true === $request->attributes->get(self::WITHOUT_LOCALE, false);
        $locale = $request->getLocale();

        if (self::DEFAULT_LOCALE === $locale) {
            $withoutLocale = true;
        }

        $cleanKeys = array_merge(
            [self::CANONICAL_KEY, self::ALTERNATE_KEY, self::CANONICAL_CLEAN_KEY],
            explode(',', $request->attributes->get(self::CANONICAL_CLEAN_KEY) ?? '')
        );
        $path = $this->router->generate(
            $canonicalRouteName,
            array_merge(
                array_diff_key($request->attributes->get('_route_params'),
                    array_flip($cleanKeys)),
                $withoutLocale ? [] : ['_locale' => $request->getLocale()]
            )
        );

        $href = $this->protoAndHost . $path;

        if ($withoutLocale) {
            $href = $this->removeLocale($href, $locale);
        }

        return sprintf('<link rel="canonical" href="%s">', $href);
    }

    public function alternateTag(): string
    {
        $request = $this->requestStack->getMasterRequest();

        if (!$request
            || !($alternateRouteName = $request->attributes->get(self::ALTERNATE_KEY))
        ) {
            return '';
        }

        $currentLocale = $request->getLocale();

        return it($this->routeLocales)
            ->map(function (string $locale) use ($alternateRouteName, $request, $currentLocale): string {
                if ($locale === self::DEFAULT_LOCALE || $locale === $currentLocale) {
                    return '';
                }

                $withoutLocale = false;

                /*
                if (self::DEFAULT_LOCALE === $locale) {
                    $hreflang = 'x-default';
                    $withoutLocale = true;
                } else
                */
                if ('zh_TW' === $locale) {
                    $hreflang = 'zh-Hant';
                } elseif ('zh_CN' === $locale) {
                    $hreflang = 'zh-Hans';
                } else {
                    $hreflang = $locale;
                }

                $href = $this->router->generate(
                    $alternateRouteName,
                    array_merge(
                        array_diff_key(
                            $request->attributes->get('_route_params'),
                            array_flip([self::CANONICAL_KEY, self::ALTERNATE_KEY])
                        ),
                        ['_locale' => $locale]
                    ),
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                if ($withoutLocale) {
                    $href = $this->removeLocale($href, $locale);
                }

                return sprintf(
                    '<link rel="alternate" href="%s" hreflang="%s">',
                    $href,
                    $hreflang
                );
            })->joinToString("\n");
    }

    public function urlLocale(string $routeName, ?array $parameters = null): string
    {
        is_array($parameters) ?: $parameters = [];

        if (!array_key_exists('_locale', $parameters) && false !== strpos($routeName, '_locale')) {
            $parameters['_locale'] = self::DEFAULT_LOCALE;
        }
        $url = $this->removeLocale($this->router->generate($routeName, $parameters));

        if (0 === strpos($url, '/' . self::DEFAULT_LOCALE . '/')) {
            $url = substr($url, strlen('/' . self::DEFAULT_LOCALE));
        }

        return $url;
    }

    private function removeLocale(string $url, string $locale = self::DEFAULT_LOCALE): string
    {
        return str_replace(
            $this->protoAndHost . '/' . $locale,
            $this->protoAndHost,
            $url
        );
    }
}
