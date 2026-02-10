<?php

namespace AwardWallet\Engine\marriott;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\GoProxiesRequest;
/*
use AwardWallet\Common\Parsing\Web\Proxy\Provider\NetNutRequest;
*/

use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotating;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\NetNutRequest;
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;

class MarriottExtensionServerConfig extends AbstractServerConfig
{
    public function configureServerCheck(?AccountOptions $accountOptions, \SeleniumFinderRequest $seleniumRequest, \SeleniumOptions $seleniumOptions): bool
    {
        //if (in_array($accountOptions->login, ['veresch80@yahoo.com', '826428328'])) {

            /*
            $seleniumOptions->setProxy($this->proxyManager->get(new NetNutRequest()));
            */
            $seleniumOptions->setProxy($this->proxyManager->get(new MountRotatingRequest()));
            $seleniumRequest->request(\SeleniumFinderRequest::BROWSER_CAMOUFOX);
            return true;
        //}
        return false;
    }
}
