<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\RenderEngine;

interface EngineInterface
{
    public function render($text, array $replacements = []);
}
