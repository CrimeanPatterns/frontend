<?php

namespace AwardWallet\Engine\testprovider;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\GoProxiesRequest;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\NetNutRequest;
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;

class TestproviderExtensionServerConfig extends AbstractServerConfig
{
    public function configureServerCheck(?AccountOptions $accountOptions, \SeleniumFinderRequest $seleniumRequest, \SeleniumOptions $seleniumOptions): bool
    {
        if (strpos($accountOptions->login, "Extension.") !== 0) {
            return false;
        }

        $seleniumOptions->setProxy($this->proxyManager->get(new GoProxiesRequest('ru')));
        $seleniumRequest->request(\SeleniumFinderRequest::BROWSER_CAMOUFOX);

        return true;
    }
}
