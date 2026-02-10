<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Entity\Usr;
use Codeception\Module\Aw;

/**
 * @group frontend-functional
 * @group functional
 */
class LogoManagerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    public function addBookingNotLoggedIn(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/awardBooking/add');
        $I->assertContains($I->grabAttributeFrom('a.logo', 'class'), ['logo', 'logo newyear']);
    }

    public function addBookingNotLoggedInWithRef(\TestSymfonyGuy $I)
    {
        $I->amOnPage('/awardBooking/add?ref=' . Aw::CAME_FROM_BOOKER);
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('.header-site a.logo', 'class'));
    }

    public function addBookingLoggedIn(\TestSymfonyGuy $I)
    {
        $login = 'test' . $I->grabRandomString(5);
        $I->createAwUser($login);
        $I->amOnPage('/awardBooking/add?_switch_user=' . $login);
        $I->seeElement("a.logo");
    }

    public function addBookingLoggedInWithRef(\TestSymfonyGuy $I)
    {
        $login = 'test' . $I->grabRandomString(5);
        $I->createAwUser($login);
        $I->amOnPage('/awardBooking/add?_switch_user=' . $login . '&ref=' . Aw::CAME_FROM_BOOKER);
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('.header-site a.logo', 'class'));
    }

    public function viewBookingRequest(\TestSymfonyGuy $I)
    {
        $requestId = $I->createAbRequest(['BookerUserID' => Aw::BOOKER_ID, 'CameFrom' => Aw::CAME_FROM_BOOKER]);
        $I->amOnPage('/awardBooking/view/' . $requestId . '?_switch_user=SiteAdmin');
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('a.logo', 'class'));
    }

    public function shareBookingRequest(\TestSymfonyGuy $I)
    {
        $requestId = $I->createAbRequest(['BookerUserID' => Aw::BOOKER_ID, 'CameFrom' => Aw::CAME_FROM_BOOKER]);
        $I->amOnPage('/awardBooking/share/' . $requestId . '?_switch_user=SiteAdmin');
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('a.logo', 'class'));
    }

    public function payBookingRequest(\TestSymfonyGuy $I)
    {
        $login = 'test' . $I->grabRandomString(5);
        $userId = $I->createAwUser($login);
        $I->executeQuery("update AbBookerInfo set PayPalPassword = 'some' where UserID = " . Aw::BOOKER_ID); // agree, bad one
        $requestId = $I->createAbRequest(['BookerUserID' => Aw::BOOKER_ID, 'UserID' => $userId]);

        $this->payForBookingRequest($I, $requestId);

        $I->amOnPage("/cart/order/details");
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('header a.logo', 'class'));

        $cartId = $I->markUserCartPaid($userId);
        $I->amOnPage("/cart/complete/" . $cartId);
        $I->see("Success");
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('header a.logo', 'class'));
    }

    public function editAccountFromRequest(\TestSymfonyGuy $I)
    {
        $requestId = $I->createAbRequest(['BookerUserID' => Aw::BOOKER_ID, 'CameFrom' => Aw::CAME_FROM_BOOKER]);
        $I->amOnPage("/contact?_switch_user=SiteAdmin");
        $I->amOnPage("/account/add/22?AbRequestID=" . $requestId);
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('a.logo', 'class'));
    }

    public function bookerOnBusiness(\TestSymfonyGuy $I)
    {
        $I->amOnSubdomain("business");

        $I->amOnPage("/awardBooking/queue?_switch_user=siteadmin");
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('a.logo', 'class'));

        $I->amOnPage("/contact");
        $I->assertEquals('logo awardwallet', $I->grabAttributeFrom('a.logo', 'class'));
    }

    public function bookerOnPersonalOther(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/contact?_switch_user=siteadmin");
        $I->seeElement("a.logo");
    }

    public function cwtLogo(\TestSymfonyGuy $I)
    {
        $I->amOnSubdomain("cwt");
        $I->amOnPage("/");
        $I->assertEquals('logo cwt', $I->grabAttributeFrom('a.logo', 'class'));
        $login = "log" . bin2hex(random_bytes(5));
        $I->createAwUser($login);

        $I->amOnPage("/user/profile?_switch_user=$login");
        $I->see("Edit my profile");
        $I->assertEquals('logo cwt', $I->grabAttributeFrom('a#main-logo', 'class'));

        $I->amOnPage("/contact");
        $I->assertEquals('logo cwt', $I->grabAttributeFrom('a#main-logo', 'class'));
    }

    public function bookerOnNewBusinessLogo(\TestSymfonyGuy $I)
    {
        $I->amOnSubdomain("business");
        $I->executeQuery("update Usr set InBeta = 1, BetaApproved = 1 where UserId = 5514");
        $I->amOnPage("/members?_switch_user=siteadmin");
        $I->seeElement("a.awardwallet");
    }

    public function newBusinessLogo(\TestSymfonyGuy $I)
    {
        $login = 'test' . $I->grabRandomString(5);
        $adminId = $I->createAwUser($login, Aw::DEFAULT_PASSWORD, [], true, true);
        $businessId = $I->createAwUser('biz' . $I->grabRandomString(), Aw::DEFAULT_PASSWORD, ["AccountLevel" => ACCOUNT_LEVEL_BUSINESS], true);

        $I->haveInDatabase("UserAgent", ["AgentID" => $adminId, "ClientID" => $businessId, "AccessLevel" => ACCESS_ADMIN, "IsApproved" => 1]);
        $I->haveInDatabase("UserAgent", ["AgentID" => $businessId, "ClientID" => $adminId, "AccessLevel" => ACCESS_WRITE, "IsApproved" => 1]);

        $I->amOnSubdomain("business");
        $I->amOnPage("/members?_switch_user=$login");
        $I->assertContains($I->grabAttributeFrom("div.header-area a.logo", "class"), ["logo", "logo newyear"]);
    }

    public function awPlusLogo(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/");
        $login = 'awplus' . $I->grabRandomString();
        $I->createAwUser($login, Aw::DEFAULT_PASSWORD, ['AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $I->amOnPage('/contact?_switch_user=' . $login);
        $I->seeElement('div.header-area span.plus', ['class' => 'plus']);
    }

    public function validExpiresSoonLogo(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/");
        $login = 'expires' . $I->grabRandomString();
        $I->createAwUser($login, Aw::DEFAULT_PASSWORD, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PlusExpirationDate' => date('Y-m-d H:i:s', time() + mt_rand(1, 29) * 86400),
            'Subscription' => null,
        ]);
        $I->amOnPage('/contact?_switch_user=' . $login);
        $I->seeElement('div.header-area a.expires', ['class' => 'expires']);
        $I->amOnPage("/?_switch_user=_exit");
    }

    public function invalidExpiresSoonSubscriptionLogo(\TestSymfonyGuy $I)
    {
        $I->amOnPage("/");
        $login = 'subexpires' . $I->grabRandomString();
        $subscriptions = array_keys(Usr::SUBSCRIPTION_NAMES);
        $I->createAwUser($login, Aw::DEFAULT_PASSWORD, [
            'AccountLevel' => ACCOUNT_LEVEL_AWPLUS,
            'PlusExpirationDate' => date('Y-m-d H:i:s', time() + mt_rand(1, 29) * 86400),
            'Subscription' => $subscriptions[array_rand($subscriptions)],
        ]);
        $I->amOnPage('/contact?_switch_user=' . $login);
        $I->dontSeeElement('div.header-area a.expires', ['class' => 'expires']);
        $I->amOnPage("/?_switch_user=_exit");
    }

    private function payForBookingRequest(\TestSymfonyGuy $I, $requestId)
    {
        $adminLogin = $I->getBookingRequestAdmin($requestId);
        $bookerId = $I->grabFromDatabase("AbRequest", "BookerUserID", ["AbRequestID" => $requestId]);
        $I->executeQuery("UPDATE AbBookerInfo SET PaypalClientID = '1' WHERE PayPalClientID IS NULL AND UserID = " . $bookerId);

        $I->wantTo("create invoice");
        $I->amOnSubdomain("business");
        $I->amOnPage("/awardBooking/view/" . $requestId . "?_switch_user=" . $adminLogin);
        $messageId = $I->haveInDatabase("AbMessage", ['RequestID' => $requestId, 'Type' => AbMessage::TYPE_COMMON, 'UserID' => $bookerId, 'FromBooker' => true]);
        $invoiceId = $I->haveInDatabase("AbInvoice", ['MessageID' => $messageId, 'Status' => AbInvoice::STATUS_UNPAID, 'PaymentType' => AbInvoice::PAYMENTTYPE_CREDITCARD]);
        $I->haveInDatabase("AbInvoiceItem", ['Description' => 'Test Booking Fee', 'Quantity' => 1, 'Price' => 100, 'Discount' => 0, 'AbInvoiceID' => $invoiceId]);

        $I->wantTo("pay this invoice");
        $I->amOnSubdomain(null);
        $login = $I->query("select u.Login from Usr u join AbRequest r on u.UserID = r.UserID where r.AbRequestID = $requestId")->fetchColumn();
        $I->amOnPage('/awardBooking/view/' . $requestId . "?_switch_user=$login");
        $I->see("PROCEED TO MAKING THE PAYMENT NOW");
        $I->sendPOST("/awardBooking/payment/byCreditCard/$requestId");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "ok"]);
    }
}
