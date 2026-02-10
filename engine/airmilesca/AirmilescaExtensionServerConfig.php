<?php

namespace AwardWallet\Engine\airmilesca;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\GoProxiesRequest;
/*
use AwardWallet\Common\Parsing\Web\Proxy\Provider\NetNutRequest;
*/
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;

class AirmilescaExtensionServerConfig extends AbstractServerConfig
{
    public function configureServerCheck(?AccountOptions $accountOptions, \SeleniumFinderRequest $seleniumRequest, \SeleniumOptions $seleniumOptions): bool
    {
        /*
        $seleniumOptions->setProxy($this->proxyManager->get(new NetNutRequest('us')));
        */
        $seleniumOptions->setProxy($this->proxyManager->get(new GoProxiesRequest()));

        return false;
    }
}
