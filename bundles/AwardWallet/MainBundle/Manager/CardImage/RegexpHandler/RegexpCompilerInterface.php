<?php

namespace AwardWallet\MainBundle\Manager\CardImage\RegexpHandler;

interface RegexpCompilerInterface
{
    /**
     * @return string|null
     */
    public function compile(string $keywords);
}
