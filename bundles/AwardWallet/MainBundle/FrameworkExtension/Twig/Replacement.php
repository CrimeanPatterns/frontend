<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig;

use AwardWallet\MainBundle\FrameworkExtension\Twig\Replacement\ReplacementCollection;

class Replacement
{
    /** @var \Twig_Environment */
    private $twig;

    /** @var ReplacementCollection */
    private $replacementCollection = [];

    public function __construct(
        \Twig_Environment $twigEnv,
        ReplacementCollection $replacementCollection
    ) {
        $this->twig = $twigEnv;
        $this->replacementCollection = $replacementCollection;

        foreach ($this->replacementCollection->getCollection() as $replacement) {
            if (!$this->twig->hasExtension(\get_class($replacement))) {
                $this->twig->addExtension($replacement);
            }
        }
    }

    /**
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Syntax
     */
    public function render(string $text, array $context = []): string
    {
        $context = $this->init($context);
        $template = $this->twig->createTemplate($text);

        return $template->render($context);
    }

    /**
     * @return array|string
     */
    public static function contextMarkup($data)
    {
        if (!\is_string($data) && !\is_array($data)) {
            return $data;
        }

        if (\is_string($data)) {
            new \Twig_Markup($data, 'utf-8');
        }

        foreach ($data as $key => $value) {
            if (\is_string($value)) {
                $data[$key] = new \Twig_Markup($value, 'utf-8');
            } elseif (\is_array($value)) {
                $data[$key] = self::contextMarkup($value);
            }
        }

        return $data;
    }

    private function init(array $context): array
    {
        $replace = [];

        foreach ($this->replacementCollection->getCollection() as $replacement) {
            if (method_exists($replacement, 'getContext')) {
                $replace = array_merge($replace, $replacement->getContext());
            }
        }

        return array_merge($replace, $context);
    }
}
