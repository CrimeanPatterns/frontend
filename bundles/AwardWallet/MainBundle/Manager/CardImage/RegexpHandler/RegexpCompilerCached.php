<?php

namespace AwardWallet\MainBundle\Manager\CardImage\RegexpHandler;

class RegexpCompilerCached extends RegexpCompiler
{
    protected $regexpCache = [];

    public function compile(string $keywords)
    {
        if (!array_key_exists($keywords, $this->regexpCache)) {
            $this->regexpCache[$keywords] = parent::compile($keywords);
        }

        return $this->regexpCache[$keywords];
    }
}
