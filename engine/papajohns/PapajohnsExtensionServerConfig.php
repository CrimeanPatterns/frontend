<?php

namespace AwardWallet\Engine\papajohns;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\GoProxiesRequest;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\NetNutRequest;
use AwardWallet\Engine\testprovider\Checker\NetNut;
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;
use SeleniumFinderRequest;
use SeleniumOptions;

class PapajohnsExtensionServerConfig extends AbstractServerConfig
{

    public function configureServerCheck(
        ?AccountOptions $accountOptions,
        SeleniumFinderRequest $seleniumRequest,
        SeleniumOptions $seleniumOptions
    ): bool {
        $seleniumRequest->request(\SeleniumFinderRequest::FIREFOX_EXTENSION_104);

        $seleniumOptions->setProxy($this->proxyManager->get(new GoProxiesRequest(GoProxiesRequest::COUNTRY_US)));
        $seleniumOptions->addHideSeleniumExtension = false;
        $seleniumOptions->userAgent = null;
        return true;
    }
}
