<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Controller\Cart;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\CartItem\Discount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\Billing\PaypalRestApi;
use AwardWallet\MainBundle\Service\Billing\PaypalSoapApi;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManager;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileResponseDetailsType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentResponseDetailsType;
use PayPal\EBLBaseComponents\GetExpressCheckoutDetailsResponseDetailsType;
use PayPal\EBLBaseComponents\PayerInfoType;
use PayPal\EBLBaseComponents\PaymentInfoType;
use PayPal\EBLBaseComponents\PersonNameType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileResponseType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentResponseType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsResponseType;
use PayPal\PayPalAPI\SetExpressCheckoutResponseType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Prophecy\Prophet;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 * @group billing
 */
class PaypalControllerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Usr
     */
    private $user;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->router = $I->grabService('router');
        $this->em = $I->grabService('doctrine.orm.entity_manager');
        $userId = $I->createAwUser();
        $this->user = $I->getContainer()->get('doctrine')->getRepository(Usr::class)->find($userId);
    }

    public function testFull(\TestSymfonyGuy $I)
    {
        $I->executeQuery("update Usr set 
            Subscription = " . Usr::SUBSCRIPTION_SAVED_CARD . ", 
            PaypalRecurringProfileID = '123' 
        where 
            UserID = {$this->user->getUserid()}"
        );

        $prophet = new Prophet();

        $paypalRestApi = $prophet->prophesize(PaypalRestApi::class);
        $paypalRestApi
            ->deleteSavedCard('123')
            ->shouldBeCalled()
        ;
        $I->mockService(PaypalRestApi::class, $paypalRestApi->reveal());

        $this->mockPaypal($I, AwPlusSubscription::PRICE, null);
        $this->pay($I);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . AwPlusSubscription::PRICE . '*', $email->getBody());
        $I->assertMatchesRegularExpression("#^pr\-#ims", $I->grabFromDatabase("Cart", "BillingTransactionID", ["UserID" => $this->user->getUserid(), "PaymentType" => PAYMENTTYPE_PAYPAL]));

        $prophet->checkPredictions();

        $I->seeInDatabase("Usr", [
            "UserID" => $this->user->getId(),
            "Subscription" => Usr::SUBSCRIPTION_PAYPAL,
            "SubscriptionType" => Usr::SUBSCRIPTION_TYPE_AWPLUS,
            "SubscriptionPrice" => AwPlusSubscription::PRICE,
            "SubscriptionPeriod" => SubscriptionPeriod::DAYS_1_YEAR,
        ]);
    }

    public function testOneCard(\TestSymfonyGuy $I)
    {
        $I->executeQuery("update Usr set 
            Subscription = " . Usr::SUBSCRIPTION_SAVED_CARD . ", 
            PaypalRecurringProfileID = '123' 
        where 
            UserID = {$this->user->getUserid()}"
        );

        $prophet = new Prophet();

        $paypalRestApi = $prophet->prophesize(PaypalRestApi::class);
        $paypalRestApi
            ->deleteSavedCard('123')
            ->shouldBeCalled()
        ;
        $I->mockService(PaypalRestApi::class, $paypalRestApi->reveal());

        $this->mockPaypal($I, AwPlusSubscription::PRICE, null);
        $this->pay($I);
        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . AwPlusSubscription::PRICE . '*', $email->getBody());
        $I->assertMatchesRegularExpression("#^pr\-#ims", $I->grabFromDatabase("Cart", "BillingTransactionID", ["UserID" => $this->user->getUserid(), "PaymentType" => PAYMENTTYPE_PAYPAL]));

        $prophet->checkPredictions();

        $I->seeInDatabase("Usr", [
            "UserID" => $this->user->getId(),
            "Subscription" => Usr::SUBSCRIPTION_PAYPAL,
            "SubscriptionType" => Usr::SUBSCRIPTION_TYPE_AWPLUS,
            "SubscriptionPrice" => AwPlusSubscription::PRICE,
            "SubscriptionPeriod" => SubscriptionPeriod::DAYS_1_YEAR,
        ]);
    }

    public function testDiscountedNowFullPrice(\TestSymfonyGuy $I)
    {
        $this->user->setDiscountedUpgradeBefore(new \DateTime("+5 day"));
        $this->em->flush();
        $this->mockPaypal($I, AwPlusSubscription::PRICE, null);
        $this->pay($I);
    }

    public function testSubscriptionWith25PercentDiscount(\TestSymfonyGuy $I)
    {
        $this->mockPaypal($I, round(AwPlusSubscription::PRICE * 0.75, 2), null);

        $couponCode = StringUtils::getRandomCode(20);
        $couponId = $I->haveInDatabase("Coupon", [
            "Code" => $couponCode,
            "Name" => "Special Discount 25%",
            "Discount" => 25,
        ]);
        $I->haveInDatabase("CouponItem", ["CouponID" => $couponId, "CartItemType" => AwPlusSubscription::TYPE]);

        $cartId = $I->haveInDatabase("Cart", [
            "UserID" => $this->user->getUserid(),
            "CouponID" => $couponId,
            "LastUsedDate" => date("Y-m-d H:i:s"),
            "CalcDate" => date("Y-m-d H:i:s"),
        ]);
        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => AwPlusSubscription::TYPE,
            "ID" => $this->user->getUserid(),
            "Name" => "AwardWallet Plus yearly subscription",
            "Cnt" => 1,
            "Price" => AwPlusSubscription::PRICE,
            "Discount" => 0,
            "Description" => "12 months (starting from 12/20/18)",
        ]);
        $I->haveInDatabase("CartItem", [
            "CartID" => $cartId,
            "TypeID" => Discount::TYPE,
            "Name" => "Special Discount 25%",
            "Cnt" => 1,
            "Price" => -1 * round(AwPlusSubscription::PRICE * 0.25, 2),
        ]);

        $I->assertEquals(ACCOUNT_LEVEL_FREE, $I->grabFromDatabase("Usr", "AccountLevel", ["UserID" => $this->user->getUserid()]));
        $I->amOnPage($this->router->generate("aw_cart_common_paymenttype", ['_switch_user' => $this->user->getLogin()]));
        $I->see("Select Payment Type");
        $I->submitForm("//form", [
            'select_payment_type' => [
                '_token' => $I->grabAttributeFrom("//input[@name='select_payment_type[_token]']", "value"),
                'type' => Cart::PAYMENTTYPE_PAYPAL,
            ],
        ]);

        $this->processPayPal($I);

        $email = $I->grabLastMail();
        $I->assertStringContainsString('You have successfully paid *$' . round(AwPlusSubscription::PRICE * 0.75, 2) . '*', $email->getBody());
    }

    public function _after(\TestSymfonyGuy $I)
    {
        // $I->verifyMocks();
        $this->router = $this->em = $this->user = null;
    }

    private function pay(\TestSymfonyGuy $I, $discount = null)
    {
        $I->amOnPage($this->router->generate("aw_users_pay", ['_switch_user' => $this->user->getLogin()]));
        $I->seeInSource('"giveAWPlus":true');

        if (!empty($discount)) {
            $I->see($discount);
        }
        $I->submitForm(".main-form", ["user_pay[awPlus]" => "true", "user_pay[onecard]" => "0"]);

        $I->see("Select Payment Type");
        $I->submitForm("form", ["select_payment_type[type]" => PAYMENTTYPE_PAYPAL]);

        $this->processPayPal($I);
    }

    private function processPayPal(\TestSymfonyGuy $I)
    {
        $I->see("You are being redirected to PayPal.com");
        $I->sendAjaxPostRequest($this->router->generate("aw_cart_paypal_process"));
        $I->seeInSource('"status":"ok"');

        $I->amOnPage($this->router->generate("aw_cart_paypal_accept", ['token' => 'sometoken', 'PayerID' => 'somepayer']));
        $I->see("do you wish to pay");
        $I->sendAjaxPostRequest($this->router->generate("aw_cart_paypal_accept", ['token' => 'sometoken', 'PayerID' => 'somepayer']));
        $I->seeInSource('"status":"ok"');

        $I->sendAjaxGetRequest($this->router->generate("aw_cart_paypal_process"));
        $I->seeInSource('"status":"ok"');

        $cart = $I
            ->query("
                select 
                  c.* 
                from Cart c
                  join CartItem ci on c.CartID = ci.CartID 
                where 
                  c.UserID = {$this->user->getId()} 
                  and c.PayDate is not null 
                order by c.CartID desc limit 1
            ")
            ->fetch(\PDO::FETCH_ASSOC);
        $I->assertEquals(PAYMENTTYPE_PAYPAL, $cart["PaymentType"]);
    }

    private function mockPaypal(\TestSymfonyGuy $I, $recurringAmount, $trialAmount, $immediateAmount = null, ?\DateTime $billingStartDate = null)
    {
        $paypalServiceMock = $I->stubMake(PayPalAPIInterfaceServiceService::class, [
            'SetExpressCheckout' => function () {
                return new SetExpressCheckoutResponseType();
            },
            'GetExpressCheckoutDetails' => function () {
                $result = new GetExpressCheckoutDetailsResponseType();
                $payerInfo = new PayerInfoType();
                $payerName = new PersonNameType();
                $payerName->FirstName = "John";
                $payerName->LastName = "Smith";
                $payerInfo->PayerName = $payerName;
                $result->GetExpressCheckoutDetailsResponseDetails = new GetExpressCheckoutDetailsResponseDetailsType();
                $result->GetExpressCheckoutDetailsResponseDetails->PayerInfo = $payerInfo;
                $result->Ack = 'Success';

                return $result;
            },
            'CreateRecurringPaymentsProfile' => Stub::exactly($recurringAmount ? 1 : 0, function (CreateRecurringPaymentsProfileReq $req) use ($I, $recurringAmount, $trialAmount, $billingStartDate) {
                $I->assertEquals($recurringAmount, $req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->ScheduleDetails->PaymentPeriod->Amount->value);

                if (!empty($trialAmount)) {
                    $I->assertEquals($trialAmount, $req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->ScheduleDetails->TrialPeriod->Amount->value);
                } else {
                    $I->assertEmpty($req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->ScheduleDetails->TrialPeriod);
                }

                if ($billingStartDate) {
                    $I->assertEquals($billingStartDate->format("Y-m-d\TH:i:s\Z"), $req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->RecurringPaymentsProfileDetails->BillingStartDate);
                } else {
                    $I->assertLessThan(300, abs(strtotime($req->CreateRecurringPaymentsProfileRequest->CreateRecurringPaymentsProfileRequestDetails->RecurringPaymentsProfileDetails->BillingStartDate) - time()));
                }

                $result = new CreateRecurringPaymentsProfileResponseType();
                $result->CreateRecurringPaymentsProfileResponseDetails = new CreateRecurringPaymentsProfileResponseDetailsType();
                $result->CreateRecurringPaymentsProfileResponseDetails->ProfileID = 'pr-' . bin2hex(random_bytes(8));
                $result->Ack = 'Success';

                return $result;
            }),
            'DoExpressCheckoutPayment' => Stub::exactly($immediateAmount ? 1 : 0, function (DoExpressCheckoutPaymentReq $req) use ($I, $immediateAmount) {
                $result = new DoExpressCheckoutPaymentResponseType();
                $result->DoExpressCheckoutPaymentResponseDetails = new DoExpressCheckoutPaymentResponseDetailsType();
                $paymentInfo = new PaymentInfoType();
                $paymentInfo->TransactionID = 'tx-' . bin2hex(random_bytes(8));
                $result->DoExpressCheckoutPaymentResponseDetails->PaymentInfo = [$paymentInfo];
                $I->assertEquals($immediateAmount, $req->DoExpressCheckoutPaymentRequest->DoExpressCheckoutPaymentRequestDetails->PaymentDetails[0]->OrderTotal->value);

                return $result;
            }),
        ], $this);

        $paypal = $I->stubMake(PaypalSoapApi::class, [
            'getPaypalService' => $paypalServiceMock,
            'getPaypalServiceForCart' => Stub::atLeastOnce(function ($debug = false, Cart $cart) use ($paypalServiceMock) {
                return $paypalServiceMock;
            }),
        ], $this);

        $I->mockService(PaypalSoapApi::class, $paypal);
    }
}
