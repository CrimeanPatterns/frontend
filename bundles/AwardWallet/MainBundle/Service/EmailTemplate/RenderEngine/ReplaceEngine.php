<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\RenderEngine;

class ReplaceEngine implements EngineInterface
{
    public function render($text, array $replacements = [])
    {
        return str_replace($this->prepareSearch($replacements), $replacements, $text);
    }

    private function prepareSearch(array $replacements)
    {
        $r = [];

        foreach (array_keys($replacements) as $v) {
            $r[] = "{{ $v }}";
        }

        return $r;
    }
}
