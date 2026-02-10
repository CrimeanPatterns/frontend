<?php

namespace AwardWallet\Tests\FunctionalSymfony\Pages;

class CreditCard
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var \TestSymfonyGuy
     */
    protected $tester;

    public function __construct(\TestSymfonyGuy $I)
    {
        $this->tester = $I;
    }

    public function pay()
    {
        $I = $this->tester;
        $router = $I->getContainer()->get("router");
        $user = $I->getContainer()->get("security.token_storage")->getToken()->getUser();

        $I->see("Order #");

        $I->submitForm("//form", [
            'billing_state_is_text' => 1,
            'card_info' => [
                '_token' => $I->grabAttributeFrom("//input[@name='card_info[_token]']", "value"),
                'full_name' => 'Billy Villy',
                'card_number' => '4032 0313 4697 7571',
                'security_code' => 123,
                'expiration_month' => 1,
                'expiration_year' => date('Y', time() + (2 * 3600 * 24 * 365)),
            ],
        ]);
        $I->see("Billy Villy");
        $I->see("XXXXXXXXXXXX7571");

        $I->sendPOST($router->generate("aw_cart_creditcard_checkout"));

        $I->seeResponseContainsJson(['success' => 1]);
        $cartId = $I->grabDataFromJsonResponse('cartId');
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase("Cart", ["CartID" => $cartId, "BillFirstName" => "Billy", "BillLastName" => "Villy"]);
        $I->amOnPage($router->generate('aw_cart_common_complete', ['id' => $cartId]));
        $I->see("Order #{$cartId} has been successfully submitted");
        $I->seeEmailTo($user->getEmail(), "Order ID: {$cartId}", null, 30);

        return $cartId;
    }
}
