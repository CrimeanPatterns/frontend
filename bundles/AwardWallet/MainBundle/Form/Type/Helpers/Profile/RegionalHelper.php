<?php

namespace AwardWallet\MainBundle\Form\Type\Helpers\Profile;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegionalHelper
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var LocalizeService
     */
    private $localizer;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var array
     */
    private $locales;

    public function __construct(
        TranslatorInterface $translator,
        LocalizeService $localizer,
        RequestStack $requestStack,
        array $locales
    ) {
        $this->translator = $translator;
        $this->localizer = $localizer;
        $this->requestStack = $requestStack;
        $this->locales = $locales;
    }

    public function getData()
    {
        $langs = [];
        $langHints = [];
        $localeHints = [];
        $regions = [];

        $request = $this->requestStack->getMasterRequest();

        if ($request->attributes->has('_aw_allowed_locales')) {
            $allowedLocales = $request->attributes->get('_aw_allowed_locales');
        } else {
            $allowedLocales = $this->locales;
        }

        foreach ($allowedLocales as $locale) {
            $langs[$this->translator->trans(/** @Ignore */ 'language.' . $locale, [], 'menu')] = $locale;
            $langHints[$locale] = $locale;
        }

        $allLocales = Locales::getNames();
        $date = new \DateTime('january 31 14:30');

        foreach ($allLocales as $locale => $localeName) {
            $localeHints[$locale] = $this->localizer->formatNumberWithFraction(1000.00, 2, $locale);
            $localeHints[$locale] .= ' | ';
            $localeHints[$locale] .= $this->localizer->formatDate($date, 'short', $locale);
            $localeHints[$locale] .= ' | ';
            $localeHints[$locale] .= $this->localizer->formatTime($date, 'short', $locale);
            $countryName = \Locale::getDisplayRegion($locale);
            $localeData = \Locale::parseLocale($locale);

            if (isset($localeData['region']) && !array_search($countryName, $regions)) {
                $regions[$localeData['region']] = $countryName;
            }
        }

        asort($regions);

        return [
            'langs' => $langs,
            'langHints' => $langHints,
            'localeHints' => $localeHints,
            'regions' => $regions,
        ];
    }
}
