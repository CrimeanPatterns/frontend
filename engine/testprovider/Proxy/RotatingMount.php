<?php

namespace AwardWallet\Engine\testprovider\Proxy;

use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\testprovider\Success;

class RotatingMount extends Success
{

    use ProxyList, \SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();

        $this->requestProxyManager(new MountRotatingRequest());
        $this->UseSelenium();
        $this->useChromePuppeteer();
        $this->usePacFile(false);
        $this->KeepState = false;
    }

    public function Parse()
    {
        $this->http->saveScreenshots = true;
        $this->http->GetURL('https://lumtest.com/myip.json');
        $this->http->GetURL('https://www.bankofamerica.com');
        $this->SetBalance(1);
    }

}