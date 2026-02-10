<?php

namespace AwardWallet\Engine\chase;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\ExtensionWorker\AbstractServerConfig;
use AwardWallet\ExtensionWorker\AccountOptions;

class ChaseExtensionServerConfig extends AbstractServerConfig
{
    public function configureServerCheck(?AccountOptions $accountOptions, \SeleniumFinderRequest $seleniumRequest, \SeleniumOptions $seleniumOptions): bool
    {
        return false;
//        if ($accountOptions->login !== "awardwallet04") {
//            return false;
//        }

        $seleniumOptions->setProxy($this->proxyManager->get(new MountRotatingRequest()));
        $seleniumRequest->request(\SeleniumFinderRequest::BROWSER_CAMOUFOX);

        return true;
    }
}
