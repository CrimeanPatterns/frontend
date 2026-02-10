<?php

namespace AwardWallet\Engine\chase;

use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ParseAllowedInterface;

class ChaseExtensionOptions implements ParseAllowedInterface
{

    public function isParseAllowed(AccountOptions $options): bool
    {
        return true; //$options->login !== 'awardwallet04';
    }
}
