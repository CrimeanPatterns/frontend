<?php

namespace AwardWallet\Engine\marriott;

use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ParseAllowedInterface;

class MarriottExtensionOptions implements ParseAllowedInterface
{

    public function isParseAllowed(AccountOptions $options): bool
    {
        // debug Alexi's account
        return true;
        return $options->login !== 'veresch80@yahoo.com';
    }
}
