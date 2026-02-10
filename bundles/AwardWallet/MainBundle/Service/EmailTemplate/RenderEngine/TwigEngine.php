<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\RenderEngine;

use Twig\Environment;

class TwigEngine implements EngineInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render($text, array $replacements = [])
    {
        $template = $this->twig->createTemplate($text);

        return $template->render($replacements);
    }
}
