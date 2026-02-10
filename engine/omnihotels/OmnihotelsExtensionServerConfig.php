<?php

namespace AwardWallet\Engine\omnihotels;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\GoProxiesRequest;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\NetNutRequest;
use AwardWallet\Engine\testprovider\Checker\NetNut;
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;
use SeleniumFinderRequest;
use SeleniumOptions;

class OmnihotelsExtensionServerConfig extends AbstractServerConfig
{

    public function configureServerCheck(
        ?AccountOptions $accountOptions,
        SeleniumFinderRequest $seleniumRequest,
        SeleniumOptions $seleniumOptions
    ): bool {
        //$seleniumOptions->setProxy($this->proxyManager->get(new GoProxiesRequest(NetNutRequest::COUNTRY_US)));
        return false;
    }
}
