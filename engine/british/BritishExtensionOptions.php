<?php

namespace AwardWallet\Engine\british;

use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ParseAllowedInterface;

class BritishExtensionOptions implements ParseAllowedInterface
{

    public function isParseAllowed(AccountOptions $options): bool
    {
        return true;
        // debug Alexi's account
        //return $options->login !== '19185334';
    }
}
