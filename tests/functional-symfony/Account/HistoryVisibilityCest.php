<?php

namespace AwardWallet\Tests\FunctionalSymfony\Account;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\Updater\Engine\Local;
use AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker\Updater;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\HistoryProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Service\CreditCards\MerchantMatcher\MerchantMatcher;
use AwardWallet\MainBundle\Service\CreditCards\ShoppingCategoryMatcher;
use Codeception\Util\Stub;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-functional
 */
class HistoryVisibilityCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private $login;
    private $accountId;
    private $subAccount1;
    private $subAccount2;

    public function _before(\TestSymfonyGuy $I)
    {
        $provider = "testprovider";

        $engine = $I->stubMake(Local::class, [
            'getProviderInfo' => Stub::atLeastOnce(function ($code) use ($I, $provider) {
                $I->assertEquals($code, $provider);
                $columns = [
                    "Type" => "Info",
                    "Eligible Nights" => "Info",
                    "Post Date" => "PostingDate",
                    "Description" => "Description",
                    "Starpoints" => "Miles",
                    "Bonus" => "Bonus",
                ];
                $historyColumns = [];

                foreach ($columns as $name => $columnCode) {
                    $historyColumns[] = new HistoryColumn($name, $columnCode);
                }

                return (new ProviderInfoResponse())->setHistorycolumns($historyColumns)->setCanparsehistory(true);
            }),
        ]);

        $processor = new HistoryProcessor(
            $I->getContainer()->get(LoggerInterface::class),
            $I->getContainer()->get('database_connection'),
            $I->getContainer()->get(MerchantMatcher::class),
            $I->getContainer()->get(ShoppingCategoryMatcher::class),
            $I->getContainer()->get(Updater::class),
            $engine,
            $I->getContainer()->get(AccountRepository::class)
        );
        $I->mockService(HistoryProcessor::class, $processor);

        $this->login = "test" . StringHandler::getRandomCode(20);
        $userId = $I->createAwUser($this->login, null, ["AccountLevel" => ACCOUNT_LEVEL_AWPLUS], true);
        $this->accountId = $I->createAwAccount($userId, $provider, "History.SubAccounts");
        $I->checkAccount($this->accountId, false, true, true);
        $this->subAccount1 = $I->grabFromDatabase("SubAccount", "SubAccountID", ["AccountID" => $this->accountId, "Code" => "testproviderSubAcc1"]);
        $this->subAccount2 = $I->grabFromDatabase("SubAccount", "SubAccountID", ["AccountID" => $this->accountId, "Code" => "testproviderSubAcc2"]);
        $I->checkAccount($this->accountId);
        $I->executeQuery("update AccountHistory set SubAccountID = {$this->subAccount1}, Description = 'SubAccount 1 History Row' where AccountID = {$this->accountId} and Description = 'Main acc hist 2'");
        $I->executeQuery("update AccountHistory set SubAccountID = {$this->subAccount2}, Description = 'SubAccount 2 History Row' where AccountID = {$this->accountId} and Description = 'Main acc hist 3'");
    }

    public function testMainHistory(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/account/history/{$this->accountId}?_switch_user={$this->login}");
        $I->seeInSource("Main acc hist 1");
        $I->dontSeeInSource("SubAccount 1 History Row");
        $I->dontSeeInSource("SubAccount 2 History Row");
    }

    public function testSubaccount1History(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/account/history/{$this->accountId}/$this->subAccount1?_switch_user={$this->login}");
        $I->seeInSource("SubAccount 1 History Row");
        $I->dontSeeInSource("SubAccount 2 History Row");
        $I->dontSeeInSource("Main acc hist 1");
    }

    public function testSubaccount2History(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/account/history/{$this->accountId}/$this->subAccount2?_switch_user={$this->login}");
        $I->seeInSource("SubAccount 2 History Row");
        $I->dontSeeInSource("SubAccount 1 History Row");
        $I->dontSeeInSource("Main acc hist 1");
    }

    public function testDetailsPopupMainHistory(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/account/info/a{$this->accountId}?_switch_user={$this->login}");
        $I->seeInSource("Main acc hist 1");
        $I->dontSeeInSource("SubAccount 1 History Row");
        $I->dontSeeInSource("SubAccount 2 History Row");
    }

    public function testDetailsPopupSubaccount1History(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/account/info/a{$this->accountId}/$this->subAccount1?_switch_user={$this->login}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['HistoryTab' => true]);
        $I->dontSeeInSource("SubAccount 2 History Row");
        $I->dontSeeInSource("Main acc hist 1");
    }

    public function testDetailsPopupSubaccount2History(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/account/info/a{$this->accountId}/$this->subAccount2?_switch_user={$this->login}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['HistoryTab' => true]);
        $I->dontSeeInSource("SubAccount 1 History Row");
        $I->dontSeeInSource("Main acc hist 1");
    }
}
