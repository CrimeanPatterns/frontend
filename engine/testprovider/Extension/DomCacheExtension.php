<?php

namespace AwardWallet\Engine\testprovider\Extension;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;

class DomCacheExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface
{

    public function getStartingUrl(AccountOptions $options): string
    {
        return "https://2ip.ru";
    }

    public function isLoggedIn(Tab $tab): bool
    {
        return false;
    }

    public function getLoginId(Tab $tab): string
    {
        return "";
    }

    public function logout(Tab $tab): void
    {

    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->logPageState();

        return LoginResult::success();
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
    }
}