<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\AbInvoice;
use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\BookingRequestManager;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApiFactory;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group billing
 */
class CreditCardControllerCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);
        $this->router = $I->grabService('router');
        $this->em = $I->grabService('doctrine.orm.entity_manager');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->router = $this->em = null;
        parent::_after($I);
    }

    public function testBooking(\TestSymfonyGuy $I)
    {
        $bookerId = $I->createBusinessUserWithBookerInfo(null, [], ['PaypalClientID' => 'notEmpty', 'PayPalPassword' => 'notEmpty', 'FromEmail' => 'some@email.com']);
        $paypal = $I->stubMake(PaypalRestApi::class, [
            'payWithCard' => Stub::exactly(1, function ($cart, $cardData, $directAmount) use ($I) {
                $I->assertEquals(102.90, $directAmount);

                return bin2hex(random_bytes(10));
            }),
        ]);
        $factory = $I->stubMake(PaypalRestApiFactory::class, [
            'getByBooker' => Stub::exactly(1, function (Usr $booker) use ($paypal, $I, $bookerId) {
                $I->assertEquals($bookerId, $booker->getUserid());

                return $paypal;
            }),
        ]);
        $I->mockService(PaypalRestApiFactory::class, $factory);

        /** @var BookingRequestManager $abRequestManager */
        $abRequestManager = $I->grabService(BookingRequestManager::class);
        /** @var AbRequest $abRequest */
        $abRequest = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find(
            $requestId = $I->createAbRequest(['UserID' => $this->user->getUserid(), 'BookerUserID' => $bookerId])
        );
        $abRequestManager->addInvoice(
            (new AbInvoice())
                ->addItem(
                    (new BookingServiceFee())
                        ->setDescription('Test Booking Fee')
                        ->setPrice(100)
                        ->setQuantity(1)
                        ->setDiscount(0)
                ),
            $abRequest
        );
        $I->sendPOST($this->router->generate("aw_booking_payment_by_cc", ["id" => $requestId]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(["status" => "ok"]);
        $I->amOnPage($this->router->generate("aw_cart_common_orderdetails"));
        $I->see("Test Booking Fee");
        $this->pay($I);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('* Total *	* $102.90 *', $email->getBody());
        $I->verifyMocks();
    }

    /**
     * @skip
     */
    public static function sendPaymentForm(\TestSymfonyGuy $I): int
    {
        $I->see("Order Review");

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

        /** @var RouterInterface $router */
        $router = $I->grabService("router");

        $I->sendPOST($router->generate("aw_cart_creditcard_checkout"));

        $I->seeResponseContainsJson(['success' => 1]);
        $cartId = $I->grabDataFromJsonResponse('cartId');
        $I->assertNotEmpty($cartId);
        $I->seeInDatabase("Cart", ["CartID" => $cartId, "BillFirstName" => "Billy", "BillLastName" => "Villy"]);
        $I->amOnPage($router->generate('aw_cart_common_complete', ['id' => $cartId]));
        $I->see("Order #{$cartId}");

        return $cartId;
    }

    private function pay(\TestSymfonyGuy $I, $seeEmail = true)
    {
        $cartId = self::sendPaymentForm($I);
        $I->see("Order #{$cartId} has been successfully submitted");

        if ($seeEmail) {
            $I->seeEmailTo($this->user->getEmail(), "Order ID: {$cartId}", null, 30);
        } else {
            $I->dontSeeEmailTo($this->user->getEmail(), "Order ID: {$cartId}", null, 30);
        }

        return $cartId;
    }
}
