<?php

namespace Account;

use AwardWallet\MainBundle\Form\Handler\Subscriber\Account\AccountGeneric;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @group frontend-functional
 */
class FraudCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    public const PROVIDER = 636; // Test provider
    private $username;
    private $userId;

    /**
     * @var Router
     */
    private $router;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser(null, null, [], true, true);
        $this->username = $I->grabFromDatabase("Usr", "Login", ["UserID" => $this->userId]);
        $this->router = $I->grabService('router');
    }

    public function checkLockoutOnAdd(\TestSymfonyGuy $I)
    {
        $this->addAccount($I);
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $this->addAccount($I);
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $locker = new AntiBruteforceLockerService($I->grabService(\Memcached::class), AccountGeneric::LOCKER_KEY . "user", 60, 60, 100, "Too many edits per hour");
        $locker->checkForLockout((string) $this->userId, false, 150);
        $this->addAccount($I);
        $I->assertEquals(1, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $I->assertStringContainsString("marking user as fraud, because too many edits\creations of account", $I->grabLastMail()->getBody());
    }

    public function checkLockoutOnEdit(\TestSymfonyGuy $I)
    {
        $accountId = $I->createAwAccount($this->userId, self::PROVIDER, "balance.random", "pass1");
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $this->editAccount($I, $accountId, "1.subaccount: 1 subaccount");
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $locker = new AntiBruteforceLockerService($I->grabService(\Memcached::class), AccountGeneric::LOCKER_KEY . "account", 60, 60, 20, "Too many edits per hour");
        $locker->checkForLockout((string) $accountId, false, 30);
        $this->editAccount($I, $accountId, "balance.random");
        $I->assertEquals(1, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $I->assertStringContainsString("marking user as fraud, because too many edits\creations of account", $I->grabLastMail()->getBody());
    }

    public function checkLockoutByIp(\TestSymfonyGuy $I)
    {
        $I->setCookie("account_edits_per_hour_from_ip", "60");
        $accountId = $I->createAwAccount($this->userId, self::PROVIDER, "balance.random", "pass1");
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $this->editAccount($I, $accountId, "1.subaccount: 1 subaccount");
        $I->assertEquals(0, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $locker = new AntiBruteforceLockerService($I->grabService(\Memcached::class), AccountGeneric::LOCKER_KEY . "ip", 60, 60, 60, "Too many edits per hour");
        $locker->checkForLockout($I->getClientIp(), false, 90);
        $this->editAccount($I, $accountId, "balance.random");
        $I->assertEquals(1, $I->grabFromDatabase("Usr", "Fraud", ["UserID" => $this->userId]));
        $I->assertStringContainsString("marking user as fraud, because too many edits\creations of account", $I->grabLastMail()->getBody());
    }

    private function addAccount(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_add', ['providerId' => self::PROVIDER]) . "?_switch_user=" . $this->username);
        $I->see('Test Provider');
        $I->selectOption('Login', '1.subaccount: 1 subaccount');
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);
        $I->seeInDatabase('Account', ['UserID' => $this->userId, 'ProviderID' => self::PROVIDER]);
        $I->executeQuery("delete from Account where UserID = {$this->userId}");
    }

    private function editAccount(\TestSymfonyGuy $I, $accountId, $login, $params = [])
    {
        $I->amOnPage($this->router->generate('aw_account_edit', array_merge(['accountId' => $accountId, "_switch_user" => $this->username], $params)));
        $I->see('Test Provider');
        $I->selectOption('Login', $login);
        $I->checkOption('#account_notrelated');
        $I->submitForm('#account-form', []);
    }
}
