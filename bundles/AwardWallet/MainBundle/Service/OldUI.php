<?php

namespace AwardWallet\MainBundle\Service;

/**
 * Some service to support old classes.
 */
class OldUI
{
    public function __construct(OldDateFormatsLoader $oldDateFormatsLoader)
    {
        global $Interface;
        $Interface = new \NDInterface();
        $Interface->Init();
    }
}
