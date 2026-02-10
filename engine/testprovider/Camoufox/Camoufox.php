<?php

namespace AwardWallet\Engine\testprovider\Camoufox;

use AwardWallet\Engine\testprovider\TestHelper;

class Camoufox extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use TestHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->useSelenium();
        $this->useCamoufox();
        $this->http->saveScreenshots = true;
    }

    public function LoadLoginForm()
    {
        $this->http->GetURL('https://myip.ru');

        return true;
    }

    public function Login()
    {
        $this->SetBalance(1);
    }
}
