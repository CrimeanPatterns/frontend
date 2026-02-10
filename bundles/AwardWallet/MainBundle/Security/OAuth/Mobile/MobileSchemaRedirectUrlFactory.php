<?php

namespace AwardWallet\MainBundle\Security\OAuth\Mobile;

class MobileSchemaRedirectUrlFactory
{
    public static function make(string $type): string
    {
        return 'awardwallet://oauth/' . $type;
    }
}
