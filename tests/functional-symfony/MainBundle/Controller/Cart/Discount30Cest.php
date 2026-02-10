<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Security\LoginTrait;
use Codeception\Module\Aw;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\RouterInterface;

class Discount30Cest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use LoginTrait;

    /* @var RouterInterface */
    private $router;

    /* @var EntityManager */
    private $entityManager;

    /** @var Usr */
    private $user;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->entityManager = $I->grabService('doctrine.orm.entity_manager');

        $this->user = $this->createUser($I, [
            'LastLogonDateTime' => date('Y-m-d H:i:s', strtotime('-25 hours')),
            'UpgradeSkippedCount' => 0,
        ], true);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);
        unset($this->router, $this->entityManager);
    }

    public function dontSeeDialogUpgradeDefault(\TestSymfonyGuy $I)
    {
        $I->amOnPage($this->router->generate('aw_account_list', ['_switch_user' => $this->user['login']]));
        $I->dontSeeInSource('upgradeNotifyPopup');
    }

    public function seeDialogUpgrade(\TestSymfonyGuy $I)
    {
        $user = $this->createUserCondition($I);
        $I->login($user['login'], $user['password']);
        $I->amOnPage($this->router->generate('aw_account_list'));
        $I->seeInSource('upgradeNotifyPopup');
    }

    public function seeDialogDiscount30(\TestSymfonyGuy $I)
    {
        $user = $this->createUserCondition($I);
        $this->entityManager->getConnection()->executeUpdate('UPDATE Usr SET UpgradeSkippedCount = ' . (PlusManager::LIMIT_UPGRADE_SKIPPED - 1) . ' WHERE UserID = ' . $user['userId']);
        $I->login($user['login'], $user['password']);

        $I->amOnPage($this->router->generate('aw_account_list'));
        $I->dontSeeInSource('upgradeDiscount30Popup');
        $I->seeInSource('upgradeNotifyPopup');
    }

    public function dontSeeDialogUpgradeByLogonDateTime(\TestSymfonyGuy $I)
    {
        $user = $this->createUserCondition($I);
        $this->entityManager->getConnection()->executeUpdate("UPDATE Usr SET LastLogonDateTime = '" . date('Y-m-d H:i:s', strtotime('-23 hours')) . "' WHERE UserID = " . $user['userId']);
        $I->login($user['login'], $user['password']);
        $I->amOnPage($this->router->generate('aw_account_list'));
        $I->dontSeeInSource('upgradeNotifyPopup');
    }

    public function dontSeeDialogUpgradeBySkippedCount(\TestSymfonyGuy $I)
    {
        $user = $this->createUserCondition($I);
        $this->entityManager->getConnection()->executeUpdate('UPDATE Usr SET UpgradeSkippedCount = ' . (PlusManager::LIMIT_UPGRADE_SKIPPED + 1) . ' WHERE UserID = ' . $user['userId']);
        $I->login($user['login'], $user['password']);
        $I->amOnPage($this->router->generate('aw_account_list'));
        $I->dontSeeInSource('upgradeNotifyPopup');
    }

    private function createUserCondition(\TestSymfonyGuy $I, $payDate = null): array
    {
        if (null === $payDate) {
            $payDate = '-370 days';
        }
        $user = $this->createUser($I, [
            'LastLogonDateTime' => date('Y-m-d H:i:s', strtotime('-25 hours')),
            'UpgradeSkippedCount' => 0,
        ], true);
        $I->createAwAccount($user['userId'], AW::TEST_PROVIDER_ID, $I->grabRandomString(6), $I->grabRandomString(8));
        $I->addUserPayment($user['userId'], PAYMENTTYPE_CREDITCARD, new AwPlusSubscription(), null, new \DateTime($payDate));

        $this->entityManager->getConnection()->executeUpdate('
            UPDATE Usr
            SET
                AccountLevel = ' . ACCOUNT_LEVEL_FREE . ',
                Subscription = NULL
            WHERE
                UserID = ' . $user['userId']
        );

        return $user;
    }
}
