<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\Service\ThemeResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * For more information about BEM, see https://en.bem.info/methodology/quick-start/.
 */
class BemExtension extends AbstractExtension
{
    private ThemeResolver $themeResolver;

    public function __construct(ThemeResolver $themeResolver)
    {
        $this->themeResolver = $themeResolver;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('bem', [$this, 'bem'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * calculates the BEM class for a block, element and modifiers.
     */
    public function bem(string $block, ?string $element = null, array $modifiers = []): string
    {
        $classes = [];

        if (!is_null($element)) {
            $classes[] = $component = $block . '__' . $element;
        } else {
            $classes[] = $component = $block;
        }

        // add the theme as a modifier
        if (!is_null($theme = $this->themeResolver->getCurrentTheme())) {
            $modifiers[] = $theme;
        }

        foreach ($modifiers as $modifier) {
            $classes[] = $component . '--' . $modifier;
        }

        return implode(' ', array_unique($classes));
    }
}
