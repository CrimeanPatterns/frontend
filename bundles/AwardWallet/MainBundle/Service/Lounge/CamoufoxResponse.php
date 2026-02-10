<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class CamoufoxResponse
{
    private string $html;

    public function __construct(string $html)
    {
        $this->html = $html;
    }

    public function getHtml(): string
    {
        return $this->html;
    }
}
