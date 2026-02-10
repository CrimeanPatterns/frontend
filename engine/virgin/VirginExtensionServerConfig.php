<?php

namespace AwardWallet\Engine\virgin;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\GoProxiesRequest;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRequest;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;
use SeleniumFinderRequest;
use SeleniumOptions;

class VirginExtensionServerConfig extends AbstractServerConfig
{

    public function configureServerCheck(?AccountOptions $accountOptions, SeleniumFinderRequest $seleniumRequest, SeleniumOptions $seleniumOptions): bool
    {
        $seleniumOptions->setProxy($this->proxyManager->get(new MountRotatingRequest()));
        $seleniumRequest->request(\SeleniumFinderRequest::BROWSER_CAMOUFOX);
        return true;
    }
}
