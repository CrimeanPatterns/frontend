<?php

namespace AwardWallet\Tests\FunctionalSymfony\User;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlus1Year;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusTrial;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticatorHandler;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @group frontend-functional
 */
class DeleteUserCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?CsrfTokenManagerInterface $csrfTokenManager;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->csrfTokenManager = $I->grabService('security.csrf.token_manager');
        $I->mockService(MobileReauthenticatorHandler::class, $I->stubMake(MobileReauthenticatorHandler::class, [
            'handle' => null,
            'reset' => null,
        ]));
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->csrfTokenManager = null;
    }

    public function deleteNoOrders(\TestSymfonyGuy $I)
    {
        // Create user and login
        $I->createAwUser($username = 'testuser-delete-' . $I->grabRandomString(5), $password = 'awardwallet', ['InBeta' => 1, 'BetaApproved' => 1]);
        $this->deleteUser($I, $username);

        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("Paid for AwardWallet Plus: 0", $email);
        $I->assertStringContainsString("Lifetime contribution: $0", $email);
    }

    public function deleteOneOrder(\TestSymfonyGuy $I)
    {
        // Create user and login
        $userId = $I->createAwUser($username = 'testuser-delete-' . $I->grabRandomString(5), $password = 'awardwallet', ['InBeta' => 1, 'BetaApproved' => 1]);
        $I->addUserPayment($userId, Cart::PAYMENTTYPE_CREDITCARD, new AwPlusTrial());
        $I->addUserPayment($userId, Cart::PAYMENTTYPE_CREDITCARD, new AwPlus1Year());

        $this->deleteUser($I, $username);

        $email = $I->grabLastMailMessageBody();
        $I->assertStringContainsString("Paid for AwardWallet Plus: 1", $email);
        $I->assertStringContainsString("Lifetime contribution: $30", $email);
    }

    public function deleteAppleOrder(\TestSymfonyGuy $I)
    {
        // Create user and login
        $userId = $I->createAwUser($username = 'testuser-delete-' . $I->grabRandomString(5), null, [
            'Subscription' => Usr::SUBSCRIPTION_MOBILE,
            'SubscriptionType' => Usr::SUBSCRIPTION_TYPE_AWPLUS,
        ]);
        $I->addUserPayment($userId, Cart::PAYMENTTYPE_APPSTORE, new AwPlusSubscription(), null, new \DateTime('-1 month'));

        $this->deleteUser($I, $username);
        $I->seeResponseContainsJson(['isAppleSubscriber' => true]);
    }

    private function deleteUser(\TestSymfonyGuy $I, string $username)
    {
        $I->amOnRoute('aw_user_delete', ['_switch_user' => $username]);
        $I->haveHttpHeader('X-XSRF-TOKEN', $this->csrfTokenManager->getToken('')->getValue());
        $I->sendPost('/user/delete', [
            'reason' => 'test',
        ]);
        $I->seeResponseCodeIs(200);
        $I->dontSeeInDatabase('Usr', ['Login' => $username]);
    }
}
